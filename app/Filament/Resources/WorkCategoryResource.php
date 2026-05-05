<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkCategoryResource\Pages;
use App\Models\WorkCategory;
use App\Support\PublicImageVariants;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WorkCategoryResource extends Resource
{
    protected static ?string $model = WorkCategory::class;

    protected static ?string $navigationLabel = 'Категории работ';

    protected static ?string $modelLabel = 'категория работ';

    protected static ?string $pluralModelLabel = 'категории работ';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')
                ->label('Слаг')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            FileUpload::make('image')
                ->label('Изображение')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->directory('work-categories')
                ->disk('public')
                ->visibility('public')
                ->saveUploadedFileUsing(
                    fn ($file): string => PublicImageVariants::storeUploadedOptimized($file, 'work-categories')
                )
                ->maxSize(20480),
            Textarea::make('short_description')
                ->label('Короткое описание')
                ->rows(3)
                ->maxLength(500)
                ->helperText('Короткий анонс для карточки категории на странице /works.'),
            RichEditor::make('description')
                ->label('Описание')
                ->toolbarButtons([
                    'bold',
                    'bulletList',
                    'italic',
                    'link',
                    'orderedList',
                    'redo',
                    'undo',
                ])
                ->helperText('Используйте абзацы, списки и ссылки. Без таблиц, изображений и сложного форматирования.'),
            TextInput::make('meta_title')
                ->label('SEO title')
                ->maxLength(255)
                ->helperText('Если не заполнено, будет использовано название категории.'),
            Textarea::make('meta_description')
                ->label('SEO description')
                ->rows(4)
                ->helperText('Если не заполнено, будет использовано описание категории.'),
            TextInput::make('sort')
                ->label('Сортировка')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('is_published')
                ->label('Опубликовано')
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Изображение')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слаг')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Опубликовано')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Сортировка')
                    ->sortable(),
                Tables\Columns\TextColumn::make('works_count')
                    ->counts('works')
                    ->label('Работы'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkCategories::route('/'),
            'create' => Pages\CreateWorkCategory::route('/create'),
            'edit' => Pages\EditWorkCategory::route('/{record}/edit'),
        ];
    }
}

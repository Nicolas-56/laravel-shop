<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkResource\Pages;
use App\Models\Work;
use App\Support\PublicImageVariants;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WorkResource extends Resource
{
    protected static ?string $model = Work::class;

    protected static ?string $navigationLabel = 'Выполненные работы';

    protected static ?string $modelLabel = 'работа';

    protected static ?string $pluralModelLabel = 'работы';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Название')
                ->required()
                ->maxLength(255),
            Select::make('category_id')
                ->label('Категория')
                ->relationship('category', 'name')
                ->searchable()
                ->preload(),
            FileUpload::make('image')
                ->label('Изображение')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->directory('works')
                ->disk('public')
                ->visibility('public')
                ->saveUploadedFileUsing(
                    fn ($file): string => PublicImageVariants::storeUploadedOptimized($file, 'works')
                )
                ->maxSize(20480)
                ->helperText('Изображение автоматически уменьшается до 1600x1600 px и сохраняется в одном оптимальном формате. Максимальный размер исходного файла: 20 МБ.'),
            TextInput::make('alt')
                ->label('Alt')
                ->maxLength(255),
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
                Tables\Columns\ImageColumn::make('image')->label('Изображение')->disk('public'),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Категория')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('is_published')->label('Опубликовано')->boolean(),
                Tables\Columns\TextColumn::make('sort')->label('Сортировка')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Обновлено')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),
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
            'index' => Pages\ListWorks::route('/'),
            'create' => Pages\CreateWork::route('/create'),
            'edit' => Pages\EditWork::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentPageItemResource\Pages;
use App\Models\EquipmentPageItem;
use App\Support\PublicImageVariants;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentPageItemResource extends Resource
{
    protected static ?string $model = EquipmentPageItem::class;

    protected static ?string $navigationLabel = 'Оборудование';

    protected static ?string $modelLabel = 'элемент оборудования';

    protected static ?string $pluralModelLabel = 'оборудование';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')
                ->label('Изображение')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->directory('equipment')
                ->disk('public')
                ->visibility('public')
                ->saveUploadedFileUsing(
                    fn ($file): string => PublicImageVariants::storeUploadedOptimized($file, 'equipment')
                )
                ->maxSize(20480)
                ->helperText('Изображение автоматически уменьшается до 1600x1600 px и сохраняется в одном оптимальном формате. Максимальный размер исходного файла: 20 МБ.'),
            TextInput::make('title')
                ->label('Название')
                ->required()
                ->maxLength(255),
            RichEditor::make('description')
                ->label('Описание')
                ->toolbarButtons([
                    'blockquote',
                    'bold',
                    'bulletList',
                    'italic',
                    'orderedList',
                    'redo',
                    'strike',
                    'undo',
                ])
                ->helperText('Используйте абзацы и списки, чтобы текст отображался на странице оборудования с разделением по блокам.')
                ->required(),
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
                Tables\Columns\IconColumn::make('is_published')->label('Опубликовано')->boolean(),
                Tables\Columns\TextColumn::make('sort')->label('Сортировка')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Обновлено')->dateTime(),
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
            'index' => Pages\ListEquipmentPageItems::route('/'),
            'create' => Pages\CreateEquipmentPageItem::route('/create'),
            'edit' => Pages\EditEquipmentPageItem::route('/{record}/edit'),
        ];
    }
}

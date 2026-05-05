<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Support\PublicImageVariants;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'productImages';

    protected static ?string $title = 'Изображения';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Изображение')
                ->disk('public')
                ->directory('products')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->visibility('public')
                ->saveUploadedFileUsing(
                    fn ($file): string => PublicImageVariants::storeUploadedOptimized($file, 'products')
                )
                ->maxSize(20480)
                ->helperText('Изображение автоматически уменьшается до 1600x1600 px и сохраняется в одном оптимальном формате. Максимальный размер исходного файла: 20 МБ.')
                ->required(),
            TextInput::make('alt')
                ->label('Alt-текст')
                ->maxLength(255),
            TextInput::make('sort')
                ->label('Сортировка')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('is_main')
                ->label('Основное')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->recordTitleAttribute('path')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->disk('public')
                    ->label('Изображение'),
                Tables\Columns\TextColumn::make('alt')
                    ->label('Alt-текст')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_main')
                    ->label('Основное')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

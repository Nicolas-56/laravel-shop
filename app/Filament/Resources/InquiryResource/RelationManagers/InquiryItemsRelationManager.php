<?php

namespace App\Filament\Resources\InquiryResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'inquiryItems';

    protected static ?string $title = 'Позиции';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('Товар')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('quantity')
                ->label('Количество')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->default(1)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.article')
                    ->label('Артикул'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable(),
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

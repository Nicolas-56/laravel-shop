<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Позиции';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Товар')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_price')
                    ->label('Цена')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' руб.'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество'),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('Сумма')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' руб.'),
            ]);
    }
}

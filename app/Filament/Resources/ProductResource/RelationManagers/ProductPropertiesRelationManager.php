<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductPropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'productProperties';

    protected static ?string $title = 'Дополнительные свойства';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('custom_name')
                ->label('Своё название')
                ->maxLength(255)
                ->helperText('Используйте для характеристик, которых нет в свойствах категории.')
                ->required(),
            Textarea::make('value')
                ->label('Значение')
                ->rows(3),
            TextInput::make('sort')
                ->label('Сортировка')
                ->numeric()
                ->default(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('property_id'))
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('custom_name')
                    ->label('Свойство')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Значение')
                    ->limit(50),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить свойство'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Изменить'),
                DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}

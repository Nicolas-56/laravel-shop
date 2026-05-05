<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = 'Свойства категории';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('name')
                ->label('Свойство')
                ->content(fn ($record): string => $record?->name ?? '-'),
            Toggle::make('is_required')
                ->label('Обязательно')
                ->default(false),
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
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge(),
                Tables\Columns\IconColumn::make('pivot.is_required')
                    ->label('Обязательно')
                    ->boolean(),
                Tables\Columns\TextColumn::make('pivot.sort')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Свойство')
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_required')
                            ->label('Обязательно')
                            ->default(false),
                        TextInput::make('sort')
                            ->label('Сортировка')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}

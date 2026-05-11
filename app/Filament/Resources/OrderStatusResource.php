<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderStatusResource\Pages;
use App\Models\OrderStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderStatusResource extends Resource
{
    protected static ?string $model = OrderStatus::class;

    protected static ?string $navigationLabel = 'Статусы заказов';

    protected static ?string $modelLabel = 'статус заказа';

    protected static ?string $pluralModelLabel = 'статусы заказов';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('code')
                        ->label('Код')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),
                    Select::make('color')
                        ->label('Цвет')
                        ->options([
                            'gray' => 'Серый',
                            'warning' => 'Жёлтый',
                            'success' => 'Зелёный',
                            'danger' => 'Красный',
                            'secondary' => 'Вторичный',
                            'info' => 'Голубой',
                            'primary' => 'Основной',
                        ])
                        ->default('gray')
                        ->required(),
                    TextInput::make('sort_order')
                        ->label('Сортировка')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Toggle::make('is_final')
                        ->label('Финальный статус')
                        ->default(false)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('color')
                    ->label('Цвет')
                    ->badge()
                    ->color(fn (string $state): string => $state),
                Tables\Columns\IconColumn::make('is_final')
                    ->label('Финальный')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_final')
                    ->label('Тип')
                    ->options([
                        '1' => 'Финальные',
                        '0' => 'Промежуточные',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderStatuses::route('/'),
            'create' => Pages\CreateOrderStatus::route('/create'),
            'edit' => Pages\EditOrderStatus::route('/{record}/edit'),
        ];
    }
}

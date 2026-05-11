<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use App\Models\OrderStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Заказы';

    protected static ?string $modelLabel = 'заказ';

    protected static ?string $pluralModelLabel = 'заказы';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('order_status_id')
                        ->label('Статус')
                        ->relationship('orderStatus', 'name', fn ($query) => $query->ordered())
                        ->getOptionLabelFromRecordUsing(fn (OrderStatus $record): string => $record->name)
                        ->preload()
                        ->required(),
                    TextInput::make('total')
                        ->label('Сумма')
                        ->numeric()
                        ->suffix(' руб.')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('customer_name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('customer_phone')
                        ->label('Телефон')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('customer_email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Textarea::make('customer_comment')
                        ->label('Комментарий')
                        ->rows(6)
                        ->columnSpanFull(),
                    TextInput::make('delivery_name')
                        ->label('Доставка')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('delivery_price')
                        ->label('Стоимость доставки')
                        ->numeric()
                        ->suffix(' руб.')
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Пользователь')
                    ->placeholder('Гость')
                    ->searchable(),
                Tables\Columns\SelectColumn::make('order_status_id')
                    ->label('Статус')
                    ->options(fn (): array => OrderStatus::query()->ordered()->pluck('name', 'id')->all())
                    ->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('orderStatus.name')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (Order $record): string => $record->orderStatus?->color ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('delivery_name')
                    ->label('Доставка')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' руб.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_status_id')
                    ->label('Статус')
                    ->relationship('orderStatus', 'name', fn ($query) => $query->ordered())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

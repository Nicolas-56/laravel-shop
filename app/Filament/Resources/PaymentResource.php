<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Платежи';

    protected static ?string $modelLabel = 'платёж';

    protected static ?string $pluralModelLabel = 'платежи';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options(static::statusOptions())
                        ->required(),
                    TextInput::make('order_id')
                        ->label('Заказ')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider')
                        ->label('Провайдер')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('provider_payment_id')
                        ->label('ID у провайдера')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('amount')
                        ->label('Сумма')
                        ->numeric()
                        ->suffix(' руб.')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('payment_url')
                        ->label('Ссылка на оплату')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Textarea::make('meta')
                        ->label('Meta')
                        ->formatStateUsing(fn (mixed $state): string => json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Заказ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Провайдер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::statusOptions()[$state] ?? $state)
                    ->colors(static::statusColors()),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' руб.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider_payment_id')
                    ->label('ID у провайдера')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(static::statusOptions()),
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Провайдер')
                    ->options([
                        'fake' => 'Fake',
                        'yookassa' => 'YooKassa',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            Payment::STATUS_PENDING => 'Ожидает оплаты',
            Payment::STATUS_PAID => 'Оплачен',
            Payment::STATUS_FAILED => 'Ошибка оплаты',
        ];
    }

    public static function statusColors(): array
    {
        return [
            'warning' => Payment::STATUS_PENDING,
            'success' => Payment::STATUS_PAID,
            'danger' => Payment::STATUS_FAILED,
        ];
    }
}

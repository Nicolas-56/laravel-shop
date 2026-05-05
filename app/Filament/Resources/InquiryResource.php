<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Filament\Resources\InquiryResource\RelationManagers\InquiryItemsRelationManager;
use App\Models\Inquiry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static ?string $navigationLabel = 'Запросы на товары';

    protected static ?string $modelLabel = 'запрос на товар';

    protected static ?string $pluralModelLabel = 'запросы на товары';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'new' => 'Новая',
                            'processing' => 'В обработке',
                            'completed' => 'Завершена',
                            'canceled' => 'Отменена',
                        ])
                        ->default('new')
                        ->required(),
                    Textarea::make('comment')
                        ->label('Комментарий')
                        ->rows(8)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Позиции')
                ->description('При создании заявки можно прикрепить товары.')
                ->schema([
                    Repeater::make('inquiryItems')
                        ->label('Товары')
                        ->defaultItems(0)
                        ->relationship('inquiryItems')
                        ->schema([
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
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => filled($state['quantity'] ?? null) ? "Кол-во: {$state['quantity']}" : null)
                        ->addActionLabel('Добавить товар')
                        ->reorderable(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'gray' => 'canceled',
                    ]),
                Tables\Columns\TextColumn::make('inquiry_items_count')
                    ->counts('inquiryItems')
                    ->label('Позиции'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime(),
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

    public static function getRelations(): array
    {
        return [
            InquiryItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'create' => Pages\CreateInquiry::route('/create'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}

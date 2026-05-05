<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $modelLabel = 'услуга';

    protected static ?string $pluralModelLabel = 'услуги';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Название')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
            TextInput::make('slug')
                ->label('Слаг')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Textarea::make('short_description')
                ->label('Краткое описание')
                ->rows(3)
                ->required(),
            Textarea::make('description')
                ->label('Описание')
                ->rows(8)
                ->required(),
            TextInput::make('sort_order')
                ->label('Сортировка')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('is_active')
                ->label('Активна')
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Слаг')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('Активна')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Сортировка')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Обновлена')->dateTime(),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}

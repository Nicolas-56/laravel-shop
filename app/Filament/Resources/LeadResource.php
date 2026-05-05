<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationLabel = 'Заявки';

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'заявки';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Имя')->required()->maxLength(255),
            TextInput::make('phone')->label('Телефон')->required()->maxLength(50),
            TextInput::make('email')->label('Email')->email()->maxLength(255),
            Select::make('service_id')
                ->label('Услуга')
                ->relationship('service', 'title')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->label('Статус')
                ->options([
                    'new' => 'Новая',
                    'contacted' => 'Связались',
                    'archived' => 'В архиве',
                ])
                ->required(),
            Textarea::make('message')->label('Сообщение')->rows(6),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Телефон'),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('service.title')->label('Услуга'),
                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors([
                    'primary' => 'new',
                    'success' => 'contacted',
                    'gray' => 'archived',
                ]),
                Tables\Columns\TextColumn::make('created_at')->label('Создана')->dateTime(),
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}

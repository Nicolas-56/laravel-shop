<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers\PropertiesRelationManager;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationLabel = 'Категории';

    protected static ?string $modelLabel = 'категория';

    protected static ?string $pluralModelLabel = 'категории';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Категория')
                ->tabs([
                    Tab::make('Основное')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('parent_id')
                                        ->label('Родительская категория')
                                        ->options(fn (?Category $record): array => Category::query()
                                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn (Category $category): array => [
                                                $category->getKey() => "{$category->name} ({$category->full_slug})",
                                            ])
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Оставьте пустым для корневой категории.')
                                        ->rule(function (?Category $record) {
                                            return function (string $attribute, $value, \Closure $fail) use ($record): void {
                                                if (! $record || ! filled($value)) {
                                                    return;
                                                }

                                                if ((int) $value === $record->getKey() || $record->hasDescendant((int) $value)) {
                                                    $fail('Родительская категория не может быть текущей категорией или её потомком.');
                                                }
                                            };
                                        }),
                                    TextInput::make('name')
                                        ->label('Название')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                                    TextInput::make('slug')
                                        ->label('Слаг')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true)
                                        ->helperText(fn (Get $get): string => static::buildPreviewPath(
                                            slug: (string) $get('slug'),
                                            parentId: filled($get('parent_id')) ? (int) $get('parent_id') : null,
                                        )),
                                    TextInput::make('sort')
                                        ->label('Сортировка')
                                        ->numeric()
                                        ->default(0)
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label('Активна')
                                        ->default(true)
                                        ->required(),
                                ])
                                ->columns(2),
                        ]),
                    Tab::make('SEO')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('meta_title')
                                        ->label('Meta title')
                                        ->maxLength(255),
                                    Textarea::make('meta_description')
                                        ->label('Meta description')
                                        ->rows(4),
                                    Textarea::make('seo_text')
                                        ->label('SEO текст')
                                        ->rows(10),
                                ]),
                        ]),
                ])
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Родитель')
                    ->placeholder('Корневая')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слаг')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_slug')
                    ->label('Полный URL')
                    ->searchable(query: function ($query, string $search) {
                        $query->where('slug', 'like', "%{$search}%");
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Сортировка')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Товары'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлена')
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
            PropertiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    protected static function buildPreviewPath(string $slug, ?int $parentId): string
    {
        $slug = trim($slug);

        if ($slug === '') {
            return 'Предпросмотр URL: -';
        }

        if (! $parentId) {
            return "Предпросмотр URL: /catalog/{$slug}";
        }

        $parent = Category::query()->find($parentId);
        $prefix = $parent?->full_slug;

        return 'Предпросмотр URL: /catalog/' . trim(implode('/', array_filter([$prefix, $slug])), '/');
    }
}

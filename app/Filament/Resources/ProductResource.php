<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ProductImagesRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Property;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'товары';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Товар')
                ->tabs([
                    Tab::make('Основное')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('category_id')
                                        ->label('Категория')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                            $set(
                                                'productProperties',
                                                static::buildStandardPropertiesState(
                                                    categoryId: filled($state) ? (int) $state : null,
                                                    currentState: $get('productProperties'),
                                                ),
                                            );
                                        })
                                        ->required(),
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
                                        ->unique(ignoreRecord: true),
                                    TextInput::make('article')
                                        ->label('Артикул')
                                        ->maxLength(255),
                                    TextInput::make('price')
                                        ->label('Цена')
                                        ->numeric()
                                        ->suffix('руб.'),
                                    TextInput::make('sort')
                                        ->label('Сортировка')
                                        ->numeric()
                                        ->default(0)
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label('Активен')
                                        ->default(true)
                                        ->required(),
                                    Textarea::make('short_description')
                                        ->label('Краткое описание')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                    Textarea::make('description')
                                        ->label('Описание')
                                        ->rows(12)
                                        ->columnSpanFull(),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'xl' => 3,
                                ]),
                        ]),
                    Tab::make('Характеристики')
                        ->schema([
                            Section::make()
                                ->description('Свойства категории заполняются сразу в форме товара.')
                                ->schema([
                                    Repeater::make('productProperties')
                                        ->label('Свойства категории')
                                        ->visible(fn (Get $get): bool => filled($get('category_id')))
                                        ->defaultItems(0)
                                        ->schema([
                                            TextInput::make('property_name')
                                                ->label(fn (Get $get): string => $get('is_required') ? 'Свойство *' : 'Свойство')
                                                ->hint(fn (Get $get): ?string => $get('is_required') ? 'Обязательно' : null)
                                                ->hintColor(fn (Get $get): ?string => $get('is_required') ? 'danger' : null)
                                                ->disabled()
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 6,
                                                ])
                                                ->dehydrated(false),
                                            Textarea::make('value')
                                                ->label('Значение')
                                                ->rows(3)
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 6,
                                                ]),
                                            TextInput::make('sort')
                                                ->label('Сортировка')
                                                ->numeric()
                                                ->default(0)
                                                ->minValue(0)
                                                ->extraInputAttributes(['style' => 'max-width: 6rem'])
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 3,
                                                ])
                                                ->extraFieldWrapperAttributes(['class' => 'self-start'])
                                                ->required(),
                                            Hidden::make('property_id'),
                                            Hidden::make('is_required'),
                                        ])
                                        ->columns([
                                            'default' => 12,
                                            'lg' => 12,
                                        ])
                                        ->itemLabel(fn (array $state): ?string => $state['property_name'] ?? null)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false),
                                    Repeater::make('customProductProperties')
                                        ->label('Дополнительные свойства')
                                        ->helperText('Используйте для характеристик, которых нет в свойствах категории.')
                                        ->defaultItems(0)
                                        ->relationship('customProductProperties')
                                        ->schema([
                                            TextInput::make('custom_name')
                                                ->label('Своё название')
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 6,
                                                ])
                                                ->required(),
                                            Textarea::make('value')
                                                ->label('Значение')
                                                ->rows(3)
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 6,
                                                ]),
                                            TextInput::make('sort')
                                                ->label('Сортировка')
                                                ->numeric()
                                                ->default(0)
                                                ->minValue(0)
                                                ->extraInputAttributes(['style' => 'max-width: 6rem'])
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'lg' => 3,
                                                ])
                                                ->extraFieldWrapperAttributes(['class' => 'self-start'])
                                                ->required(),
                                        ])
                                        ->columns([
                                            'default' => 12,
                                            'lg' => 12,
                                        ])
                                        ->itemLabel(fn (array $state): ?string => $state['custom_name'] ?? null)
                                        ->addActionLabel('Добавить свойство')
                                        ->reorderable(false),
                                ]),
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
                Tables\Columns\ImageColumn::make('mainImage.path')
                    ->label('Изображение')
                    ->disk('public')
                    ->defaultImageUrl(url('/favicon.ico')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable(),
                Tables\Columns\TextColumn::make('article')
                    ->label('Артикул')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' руб.')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Сортировка')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
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
            ProductImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    protected static function buildStandardPropertiesState(?int $categoryId, mixed $currentState): array
    {
        $state = collect(is_array($currentState) ? array_values($currentState) : [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();

        if (! $categoryId) {
            return $state->all();
        }

        /** @var Category|null $category */
        $category = Category::query()->find($categoryId);

        if (! $category) {
            return $state->all();
        }

        /** @var Collection<int, int> $existingPropertyIds */
        $existingPropertyIds = $state
            ->pluck('property_id')
            ->filter()
            ->map(fn (mixed $value): int => (int) $value);

        $additionalItems = $category->properties()
            ->orderBy('category_property.sort')
            ->get(['properties.id', 'properties.name'])
            ->reject(fn (Property $property): bool => $existingPropertyIds->contains($property->getKey()))
            ->map(fn (Property $property): array => [
                'property_id' => $property->getKey(),
                'property_name' => $property->name,
                'value' => null,
                'sort' => (int) ($property->pivot->sort ?? 0),
                'is_required' => (bool) ($property->pivot->is_required ?? false),
            ]);

        return $state
            ->concat($additionalItems)
            ->sortBy(['sort', 'property_name'])
            ->values()
            ->all();
    }

    protected static function enrichStandardPropertiesState(array $data): array
    {
        $propertyIds = collect($data)
            ->pluck('property_id')
            ->filter()
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        if ($propertyIds->isEmpty()) {
            return $data;
        }

        $properties = Property::query()
            ->with(['categories' => fn ($query) => $query->select('categories.id')])
            ->whereKey($propertyIds->all())
            ->get()
            ->keyBy('id');

        return collect($data)
            ->map(function (array $item) use ($properties): array {
                $property = $properties->get((int) ($item['property_id'] ?? 0));

                return [
                    ...$item,
                    'property_name' => $property?->name ?? ($item['property_name'] ?? 'Свойство'),
                    'is_required' => (bool) ($item['is_required'] ?? false),
                ];
            })
            ->all();
    }

    protected static function stripStandardPropertiesUiFields(array $data): array
    {
        return collect($data)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => collect($item)
                ->except(['property_name', 'is_required'])
                ->all())
            ->values()
            ->all();
    }

    public static function buildStandardPropertiesFormState(Product $product): array
    {
        $product->loadMissing([
            'category.properties',
            'standardProductProperties.property',
        ]);

        return $product->standardProductProperties
            ->map(function ($item) use ($product): array {
                $isRequired = (bool) $product->category
                    ?->properties
                    ->firstWhere('id', $item->property_id)
                    ?->pivot
                    ?->is_required;

                return [
                    'property_id' => $item->property_id,
                    'property_name' => $item->property?->name ?? 'Свойство',
                    'value' => $item->value,
                    'sort' => $item->sort,
                    'is_required' => $isRequired,
                ];
            })
            ->sortBy(['sort', 'property_name'])
            ->values()
            ->all();
    }
}

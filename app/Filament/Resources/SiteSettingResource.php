<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $modelLabel = 'настройка сайта';

    protected static ?string $pluralModelLabel = 'настройки сайта';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Настройка')
                ->columns(2)
                ->schema([
                    Select::make('setting_group')
                        ->label('Группа')
                        ->options(self::groupOptions())
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Select $component, Get $get): void {
                            if (filled($get('setting_group'))) {
                                return;
                            }

                            $component->state(self::settingGroup($get('key')));
                        })
                        ->afterStateUpdated(function (callable $set, ?string $state, ?string $old): void {
                            if ($state === $old) {
                                return;
                            }

                            $set('key', null);
                        })
                        ->required(),
                    Select::make('key')
                        ->label('Ключ')
                        ->required()
                        ->options(fn (Get $get): array => self::settingOptions($get('setting_group')))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (Get $get): bool => blank($get('setting_group')))
                        ->helperText(fn (Get $get): ?string => blank($get('setting_group')) ? 'Сначала выберите группу.' : null),
                    Placeholder::make('selected_group_label')
                        ->label('Выбранная группа')
                        ->content(fn (Get $get): string => self::settingGroup($get('key'))),
                    Placeholder::make('key_description')
                        ->label('Описание')
                        ->content(fn (Get $get): string => self::settingDescription($get('key')))
                        ->columnSpanFull(),
                    Textarea::make('value')
                        ->label('Значение')
                        ->rows(6)
                        ->rules(fn (Get $get): array => SiteSetting::validationRules($get('key')))
                        ->dehydrateStateUsing(
                            fn (?string $state, Get $get): ?string => SiteSetting::sanitizeValue($get('key'), $state)
                        )
                        ->helperText(fn (Get $get): ?string => self::settingHelperText($get('key')))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Группа')
                    ->state(fn (SiteSetting $record): string => self::settingGroup($record->key))
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('key', $direction)),
                Tables\Columns\TextColumn::make('key')
                    ->label('Ключ')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => SiteSetting::KNOWN_KEYS[$state] ?? $state),
                Tables\Columns\TextColumn::make('value')->label('Значение')->limit(60),
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
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }

    private static function groupOptions(): array
    {
        return collect(array_keys(self::settingGroups()))
            ->mapWithKeys(fn (string $group): array => [$group => $group])
            ->all();
    }

    private static function settingOptions(?string $group = null): array
    {
        if (blank($group) || ! array_key_exists($group, self::settingGroups())) {
            return [];
        }

        return collect(self::settingGroups())
            ->only([$group])
            ->flatMap(fn (array $keys): array => collect($keys)
                ->mapWithKeys(fn (string $key): array => [$key => SiteSetting::KNOWN_KEYS[$key] ?? $key])
                ->all())
            ->all();
    }

    private static function settingGroups(): array
    {
        return [
            'Брендинг' => [
                'site_name',
                'site_logo',
            ],
            'Главная' => [
                'home_seo_title',
                'home_seo_description',
                'hero_title',
                'hero_subtitle',
                'hero_background',
            ],
            'Оборудование' => [
                'equipment_seo_title',
                'equipment_seo_description',
            ],
            'Работы' => [
                'works_seo_title',
                'works_seo_description',
            ],
            'Контакты' => [
                'contacts_seo_title',
                'contacts_seo_description',
                'company_phone',
                'company_email',
                'company_telegram',
                'company_address',
                'company_city',
                'company_schedule',
            ],
            'Компания' => [
                'company_business_name',
                'company_inn',
                'company_ogrnip',
                'company_description',
            ],
        ];
    }

    private static function settingGroup(?string $key): string
    {
        if (blank($key)) {
            return 'Не выбрана';
        }

        foreach (self::settingGroups() as $group => $keys) {
            if (in_array($key, $keys, true)) {
                return $group;
            }
        }

        return 'Прочее';
    }

    private static function settingDescription(?string $key): string
    {
        return match ($key) {
            'site_logo', 'hero_background' => 'Только локальный путь: `data/...`, `storage/...` или путь внутри `storage/app/public`.',
            'company_telegram' => 'Только полная HTTPS-ссылка на Telegram, например `https://t.me/laser_met96`.',
            'company_email' => 'Email для контактов и политики конфиденциальности.',
            'company_description' => 'Разрешены только базовые HTML-теги: p, br, strong, em, ul, ol, li.',
            'home_seo_title' => 'Уникальный title для главной страницы. Лучше держать в пределах 50-70 символов.',
            'home_seo_description' => 'Краткое описание главной страницы для поисковой выдачи. Лучше держать в пределах 120-170 символов.',
            'equipment_seo_title' => 'Уникальный title для страницы оборудования. Лучше держать в пределах 50-70 символов.',
            'equipment_seo_description' => 'Краткое описание страницы оборудования для поисковой выдачи. Лучше держать в пределах 120-170 символов.',
            'works_seo_title' => 'Уникальный title для страницы выполненных работ. Лучше держать в пределах 50-70 символов.',
            'works_seo_description' => 'Краткое описание страницы выполненных работ для поисковой выдачи. Лучше держать в пределах 120-170 символов.',
            'contacts_seo_title' => 'Уникальный title для страницы контактов. Лучше держать в пределах 50-70 символов.',
            'contacts_seo_description' => 'Краткое описание страницы контактов для поисковой выдачи. Лучше держать в пределах 120-170 символов.',
            default => 'Настройка используется в публичных Blade-шаблонах сайта.',
        };
    }

    private static function settingHelperText(?string $key): ?string
    {
        return match ($key) {
            'site_logo' => 'Пример: `branding/logo.webp` или `images/logo.webp`.',
            'hero_background' => 'Пример: `branding/hero.webp` или `images/back.webp`.',
            'company_phone' => 'Отображаемый формат телефона.',
            'company_schedule' => 'Например: `Пн–Пт: 9:00–18:00`.',
            'home_seo_title' => 'Пример: `Лазерная резка металла в Оренбурге | ЛазерМет96`.',
            'home_seo_description' => 'Можно отделить SEO-описание от текста hero-блока.',
            'equipment_seo_title' => 'Пример: `Оборудование для лазерной резки металла в Оренбурге`.',
            'equipment_seo_description' => 'Укажите материалы, тип работ и географию, если это важно для локального SEO.',
            'works_seo_title' => 'Пример: `Выполненные работы по лазерной резке металла`.',
            'works_seo_description' => 'Укажите, что на странице есть категории работ, кейсы и примеры изделий.',
            'contacts_seo_title' => 'Пример: `Контакты компании ЛазерМет96`.',
            'contacts_seo_description' => 'Укажите телефон, город и назначение страницы для локального SEO.',
            default => null,
        };
    }
}

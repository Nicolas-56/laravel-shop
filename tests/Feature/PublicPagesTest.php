<?php

namespace Tests\Feature;

use App\Models\EquipmentPageItem;
use App\Models\SiteSetting;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Services\PageService;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_scope_returns_only_published_items_in_sort_order(): void
    {
        EquipmentPageItem::query()->create([
            'title' => 'Поздний элемент',
            'description' => 'Описание',
            'sort' => 2,
            'is_published' => true,
        ]);

        EquipmentPageItem::query()->create([
            'title' => 'Скрытый элемент',
            'description' => 'Описание',
            'sort' => 0,
            'is_published' => false,
        ]);

        EquipmentPageItem::query()->create([
            'title' => 'Первый элемент',
            'description' => 'Описание',
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->assertSame(
            ['Первый элемент', 'Поздний элемент'],
            EquipmentPageItem::query()->published()->ordered()->pluck('title')->all()
        );
    }

    public function test_work_scope_returns_only_published_items_in_sort_order(): void
    {
        Work::query()->create([
            'title' => 'Вторая работа',
            'sort' => 2,
            'is_published' => true,
        ]);

        Work::query()->create([
            'title' => 'Черновик',
            'sort' => 0,
            'is_published' => false,
        ]);

        Work::query()->create([
            'title' => 'Первая работа',
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->assertSame(
            ['Первая работа', 'Вторая работа'],
            Work::query()->published()->ordered()->pluck('title')->all()
        );
    }

    public function test_home_page_sanitizes_company_description_html(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'company_description',
            'value' => '<p>Безопасный текст</p><script>alert("xss")</script><img src=x onerror=alert(1)>',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<p>Безопасный текст</p>', false);
        $response->assertDontSee('<script>', false);
        $response->assertDontSee('onerror=', false);
    }

    public function test_contacts_page_does_not_render_unsafe_telegram_link(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'company_telegram',
            'value' => 'javascript:alert(1)',
        ]);

        $response = $this->get('/contacts');

        $response->assertOk();
        $response->assertDontSee('javascript:alert(1)', false);
        $response->assertDontSee('Написать в Telegram');
    }

    public function test_equipment_page_strips_unsafe_attributes_from_rich_text(): void
    {
        EquipmentPageItem::query()->create([
            'title' => 'Компрессор',
            'description' => '<p onclick="alert(1)">Абзац</p><blockquote style="color:red">Цитата</blockquote>',
            'sort' => 1,
            'is_published' => true,
        ]);

        $response = $this->get('/equipment');

        $response->assertOk();
        $response->assertSee('<p>Абзац</p>', false);
        $response->assertSee('<blockquote>Цитата</blockquote>', false);
        $response->assertDontSee('<p onclick="alert(1)">Абзац</p>', false);
        $response->assertDontSee('<blockquote style="color:red">Цитата</blockquote>', false);
    }

    public function test_equipment_page_uses_seo_values_from_site_settings(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'equipment_seo_title',
            'value' => 'Лазерное оборудование для резки металла в Оренбурге',
        ]);

        SiteSetting::query()->create([
            'key' => 'equipment_seo_description',
            'value' => 'Станки для лазерной резки стали, нержавейки и алюминия, а также участок гравировки металла по чертежам заказчика.',
        ]);

        $response = $this->get('/equipment');

        $response->assertOk();
        $response->assertSee('<title>Лазерное оборудование для резки металла в Оренбурге</title>', false);
        $response->assertSee(
            '<meta name="description" content="Станки для лазерной резки стали, нержавейки и алюминия, а также участок гравировки металла по чертежам заказчика.">',
            false
        );
    }

    public function test_home_page_uses_seo_values_from_site_settings(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'home_seo_title',
            'value' => 'Лазерная резка металла в Оренбурге | ЛазерМет96',
        ]);

        SiteSetting::query()->create([
            'key' => 'home_seo_description',
            'value' => 'Производство деталей из металла по чертежам заказчика: лазерная резка, гравировка и серийные партии.',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<title>Лазерная резка металла в Оренбурге | ЛазерМет96</title>', false);
        $response->assertSee(
            '<meta name="description" content="Производство деталей из металла по чертежам заказчика: лазерная резка, гравировка и серийные партии.">',
            false
        );
    }

    public function test_works_page_uses_seo_values_from_site_settings(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'works_seo_title',
            'value' => 'Выполненные работы по лазерной резке металла',
        ]);

        SiteSetting::query()->create([
            'key' => 'works_seo_description',
            'value' => 'Подборка выполненных работ и категорий по лазерной резке, изготовлению деталей и металлообработке.',
        ]);

        $response = $this->get('/works');

        $response->assertOk();
        $response->assertSee('<title>Выполненные работы по лазерной резке металла</title>', false);
        $response->assertSee(
            '<meta name="description" content="Подборка выполненных работ и категорий по лазерной резке, изготовлению деталей и металлообработке.">',
            false
        );
    }

    public function test_contacts_page_uses_seo_values_from_site_settings(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'contacts_seo_title',
            'value' => 'Контакты компании ЛазерМет96',
        ]);

        SiteSetting::query()->create([
            'key' => 'contacts_seo_description',
            'value' => 'Телефон, email и график работы для расчёта стоимости лазерной резки металла и заказов по чертежам.',
        ]);

        $response = $this->get('/contacts');

        $response->assertOk();
        $response->assertSee('<title>Контакты компании ЛазерМет96</title>', false);
        $response->assertSee(
            '<meta name="description" content="Телефон, email и график работы для расчёта стоимости лазерной резки металла и заказов по чертежам.">',
            false
        );
    }

    public function test_work_category_page_uses_category_seo_values(): void
    {
        $category = WorkCategory::query()->create([
            'name' => 'Лазерная резка нержавейки',
            'slug' => 'laser-cutting-stainless',
            'description' => 'Основное описание категории.',
            'meta_title' => 'Лазерная резка нержавейки в Оренбурге',
            'meta_description' => 'Категория работ по лазерной резке нержавейки: примеры изделий, описание задач и выполненные проекты.',
            'sort' => 1,
            'is_published' => true,
        ]);

        $response = $this->get(route('works.category', $category));

        $response->assertOk();
        $response->assertSee('<title>Лазерная резка нержавейки в Оренбурге</title>', false);
        $response->assertSee(
            '<meta name="description" content="Категория работ по лазерной резке нержавейки: примеры изделий, описание задач и выполненные проекты.">',
            false
        );
    }

    public function test_work_category_page_falls_back_to_category_content_for_seo(): void
    {
        $category = WorkCategory::query()->create([
            'name' => 'Гравировка металла',
            'slug' => 'metal-engraving',
            'description' => 'Описание категории для поисковой выдачи.',
            'sort' => 1,
            'is_published' => true,
        ]);

        $response = $this->get(route('works.category', $category));

        $response->assertOk();
        $response->assertSee('<title>Гравировка металла | Выполненные работы</title>', false);
        $response->assertSee(
            '<meta name="description" content="Описание категории для поисковой выдачи.">',
            false
        );
    }

    public function test_unpublished_work_category_page_returns_not_found(): void
    {
        $category = WorkCategory::query()->create([
            'name' => 'Черновая категория',
            'slug' => 'draft-category',
            'description' => 'Скрытый раздел.',
            'sort' => 1,
            'is_published' => false,
        ]);

        $this->get(route('works.category', $category))
            ->assertNotFound();
    }

    public function test_html_sanitizer_removes_attributes_from_allowed_tags(): void
    {
        $sanitized = HtmlSanitizer::sanitizeBasicContent('<p class="x">A</p><br class="y"><ul data-test="1"><li>B</li></ul>');

        $this->assertSame('<p>A</p><br><ul><li>B</li></ul>', $sanitized);
    }

    public function test_equipment_items_cache_is_invalidated_when_models_change(): void
    {
        Cache::flush();

        $service = app(PageService::class);

        EquipmentPageItem::query()->create([
            'title' => 'Лазерный станок',
            'description' => 'Описание',
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->assertSame(['Лазерный станок'], $service->equipmentItems()->pluck('title')->all());

        DB::table('equipment_page_items')->insert([
            'title' => 'Компрессор',
            'description' => 'Описание',
            'sort' => 2,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(['Лазерный станок'], $service->equipmentItems()->pluck('title')->all());

        EquipmentPageItem::query()->create([
            'title' => 'Чиллер',
            'description' => 'Описание',
            'sort' => 3,
            'is_published' => true,
        ]);

        $this->assertSame(
            ['Лазерный станок', 'Компрессор', 'Чиллер'],
            $service->equipmentItems()->pluck('title')->all()
        );
    }

    public function test_equipment_items_cache_recovers_from_invalid_cached_value(): void
    {
        Cache::flush();

        EquipmentPageItem::query()->create([
            'title' => 'Лазерный станок',
            'description' => 'Описание',
            'sort' => 1,
            'is_published' => true,
        ]);

        Cache::put('public_pages:equipment:items:v1', ['broken'], now()->addMinutes(30));

        $items = app(PageService::class)->equipmentItems();
        $currentVersion = (int) Cache::get('public_pages:equipment:version', 1);

        $this->assertSame(['Лазерный станок'], $items->pluck('title')->all());
        $this->assertInstanceOf(
            \Illuminate\Support\Collection::class,
            Cache::get("public_pages:equipment:items:v{$currentVersion}")
        );
    }

    public function test_work_pages_cache_is_invalidated_when_models_change(): void
    {
        Cache::flush();

        $service = app(PageService::class);

        $category = WorkCategory::query()->create([
            'name' => 'Александровы вазы',
            'slug' => 'aleksandrovy-vazy',
            'short_description' => 'Описание',
            'description' => 'Полное описание',
            'sort' => 1,
            'is_published' => true,
        ]);

        Work::query()->create([
            'title' => 'Ваза 1',
            'category_id' => $category->id,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->assertSame(['Александровы вазы'], $service->workCategories()->pluck('name')->all());
        $this->assertSame(['Ваза 1'], $service->worksByCategory($category)->pluck('title')->all());

        DB::table('works')->insert([
            'title' => 'Ваза 2',
            'category_id' => $category->id,
            'image' => null,
            'alt' => null,
            'sort' => 2,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(['Ваза 1'], $service->worksByCategory($category)->pluck('title')->all());

        Work::query()->create([
            'title' => 'Ваза 3',
            'category_id' => $category->id,
            'sort' => 3,
            'is_published' => true,
        ]);

        $this->assertSame(
            ['Ваза 1', 'Ваза 2', 'Ваза 3'],
            $service->worksByCategory($category)->pluck('title')->all()
        );
    }

    public function test_public_pages_include_security_headers(): void
    {
        $response = $this->get('/');
        $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->assertStringContainsString("default-src 'self'", $contentSecurityPolicy);
        $this->assertStringContainsString("base-uri 'self'", $contentSecurityPolicy);
        $this->assertStringContainsString("form-action 'self'", $contentSecurityPolicy);
        $this->assertStringContainsString("frame-ancestors 'self'", $contentSecurityPolicy);
        $this->assertStringContainsString("object-src 'none'", $contentSecurityPolicy);
        $this->assertStringContainsString("img-src 'self' data: blob:", $contentSecurityPolicy);
        $this->assertStringContainsString("font-src 'self' data: https://cdn.jsdelivr.net", $contentSecurityPolicy);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net", $contentSecurityPolicy);
        $this->assertStringContainsString("script-src 'self' https://cdn.jsdelivr.net https://mc.yandex.ru https://yastatic.net https://mc.yandex.com 'unsafe-inline'", $contentSecurityPolicy);
        $this->assertStringContainsString("connect-src 'self' https://mc.yandex.ru https://mc.yandex.com wss://mc.yandex.ru ws: wss: http: https:", $contentSecurityPolicy);

        $viteDevServer = $this->viteDevServer();

        if ($viteDevServer) {
            $this->assertStringContainsString($viteDevServer, $contentSecurityPolicy);
        } else {
            $this->assertFalse(Str::contains($contentSecurityPolicy, 'http://127.0.0.1:5173'));
        }
    }

    public function test_public_pages_use_nonce_based_csp_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $response = $this->get('/');
        $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');
        $scriptDirective = Str::of($contentSecurityPolicy)
            ->explode('; ')
            ->first(fn (string $directive): bool => Str::startsWith($directive, 'script-src '));

        $this->assertStringContainsString("script-src 'self' https://cdn.jsdelivr.net https://mc.yandex.ru https://yastatic.net https://mc.yandex.com 'nonce-", $contentSecurityPolicy);
        $this->assertStringNotContainsString("'unsafe-inline'", (string) $scriptDirective);
        $this->assertStringContainsString("connect-src 'self' https://mc.yandex.ru https://mc.yandex.com wss://mc.yandex.ru", $contentSecurityPolicy);
        $response->assertSee('type="application/ld+json" nonce="', false);
    }

    private function viteDevServer(): ?string
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return null;
        }

        $url = trim((string) file_get_contents($hotFile));

        return Str::startsWith($url, ['http://', 'https://']) ? rtrim($url, '/') : null;
    }

    public function test_home_page_includes_basic_structured_data(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'site_name',
            'value' => 'ЛазерМет96',
        ]);

        SiteSetting::query()->create([
            'key' => 'company_business_name',
            'value' => 'ИП Пегов Павел Сергеевич',
        ]);

        SiteSetting::query()->create([
            'key' => 'company_phone',
            'value' => '+7 (922) 854-74-86',
        ]);

        SiteSetting::query()->create([
            'key' => 'company_email',
            'value' => 'pegov_92@mail.ru',
        ]);

        SiteSetting::query()->create([
            'key' => 'company_city',
            'value' => 'Екатеринбург',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('"@type":"LocalBusiness"', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"name":"ИП Пегов Павел Сергеевич"', false);
        $response->assertSee('"telephone":"+79228547486"', false);
        $response->assertSee('"inLanguage":"ru-RU"', false);
    }

    public function test_site_settings_cache_is_invalidated_when_models_change(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'company_phone',
            'value' => '+7 (900) 000-00-01',
        ]);

        $this->get('/')->assertSee('+7 (900) 000-00-01');

        DB::table('site_settings')->where('key', 'company_phone')->update([
            'value' => '+7 (900) 000-00-02',
            'updated_at' => now(),
        ]);

        $this->get('/')->assertSee('+7 (900) 000-00-01');
        $this->get('/')->assertDontSee('+7 (900) 000-00-02');

        SiteSetting::query()->updateOrCreate(
            ['key' => 'company_phone'],
            ['value' => '+7 (900) 000-00-03']
        );

        $this->get('/')->assertSee('+7 (900) 000-00-03');
    }

    public function test_home_page_loads_site_settings_with_single_select_query(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'key' => 'site_name',
            'value' => 'ЛазерМет96',
        ]);

        SiteSetting::query()->create([
            'key' => 'home_seo_title',
            'value' => 'Лазерная резка металла',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/')->assertOk();

        $siteSettingsQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(function (string $query): bool {
                $query = strtolower($query);

                return str_contains($query, 'select')
                    && str_contains($query, 'from "site_settings"');
            });

        $this->assertCount(1, $siteSettingsQueries);
    }
}

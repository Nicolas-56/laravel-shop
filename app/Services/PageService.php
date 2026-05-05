<?php

namespace App\Services;

use App\Models\EquipmentPageItem;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Support\PublicPageCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PageService
{
    private ?Collection $siteSettings = null;

    private ?int $siteSettingsVersion = null;

    private array $tableExists = [];

    public function homeSeoTitle(): string
    {
        return $this->sanitizedSiteSetting('home_seo_title')
            ?: $this->sanitizedSiteSetting('site_name')
            ?: config('app.name');
    }

    public function homeSeoDescription(): string
    {
        return $this->sanitizedSiteSetting('home_seo_description')
            ?: $this->sanitizedSiteSetting('hero_subtitle')
            ?: 'Лазерная резка металла, гравировка и производство деталей по чертежам.';
    }

    public function serviceItems(): Collection
    {
        if (! $this->hasTable('services')) {
            return collect();
        }

        return Service::query()->active()->ordered()->limit(3)->get();
    }

    public function equipmentItems(): Collection
    {
        if (! $this->hasTable('equipment_page_items')) {
            return collect();
        }

        return PublicPageCache::rememberEquipmentItems(
            fn () => EquipmentPageItem::query()->published()->ordered()->get()
        );
    }

    public function worksItems(): Collection
    {
        if (! $this->hasTable('works')) {
            return collect();
        }

        return Work::query()
            ->published()
            ->ordered()
            ->get();
    }

    public function workCategories(): Collection
    {
        if (! $this->hasTable('category_work') || ! $this->hasTable('works')) {
            return collect();
        }

        return PublicPageCache::rememberWorkCategories(
            fn () => WorkCategory::query()
                ->published()
                ->ordered()
                ->whereHas('works', fn ($query) => $query->published())
                ->withCount([
                    'works' => fn ($query) => $query->published(),
                ])
                ->get()
        );
    }

    public function worksByCategory(WorkCategory $category): Collection
    {
        if (! $this->hasTable('works')) {
            return collect();
        }

        return PublicPageCache::rememberWorksByCategory(
            (int) $category->getKey(),
            fn () => Work::query()
                ->where('category_id', $category->getKey())
                ->published()
                ->ordered()
                ->get()
        );
    }

    public function siteSettings(): Collection
    {
        $currentVersion = (int) Cache::get('public_pages:site_settings:version', 1);

        if ($this->siteSettings instanceof Collection && $this->siteSettingsVersion === $currentVersion) {
            return $this->siteSettings;
        }

        if (! $this->hasTable('site_settings')) {
            $this->siteSettingsVersion = $currentVersion;

            return $this->siteSettings = collect();
        }

        try {
            $this->siteSettingsVersion = $currentVersion;

            return $this->siteSettings = PublicPageCache::rememberSiteSettings(
                fn () => SiteSetting::query()->pluck('value', 'key')
            );
        } catch (\Throwable) {
            $this->siteSettingsVersion = $currentVersion;

            return $this->siteSettings = collect();
        }
    }

    public function companyDescription(): ?string
    {
        return SiteSetting::sanitizeValue('company_description', $this->siteSetting('company_description'));
    }

    public function equipmentSeoTitle(): ?string
    {
        return $this->sanitizedSiteSetting('equipment_seo_title');
    }

    public function equipmentSeoDescription(): ?string
    {
        return $this->sanitizedSiteSetting('equipment_seo_description');
    }

    public function worksSeoTitle(): ?string
    {
        return $this->sanitizedSiteSetting('works_seo_title');
    }

    public function worksSeoDescription(): ?string
    {
        return $this->sanitizedSiteSetting('works_seo_description');
    }

    public function contactsSeoTitle(): ?string
    {
        return $this->sanitizedSiteSetting('contacts_seo_title');
    }

    public function contactsSeoDescription(): ?string
    {
        return $this->sanitizedSiteSetting('contacts_seo_description');
    }

    public function mapEquipmentItems(Collection $items): Collection
    {
        return $items->map(fn (EquipmentPageItem $item) => (object) [
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => SiteSetting::resolveMediaPath($item->image, 'images/back.webp'),
            'image_alt' => $item->title,
        ]);
    }

    public function mapWorks(Collection $items): Collection
    {
        return $items->map(fn (Work $item) => (object) [
            'title' => $item->title,
            'image_url' => SiteSetting::resolveMediaPath($item->image, 'images/back.webp'),
            'alt' => $item->alt ?: $item->title,
        ]);
    }

    public function mapWorkCategories(Collection $items): Collection
    {
        return $items->map(fn (WorkCategory $item) => (object) [
            'name' => $item->name,
            'slug' => $item->slug,
            'short_description' => $item->short_description ?: strip_tags((string) $item->description),
            'image_url' => SiteSetting::resolveMediaPath($item->image, 'images/back.webp'),
            'works_count' => (int) ($item->works_count ?? 0),
        ]);
    }

    private function siteSetting(string $key): ?string
    {
        return $this->siteSettings()->get($key);
    }

    private function sanitizedSiteSetting(string $key): ?string
    {
        return SiteSetting::sanitizeValue($key, $this->siteSetting($key));
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableExists)) {
            return $this->tableExists[$table];
        }

        return $this->tableExists[$table] = Schema::hasTable($table);
    }
}

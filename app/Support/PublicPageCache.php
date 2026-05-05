<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PublicPageCache
{
    private const int TTL_MINUTES = 30;

    private const string EQUIPMENT_VERSION_KEY = 'public_pages:equipment:version';

    private const string SITE_SETTINGS_VERSION_KEY = 'public_pages:site_settings:version';

    private const string SITEMAP_VERSION_KEY = 'public_pages:sitemap:version';

    private const string WORKS_VERSION_KEY = 'public_pages:works:version';

    public static function rememberEquipmentItems(Closure $callback): mixed
    {
        return self::rememberCollection(
            self::versionedKey('public_pages:equipment:items', self::EQUIPMENT_VERSION_KEY),
            $callback,
        );
    }

    public static function rememberSiteSettings(Closure $callback): mixed
    {
        return self::rememberCollection(
            self::versionedKey('public_pages:site_settings:items', self::SITE_SETTINGS_VERSION_KEY),
            $callback,
        );
    }

    public static function rememberWorkCategories(Closure $callback): mixed
    {
        return self::rememberCollection(
            self::versionedKey('public_pages:works:categories', self::WORKS_VERSION_KEY),
            $callback,
        );
    }

    public static function rememberSitemapEntries(Closure $callback): mixed
    {
        return self::rememberCollection(
            self::versionedKey('public_pages:sitemap:entries', self::SITEMAP_VERSION_KEY),
            $callback,
        );
    }

    public static function rememberWorksByCategory(int $categoryId, Closure $callback): mixed
    {
        return self::rememberCollection(
            self::versionedKey("public_pages:works:category:{$categoryId}", self::WORKS_VERSION_KEY),
            $callback,
        );
    }

    public static function invalidateEquipment(): void
    {
        self::bumpVersion(self::EQUIPMENT_VERSION_KEY);
        self::invalidateSitemap();
    }

    public static function invalidateSiteSettings(): void
    {
        self::bumpVersion(self::SITE_SETTINGS_VERSION_KEY);
        self::invalidateSitemap();
    }

    public static function invalidateWorks(): void
    {
        self::bumpVersion(self::WORKS_VERSION_KEY);
        self::invalidateSitemap();
    }

    public static function invalidateSitemap(): void
    {
        self::bumpVersion(self::SITEMAP_VERSION_KEY);
    }

    private static function rememberCollection(string $key, Closure $callback): Collection
    {
        $cached = Cache::get($key);

        if ($cached instanceof Collection) {
            return $cached;
        }

        if ($cached !== null) {
            Cache::forget($key);
        }

        $fresh = $callback();

        if (! $fresh instanceof Collection) {
            return collect($fresh);
        }

        Cache::put($key, $fresh, now()->addMinutes(self::TTL_MINUTES));

        return $fresh;
    }

    private static function versionedKey(string $prefix, string $versionKey): string
    {
        return sprintf('%s:v%d', $prefix, self::version($versionKey));
    }

    private static function version(string $key): int
    {
        return (int) Cache::get($key, 1);
    }

    private static function bumpVersion(string $key): void
    {
        Cache::forever($key, self::version($key) + 1);
    }
}

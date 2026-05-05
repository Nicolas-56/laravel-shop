<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use App\Support\PublicImageVariants;
use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['key', 'value'])]
class SiteSetting extends Model
{
    public const KNOWN_KEYS = [
        'site_name' => 'Название сайта',
        'site_logo' => 'Логотип',
        'home_seo_title' => 'SEO title главной страницы',
        'home_seo_description' => 'SEO description главной страницы',
        'hero_title' => 'Заголовок hero-блока',
        'hero_subtitle' => 'Подзаголовок hero-блока',
        'hero_background' => 'Фоновое изображение hero-блока',
        'equipment_seo_title' => 'SEO title страницы "Оборудование"',
        'equipment_seo_description' => 'SEO description страницы "Оборудование"',
        'works_seo_title' => 'SEO title страницы "Работы"',
        'works_seo_description' => 'SEO description страницы "Работы"',
        'contacts_seo_title' => 'SEO title страницы "Контакты"',
        'contacts_seo_description' => 'SEO description страницы "Контакты"',
        'company_phone' => 'Телефон',
        'company_email' => 'Email',
        'company_address' => 'Адрес',
        'company_description' => 'Описание компании',
        'company_telegram' => 'Telegram',
        'company_city' => 'Город',
        'company_schedule' => 'Режим работы',
        'company_business_name' => 'ИП / наименование',
        'company_inn' => 'ИНН',
        'company_ogrnip' => 'ОГРНИП',
    ];

    protected static function booted(): void
    {
        $invalidateCache = static function (): void {
            PublicPageCache::invalidateSiteSettings();
        };

        static::saved($invalidateCache);
        static::deleted($invalidateCache);
    }

    public static function validationRules(?string $key): array
    {
        return match ($key) {
            'company_email' => ['nullable', 'email:rfc', 'max:255'],
            'company_telegram' => [function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (! self::isSafeExternalUrl((string) $value, ['t.me', 'telegram.me'])) {
                    $fail('Укажите корректную HTTPS-ссылку на Telegram.');
                }
            }],
            'site_logo', 'hero_background' => [function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (self::normalizeLocalMediaPath((string) $value) === null) {
                    $fail('Разрешены только локальные пути из public/data или storage.');
                }
            }],
            'company_description' => ['nullable', 'string', 'max:5000'],
            default => ['nullable', 'string', 'max:1000'],
        };
    }

    public static function sanitizeValue(?string $key, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return match ($key) {
            'company_description' => self::sanitizeCompanyDescription($value),
            'company_telegram' => self::isSafeExternalUrl($value, ['t.me', 'telegram.me']) ? $value : null,
            'site_logo', 'hero_background' => self::normalizeLocalMediaPath($value),
            default => $value,
        };
    }

    public static function resolveMediaPath(?string $path, string $fallback): string
    {
        $normalizedPath = self::normalizeLocalMediaPath((string) $path);

        if (blank($normalizedPath)) {
            return asset($fallback);
        }

        $preferredPath = PublicImageVariants::preferredPublicPath($normalizedPath) ?? $normalizedPath;

        if (Str::startsWith($preferredPath, ['storage/', 'data/', 'images/'])) {
            return asset($preferredPath);
        }

        return asset('storage/' . ltrim($preferredPath, '/'));
    }

    public static function isSafeExternalUrl(string $url, array $allowedHosts = []): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        if ($allowedHosts === []) {
            return true;
        }

        return in_array($host, $allowedHosts, true);
    }

    private static function sanitizeCompanyDescription(string $value): string
    {
        return HtmlSanitizer::sanitizeBasicContent($value) ?? '';
    }

    private static function normalizeLocalMediaPath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//'])) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if (str_contains($normalized, '..') || ! preg_match('/^[A-Za-z0-9_\/.\-]+$/', $normalized)) {
            return null;
        }

        if (Str::startsWith($normalized, ['storage/', 'data/'])) {
            return $normalized;
        }

        return $normalized;
    }
}

<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Models\WorkCategory;
use App\Support\PublicPageCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/equipment', [PageController::class, 'equipment'])->name('equipment');
Route::get('/works', [PageController::class, 'works'])->name('works');
Route::get('/works/{category:slug}', [PageController::class, 'workCategory'])->name('works.category');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');
Route::get('/sitemap.xml', function () {
    $urls = PublicPageCache::rememberSitemapEntries(function () {
        $latestPublicUpdate = collect([
            'site_settings',
            'equipment_page_items',
            'works',
            'category_work',
        ])->filter(fn (string $table): bool => Schema::hasTable($table))
            ->map(function (string $table): ?string {
                $updatedAt = DB::table($table)->max('updated_at');

                return $updatedAt ? date('Y-m-d', strtotime((string) $updatedAt)) : null;
            })
            ->filter()
            ->sort()
            ->last() ?? now()->toDateString();

        $staticUrls = collect([
            route('home'),
            route('equipment'),
            route('works'),
            route('contacts'),
            route('privacy'),
        ])->map(fn (string $url): array => [
            'loc' => $url,
            'lastmod' => $latestPublicUpdate,
        ]);

        $categoryUrls = collect();

        if (Schema::hasTable('category_work')) {
            $categoryUrls = WorkCategory::query()
                ->published()
                ->ordered()
                ->get(['slug', 'updated_at'])
                ->map(fn (WorkCategory $category): array => [
                    'loc' => route('works.category', $category->slug),
                    'lastmod' => $category->updated_at?->toDateString() ?? $latestPublicUpdate,
                ]);
        }

        return $staticUrls->merge($categoryUrls)
            ->unique('loc')
            ->values();
    });

    return response()
        ->view('sitemap', [
            'urls' => $urls,
        ])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

/*Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'show'])->name('register');
    Route::post('/register', [AuthController::class, 'store']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/personal/profile/', [AuthController::class, 'profile'])->name('profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});*/

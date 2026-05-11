<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StoreController;
use App\Models\WorkCategory;
use App\Support\PublicPageCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::redirect('/store', '/catalog', 301)->name('store.home');
Route::get('/catalog', [StoreController::class, 'catalog'])->name('catalog.index');
Route::get('/catalog/{category:slug}', [StoreController::class, 'catalog'])->name('catalog.category');
Route::get('/product/{product:slug}', [StoreController::class, 'product'])->name('products.show');
Route::get('/products/{product:slug}', [StoreController::class, 'legacyProduct'])->name('products.legacy.show');
Route::get('/cart', [StoreController::class, 'cart'])->name('cart.index');
Route::get('/checkout', [StoreController::class, 'checkout'])->name('checkout.show');
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

Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{product}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/payment/{order}', [PaymentController::class, 'fakePayment'])->name('payments.fake.show');
Route::get('/payment/{order}/success', [PaymentController::class, 'fakeSuccess'])->name('payments.fake.success');
Route::get('/payment/{order}/fail', [PaymentController::class, 'fakeFail'])->name('payments.fake.fail');
Route::get('/payment/{order}/cancel', [PaymentController::class, 'fakeCancel'])->name('payments.fake.cancel');
Route::post('/webhooks/fake-payment', [PaymentController::class, 'fakeWebhook'])->name('payments.fake.webhook');
Route::post('/webhooks/yookassa', [PaymentController::class, 'yookassaWebhook'])->name('payments.yookassa.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::get('/register', [AuthController::class, 'show'])->name('register');
    Route::post('/register', [AuthController::class, 'store']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/personal/profile/', [AuthController::class, 'profile'])->name('profile');
    Route::get('/personal/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/personal/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/personal/orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

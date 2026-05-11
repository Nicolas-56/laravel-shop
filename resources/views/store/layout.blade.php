@php
    use Illuminate\Support\Facades\Route;

    $siteName = $siteSettings['site_name'] ?? config('app.name', 'Tech Store');
    $storeTitle = $title ?? $siteName;
    $storeDescription = $description ?? 'Интернет-магазин электроники, комплектующих и техно-аксессуаров.';
    $storeCategories = $storeCategories ?? collect();
    $cartCount = $cartCount ?? collect(session('cart.items', []))->sum(fn (mixed $quantity): int => (int) $quantity);
    $catalogUrl = Route::has('catalog.index') ? route('catalog.index') : url('/catalog');
    $cartUrl = Route::has('cart.index') ? route('cart.index') : '#cart';
    $accountUrl = auth()->check()
        ? (Route::has('profile') ? route('profile') : '#account')
        : (Route::has('login') ? route('login') : '#login');
    $storeNavigation = array_values(array_filter([
        [
            'label' => 'Главная',
            'url' => Route::has('store.home') ? route('store.home') : url('/store'),
            'active' => request()->routeIs('store.home'),
        ],
        [
            'label' => 'Каталог',
            'url' => $catalogUrl,
            'active' => request()->routeIs('catalog.*', 'products.show', 'products.legacy.show'),
        ],
        auth()->check() && Route::has('orders.index') ? [
            'label' => 'Заказы',
            'url' => route('orders.index'),
            'active' => request()->routeIs('orders.*'),
        ] : null,
    ]));
@endphp

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $storeTitle }}</title>
    <meta name="description" content="{{ $storeDescription }}">
    <link rel="canonical" href="{{ $canonical ?? request()->url() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
<div class="relative isolate min-h-screen overflow-x-clip">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[36rem] bg-[radial-gradient(circle_at_20%_0%,rgba(14,165,233,0.22),transparent_34%),radial-gradient(circle_at_88%_12%,rgba(34,211,238,0.2),transparent_28%),linear-gradient(180deg,#eff6ff_0%,#f8fafc_72%)]"></div>

    @include('store.components.header')

    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (! empty($breadcrumbs))
            <nav aria-label="Хлебные крошки" class="mb-5 flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500">
                @foreach ($breadcrumbs as $crumb)
                    @if (! $loop->first)
                        <span class="text-slate-300">/</span>
                    @endif

                    @if (! empty($crumb['url']) && ! $loop->last)
                        <a href="{{ $crumb['url'] }}" class="transition hover:text-cyan-800">{{ $crumb['label'] }}</a>
                    @else
                        <span class="text-slate-950">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @yield('content')
    </main>

    @include('store.components.footer')
</div>
@stack('scripts')
</body>
</html>

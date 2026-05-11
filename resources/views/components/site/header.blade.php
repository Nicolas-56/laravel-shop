@php
    $navigation = [
        ['label' => 'Главная', 'route' => 'home'],
        ['label' => 'Каталог', 'route' => 'catalog.index', 'active' => ['catalog.*', 'products.show']],
        ['label' => 'Оборудование', 'route' => 'equipment'],
        ['label' => 'Выполненные работы', 'route' => 'works'],
        ['label' => 'Контакты', 'route' => 'contacts'],
    ];

    $companyPhone = $siteSettings['company_phone'] ?? '+7 (922) 854-74-86';
    $companyPhoneHref = $companyPhone ? preg_replace('/[^\d+]/', '', $companyPhone) : '+79228547486';
    $companyEmail = $siteSettings['company_email'] ?? 'pegov_92@mail.ru';
    $siteLogo = \App\Models\SiteSetting::resolveMediaPath($siteSettings['site_logo'] ?? null, 'images/logo-header.webp');
    $hasProfileRoute = \Illuminate\Support\Facades\Route::has('profile');
    $hasLogoutRoute = \Illuminate\Support\Facades\Route::has('logout');
    $hasCartRoute = \Illuminate\Support\Facades\Route::has('cart.index');
    $cartCount = $cartCount ?? collect(session('cart.items', []))->sum(fn (mixed $quantity): int => (int) $quantity);
@endphp

<header class="relative sticky top-0 z-50 border-b border-slate-300/80 bg-white/84 backdrop-blur-xl shadow-[0_12px_34px_rgba(15,23,42,0.1)] after:pointer-events-none after:absolute after:inset-0 after:-z-10 after:bg-[linear-gradient(180deg,rgba(15,23,42,0.08),rgba(15,23,42,0.02))]">
    <div class="mx-auto flex w-full max-w-7xl items-center gap-5 px-4 py-0 sm:px-6 sm:py-0 lg:px-8">
        <div class="flex min-w-0 items-center gap-5 lg:flex-1">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center">
                <img
                    src="{{ $siteLogo }}"
                    alt="{{ $siteSettings['site_name'] ?? 'Логотип компании' }}"
                    width="480"
                    height="384"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    class="-ml-1 h-[6.2rem] w-[10.5rem] rounded-xl border border-slate-200 object-fill object-center shadow-[0_8px_20px_rgba(15,23,42,0.08)] sm:h-[6.8rem] sm:w-[11.75rem]"
                >
            </a>

            <nav class="hidden min-w-0 items-center gap-2 lg:flex lg:flex-wrap">
                @foreach ($navigation as $item)
                    @php($isActive = request()->routeIs(...($item['active'] ?? [$item['route']])))
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'rounded-full px-5 py-2.5 text-lg font-medium transition',
                            'border border-slate-300 bg-white/90 text-slate-900 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.6),0_8px_20px_rgba(148,163,184,0.18)]' => $isActive,
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $isActive,
                        ])
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="ml-auto hidden items-center gap-6 lg:flex">
            @if ($hasCartRoute)
                <a href="{{ route('cart.index') }}" class="relative inline-flex items-center justify-center rounded-full border border-orange-300 bg-orange-50 px-4 py-2.5 text-base font-medium text-orange-700 transition hover:border-orange-400 hover:bg-orange-100">
                    Корзина
                    <span data-cart-count>
                        <span class="ml-2 inline-flex min-w-6 items-center justify-center rounded-full border border-orange-500 bg-orange-500 px-2 py-0.5 text-xs font-bold text-white">
                            {{ $cartCount }}
                        </span>
                    </span>
                </a>
            @endif

            <div class="text-right">
                <a href="tel:{{ $companyPhoneHref }}" class="block text-lg font-semibold text-slate-900 transition hover:text-orange-600">{{ $companyPhone }}</a>
                <a href="mailto:{{ $companyEmail }}" class="text-base text-slate-500 transition hover:text-slate-900">{{ $companyEmail }}</a>
            </div>

            @auth
                <div class="relative" data-user-menu>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2.5 text-lg text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                        data-user-menu-trigger
                        aria-expanded="false"
                    >
                        <span class="max-w-32 truncate">{{ auth()->user()->name }}</span>
                        <span class="text-xs text-slate-400">▾</span>
                    </button>

                    <div
                        class="absolute right-0 mt-3 grid min-w-56 gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl"
                        data-user-menu-dropdown
                        hidden
                    >
                        @if ($hasProfileRoute)
                            <a href="{{ route('profile') }}" class="rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                                Личный кабинет
                            </a>
                        @endif

                        @if ($hasLogoutRoute)
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                                    Выйти
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endauth

            @guest
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full border border-orange-300 bg-orange-50 px-4 py-2.5 text-base font-medium text-orange-700 transition hover:border-orange-400 hover:bg-orange-100"
                    data-modal-open="login-modal"
                >
                    Войти
                </button>
            @endguest
        </div>

        <button
            type="button"
            class="ml-auto inline-flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-100 lg:hidden"
            data-mobile-menu-trigger
            aria-expanded="false"
            aria-controls="mobile-menu"
        >
            <span class="sr-only">Открыть меню</span>
            <span class="space-y-1.5">
                <span class="block h-0.5 w-5 bg-current"></span>
                <span class="block h-0.5 w-5 bg-current"></span>
                <span class="block h-0.5 w-5 bg-current"></span>
            </span>
        </button>
    </div>

    <div id="mobile-menu" class="border-t border-slate-200 bg-white/95 lg:hidden" data-mobile-menu hidden>
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-4 sm:px-6">
            @foreach ($navigation as $item)
                @php($isActive = request()->routeIs(...($item['active'] ?? [$item['route']])))
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'rounded-2xl px-4 py-3 text-sm font-medium transition',
                        'border border-slate-300 bg-white text-slate-900 shadow-sm' => $isActive,
                        'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => ! $isActive,
                    ])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="metal-panel mt-2 rounded-2xl px-4 py-4 text-sm">
                <a href="tel:{{ $companyPhoneHref }}" class="block font-semibold text-slate-900">{{ $companyPhone }}</a>
                <a href="mailto:{{ $companyEmail }}" class="mt-1 block text-slate-500">{{ $companyEmail }}</a>
            </div>

            @if ($hasCartRoute)
                <a href="{{ route('cart.index') }}" class="mt-2 flex items-center justify-between rounded-2xl border border-orange-300 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-700 transition hover:border-orange-400 hover:bg-orange-100">
                    <span>Корзина</span>
                    <span data-cart-count>
                        <span class="inline-flex min-w-6 items-center justify-center rounded-full border border-orange-500 bg-orange-500 px-2 py-0.5 text-xs font-bold text-white">
                            {{ $cartCount }}
                        </span>
                    </span>
                </a>
            @endif
        </div>
    </div>
</header>

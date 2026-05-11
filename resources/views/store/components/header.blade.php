@php
    use Illuminate\Support\Facades\Route;
@endphp

<header class="sticky top-0 z-50 border-b border-cyan-100/80 bg-white/88 backdrop-blur-xl shadow-[0_16px_44px_rgba(15,23,42,0.08)]">
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <a href="{{ Route::has('store.home') ? route('store.home') : (Route::has('home') ? route('home') : url('/')) }}" class="group flex min-w-0 items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-slate-950 text-sm font-black tracking-tight text-cyan-300 shadow-[0_14px_34px_rgba(8,47,73,0.28)]">
                    TS
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-base font-black tracking-tight text-slate-950 sm:text-lg">{{ $siteName }}</span>
                    <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">electronics hub</span>
                </span>
            </a>

            <nav aria-label="Основная навигация магазина" class="ml-8 hidden items-center gap-2 lg:flex">
                @foreach ($storeNavigation as $item)
                    <a href="{{ $item['url'] }}" @class([
                        'rounded-full px-4 py-2 text-sm font-black transition',
                        'bg-slate-950 text-white shadow-[0_14px_34px_rgba(15,23,42,0.18)]' => $item['active'],
                        'text-slate-700 hover:bg-cyan-50 hover:text-cyan-800' => ! $item['active'],
                    ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto hidden items-center gap-2 sm:flex">
                <a href="{{ $cartUrl }}" class="relative inline-flex h-11 items-center justify-center rounded-2xl border border-cyan-100 bg-cyan-50 px-3 text-sm font-bold text-slate-900 transition hover:border-cyan-300 hover:bg-cyan-100">
                    Корзина
                    <span data-cart-count>
                        @include('store.components.badge', [
                            'slot' => $cartCount,
                            'variant' => $cartCount > 0 ? 'cyan' : 'slate',
                            'class' => 'ml-2',
                        ])
                    </span>
                </a>
                <a href="{{ $accountUrl }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-800 transition hover:border-cyan-300 hover:text-cyan-800">
                    {{ auth()->check() ? 'Аккаунт' : 'Войти' }}
                </a>
            </div>

            <button
                type="button"
                class="ml-auto inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-100 bg-white text-slate-900 transition hover:border-cyan-300 hover:bg-cyan-50 lg:hidden"
                data-mobile-menu-trigger
                aria-expanded="false"
                aria-controls="store-mobile-menu"
            >
                <span class="sr-only">Открыть меню магазина</span>
                <span class="space-y-1.5">
                    <span class="block h-0.5 w-5 bg-current"></span>
                    <span class="block h-0.5 w-5 bg-current"></span>
                    <span class="block h-0.5 w-5 bg-current"></span>
                </span>
            </button>
        </div>

        <div class="mt-4 rounded-[2rem] border border-cyan-100 bg-white/82 p-4 shadow-[0_20px_60px_rgba(15,23,42,0.08)] sm:p-5">
            <form method="GET" action="{{ $catalogUrl }}" role="search" class="grid gap-3 xl:grid-cols-[1fr_auto]">
                <label class="relative block">
                    <span class="sr-only">Поиск по магазину</span>
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-cyan-700">⌕</span>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Найти ноутбук, SSD, кабель USB-C..."
                        class="h-12 w-full rounded-2xl border border-cyan-100 bg-white px-11 text-sm font-medium text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                    >
                </label>
                @include('store.components.button', [
                    'slot' => 'Искать',
                    'type' => 'submit',
                    'variant' => 'primary',
                    'class' => 'h-12 w-full xl:w-auto',
                ])
            </form>

            <nav aria-label="Категории магазина" class="-mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1">
                <a href="{{ Route::has('catalog.index') ? route('catalog.index') : url('/catalog') }}" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-800">
                    Все категории
                </a>
                @foreach ($storeCategories as $category)
                    <a href="{{ Route::has('catalog.category') ? route('catalog.category', $category->slug) : url('/catalog/'.$category->slug) }}" class="shrink-0 rounded-full border border-cyan-100 bg-cyan-50 px-4 py-2 text-sm font-bold text-slate-800 transition hover:border-cyan-300 hover:bg-cyan-100">
                        {{ $category->name }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div id="store-mobile-menu" class="mt-4 rounded-[2rem] border border-cyan-100 bg-white p-4 shadow-[0_18px_44px_rgba(15,23,42,0.08)] lg:hidden" data-mobile-menu hidden>
            <nav aria-label="Мобильная навигация магазина" class="grid gap-2">
                @foreach ($storeNavigation as $item)
                    <a href="{{ $item['url'] }}" @class([
                        'rounded-2xl px-4 py-3 text-sm font-black transition',
                        'bg-slate-950 text-white' => $item['active'],
                        'bg-slate-50 text-slate-700 hover:bg-cyan-50 hover:text-cyan-800' => ! $item['active'],
                    ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-4 grid gap-2 sm:hidden">
                <a href="{{ $cartUrl }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-cyan-100 bg-cyan-50 px-4 text-sm font-bold text-slate-900 transition hover:border-cyan-300 hover:bg-cyan-100">
                    Корзина
                </a>
                <a href="{{ $accountUrl }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-800 transition hover:border-cyan-300 hover:text-cyan-800">
                    {{ auth()->check() ? 'Аккаунт' : 'Войти' }}
                </a>
            </div>
        </div>
    </div>
</header>

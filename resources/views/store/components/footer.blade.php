<footer class="mt-14 border-t border-cyan-100 bg-white/92">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.9fr_0.9fr]">
            <div class="rounded-[2rem] border border-cyan-100 bg-slate-950 p-6 shadow-[0_24px_70px_rgba(8,47,73,0.16)]">
                @include('store.components.badge', ['slot' => 'tech store', 'variant' => 'cyan'])
                <p class="mt-5 text-2xl font-black tracking-tight text-white">{{ $siteName }}</p>
                <p class="mt-3 max-w-md text-sm font-medium leading-7 text-slate-300">
                    Техно-витрина для электроники, комплектующих и аксессуаров с быстрым поиском и понятной навигацией.
                </p>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-slate-400">Навигация</p>
                <div class="mt-4 grid gap-2 text-sm font-semibold text-slate-700">
                    @foreach ($storeNavigation as $item)
                        <a href="{{ $item['url'] }}" class="transition hover:text-cyan-700">{{ $item['label'] }}</a>
                    @endforeach
                    <a href="{{ $cartUrl }}" class="transition hover:text-cyan-700">Корзина</a>
                </div>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-slate-400">Категории</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($storeCategories->take(5) as $category)
                        @include('store.components.badge', [
                            'slot' => $category->name,
                            'variant' => 'cyan',
                        ])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</footer>

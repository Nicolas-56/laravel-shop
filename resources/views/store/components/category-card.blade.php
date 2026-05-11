<a href="{{ route('catalog.category', $category->slug) }}" class="group relative overflow-hidden rounded-3xl border border-cyan-100 bg-white p-5 shadow-[0_18px_44px_rgba(15,23,42,0.06)] transition hover:-translate-y-0.5 hover:border-cyan-300">
    <div class="absolute inset-y-0 right-0 w-24 bg-[radial-gradient(circle_at_center,rgba(34,211,238,0.24),transparent_62%)]"></div>
    <div class="relative">
        @include('store.components.badge', ['slot' => 'category', 'variant' => 'cyan'])
        <h3 class="mt-4 text-lg font-black tracking-tight text-slate-950">{{ $category->name }}</h3>
        <p class="mt-2 text-sm font-semibold text-slate-500">Перейти в раздел</p>
    </div>
</a>

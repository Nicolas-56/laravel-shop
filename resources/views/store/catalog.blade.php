@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        @if (! empty($breadcrumbs))
            <nav aria-label="Хлебные крошки" class="mb-8 flex flex-wrap items-center gap-2 text-sm font-medium text-slate-500">
                @foreach ($breadcrumbs as $crumb)
                    @if (! $loop->first)
                        <span class="text-slate-300">/</span>
                    @endif

                    @if (! empty($crumb['url']) && ! $loop->last)
                        <a href="{{ $crumb['url'] }}" class="transition hover:text-orange-700">{{ $crumb['label'] }}</a>
                    @else
                        <span class="text-slate-900">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <div class="max-w-5xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Каталог
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">
                {{ $currentCategory?->name ?? 'Каталог оборудования и комплектующих' }}
            </h1>
            <div class="mt-5 max-w-4xl space-y-5 text-base leading-8 text-slate-600 sm:text-lg">
                <p>
                    {{ $searchQuery ? 'Результаты поиска по каталогу и актуальные позиции по выбранной категории.' : 'Подборка товаров в спокойной витрине, визуально согласованной с разделом выполненных работ.' }}
                </p>
            </div>
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-[18rem_1fr] lg:items-start">
            <aside class="metal-panel h-fit rounded-[1.75rem] p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-orange-700">Категории</p>
                <div class="mt-5 grid gap-2">
                    <a href="{{ route('catalog.index', ['q' => $searchQuery]) }}" @class([
                        'rounded-full px-4 py-2.5 text-sm font-medium transition',
                        'bg-slate-900 text-white shadow-sm' => ! $activeCategory,
                        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => $activeCategory,
                    ])>Все товары</a>
                    @foreach ($storeCategories as $category)
                        <a href="{{ route('catalog.category', ['category' => $category->slug, 'q' => $searchQuery]) }}" @class([
                            'rounded-full px-4 py-2.5 text-sm font-medium transition',
                            'bg-slate-900 text-white shadow-sm' => $activeCategory === $category->slug,
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => $activeCategory !== $category->slug,
                        ])>{{ $category->name }}</a>
                    @endforeach
                </div>
            </aside>

            <div>
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        Найдено: <span class="font-semibold text-slate-900">{{ $products->total() }}</span>
                    </p>

                    @if ($searchQuery)
                        <div class="rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700">
                            Поиск: {{ $searchQuery }}
                        </div>
                    @endif
                </div>

                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($products as $product)
                        @include('store.components.product-card', ['product' => $product])
                    @empty
                        <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white/60 px-6 py-12 text-center text-slate-500 sm:col-span-2 xl:col-span-3">
                            Товары не найдены.
                        </div>
                    @endforelse
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection

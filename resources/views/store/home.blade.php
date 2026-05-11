@extends('store.layout')

@section('content')
    <section class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
        <div class="rounded-[2rem] border border-cyan-100 bg-white/82 p-6 shadow-[0_24px_70px_rgba(8,47,73,0.10)] sm:p-8">
            @include('store.components.badge', ['slot' => 'tech store', 'variant' => 'cyan'])
            <h1 class="mt-5 text-4xl font-black tracking-tight text-slate-950 sm:text-6xl">
                Электроника без лишнего шума.
            </h1>
            <p class="mt-5 max-w-2xl text-base font-medium leading-7 text-slate-600">
                Компактная витрина товаров, быстрый поиск, технические бейджи и корзина для оформления заказа.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                @include('store.components.button', ['slot' => 'Открыть каталог', 'href' => route('catalog.index'), 'variant' => 'primary'])
                @include('store.components.button', ['slot' => 'Корзина', 'href' => route('cart.index'), 'variant' => 'secondary'])
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach ($storeCategories->take(4) as $category)
                @include('store.components.category-card', ['category' => $category])
            @endforeach
        </div>
    </section>

    <section class="mt-10">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-700">featured</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Популярные товары</h2>
            </div>
            <a href="{{ route('catalog.index') }}" class="text-sm font-black text-cyan-800 transition hover:text-slate-950">Все товары</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($featuredProducts as $product)
                @include('store.components.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
@endsection

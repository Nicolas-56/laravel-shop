@extends('layouts.app')

@php
    $imageUrl = \App\Models\SiteSetting::resolveMediaPath($product->mainImage?->path, 'images/back.webp');
    $isAvailable = (int) ($product->stock ?? 0) > 0;
@endphp

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-6xl">
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-orange-700 transition hover:text-orange-800">
                ← Вернуться в каталог
            </a>

            <div class="mt-6">
                <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                    {{ $product->category?->name ?? 'Каталог' }}
                </span>
                @if ($product->category)
                    <div class="mt-4">
                        <a href="{{ route('catalog.category', $product->category->slug) }}" class="inline-flex items-center gap-2 text-sm font-medium text-orange-700 transition hover:text-orange-800">
                            Смотреть все товары категории →
                        </a>
                    </div>
                @endif
                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">{{ $product->name }}</h1>

                @if ($product->short_description)
                    <p class="mt-5 max-w-4xl text-base leading-8 text-slate-600 sm:text-lg">{{ $product->short_description }}</p>
                @endif
            </div>
        </div>

        <div class="mt-12 grid gap-6 xl:grid-cols-[1.05fr_0.95fr] xl:items-start">
            <div class="metal-panel overflow-hidden rounded-[2rem]">
                <img src="{{ $imageUrl }}" alt="{{ $product->mainImage?->alt ?: $product->name }}" class="aspect-[4/3] w-full object-cover">
            </div>

            <div class="metal-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-wrap gap-2">
                    @if ($product->article)
                        <span class="rounded-full border border-slate-300 bg-white/90 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-slate-700 shadow-sm">
                            {{ $product->article }}
                        </span>
                    @endif
                    <span @class([
                        'rounded-full border px-3 py-1 text-xs font-medium shadow-sm',
                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $isAvailable,
                        'border-slate-300 bg-white/90 text-slate-500' => ! $isAvailable,
                    ])>
                        {{ $isAvailable ? 'В наличии' : 'Нет на складе' }}
                    </span>
                </div>

                <div class="mt-6">
                    @include('store.components.price', ['amount' => $product->price ?? 0, 'class' => 'text-3xl'])
                </div>

                <form method="POST" action="{{ route('cart.items.store') }}" class="mt-8 flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <label class="sr-only" for="product-quantity">Количество</label>
                    <input id="product-quantity" type="number" name="quantity" value="1" min="1" class="h-12 w-24 rounded-full border border-slate-300 bg-white/90 px-4 text-sm font-medium text-slate-900 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-full bg-slate-900 px-6 text-sm font-medium text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] transition hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100">
                        Добавить в корзину
                    </button>
                </form>
            </div>
        </div>

        @if ($product->description || $product->productProperties->isNotEmpty())
        <div class="mt-14 grid gap-6 lg:grid-cols-2">
            @if ($product->description)
                <article class="metal-panel rounded-[2rem] p-6 sm:p-8">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Описание</h2>
                    <div class="mt-5 text-base leading-8 text-slate-600">{!! nl2br(e($product->description)) !!}</div>
                </article>
            @endif

            @if ($product->productProperties->isNotEmpty())
                <article class="metal-panel rounded-[2rem] p-6 sm:p-8">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Характеристики</h2>
                    <dl class="mt-5 grid gap-4">
                        @foreach ($product->productProperties as $property)
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-4 text-sm">
                                <dt class="font-medium text-slate-500">{{ $property->property?->name ?? $property->custom_name ?? 'Свойство' }}</dt>
                                <dd class="text-right font-semibold text-slate-900">{{ $property->value ?: '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endif
        </div>
        @endif

        @if ($relatedProducts->isNotEmpty())
        <div class="mt-16">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                    Подборка
                </span>
                <div class="mt-5 flex items-end justify-between gap-4">
                <div>
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-900">Похожие товары</h2>
                    </div>
                </div>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($relatedProducts as $relatedProduct)
                    @include('store.components.product-card', ['product' => $relatedProduct])
                @endforeach
            </div>
        </div>
        @endif
    </section>
@endsection

@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-5xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Аккаунт
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Мои заказы</h1>
            <p class="mt-5 max-w-4xl text-base leading-8 text-slate-600 sm:text-lg">
                История оформленных заказов, текущие статусы и переход к деталям по каждой покупке.
            </p>
        </div>

        <div class="mt-12 grid gap-6">
            @forelse ($orders as $order)
                <article class="metal-panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold text-slate-900">Заказ #{{ $order->id }}</h2>
                                @include('store.components.order-status-badge', ['status' => $order->status, 'theme' => 'works'])
                            </div>
                            <p class="mt-2 text-sm leading-7 text-slate-500">{{ $order->created_at?->format('d.m.Y H:i') }} · {{ $order->items_count }} поз.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            @include('store.components.price', ['amount' => $order->total])
                            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white/90 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-orange-300 hover:text-orange-700">
                                Детали
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <article class="metal-panel rounded-[2rem] px-6 py-12 text-center">
                    <h2 class="text-2xl font-semibold text-slate-900">Заказов пока нет</h2>
                    <p class="mt-3 text-base leading-7 text-slate-600">После оформления заказа он появится здесь.</p>
                </article>
            @endforelse
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-5xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Корзина
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Товары к оформлению</h1>
            <p class="mt-5 max-w-4xl text-base leading-8 text-slate-600 sm:text-lg">
                Проверьте состав заказа, количество товаров и итоговую сумму перед переходом к оформлению.
            </p>
        </div>

        <div class="mt-12 grid gap-6 xl:grid-cols-[1.08fr_0.92fr] xl:items-start">
            <div class="grid gap-6" data-cart-lines>
                @foreach ($cartLines as $line)
                    @include('store.components.cart-item', ['line' => $line, 'theme' => 'works'])
                @endforeach
            </div>

            <article @class([
                'metal-panel rounded-[2rem] px-6 py-12 text-center',
                'hidden' => $cartLines->isNotEmpty(),
            ]) data-cart-empty>
                <h2 class="text-2xl font-semibold text-slate-900">Корзина пуста</h2>
                <p class="mt-3 text-base leading-7 text-slate-600">Добавьте товары из каталога.</p>
                <div class="mt-5">
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center rounded-full bg-slate-900 px-6 py-3 text-sm font-medium text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] transition hover:bg-orange-700">
                        Перейти в каталог
                    </a>
                </div>
            </article>

            <aside class="metal-panel h-fit rounded-[2rem] p-6 sm:p-8" data-cart-summary>
                <span class="inline-flex rounded-full border border-slate-300 bg-white/90 px-4 py-2 text-xs uppercase tracking-[0.32em] text-slate-700">
                    Сводка
                </span>
                <div class="mt-5 flex items-center justify-between">
                    <span class="text-base font-medium text-slate-500">Итого</span>
                    <div data-cart-summary-total>
                        @include('store.components.price', ['amount' => $cartTotal, 'class' => 'text-2xl'])
                    </div>
                </div>
                <p class="mt-4 text-sm leading-7 text-slate-500" data-cart-summary-count>
                    Позиций в корзине: {{ $cartLines->sum('quantity') }}.
                </p>
                <div class="mt-6">
                    <a
                        href="{{ route('checkout.show') }}"
                        data-cart-checkout
                        @class([
                            'inline-flex w-full items-center justify-center rounded-full px-6 py-3 text-sm font-medium transition',
                            'bg-slate-900 text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] hover:bg-orange-700' => $cartLines->isNotEmpty(),
                            'pointer-events-none border border-slate-300 bg-white/80 text-slate-400' => $cartLines->isEmpty(),
                        ])>
                        Оформить заказ
                    </a>
                </div>
            </aside>
        </div>
    </section>
@endsection

@extends('store.layout')

@section('content')
    <section class="grid gap-8">
        @include('store.components.page-heading', [
            'eyebrow' => 'order',
            'title' => 'Заказ #'.$order->id,
            'description' => 'Состав заказа, контактные данные и итоговая сумма.',
        ])

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        <div class="rounded-[2rem] border border-cyan-100 bg-white/82 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
            <div class="flex flex-wrap items-center gap-3">
                @include('store.components.order-status-badge', ['status' => $order->status])
            </div>

            <div class="mt-6 grid gap-3">
                @foreach ($order->items as $item)
                    <div class="flex flex-col gap-2 rounded-2xl bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-black text-slate-950">{{ $item->product_name }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $item->quantity }} x {{ number_format((float) $item->product_price, 2, '.', ' ') }} руб.</p>
                        </div>
                        @include('store.components.price', ['amount' => $item->line_total])
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="h-fit rounded-[2rem] border border-cyan-100 bg-white/82 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
            <p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-700">details</p>
            <dl class="mt-4 grid gap-3 text-sm">
                <div>
                    <dt class="font-bold text-slate-500">Имя</dt>
                    <dd class="mt-1 font-black text-slate-950">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="font-bold text-slate-500">Телефон</dt>
                    <dd class="mt-1 font-black text-slate-950">{{ $order->customer_phone }}</dd>
                </div>
                @if ($order->customer_email)
                    <div>
                        <dt class="font-bold text-slate-500">Email</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $order->customer_email }}</dd>
                    </div>
                @endif
                @if ($order->delivery_name)
                    <div>
                        <dt class="font-bold text-slate-500">Доставка</dt>
                        <dd class="mt-1 font-black text-slate-950">
                            {{ $order->delivery_name }} · {{ number_format((float) $order->delivery_price, 2, '.', ' ') }} руб.
                        </dd>
                    </div>
                @endif
                <div class="border-t border-slate-100 pt-4">
                    <dt class="font-bold text-slate-500">Итого</dt>
                    <dd class="mt-1">@include('store.components.price', ['amount' => $order->total])</dd>
                </div>
            </dl>
        </aside>
        </div>
    </section>
@endsection

@extends('store.layout')

@section('content')
    <section class="grid gap-8">
        @include('store.components.page-heading', [
            'eyebrow' => 'payment',
            'title' => 'Оплата заказа #'.$order->id,
            'description' => 'Тестовая страница оплаты с ручным подтверждением результата.',
        ])

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
            <div class="rounded-[2rem] border border-cyan-100 bg-white/82 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    @include('store.components.order-status-badge', ['status' => $order->status])
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-slate-600">
                        Платёж: {{ $paymentStatus }}
                    </span>
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

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($paymentStatus === \App\Models\Payment::STATUS_PENDING)
                        <a href="{{ $paymentActions['success'] }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-600">
                            Успех
                        </a>
                        <a href="{{ $paymentActions['fail'] }}" class="inline-flex items-center justify-center rounded-2xl bg-rose-500 px-5 py-3 text-sm font-black text-white transition hover:bg-rose-600">
                            Ошибка оплаты
                        </a>
                        <a href="{{ $paymentActions['cancel'] }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:text-slate-950">
                            Отмена
                        </a>
                    @else
                        <p class="text-sm font-bold text-slate-500">Этот платёж уже обработан.</p>
                    @endif
                </div>
            </div>

            <aside class="h-fit rounded-[2rem] border border-cyan-100 bg-white/82 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                <p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-700">details</p>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div>
                        <dt class="font-bold text-slate-500">Заказ</dt>
                        <dd class="mt-1 font-black text-slate-950">#{{ $order->id }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-500">Провайдер</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $payment->provider }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-500">Платёж</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $payment->provider_payment_id }}</dd>
                    </div>
                    @if ($payment->expires_at)
                        <div>
                            <dt class="font-bold text-slate-500">Действует до</dt>
                            <dd class="mt-1 font-black text-slate-950">{{ $payment->expires_at->format('d.m.Y H:i') }}</dd>
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

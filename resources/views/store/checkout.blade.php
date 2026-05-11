@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-5xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Оформление
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Оформление заказа</h1>
            <p class="mt-5 max-w-4xl text-base leading-8 text-slate-600 sm:text-lg">
                Заполните контактные данные, выберите доставку и проверьте состав заказа перед подтверждением.
            </p>
        </div>

        <div class="mt-12 grid gap-6 xl:grid-cols-[1fr_24rem] xl:items-start">
        <form method="POST" action="{{ route('checkout.store') }}" class="metal-panel rounded-[2rem] p-6 sm:p-8">
            @csrf

            @if ($errors->has('cart'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">{{ $errors->first('cart') }}</div>
            @endif

            <div class="@class(['grid gap-4 sm:grid-cols-2', 'mt-6' => $errors->has('cart')])">
                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Имя
                    <input name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required class="h-12 rounded-2xl border border-slate-200 bg-white/85 px-4 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                    @error('customer_name') <span class="text-xs text-amber-700">{{ $message }}</span> @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Телефон
                    <input name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" data-phone-mask class="h-12 rounded-2xl border border-slate-200 bg-white/85 px-4 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                    @error('customer_phone') <span class="text-xs text-amber-700">{{ $message }}</span> @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Email
                    <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" class="h-12 rounded-2xl border border-slate-200 bg-white/85 px-4 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                    @error('customer_email') <span class="text-xs text-amber-700">{{ $message }}</span> @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Доставка
                    <select name="delivery_id" required data-checkout-delivery class="h-12 rounded-2xl border border-slate-200 bg-white/85 px-4 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                        <option value="">Выберите способ</option>
                        @foreach ($deliveryMethods as $delivery)
                            <option value="{{ $delivery->id }}" data-price="{{ (float) $delivery->price }}" @selected((int) old('delivery_id') === $delivery->id)>
                                {{ $delivery->name }} · {{ number_format((float) $delivery->price, 2, '.', ' ') }} руб.
                            </option>
                        @endforeach
                    </select>
                    @error('delivery_id') <span class="text-xs text-amber-700">{{ $message }}</span> @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Оплата
                    <select name="payment_provider" required class="h-12 rounded-2xl border border-slate-200 bg-white/85 px-4 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">
                        @foreach ($paymentProviders as $provider)
                            <option value="{{ $provider }}" @selected(old('payment_provider', 'fake') === $provider)>{{ $provider }}</option>
                        @endforeach
                    </select>
                    @error('payment_provider') <span class="text-xs text-amber-700">{{ $message }}</span> @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                    Комментарий
                    <textarea name="customer_comment" rows="5" class="rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">{{ old('customer_comment') }}</textarea>
                    @error('customer_comment') <span class="text-xs text-amber-700">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-6">
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-slate-900 px-6 py-3 text-sm font-medium text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] transition hover:bg-orange-700 sm:w-auto">
                    Создать заказ
                </button>
            </div>
        </form>

        <aside class="metal-panel h-fit rounded-[2rem] p-6 sm:p-8" data-checkout-summary data-cart-total="{{ (float) $cartTotal }}">
            <span class="inline-flex rounded-full border border-slate-300 bg-white/90 px-4 py-2 text-xs uppercase tracking-[0.32em] text-slate-700">
                Заказ
            </span>
            <div class="mt-4 grid gap-3">
                @forelse ($cartLines as $line)
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="font-medium text-slate-600">{{ $line->product->name }} x {{ $line->quantity }}</span>
                        <span class="font-semibold text-slate-950">{{ number_format((float) $line->line_total, 2, '.', ' ') }} руб.</span>
                    </div>
                @empty
                    <p class="text-sm font-semibold text-slate-500">Корзина пуста.</p>
                @endforelse
            </div>
            <div class="mt-5 border-t border-slate-100 pt-5">
                <div class="mb-4 flex items-center justify-between gap-3 text-sm">
                    <span class="font-bold text-slate-500">Доставка</span>
                    <span class="font-black text-slate-950" data-checkout-delivery-price>Выберите в форме</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-500">Товары</span>
                    @include('store.components.price', ['amount' => $cartTotal])
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-500">Итого</span>
                    <div data-checkout-grand-total>
                        @include('store.components.price', ['amount' => $cartTotal])
                    </div>
                </div>
            </div>
        </aside>
        </div>
    </section>
@endsection

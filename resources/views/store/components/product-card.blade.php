@php
    $imageUrl = \App\Models\SiteSetting::resolveMediaPath($product->mainImage?->path, 'images/back.webp');
    $isAvailable = (int) ($product->stock ?? 0) > 0;
    $cartQuantity = (int) ($cartItems[$product->id] ?? 0);
@endphp

<article data-cart-card class="metal-panel group overflow-hidden rounded-[1.75rem]">
    <a href="{{ route('products.show', $product->slug) }}" class="block">
        <div class="relative overflow-hidden bg-slate-100">
            <img src="{{ $imageUrl }}" alt="{{ $product->mainImage?->alt ?: $product->name }}" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
            <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                <span class="rounded-full border border-white/70 bg-white/90 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-slate-700 shadow-sm">
                    {{ $product->article ?: 'Каталог' }}
                </span>
                <span @class([
                    'rounded-full border px-3 py-1 text-xs font-medium shadow-sm',
                    'border-emerald-200 bg-emerald-50/95 text-emerald-700' => $isAvailable,
                    'border-slate-200 bg-white/90 text-slate-500' => ! $isAvailable,
                ])>
                    {{ $isAvailable ? 'В наличии' : 'Нет на складе' }}
                </span>
            </div>
        </div>
    </a>

    <div class="space-y-4 p-5">
        <div class="space-y-2">
            <p class="text-xs font-medium uppercase tracking-[0.28em] text-orange-700">{{ $product->category?->name ?? 'Каталог' }}</p>
            <a href="{{ route('products.show', $product->slug) }}" class="line-clamp-2 block text-lg font-semibold leading-snug text-slate-900 transition hover:text-orange-700">
                {{ $product->name }}
            </a>
        </div>

        <div class="grid gap-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                @include('store.components.price', ['amount' => $product->price ?? 0])

                <div
                    class="flex items-center gap-2"
                    data-cart-quantity-control
                    data-product-id="{{ $product->id }}"
                    data-store-url="{{ route('cart.items.store') }}"
                    data-update-url="{{ route('cart.items.update', $product) }}"
                    data-stock="{{ (int) ($product->stock ?? 0) }}"
                    data-available="{{ $isAvailable ? '1' : '0' }}"
                    data-token="{{ csrf_token() }}"
                >
                    <button
                        type="button"
                        data-cart-quantity-decrease
                        @disabled(! $isAvailable || $cartQuantity <= 1)
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-semibold text-slate-600 transition hover:border-orange-300 hover:text-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100 disabled:pointer-events-none disabled:text-slate-300"
                    >
                        -
                    </button>
                    <input
                        type="number"
                        min="0"
                        max="{{ (int) ($product->stock ?? 0) }}"
                        value="{{ $cartQuantity }}"
                        readonly
                        aria-label="Количество в корзине"
                        data-cart-quantity-input
                        class="h-10 w-14 rounded-2xl border border-slate-200 bg-white/90 px-2 text-center text-sm font-semibold text-slate-900 outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100"
                    >
                    <button
                        type="button"
                        data-cart-quantity-increase
                        @disabled(! $isAvailable || ($cartQuantity > 0 && $cartQuantity >= (int) ($product->stock ?? 0)))
                        @class([
                            'inline-flex h-10 w-10 items-center justify-center rounded-full text-lg font-semibold transition focus:outline-none focus:ring-4',
                            'bg-slate-900 text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] hover:bg-orange-700 focus:ring-orange-100' => $isAvailable,
                            'border border-slate-200 bg-white text-slate-400 focus:ring-slate-100' => ! $isAvailable,
                        ])
                    >
                        +
                    </button>
                </div>
            </div>

            <p data-cart-feedback class="hidden rounded-2xl border px-3 py-2 text-xs font-medium shadow-[0_14px_34px_rgba(15,23,42,0.12)]"></p>
        </div>
    </div>
</article>

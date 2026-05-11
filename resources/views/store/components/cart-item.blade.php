@php
    $product = $line->product;
    $imageUrl = \App\Models\SiteSetting::resolveMediaPath($product->mainImage?->path, 'images/back.webp');
    $theme = $theme ?? 'store';
@endphp

<article @class([
    'grid gap-4 sm:grid-cols-[7rem_1fr]',
    'rounded-3xl border border-cyan-100 bg-white p-4 shadow-[0_16px_40px_rgba(15,23,42,0.06)]' => $theme === 'store',
    'metal-panel rounded-[1.75rem] p-4 sm:p-5' => $theme === 'works',
]) data-cart-card>
    <a href="{{ route('products.show', $product->slug) }}" class="block overflow-hidden rounded-2xl bg-slate-100">
        <img src="{{ $imageUrl }}" alt="{{ $product->mainImage?->alt ?: $product->name }}" class="aspect-square h-full w-full object-cover" loading="lazy">
    </a>

    <div class="grid gap-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p @class([
                    'text-xs uppercase tracking-[0.18em]' ,
                    'font-black text-cyan-700' => $theme === 'store',
                    'font-medium tracking-[0.28em] text-orange-700' => $theme === 'works',
                ])>{{ $product->article ?: 'TECH ITEM' }}</p>
                <a href="{{ route('products.show', $product->slug) }}" @class([
                    'mt-1 block text-lg transition',
                    'font-black text-slate-950 hover:text-cyan-800' => $theme === 'store',
                    'font-semibold text-slate-900 hover:text-orange-700' => $theme === 'works',
                ])>{{ $product->name }}</a>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $product->category?->name ?? 'Каталог' }}</p>
            </div>
            <div data-cart-line-total data-unit-price="{{ (float) ($product->price ?? 0) }}">
                @include('store.components.price', ['amount' => $line->line_total])
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div
                class="flex items-center gap-2"
                data-cart-quantity-control
                data-product-id="{{ $product->id }}"
                data-update-url="{{ route('cart.items.update', $product) }}"
                data-stock="{{ (int) ($product->stock ?? 0) }}"
                data-available="{{ $product->is_active ? '1' : '0' }}"
                data-token="{{ csrf_token() }}"
            >
                <button
                    type="button"
                    data-cart-quantity-decrease
                    @disabled($line->quantity <= 1)
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-semibold text-slate-600 transition hover:border-orange-300 hover:text-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100 disabled:pointer-events-none disabled:text-slate-300"
                >
                    -
                </button>

                <div class="flex items-center gap-2">
                    <label class="sr-only" for="quantity-{{ $product->id }}">Количество</label>
                    <input
                        id="quantity-{{ $product->id }}"
                        type="number"
                        value="{{ $line->quantity }}"
                        min="1"
                        max="{{ (int) ($product->stock ?? $line->quantity) }}"
                        readonly
                        aria-label="Количество в корзине"
                        data-cart-quantity-input
                        class="h-10 w-16 rounded-2xl border border-slate-200 bg-white/90 px-2 text-center text-sm font-semibold text-slate-900 outline-none"
                    >
                    <span class="text-sm font-medium text-slate-500">
                        в корзине
                    </span>
                </div>

                <button
                    type="button"
                    data-cart-quantity-increase
                    @disabled((int) ($product->stock ?? 0) > 0 && $line->quantity >= (int) $product->stock)
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] transition hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100 disabled:pointer-events-none disabled:border disabled:border-slate-200 disabled:bg-white disabled:text-slate-400 disabled:shadow-none"
                >
                    +
                </button>
            </div>

            <form method="POST" action="{{ route('cart.items.destroy', $product) }}" data-cart-remove-form>
                @csrf
                @method('DELETE')
                @if ($theme === 'works')
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-100"
                    >
                        Удалить
                    </button>
                @else
                    @include('store.components.button', ['slot' => 'Удалить', 'type' => 'submit', 'variant' => 'ghost', 'size' => 'sm'])
                @endif
            </form>
        </div>

        <p data-cart-feedback class="hidden rounded-2xl border px-3 py-2 text-xs font-medium shadow-[0_14px_34px_rgba(15,23,42,0.12)]"></p>
    </div>
</article>

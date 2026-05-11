<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Payments\PaymentProviderManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class StorePageService
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly PaymentProviderManager $paymentProviders,
    ) {}

    public function home(): array
    {
        return $this->pageData([
            'title' => 'Магазин электроники',
            'description' => 'Каталог электроники, комплектующих и техно-аксессуаров.',
            'featuredProducts' => $this->productsQuery()
                ->limit(8)
                ->get(),
        ]);
    }

    public function catalog(array $filters = [], ?Category $category = null): array
    {
        abort_if($category && ! $category->is_active, 404);

        $products = $this->productsQuery()
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('article', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query, Category $category) => $query->where('category_id', $category->getKey()))
            ->paginate(12)
            ->withQueryString();

        $title = $category ? $category->name : 'Каталог';

        return $this->pageData([
            'title' => $category?->meta_title ?: $title,
            'description' => $category?->meta_description ?: 'Компактный каталог электроники и аксессуаров.',
            'products' => $products,
            'cartItems' => $this->sessionCartItems()->all(),
            'activeCategory' => $category?->slug,
            'currentCategory' => $category,
            'searchQuery' => $filters['q'] ?? null,
            'breadcrumbs' => $this->catalogBreadcrumbs($category),
            'canonical' => $category ? route('catalog.category', $category->slug) : route('catalog.index'),
        ]);
    }

    public function product(Product $product): array
    {
        abort_unless($product->is_active, 404);

        $product->loadMissing(['category', 'mainImage', 'productProperties.property']);

        return $this->pageData([
            'title' => $product->meta_title ?: $product->name,
            'description' => $product->meta_description ?: ($product->short_description ?: 'Карточка товара магазина электроники.'),
            'product' => $product,
            'breadcrumbs' => $this->productBreadcrumbs($product),
            'canonical' => route('products.show', $product->slug),
            'relatedProducts' => $this->productsQuery()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->getKey())
                ->limit(4)
                ->get(),
        ]);
    }

    public function cart(): array
    {
        return $this->pageData([
            'title' => 'Корзина',
            'description' => 'Товары, добавленные в корзину.',
            ...$this->cartData(),
        ]);
    }

    public function checkout(): array
    {
        return $this->pageData([
            'title' => 'Оформление заказа',
            'description' => 'Контакты покупателя и способ оплаты.',
            'deliveryMethods' => Delivery::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'price']),
            'paymentProviders' => $this->paymentProviders->names(),
            ...$this->cartData(),
        ]);
    }

    public function orders(Collection $orders): array
    {
        return $this->pageData([
            'title' => 'Мои заказы',
            'description' => 'История заказов пользователя.',
            'orders' => $orders,
        ]);
    }

    public function order(Order $order): array
    {
        return $this->pageData([
            'title' => 'Заказ #'.$order->id,
            'description' => 'Детали заказа #'.$order->id,
            'order' => $order,
        ]);
    }

    public function payment(Order $order): array
    {
        $payment = $order->payments()
            ->where('provider', 'fake')
            ->latest()
            ->firstOrFail();

        $expiresAt = $payment->expires_at ?? now()->addMinutes(30);

        return $this->pageData([
            'title' => 'Оплата заказа #'.$order->id,
            'description' => 'Подтверждение оплаты заказа #'.$order->id,
            'order' => $order->loadMissing('items', 'orderStatus'),
            'payment' => $payment,
            'paymentStatus' => $payment->status,
            'paymentActions' => [
                'success' => URL::temporarySignedRoute('payments.fake.success', $expiresAt, ['order' => $order]),
                'fail' => URL::temporarySignedRoute('payments.fake.fail', $expiresAt, ['order' => $order]),
                'cancel' => URL::temporarySignedRoute('payments.fake.cancel', $expiresAt, ['order' => $order]),
            ],
        ]);
    }

    public function productImage(Product $product): string
    {
        return SiteSetting::resolveMediaPath($product->mainImage?->path, 'images/back.webp');
    }

    private function pageData(array $data = []): array
    {
        return [
            'siteSettings' => $this->pageService->siteSettings(),
            'storeCategories' => $this->storeCategories(),
            'cartCount' => collect(session('cart.items', []))->sum(fn (mixed $quantity): int => (int) $quantity),
            ...$data,
        ];
    }

    private function storeCategories(): Collection
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }

        return Category::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug']);
    }

    private function productsQuery()
    {
        return Product::query()
            ->active()
            ->with(['category', 'mainImage'])
            ->orderBy('sort')
            ->orderBy('name');
    }

    private function catalogBreadcrumbs(?Category $category = null): array
    {
        $breadcrumbs = [
            ['label' => 'Каталог', 'url' => route('catalog.index')],
        ];

        if (! $category) {
            return $breadcrumbs;
        }

        return [
            ...$breadcrumbs,
            ...$this->categoryBreadcrumbs($category),
        ];
    }

    private function productBreadcrumbs(Product $product): array
    {
        $breadcrumbs = $this->catalogBreadcrumbs();

        if ($product->category) {
            $breadcrumbs = [
                ...$breadcrumbs,
                ...$this->categoryBreadcrumbs($product->category),
            ];
        }

        $breadcrumbs[] = ['label' => $product->name, 'url' => null];

        return $breadcrumbs;
    }

    private function categoryBreadcrumbs(Category $category): array
    {
        $trail = [];
        $current = $category;

        while ($current) {
            array_unshift($trail, [
                'label' => $current->name,
                'url' => route('catalog.category', $current->slug),
            ]);

            $current = $current->parent;
        }

        return $trail;
    }

    private function cartData(): array
    {
        $cartItems = $this->sessionCartItems();

        $products = Product::query()
            ->whereKey($cartItems->keys()->all())
            ->with(['category', 'mainImage'])
            ->get()
            ->keyBy('id');

        $lines = $cartItems
            ->map(function (int $quantity, int $productId) use ($products): ?object {
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                $price = (float) ($product->price ?? 0);

                return (object) [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => $price * $quantity,
                ];
            })
            ->filter()
            ->values();

        return [
            'cartLines' => $lines,
            'cartTotal' => $lines->sum('line_total'),
        ];
    }

    private function sessionCartItems(): Collection
    {
        return collect(session('cart.items', []))
            ->mapWithKeys(fn (mixed $quantity, mixed $productId): array => [(int) $productId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity > 0);
    }
}

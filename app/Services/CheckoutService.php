<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly OrderStockService $stock,
    ) {}

    public function checkout(array $customerData, ?int $userId = null, string $paymentProvider = 'fake'): Order
    {
        $cartItems = collect(session('cart.items', []))
            ->mapWithKeys(fn (mixed $quantity, mixed $productId): array => [(int) $productId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity > 0);

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Корзина пуста.',
            ]);
        }

        $products = Product::query()
            ->whereKey($cartItems->keys()->all())
            ->get()
            ->keyBy('id');

        $this->validateProducts($cartItems, $products);

        $delivery = Delivery::query()
            ->active()
            ->find((int) ($customerData['delivery_id'] ?? 0));

        if (! $delivery) {
            throw ValidationException::withMessages([
                'delivery_id' => 'Выбранный способ доставки недоступен.',
            ]);
        }

        return DB::transaction(function () use ($customerData, $userId, $cartItems, $products, $paymentProvider, $delivery): Order {
            $products = Product::query()
                ->whereKey($cartItems->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->validateProducts($cartItems, $products);

            $itemsTotal = $cartItems->reduce(
                fn (float $total, int $quantity, int $productId): float => $total + $this->lineTotal($products->get($productId), $quantity),
                0.0,
            );
            $deliveryPrice = (float) $delivery->price;

            $order = Order::query()->create([
                'user_id' => $userId,
                'delivery_id' => $delivery->getKey(),
                'order_status_id' => Order::statusId(Order::STATUS_PENDING_PAYMENT),
                'customer_name' => $customerData['customer_name'],
                'customer_phone' => $customerData['customer_phone'],
                'customer_email' => $customerData['customer_email'] ?? null,
                'customer_comment' => $customerData['customer_comment'] ?? null,
                'delivery_name' => $delivery->name,
                'delivery_price' => $deliveryPrice,
                'total' => $itemsTotal + $deliveryPrice,
            ]);

            $order->items()->createMany(
                $cartItems
                    ->map(function (int $quantity, int $productId) use ($products): array {
                        /** @var Product $product */
                        $product = $products->get($productId);
                        $price = (float) ($product->price ?? 0);

                        return [
                            'product_id' => $product->getKey(),
                            'product_name' => $product->name,
                            'product_price' => $price,
                            'quantity' => $quantity,
                            'line_total' => $price * $quantity,
                        ];
                    })
                    ->values()
                    ->all()
            );

            $order->load('items');
            $this->stock->reserve($order, $products);

            $this->payments->create($order, $paymentProvider);

            session()->forget('cart.items');

            return $order->load(['items', 'payments']);
        });
    }

    /**
     * @param  Collection<int, int>  $cartItems
     * @param  Collection<int, Product>  $products
     */
    private function validateProducts(Collection $cartItems, Collection $products): void
    {
        foreach ($cartItems as $productId => $quantity) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'cart' => 'В корзине есть недоступный товар.',
                ]);
            }

            if (Schema::hasColumn('products', 'stock') && (int) $product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => 'В корзине есть товар в недостаточном количестве.',
                ]);
            }
        }
    }

    private function lineTotal(Product $product, int $quantity): float
    {
        return (float) ($product->price ?? 0) * $quantity;
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function add(int $productId, int $quantity): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->validateProduct($product, $quantity, 'product_id');

        $items = $this->sessionItems();
        $currentQuantity = (int) ($items[$product->getKey()] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        $this->validateStock($product, $newQuantity, 'quantity');

        $items[$product->getKey()] = $newQuantity;

        $this->storeItems($items);
    }

    public function update(int $productId, int $quantity): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->validateProduct($product, $quantity, 'product_id');
        $this->validateStock($product, $quantity, 'quantity');

        $items = $this->sessionItems();
        $items[$product->getKey()] = $quantity;

        $this->storeItems($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->sessionItems();

        unset($items[$productId]);

        $this->storeItems($items);
    }

    private function sessionItems(): array
    {
        return session('cart.items', []);
    }

    private function storeItems(array $items): void
    {
        session()->put('cart.items', $items);
    }

    private function validateProduct(Product $product, int $quantity, string $errorField): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                $errorField => 'Товар недоступен.',
            ]);
        }

        $this->validateStock($product, $quantity, 'quantity');
    }

    private function validateStock(Product $product, int $quantity, string $errorField): void
    {
        if (! Schema::hasColumn('products', 'stock')) {
            return;
        }

        if ((int) $product->stock < $quantity) {
            throw ValidationException::withMessages([
                $errorField => 'Недостаточно товара в наличии.',
            ]);
        }
    }
}

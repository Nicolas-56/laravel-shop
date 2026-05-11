<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request, CartService $cart): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $cart->add(
            (int) $validated['product_id'],
            (int) ($validated['quantity'] ?? 1),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Товар добавлен в корзину.',
                'cart_count' => $this->cartCount(),
                'item_quantity' => $this->itemQuantity((int) $validated['product_id']),
            ]);
        }

        return back()->with('success', 'Товар добавлен в корзину.');
    }

    public function update(Request $request, int $product, CartService $cart): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart->update($product, (int) $validated['quantity']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Корзина обновлена.',
                'cart_count' => $this->cartCount(),
                'item_quantity' => $this->itemQuantity($product),
            ]);
        }

        return back()->with('success', 'Корзина обновлена.');
    }

    public function destroy(Request $request, int $product, CartService $cart): JsonResponse|RedirectResponse
    {
        $cart->remove($product);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Товар удалён из корзины.',
                'cart_count' => $this->cartCount(),
                'item_quantity' => 0,
            ]);
        }

        return back()->with('success', 'Товар удалён из корзины.');
    }

    private function cartCount(): int
    {
        return collect(session('cart.items', []))->sum(fn (mixed $quantity): int => (int) $quantity);
    }

    private function itemQuantity(int $productId): int
    {
        return (int) data_get(session('cart.items', []), $productId, 0);
    }
}

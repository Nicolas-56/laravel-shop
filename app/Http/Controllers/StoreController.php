<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\StorePageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(
        private readonly StorePageService $storePages,
    ) {}

    public function home(): View
    {
        return view('store.home', $this->storePages->home());
    }

    public function catalog(Request $request, ?Category $category = null): View|RedirectResponse
    {
        if (! $category && $request->filled('category')) {
            $legacyCategorySlug = $request->string('category')->toString();
            $legacyCategory = Category::query()
                ->active()
                ->orderBy('sort')
                ->orderBy('name')
                ->get()
                ->first(fn (Category $category): bool => in_array($legacyCategorySlug, [$category->slug, $category->full_slug], true));

            if ($legacyCategory) {
                return redirect()->route('catalog.category', [
                    'category' => $legacyCategory->slug,
                    ...$request->only('q'),
                ], 301);
            }
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('store.catalog', $this->storePages->catalog($filters, $category));
    }

    public function product(Product $product): View
    {
        return view('store.product', $this->storePages->product($product));
    }

    public function legacyProduct(Product $product): RedirectResponse
    {
        return redirect()->route('products.show', $product->slug, 301);
    }

    public function cart(): View
    {
        return view('store.cart', $this->storePages->cart());
    }

    public function checkout(): View
    {
        return view('store.checkout', $this->storePages->checkout());
    }
}

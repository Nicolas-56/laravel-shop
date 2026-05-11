<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StorePageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly StorePageService $storePages,
    ) {}

    public function index(Request $request): View
    {
        $orders = $request->user()
                ->orders()
                ->latest()
                ->withCount('items')
                ->get();

        return view('store.orders.index', $this->storePages->orders($orders));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        return view('store.orders.show', $this->storePages->order($order->load('items')));
    }
}

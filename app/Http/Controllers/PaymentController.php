<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\PaymentProviderManager;
use App\Services\PaymentService;
use App\Services\StorePageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, Order $order, PaymentService $payments, PaymentProviderManager $providers): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()?->id, 404);

        $validated = $request->validate([
            'payment_provider' => ['nullable', 'string', Rule::in($providers->names())],
        ]);

        $payment = $payments->create($order, $validated['payment_provider'] ?? null);

        return redirect($payment->payment_url);
    }

    public function fakePayment(Request $request, Order $order, StorePageService $storePages): View
    {
        abort_unless($request->hasValidSignature(), 404);

        return view('store.payment', $storePages->payment($order));
    }

    public function fakeSuccess(Request $request, Order $order, PaymentService $payments): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 404);

        $payments->markFakePaymentSuccessful($order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Оплата подтверждена.');
    }

    public function fakeFail(Request $request, Order $order, PaymentService $payments): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 404);

        $payments->markFakePaymentFailed($order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Оплата отклонена.');
    }

    public function fakeCancel(Request $request, Order $order, PaymentService $payments): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 404);

        $payments->markFakePaymentCancelled($order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Оплата отменена.');
    }

    public function fakeWebhook(Request $request, PaymentService $payments): JsonResponse
    {
        $payment = $payments->handleWebhook(
            $request->all(),
            $request->header('X-Fake-Signature'),
            'fake',
        );

        return response()->json([
            'payment_id' => $payment->getKey(),
            'payment_status' => $payment->status,
            'order_status' => $payment->order->status,
        ]);
    }

    public function yookassaWebhook(Request $request, PaymentService $payments): JsonResponse
    {
        $payment = $payments->handleWebhook(
            $request->all(),
            $request->header('X-Yookassa-Webhook-Secret'),
            'yookassa',
        );

        return response()->json([
            'payment_id' => $payment->getKey(),
            'payment_status' => $payment->status,
            'order_status' => $payment->order->status,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Payments\PaymentProviderManager;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function store(Request $request, CheckoutService $checkout, PaymentProviderManager $providers): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_comment' => ['nullable', 'string', 'max:2000'],
            'delivery_id' => ['required', 'integer', Rule::exists('deliveries', 'id')->where('is_active', true)],
            'payment_provider' => ['required', 'string', Rule::in($providers->names())],
        ]);

        $order = $checkout->checkout(
            $validated,
            $request->user()?->id,
            $validated['payment_provider'],
        );
        $payment = $order->payments->first();

        if ($payment?->payment_url) {
            return redirect($payment->payment_url)
                ->with('success', 'Заказ создан.');
        }

        return redirect()
            ->route('home')
            ->with('success', 'Заказ создан.');
    }
}

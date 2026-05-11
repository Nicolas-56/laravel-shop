@php
    $theme = $theme ?? 'store';
    $labels = [
        \App\Models\Order::STATUS_PENDING => 'Новый',
        \App\Models\Order::STATUS_AWAITING_PAYMENT => 'Ожидает оплаты',
        \App\Models\Order::STATUS_PAID => 'Оплачен',
        \App\Models\Order::STATUS_FAILED => 'Ошибка оплаты',
        \App\Models\Order::STATUS_CANCELLED => 'Отменён',
    ];
    $variants = [
        \App\Models\Order::STATUS_PENDING => 'slate',
        \App\Models\Order::STATUS_AWAITING_PAYMENT => 'warning',
        \App\Models\Order::STATUS_PAID => 'success',
        \App\Models\Order::STATUS_FAILED => 'warning',
        \App\Models\Order::STATUS_CANCELLED => 'slate',
    ];
@endphp

@if ($theme === 'works')
    <span @class([
        'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium',
        'border-slate-300 bg-white/90 text-slate-700' => ($variants[$status] ?? 'slate') === 'slate',
        'border-amber-200 bg-amber-50 text-amber-700' => ($variants[$status] ?? 'slate') === 'warning',
        'border-emerald-200 bg-emerald-50 text-emerald-700' => ($variants[$status] ?? 'slate') === 'success',
    ])>
        {{ $labels[$status] ?? $status }}
    </span>
@else
    @include('store.components.badge', [
        'slot' => $labels[$status] ?? $status,
        'variant' => $variants[$status] ?? 'slate',
    ])
@endif

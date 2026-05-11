@php
    $amount = $amount ?? 0;
    $currency = $currency ?? 'руб.';
    $oldAmount = $oldAmount ?? null;
    $class = $class ?? '';
    $formattedAmount = number_format((float) $amount, 2, '.', ' ');
    $formattedOldAmount = $oldAmount !== null ? number_format((float) $oldAmount, 2, '.', ' ') : null;
@endphp

<span class="inline-flex flex-wrap items-baseline gap-2 {{ $class }}">
    <span class="text-xl font-black tracking-tight text-slate-950">
        {{ $formattedAmount }} <span class="text-sm font-bold text-slate-500">{{ $currency }}</span>
    </span>

    @if ($formattedOldAmount)
        <span class="text-sm font-semibold text-slate-400 line-through">
            {{ $formattedOldAmount }} {{ $currency }}
        </span>
    @endif
</span>

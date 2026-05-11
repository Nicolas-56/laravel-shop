@php
    $variant = $variant ?? 'cyan';
    $class = $class ?? '';
    $content = $slot ?? '';

    $variantClasses = [
        'cyan' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-800',
        'slate' => 'border-slate-200 bg-slate-100 text-slate-600',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
    ][$variant] ?? 'border-cyan-200 bg-cyan-50 text-cyan-800';
@endphp

<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-black leading-none {{ $variantClasses }} {{ $class }}">
    {{ $content }}
</span>

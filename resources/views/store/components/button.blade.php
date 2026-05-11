@php
    $type = $type ?? 'button';
    $variant = $variant ?? 'primary';
    $size = $size ?? 'md';
    $href = $href ?? null;
    $class = $class ?? '';
    $content = $slot ?? '';

    $baseClasses = 'inline-flex items-center justify-center rounded-2xl font-black transition focus:outline-none focus:ring-4 disabled:pointer-events-none disabled:opacity-50';
    $variantClasses = [
        'primary' => 'bg-slate-950 text-white shadow-[0_16px_36px_rgba(8,47,73,0.24)] hover:bg-cyan-800 focus:ring-cyan-200',
        'secondary' => 'border border-cyan-200 bg-cyan-50 text-cyan-900 hover:border-cyan-300 hover:bg-cyan-100 focus:ring-cyan-100',
        'ghost' => 'text-slate-700 hover:bg-slate-100 hover:text-slate-950 focus:ring-slate-100',
    ][$variant] ?? 'bg-slate-950 text-white hover:bg-cyan-800 focus:ring-cyan-200';
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-5 py-3 text-sm',
        'lg' => 'px-6 py-4 text-base',
    ][$size] ?? 'px-5 py-3 text-sm';
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $baseClasses }} {{ $variantClasses }} {{ $sizeClasses }} {{ $class }}">
        {{ $content }}
    </a>
@else
    <button type="{{ $type }}" class="{{ $baseClasses }} {{ $variantClasses }} {{ $sizeClasses }} {{ $class }}">
        {{ $content }}
    </button>
@endif

@php
    $eyebrow = $eyebrow ?? null;
    $title = $title ?? '';
    $description = $description ?? null;
    $meta = $meta ?? null;
@endphp

<section class="rounded-[2rem] border border-cyan-100 bg-white/82 p-6 shadow-[0_24px_70px_rgba(8,47,73,0.10)] sm:p-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
            @if ($eyebrow)
                @include('store.components.badge', ['slot' => $eyebrow, 'variant' => 'cyan'])
            @endif

            <h1 class="mt-5 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">{{ $title }}</h1>

            @if ($description)
                <p class="mt-4 max-w-2xl text-base font-medium leading-7 text-slate-600">{{ $description }}</p>
            @endif
        </div>

        @if ($meta)
            <div class="shrink-0">
                @include('store.components.badge', ['slot' => $meta, 'variant' => 'blue'])
            </div>
        @endif
    </div>
</section>

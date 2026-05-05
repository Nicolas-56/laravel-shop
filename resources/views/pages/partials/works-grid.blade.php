<div class="mt-14 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($works as $work)
        <article class="metal-panel group overflow-hidden rounded-[1.75rem]">
            <a href="{{ $work->image_url }}" data-fancybox="works" data-caption="{{ $work->title }}">
                <img
                    src="{{ $work->image_url }}"
                    alt="{{ $work->alt }}"
                    loading="lazy"
                    decoding="async"
                    class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                >
            </a>

            @if ($work->title)
                <div class="space-y-3 p-5">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $work->title }}</h2>
                </div>
            @endif
        </article>
    @empty
        <div class="sm:col-span-2 xl:col-span-3 rounded-[2rem] border border-dashed border-slate-300 bg-white/60 px-6 py-12 text-center text-slate-500">
            В этой категории работы пока не добавлены.
        </div>
    @endforelse
</div>

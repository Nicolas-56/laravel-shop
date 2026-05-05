@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-5xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Портфолио
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Примеры выполненных работ по лазерной резке металла</h1>
            <div class="mt-5 max-w-4xl space-y-5 text-base leading-8 text-slate-600 sm:text-lg">
                <p>
                    На странице представлены примеры выполненных работ по лазерной резке металла в Оренбурге. Мы изготавливаем
                    детали и изделия по чертежам заказчика, работаем с различными видами металла и выполняем заказы любой сложности.
                </p>

                <p>
                    В портфолио вы можете увидеть реальные изделия: кронштейны, декоративные элементы, костровые чаши и другие
                    металлоконструкции. Все работы выполнены с высокой точностью и соблюдением требований к качеству.
                </p>
            </div>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($categories as $category)
                <article class="metal-panel group overflow-hidden rounded-[1.75rem]">
                    <a href="{{ route('works.category', $category->slug) }}" class="block">
                        <img
                            src="{{ $category->image_url }}"
                            alt="{{ $category->name }}"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                        >

                        <div class="space-y-3 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <h2 class="text-lg font-semibold text-slate-900">{{ $category->name }}</h2>
                                <span class="shrink-0 rounded-full bg-slate-900 px-3 py-1 text-xs font-medium text-white">
                                    {{ $category->works_count }}
                                </span>
                            </div>

                            @if ($category->short_description)
                                <p class="line-clamp-3 text-sm leading-7 text-slate-600">
                                    {{ $category->short_description }}
                                </p>
                            @endif

                            <span class="inline-flex text-sm font-medium text-orange-700 transition group-hover:translate-x-1">
                                Смотреть работы →
                            </span>
                        </div>
                    </a>
                </article>
            @empty
                <div class="sm:col-span-2 xl:col-span-3 rounded-[2rem] border border-dashed border-slate-300 bg-white/60 px-6 py-12 text-center text-slate-500">
                    Категории выполненных работ пока не заполнены.
                </div>
            @endforelse
        </div>
    </section>
@endsection

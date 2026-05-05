@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-5xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Оборудование
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Оборудование для лазерной резки металла в Оренбурге</h1>
            <div class="mt-5 max-w-4xl space-y-5 text-justify text-base leading-8 text-slate-600 sm:text-lg">
                <p>
                    Мы используем современное оборудование для лазерной резки металла в Оренбурге, что позволяет выполнять
                    заказы любой сложности с высокой точностью и в сжатые сроки. Станки с ЧПУ обеспечивают чистый рез без
                    деформации материала, благодаря чему детали не требуют дополнительной обработки.
                </p>

                <p>
                    Это особенно важно при изготовлении изделий со сложной геометрией и высокими требованиями к точности.
                    Оборудование позволяет стабильно выдерживать размеры и обеспечивать аккуратную кромку при резке.
                </p>

                <p>
                    Выполняем лазерную резку различных видов металла: черновая сталь, нержавеющая сталь, оцинкованный и
                    окрашенный металл. Толщина обрабатываемого материала подбирается индивидуально в зависимости от задачи клиента.
                </p>

                <p>
                    Используемое оборудование позволяет работать как с тонкими листами, так и с более плотными материалами,
                    обеспечивая высокое качество готовых изделий.
                </p>
            </div>
        </div>

        <div class="mt-12 space-y-8">
            @forelse ($equipmentItems as $index => $item)
                <article class="metal-panel grid overflow-hidden rounded-[2rem] lg:grid-cols-2">
                    <div @class([
                        'order-1',
                        'lg:order-2' => $index % 2 === 1,
                    ])>
                        <a href="{{ $item->image_url }}" data-fancybox="equipment" data-caption="{{ $item->title }}">
                            <img
                                src="{{ $item->image_url }}"
                                alt="{{ $item->image_alt }}"
                                loading="lazy"
                                decoding="async"
                                class="h-72 w-full object-cover transition duration-500 hover:scale-[1.03] lg:h-full"
                            >
                        </a>
                    </div>

                        <div class="order-2 flex items-center p-6 sm:p-8 lg:p-12">
                        <div class="max-w-xl">
                            @php
                                $itemDescription = (string) $item->description;
                                $hasHtmlFormatting = $itemDescription !== strip_tags($itemDescription);
                                $sanitizedDescription = \App\Support\HtmlSanitizer::sanitizeBasicContent($itemDescription);
                            @endphp

                            <h2 class="mt-4 text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $item->title }}</h2>
                            <div class="mt-4 text-base leading-8 text-slate-600 [&>blockquote]:border-l-4 [&>blockquote]:border-slate-300 [&>blockquote]:pl-4 [&>blockquote]:italic [&>ol]:list-decimal [&>ol]:space-y-2 [&>ol]:pl-6 [&>p]:mt-0 [&>p]:mb-4 [&>p:last-child]:mb-0 [&>ul]:list-disc [&>ul]:space-y-2 [&>ul]:pl-6">
                                @if ($hasHtmlFormatting)
                                    {!! $sanitizedDescription !!}
                                @else
                                    {!! nl2br(e($itemDescription)) !!}
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white/60 px-6 py-12 text-center text-slate-500">
                    Список оборудования пока не заполнен.
                </div>
            @endforelse
        </div>
    </section>
@endsection

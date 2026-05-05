@extends('layouts.app')

@push('styles')
    <style nonce="{{ request()->attributes->get('csp_nonce') }}">
        .category-content p + p,
        .category-content ul + p,
        .category-content ol + p,
        .category-content p + ul,
        .category-content p + ol,
        .category-content ul + ul,
        .category-content ol + ol {
            margin-top: 1.25rem;
        }

        .category-content ul,
        .category-content ol {
            padding-left: 1.5rem;
        }

        .category-content ul {
            list-style: disc;
        }

        .category-content ol {
            list-style: decimal;
        }

        .category-content li + li {
            margin-top: 0.5rem;
        }

        .category-content a {
            color: rgb(194 65 12);
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }

        .category-content strong {
            color: rgb(15 23 42);
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-6xl">
            <a href="{{ route('works') }}" class="inline-flex items-center gap-2 text-sm font-medium text-orange-700 transition hover:text-orange-800">
                ← Все категории работ
            </a>

            <div class="mt-6">
                <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                    Категория
                </span>
                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">{{ $category->name }}</h1>

                @if ($category->description)
                    <div class="category-content mt-6 max-w-full text-justify text-base leading-8 text-slate-600 sm:max-w-4xl sm:text-lg xl:max-w-5xl 2xl:max-w-6xl">
                        @if ($category->description !== strip_tags($category->description))
                            {!! str($category->description)->sanitizeHtml() !!}
                        @else
                            {!! nl2br(e($category->description)) !!}
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @include('pages.partials.works-grid', ['works' => $works])
    </section>
@endsection

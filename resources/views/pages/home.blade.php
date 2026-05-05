@extends('layouts.app')

@php
    $heroBackground = \App\Models\SiteSetting::resolveMediaPath($siteSettings['hero_background'] ?? null, 'images/back.webp');
    $companyPhone = $siteSettings['company_phone'] ?? '+7 (922) 854-74-86';
    $companyPhoneHref = $companyPhone ? preg_replace('/[^\d+]/', '', $companyPhone) : '+79228547486';
    $companyEmail = $siteSettings['company_email'] ?? 'pegov_92@mail.ru';
@endphp

@section('content')
    <section class="relative">
        <div class="absolute inset-0 -z-10 overflow-hidden">
            <img
                src="{{ $heroBackground }}"
                alt="Фоновое изображение производства"
                width="1408"
                height="768"
                loading="eager"
                fetchpriority="high"
                decoding="async"
                class="hero-background"
            >
        </div>

        <div class="mx-auto flex min-h-[calc(78vh-5rem)] w-full max-w-7xl items-start px-4 pt-20 pb-4 sm:px-6 sm:pt-24 sm:pb-6 lg:px-8 lg:pt-28 lg:pb-8">
            <div class="w-full max-w-3xl rounded-[2.25rem] border border-white/12 bg-slate-950/18 p-2.5 shadow-[0_24px_64px_rgba(15,23,42,0.22)] backdrop-blur-sm">
                <div class="rounded-[1.75rem] border border-white/10 bg-[linear-gradient(135deg,rgba(15,23,42,0.5),rgba(15,23,42,0.2))] p-5 sm:p-6 lg:p-8">
                    <span class="inline-flex w-fit rounded-full border border-orange-300/60 bg-orange-50/92 px-4 py-2 text-xs font-semibold uppercase tracking-[0.34em] text-orange-700 shadow-sm">
                        Производство деталей из металла
                    </span>
                    <h1 class="mt-5 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-4xl lg:text-5xl">
                        {{ $siteSettings['hero_title'] ?? 'Лазерная резка металла с промышленной точностью' }}
                    </h1>
                    <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-100 sm:text-base">
                        {{ $siteSettings['hero_subtitle'] ?? 'Черновая сталь до 20 мм, нержавеющий лист до 12 мм и гравировка металла.' }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a
                            href="tel:{{ $companyPhoneHref }}"
                            class="inline-flex items-center justify-center rounded-full bg-orange-500 px-6 py-3.5 text-lg font-bold text-white shadow-[0_18px_40px_rgba(249,115,22,0.28)] transition hover:bg-orange-600 sm:min-w-56"
                        >
                            Позвонить
                        </a>
                        <a
                            href="mailto:{{ $companyEmail }}"
                            class="inline-flex items-center justify-center rounded-full bg-orange-500 px-6 py-3.5 text-lg font-bold text-white shadow-[0_18px_40px_rgba(249,115,22,0.28)] transition hover:bg-orange-600 sm:min-w-56"
                        >
                            Написать нам
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <article class="rounded-[2rem] border border-slate-200 bg-white/78 p-7 shadow-[0_18px_40px_rgba(15,23,42,0.06)] sm:p-9 lg:p-10">
            <span class="inline-flex rounded-full border border-slate-200 bg-white/70 px-4 py-2 text-xs uppercase tracking-[0.28em] text-slate-500">
                О компании
            </span>
            <h2 class="mt-5 text-3xl font-semibold text-slate-900 sm:text-4xl">Производственный подход без лишней бюрократии</h2>
            <div class="mt-5 max-w-5xl space-y-5 text-base leading-8 text-slate-600">
                {!! $companyDescription !!}
            </div>
        </article>
    </section>
@endsection

@extends('layouts.app')

@php
    $companyPhone = $siteSettings['company_phone'] ?? '+7 (922) 854-74-86';
    $companyPhoneHref = $companyPhone ? preg_replace('/[^\d+]/', '', $companyPhone) : '+79228547486';
    $companyEmail = $siteSettings['company_email'] ?? 'pegov_92@mail.ru';
    $telegram = \App\Models\SiteSetting::sanitizeValue('company_telegram', $siteSettings['company_telegram'] ?? null);
    $companyCity = $siteSettings['company_city'] ?? 'Екатеринбург';
    $companySchedule = $siteSettings['company_schedule'] ?? 'Пн–Пт: 9:00–18:00';
    $businessName = $siteSettings['company_business_name'] ?? 'ИП Пегов Павел Сергеевич';
    $inn = $siteSettings['company_inn'] ?? 'ИНН 667000000000';
    $ogrnip = $siteSettings['company_ogrnip'] ?? '';
@endphp

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-3xl">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Контакты
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Контакты</h1>
            <p class="mt-5 text-base leading-8 text-slate-600 sm:text-lg">
               Свяжитесь с нами для заказа лазерной резки металла в Оренбурге. Поможем рассчитать стоимость и проконсультируем по вашему проекту.
            </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_22rem]">
            <div class="metal-panel rounded-[2rem] p-6 sm:p-8">
                <h2 class="text-2xl font-semibold text-slate-900">Быстрый контакт</h2>
                <div class="mt-6 grid gap-4">
                    <a href="tel:{{ $companyPhoneHref }}" class="rounded-[1.5rem] border border-slate-200 bg-white/75 px-5 py-4 transition hover:border-orange-300 hover:bg-white">
                        <div class="text-xs uppercase tracking-[0.28em] text-slate-500">Телефон</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">{{ $companyPhone }}</div>
                    </a>

                    @if ($telegram)
                        <a href="{{ $telegram }}" target="_blank" rel="noopener noreferrer" class="rounded-[1.5rem] border border-slate-200 bg-white/75 px-5 py-4 transition hover:border-orange-300 hover:bg-white">
                            <div class="text-xs uppercase tracking-[0.28em] text-slate-500">Telegram</div>
                            <div class="mt-2 text-lg font-semibold text-slate-900">Написать в Telegram</div>
                        </a>
                    @endif

                    <a href="mailto:{{ $companyEmail }}" class="rounded-[1.5rem] border border-slate-200 bg-white/75 px-5 py-4 transition hover:border-orange-300 hover:bg-white">
                        <div class="text-xs uppercase tracking-[0.28em] text-slate-500">Email</div>
                        <div class="mt-2 break-all text-lg font-semibold text-slate-900">{{ $companyEmail }}</div>
                    </a>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="metal-panel rounded-[2rem] p-6">
                    <h2 class="text-xl font-semibold text-slate-900">Информация о работе</h2>
                    <div class="mt-5 space-y-4 text-sm leading-7 text-slate-600">
                        <div>
                            <div class="text-xs uppercase tracking-[0.28em] text-slate-500">Город</div>
                            <div class="mt-1 text-slate-900">{{ $companyCity }}</div>
                        </div>
                        <div>Работаем по всей России</div>
                        <div>{{ $companySchedule }}</div>
                    </div>
                </div>

                <div class="metal-panel rounded-[2rem] p-6 text-sm leading-7 text-slate-500">
                    <div class="text-xs uppercase tracking-[0.28em] text-slate-500">Реквизиты</div>
                    <div class="mt-4">{{ $businessName }}</div>
                    <div>{{ $inn }}</div>
                    <div>{{ $ogrnip }}</div>
                </div>
            </aside>
        </div>
    </section>
@endsection

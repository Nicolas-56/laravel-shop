@extends('layouts.app', [
    'title' => 'Политика конфиденциальности',
    'description' => 'Краткая политика конфиденциальности сайта услуг лазерной резки металла.',
])

@php
    $companyEmail = $siteSettings['company_email'] ?? 'pegov_92@mail.ru';
@endphp

@section('content')
    <section class="mx-auto w-full max-w-4xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="metal-panel rounded-[2rem] p-6 sm:p-8 lg:p-10">
            <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-4 py-2 text-xs uppercase tracking-[0.32em] text-orange-700">
                Privacy
            </span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Политика конфиденциальности</h1>

            <div class="mt-8 space-y-6 text-base leading-8 text-slate-600">
                <p>
                    Этот документ объясняет, как сайт использует информацию, которую посетитель может передать при обращении по телефону, email или через Telegram.
                </p>
                <p>
                    Мы можем обрабатывать контактные данные, которые пользователь сообщает сам: имя, номер телефона, адрес электронной почты, а также содержание обращения или технического задания.
                </p>
                <p>
                    Эти данные используются только для связи с пользователем, расчёта стоимости работ, подготовки предложения и дальнейшего выполнения заказа.
                </p>
                <p>
                    Сайт может использовать cookies, технические журналы и инструменты аналитики для корректной работы, оценки посещаемости и улучшения содержания страниц.
                </p>
                <p>
                    Мы не передаём данные третьим лицам, кроме случаев, когда это требуется законом или необходимо для защиты законных интересов компании.
                </p>
                <p>
                    Если у вас есть вопросы по обработке данных, напишите на <a href="mailto:{{ $companyEmail }}" class="text-slate-900 underline decoration-orange-400/60 underline-offset-4">{{ $companyEmail }}</a>.
                </p>
                <p>
                    Продолжая использовать сайт, пользователь подтверждает, что ознакомился с этой политикой и понимает её основные положения.
                </p>
            </div>
        </div>
    </section>
@endsection

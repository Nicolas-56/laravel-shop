@php
    $companyPhone = $siteSettings['company_phone'] ?? '+7 (922) 854-74-86';
    $companyPhoneHref = $companyPhone ? preg_replace('/[^\d+]/', '', $companyPhone) : '+79228547486';
    $companyEmail = $siteSettings['company_email'] ?? 'pegov_92@mail.ru';
@endphp

<footer class="mt-8 border-t border-slate-200 bg-[linear-gradient(180deg,#f8fafc_0%,#edf2f7_100%)]">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-8 text-base text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div>
            <div class="text-lg text-slate-900">{{ $siteSettings['site_name'] ?? 'ЛазерМет96' }}</div>
            <div class="mt-1">© {{ now()->year }}. Лазерная резка металла и гравировка.</div>
        </div>

        <div class="flex flex-col gap-2 lg:items-end">
            <a href="tel:{{ $companyPhoneHref }}" class="text-lg transition hover:text-slate-900">{{ $companyPhone }}</a>
            <a href="mailto:{{ $companyEmail }}" class="text-lg transition hover:text-slate-900">{{ $companyEmail }}</a>
            <a href="{{ route('privacy') }}" class="text-lg transition hover:text-slate-900">Политика конфиденциальности</a>
        </div>
    </div>
</footer>

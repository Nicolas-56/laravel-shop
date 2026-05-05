<!DOCTYPE html>
<html lang="ru">
<head>
    @php
        $siteName = $siteSettings['site_name'] ?? config('app.name');
        $siteUrl = rtrim((string) config('app.url'), '/');
        $canonicalUrl = $canonical ?? ($siteUrl !== '' ? $siteUrl . request()->getPathInfo() : request()->url());

        if (isset($canonical) && is_string($canonical) && str_starts_with($canonical, '/')) {
            $canonicalUrl = $siteUrl !== '' ? $siteUrl . $canonical : $canonical;
        }

        $siteLogo = \App\Models\SiteSetting::resolveMediaPath($siteSettings['site_logo'] ?? null, 'images/logo.webp');
        $headerLogo = \App\Models\SiteSetting::resolveMediaPath($siteSettings['site_logo'] ?? null, 'images/logo-header.webp');
        $companyPhone = $siteSettings['company_phone'] ?? null;
        $companyEmail = $siteSettings['company_email'] ?? null;
        $companyAddress = $siteSettings['company_address'] ?? null;
        $companyCity = $siteSettings['company_city'] ?? null;
        $companySchedule = $siteSettings['company_schedule'] ?? null;
        $businessName = $siteSettings['company_business_name'] ?? $siteName;
        $telegram = \App\Models\SiteSetting::sanitizeValue('company_telegram', $siteSettings['company_telegram'] ?? null);
        $cleanPhone = $companyPhone ? preg_replace('/[^\d+]/', '', $companyPhone) : null;
        $shouldRenderMetrika = app()->environment('production') && request()->getHost() === 'raycut.ru';
        $cspNonce = request()->attributes->get('csp_nonce');

        $localBusinessSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $siteUrl !== '' ? $siteUrl . '#organization' : null,
            'name' => $businessName,
            'url' => $siteUrl !== '' ? $siteUrl : null,
            'image' => $siteLogo,
            'logo' => $siteLogo,
            'telephone' => $cleanPhone ?: null,
            'email' => $companyEmail ?: null,
            'description' => $description ?? ($siteSettings['hero_subtitle'] ?? null),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $companyAddress ?: null,
                'addressLocality' => $companyCity ?: null,
                'addressCountry' => 'RU',
            ]),
            'openingHours' => $companySchedule ?: null,
            'sameAs' => array_values(array_filter([
                $telegram,
            ])),
        ], function ($value) {
            if (is_array($value)) {
                return $value !== [];
            }

            return $value !== null && $value !== '';
        });

        $websiteSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $siteUrl !== '' ? $siteUrl . '#website' : null,
            'name' => $siteName,
            'url' => $siteUrl !== '' ? $siteUrl : null,
            'inLanguage' => 'ru-RU',
            'publisher' => $siteUrl !== '' ? ['@id' => $siteUrl . '#organization'] : null,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $siteName }}</title>
    <meta name="description" content="{{ $description ?? ($siteSettings['hero_subtitle'] ?? 'Лазерная резка металла, гравировка и производство деталей по чертежам.') }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="preload" as="image" href="{{ $headerLogo }}">
    <meta name="yandex-verification" content="0a17fb19711f5adf" />
    <meta name="google-site-verification" content="inP-bkvZpX7t1-utzFZPv0XQTD3WrUj_QztT_-7oUZ8" />
    @if ($shouldRenderMetrika)
        <!-- Yandex.Metrika counter -->
        <script type="text/javascript" nonce="{{ $cspNonce }}">
            (function(m,e,t,r,i,k,a){
                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();
                for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
            })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=108789340', 'ym');

            ym(108789340, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/108789340" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter -->
    @endif
    <script type="application/ld+json" nonce="{{ $cspNonce }}">{!! json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json" nonce="{{ $cspNonce }}">{!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div class="relative isolate overflow-x-clip">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[42rem] bg-[radial-gradient(circle_at_top,rgba(249,115,22,0.18),transparent_30%),radial-gradient(circle_at_15%_10%,rgba(71,85,105,0.14),transparent_18%),linear-gradient(180deg,#f8fafc_0%,#eef2f7_58%,#f8fafc_100%)]"></div>

    @include('components.site.header')

    <main>
        @yield('content')
    </main>

    @guest
        @php
            $hasLoginRoute = \Illuminate\Support\Facades\Route::has('login');
            $hasRegisterRoute = \Illuminate\Support\Facades\Route::has('register');
        @endphp

        @if ($hasLoginRoute || $hasRegisterRoute)
        <div
            class="modal"
            id="login-modal"
            data-modal
            @if (session('openLoginModal') || (($errors ?? null)?->has('login'))) data-open="true" @endif
            hidden
        >
            <div class="modal__backdrop" data-modal-close></div>

            <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="login-modal-title">
                <button type="button" class="modal__close" data-modal-close aria-label="Закрыть">×</button>

                <div class="modal__heading">
                    <span class="inline-flex rounded-full border border-orange-300 bg-orange-50 px-3 py-1 text-xs uppercase tracking-[0.3em] text-orange-700">
                        Account
                    </span>
                    <h2 id="login-modal-title" class="mt-4 text-3xl font-semibold text-slate-900">Авторизация</h2>
                </div>

                @if (($errors ?? null)?->has('login'))
                    <div class="flash flash--error">
                        {{ $errors->first('login') }}
                    </div>
                @endif

                @if ($hasLoginRoute)
                    <form method="POST" action="{{ route('login') }}" class="form-grid">
                        @csrf

                        <label>
                            Email
                            <input
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="Email"
                                required
                                class="form-input mt-2"
                            >
                        </label>

                        <label>
                            Пароль
                            <input
                                name="password"
                                type="password"
                                placeholder="Password"
                                required
                                class="form-input mt-2"
                            >
                        </label>

                        <label class="checkbox-field">
                            <input type="checkbox" name="remember" value="1">
                            <span>Запомнить меня</span>
                        </label>

                        <button type="submit">Войти</button>
                    </form>
                @endif

                @if ($hasRegisterRoute)
                    <p class="modal__footer">
                        Нет аккаунта?
                        <a href="{{ route('register') }}">Зарегистрироваться</a>
                    </p>
                @endif
            </div>
        </div>
        @endif
    @endguest

    @include('components.site.footer')
</div>
@stack('scripts')
</body>
</html>

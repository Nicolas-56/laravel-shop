<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('csp_nonce', $this->nonce());

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $styleSources = ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net'];
        $scriptSources = ["'self'", 'https://cdn.jsdelivr.net', 'https://mc.yandex.ru', 'https://yastatic.net', 'https://mc.yandex.com'];
        $connectSources = ["'self'", 'https://mc.yandex.ru', 'https://mc.yandex.com', 'wss://mc.yandex.ru'];
        $imgSources = ["'self'", 'data:', 'blob:', 'https://mc.yandex.ru'];
        $fontSources = ["'self'", 'data:', 'https://cdn.jsdelivr.net'];
        $frameSources = ["'self'", 'https://mc.yandex.ru', 'https://mc.yandex.md'];

        if ($this->isProduction()) {
            $scriptSources[] = sprintf("'nonce-%s'", $this->nonce());
        } else {
            $scriptSources[] = "'unsafe-inline'";
            $connectSources[] = 'ws:';
            $connectSources[] = 'wss:';
            $connectSources[] = 'http:';
            $connectSources[] = 'https:';
        }

        if ($viteDevServer = $this->viteDevServer()) {
            $styleSources[] = $viteDevServer;
            $scriptSources[] = $viteDevServer;
            $connectSources[] = $viteDevServer;
            $imgSources[] = $viteDevServer;
            $fontSources[] = $viteDevServer;
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            'img-src ' . implode(' ', array_unique($imgSources)),
            'font-src ' . implode(' ', array_unique($fontSources)),
            'style-src ' . implode(' ', array_unique($styleSources)),
            'script-src ' . implode(' ', array_unique($scriptSources)),
            'connect-src ' . implode(' ', array_unique($connectSources)),
            'frame-src ' . implode(' ', array_unique($frameSources)),
        ]);
    }

    private function isProduction(): bool
    {
        return app()->environment('production');
    }

    private function nonce(): string
    {
        $nonce = request()->attributes->get('csp_nonce');

        if ($nonce instanceof Stringable) {
            return (string) $nonce;
        }

        if (is_string($nonce) && $nonce !== '') {
            return $nonce;
        }

        $nonce = Str::random(40);
        request()->attributes->set('csp_nonce', $nonce);

        return $nonce;
    }

    private function viteDevServer(): ?string
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return null;
        }

        $url = trim((string) file_get_contents($hotFile));

        if ($url === '' || ! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        return rtrim($url, '/');
    }
}

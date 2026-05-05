<?php

namespace App\Support;

class HtmlSanitizer
{
    public static function sanitizeBasicContent(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);

        if ($html === '') {
            return null;
        }

        $sanitized = strip_tags($html, '<p><br><strong><em><ul><ol><li><blockquote><s>');

        $patterns = [
            '/<p\b[^>]*>/i' => '<p>',
            '/<strong\b[^>]*>/i' => '<strong>',
            '/<em\b[^>]*>/i' => '<em>',
            '/<ul\b[^>]*>/i' => '<ul>',
            '/<ol\b[^>]*>/i' => '<ol>',
            '/<li\b[^>]*>/i' => '<li>',
            '/<blockquote\b[^>]*>/i' => '<blockquote>',
            '/<s\b[^>]*>/i' => '<s>',
            '/<br\b[^>]*\/?>/i' => '<br>',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $sanitized) ?: null;
    }
}

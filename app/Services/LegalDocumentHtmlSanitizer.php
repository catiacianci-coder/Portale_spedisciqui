<?php

namespace App\Services;

class LegalDocumentHtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><span><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><table><thead><tbody><tr><th><td><hr><div>';

    public function sanitize(string $html): string
    {
        $html = $this->stripTinyMceDataAttributes($html);
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = $this->normalizeSafeAnchors($html);
        $html = preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html) ?? $html;
        $html = str_replace(array_keys($this->anchorPlaceholders), array_values($this->anchorPlaceholders), $html);
        $html = $this->unwrapBareAnchors($html);

        return $html;
    }

    private function stripTinyMceDataAttributes(string $html): string
    {
        $html = preg_replace('/\s+data-mce-[a-z0-9_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;

        return $html;
    }

    /** @var array<string, string> */
    private array $anchorPlaceholders = [];

    private function normalizeSafeAnchors(string $html): string
    {
        $this->anchorPlaceholders = [];
        $i = 0;

        return preg_replace_callback('/<a\b[^>]*>/i', function (array $m) use (&$i): string {
            $raw = $m[0];
            $replacement = '<a>';
            $url = $this->extractHrefFromAnchorOpeningTag($raw);
            $url = $this->normalizeHrefBeforeValidation($url);
            if ($url !== null && $url !== '' && $this->isSafeHref($url)) {
                $replacement = '<a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'">';
            }
            $key = '%%SQ_ANCHOR_'.$i++.'%%';
            $this->anchorPlaceholders[$key] = $replacement;

            return $key;
        }, $html) ?? $html;
    }

    private function extractHrefFromAnchorOpeningTag(string $raw): ?string
    {
        if (preg_match('/(?<![a-zA-Z])href\s*=\s*([\'"])\s*(.*?)\s*\1/si', $raw, $h) === 1) {
            return trim(html_entity_decode($h[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/(?<![a-zA-Z])href\s*=\s*([^\s>]+)/si', $raw, $h) === 1) {
            return trim(html_entity_decode($h[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return null;
    }

    private function normalizeHrefBeforeValidation(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(https?:|mailto:)#i', $url) === 1) {
            return $url;
        }
        if ($url[0] === '#' || $url[0] === '/') {
            return $url;
        }
        if (str_contains($url, '..') || str_contains($url, '//') || preg_match('/[\s<>"\']/', $url) === 1) {
            return $url;
        }
        if (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_\-.]*$#', $url) === 1) {
            return '/'.ltrim($url, '/');
        }

        return $url;
    }

    private function isSafeHref(string $url): bool
    {
        if (strlen($url) > 2048) {
            return false;
        }

        if (preg_match('#^(https?:|mailto:)#i', $url) === 1) {
            return true;
        }

        if ($url[0] === '#') {
            return preg_match('/^#[\w\-./?=&;%+]*$/u', $url) === 1;
        }

        if ($url[0] !== '/') {
            return false;
        }

        if (str_starts_with($url, '//')) {
            return false;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1) {
            return false;
        }

        return true;
    }

    private function unwrapBareAnchors(string $html): string
    {
        $out = preg_replace('/<a>(.*?)<\/a>/is', '$1', $html) ?? $html;

        return $out;
    }
}

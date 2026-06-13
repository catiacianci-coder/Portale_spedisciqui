<?php

namespace App\Services;

class LegalDocumentPlainTextToHtml
{
    public function convert(string $raw): string
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = explode("\n", $raw);
        $html = '';
        /** @var list<string> $listBuffer */
        $listBuffer = [];
        /** @var list<string> $paraBuffer */
        $paraBuffer = [];

        $flushList = static function () use (&$html, &$listBuffer): void {
            if ($listBuffer === []) {
                return;
            }
            $html .= '<ul>';
            foreach ($listBuffer as $item) {
                $html .= '<li>'.e($item).'</li>';
            }
            $html .= '</ul>';
            $listBuffer = [];
        };

        $flushPara = static function () use (&$html, &$paraBuffer, &$flushList): void {
            $flushList();
            if ($paraBuffer === []) {
                return;
            }
            $text = trim(implode(' ', array_map(static fn (string $x): string => trim($x), $paraBuffer)));
            if ($text !== '') {
                $html .= '<p>'.e($text).'</p>';
            }
            $paraBuffer = [];
        };

        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '') {
                $flushPara();

                continue;
            }
            if (preg_match('/^\d+\.\d+\.\s*(.+)$/', $trim, $m)) {
                $flushPara();
                $html .= '<h3>'.e($trim).'</h3>';

                continue;
            }
            if (preg_match('/^(\d+)\.\s+(.+)$/', $trim, $m)) {
                $flushPara();
                $html .= '<h2>'.e($m[1]).'. '.e($m[2]).'</h2>';

                continue;
            }
            if (preg_match('/^(-|•|\*)\s+(.+)$/', $trim, $m)) {
                $flushPara();
                $listBuffer[] = $m[2];

                continue;
            }
            $paraBuffer[] = $trim;
        }
        $flushPara();

        return $html;
    }
}

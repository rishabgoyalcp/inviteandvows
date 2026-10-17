<?php

/** Pick the current-locale string from a JSON map like {"en":"Wedding","hi":"शादी"}, falling back to English. */
function i18n(?string $json, ?string $fallback = ''): string
{
    $map = json_decode($json ?? '', true);
    if (! is_array($map)) {
        return (string) $fallback;
    }
    $locale = service('request')->getLocale();

    return (string) ($map[$locale] ?? $map['en'] ?? (reset($map) ?: $fallback));
}

function money(int $minor, string $currency): string
{
    $amount = $minor / 100;
    if (class_exists(\NumberFormatter::class)) {
        // Locale-aware: digits, grouping and symbol placement follow the visitor's language.
        $fmt = new \NumberFormatter(service('request')->getLocale() . '_' . ($currency === 'INR' ? 'IN' : 'US'), \NumberFormatter::CURRENCY);
        if ($minor % 100 === 0) {
            $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 0);
            $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        }
        $out = $fmt->formatCurrency($amount, $currency);
        if ($out !== false) {
            return $out;
        }
    }
    $symbols = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];

    return ($symbols[$currency] ?? $currency . ' ') . number_format($amount, $minor % 100 ? 2 : 0);
}

/** Random URL-safe code, e.g. for short links. */
function random_code(int $len = 7): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out   = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $out;
}

/** Public URL of a published invite. Uses the short domain when configured. */
function invite_url(string $code): string
{
    $short = env('app.shortDomain');

    return $short ? 'https://' . $short . '/' . $code : site_url('i/' . $code);
}

/** URL of the current page in another language. SEO pages get a /xx/ prefix; other pages use ?lang=. */
function lang_url(string $code, ?string $path = null): string
{
    $request = service('request');
    $locales = config(\Config\App::class)->supportedLocales;
    $segs    = $request->getUri()->getSegments();
    if ($segs && in_array($segs[0], $locales, true)) {
        array_shift($segs);
    }
    $path = $path ?? implode('/', $segs);
    $seo  = $path === '' || $path === 'templates' || str_starts_with($path, 'templates/');
    if ($seo) {
        return site_url($code . ($path === '' ? '' : '/' . $path));
    }

    return site_url($path) . '?lang=' . $code;
}

/** <link> tags that load the web font for a language's script. Only the active language is loaded. */
function font_link(?string $family): string
{
    if (! $family) {
        return '';
    }

    return '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $family) . ':wght@400;600&display=swap">';
}

/** Canonical URL of the current page without query string. */
function canonical_url(): string
{
    return rtrim(site_url(implode('/', service('request')->getUri()->getSegments())), '/') ?: site_url('/');
}

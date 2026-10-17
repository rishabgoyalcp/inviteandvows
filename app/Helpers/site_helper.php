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
    $symbols = ['INR' => '₹', 'USD' => '$'];

    return ($symbols[$currency] ?? $currency . ' ') . number_format($minor / 100, $minor % 100 ? 2 : 0);
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

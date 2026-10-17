<?php

namespace App\Filters;

use App\Libraries\Region;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Picks the locale: ?lang=xx (saved to session) > session > region default. Only languages the region offers are accepted. */
class RegionLocale implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $region    = Region::current();
        $languages = Region::languages();
        $session   = service('session');

        $requested = $request->getGet('lang');
        if (is_string($requested) && isset($languages[$requested])) {
            $session->set('locale', $requested);
        }
        $locale = $session->get('locale');
        if (! is_string($locale) || ! isset($languages[$locale])) {
            $locale = isset($languages[$region['default_locale']]) ? $region['default_locale'] : array_key_first($languages);
        }
        service('request')->setLocale($locale);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

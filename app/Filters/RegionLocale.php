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

        // /hi/templates style URLs: the first segment names the language. Only languages this region offers are valid.
        $first = $request->getUri()->getSegment(1);
        if (in_array($first, config(\Config\App::class)->supportedLocales, true)) {
            if (! isset($languages[$first])) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }
            $request->setLocale($first);
            $session->set('locale', $first);

            return;
        }

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

<?php

namespace App\Controllers;

use App\Libraries\Region;

class Seo extends BaseController
{
    public function sitemap()
    {
        $db   = db_connect();
        $rid  = (int) Region::current()['id'];
        $base = rtrim(base_url(), '/');
        $tpls = $db->table('templates t')->select('t.slug, t.updated_at')->join('event_types e', 'e.id = t.event_type_id')
            ->where('t.is_active', 1)->where('e.is_active', 1)
            ->where("EXISTS (SELECT 1 FROM template_formats f WHERE f.template_id = t.id AND f.region_id = {$rid} AND f.is_active = 1)", null, false)
            ->get()->getResultArray();
        $codes = array_keys(Region::languages());

        $paths = [''];
        $paths[] = 'templates';
        $paths[] = 'tools/budget';
        foreach ($tpls as $t) {
            $paths[] = 'templates/' . $t['slug'];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach ($paths as $path) {
            foreach ($codes as $code) {
                $xml .= '<url><loc>' . htmlspecialchars($base . '/' . $code . ($path === '' ? '' : '/' . $path), ENT_XML1) . '</loc>';
                foreach ($codes as $alt) {
                    $xml .= '<xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . htmlspecialchars($base . '/' . $alt . ($path === '' ? '' : '/' . $path), ENT_XML1) . '"/>';
                }
                $xml .= '</url>';
            }
        }

        // Blog: the index in every language; each post only in the language it is written in.
        foreach ($codes as $code) {
            $xml .= '<url><loc>' . htmlspecialchars($base . '/' . $code . '/blog', ENT_XML1) . '</loc></url>';
        }
        foreach ($db->table('blog_posts')->select('locale, slug')->where('is_published', 1)->get()->getResultArray() as $p) {
            if (in_array($p['locale'], $codes, true)) {
                $xml .= '<url><loc>' . htmlspecialchars($base . '/' . $p['locale'] . '/blog/' . $p['slug'], ENT_XML1) . '</loc></url>';
            }
        }

        return $this->response->setContentType('application/xml')->setBody($xml . '</urlset>');
    }

    /** Per-domain robots.txt so the Sitemap line always points at the host being crawled. */
    public function robots()
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /build/', 'Disallow: /checkout/', 'Disallow: /buy/', 'Disallow: /dashboard', 'Disallow: /login', 'Disallow: /i/', 'Disallow: /webhooks/', '', 'Sitemap: ' . base_url('sitemap.xml')];

        return $this->response->setContentType('text/plain')->setBody(implode("\n", $lines) . "\n");
    }
}

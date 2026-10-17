<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Blog extends BaseController
{
    /** Posts in the visitor's language; if there are none yet, English ones are shown instead. */
    public function index()
    {
        $db     = db_connect();
        $locale = service('request')->getLocale();
        $posts  = $db->table('blog_posts')->where(['locale' => $locale, 'is_published' => 1])->orderBy('published_at', 'DESC')->get()->getResultArray();
        if (! $posts && $locale !== 'en') {
            $posts = $db->table('blog_posts')->where(['locale' => 'en', 'is_published' => 1])->orderBy('published_at', 'DESC')->get()->getResultArray();
        }

        return view('blog/index', ['title' => lang('Site.blog') . ' · ' . lang('Site.brand'), 'description' => lang('Site.descBlog'), 'posts' => $posts, 'noAlternates' => true]);
    }

    public function show(string $slug)
    {
        $locale = service('request')->getLocale();
        $db     = db_connect();
        $post   = $db->table('blog_posts')->where(['slug' => $slug, 'is_published' => 1])->whereIn('locale', array_unique([$locale, 'en']))->orderBy("locale = '" . $db->escapeString($locale) . "'", 'DESC', false)->get(1)->getRowArray();
        if (! $post) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('blog/show', ['title' => $post['title'] . ' · ' . lang('Site.brand'), 'description' => $post['excerpt'] ?: mb_substr(strip_tags($post['body']), 0, 160), 'p' => $post, 'noAlternates' => true]);
    }
}

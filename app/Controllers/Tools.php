<?php

namespace App\Controllers;

use App\Libraries\Region;

class Tools extends BaseController
{
    /** A budget planner that runs in the browser; nothing is sent to the server. */
    public function budget()
    {
        $keys = ['venue', 'catering', 'decor', 'photo', 'attire', 'music', 'invites', 'misc'];

        return view('tools/budget', [
            'title' => lang('Site.budgetTitle') . ' · ' . lang('Site.brand'), 'description' => lang('Site.budgetDesc'), 'noAlternates' => false,
            'currency' => Region::current()['currency'], 'locale' => service('request')->getLocale(),
            'cats' => array_map(static fn ($k) => ['key' => $k, 'label' => lang('Site.budget_' . $k)], $keys),
        ]);
    }
}

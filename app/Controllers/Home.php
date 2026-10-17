<?php

namespace App\Controllers;

use App\Libraries\Region;

class Home extends BaseController
{
    public function index()
    {
        $db = db_connect();

        return view('home', [
            'description' => lang('Site.descHome'),
            'region'     => Region::current(),
            'languages'  => Region::languages(),
            'eventTypes' => $db->tableExists('event_types') ? $db->table('event_types')->where('is_active', 1)->orderBy('sort')->get()->getResultArray() : [],
        ]);
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $db     = db_connect();
        $counts = [];
        foreach (['event_types', 'field_definitions', 'templates', 'invites', 'orders'] as $t) {
            $counts[$t] = $db->table($t)->countAllResults();
        }

        return view('admin/dashboard', ['counts' => $counts]);
    }
}

<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** While an update runs, visitors see a short notice. Signed-in admins can still work. */
class Maintenance implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $flag = WRITEPATH . 'maintenance.flag';
        if (! is_file($flag)) {
            return;
        }
        if (time() - (int) filemtime($flag) > 1800) { // a crashed update must not lock the site forever
            @unlink($flag);

            return;
        }
        if (session()->get('user_role') === 'admin') {
            return;
        }

        return service('response')->setStatusCode(503)->setHeader('Retry-After', '120')
            ->setBody('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Updating</title><body style="font-family:system-ui;text-align:center;padding:3em"><h1>We are updating the site</h1><p>Please try again in a minute or two.</p></body>');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

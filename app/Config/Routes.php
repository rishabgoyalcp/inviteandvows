<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Language-prefixed pages for search engines: /hi, /hi/templates, /hi/templates/classic-wedding
foreach (config(\Config\App::class)->supportedLocales as $code) {
    $routes->get($code, 'Home::index');
    $routes->get($code . '/templates', 'Templates::index');
    $routes->get($code . '/templates/(:segment)', 'Templates::show/$1');
}
$routes->get('sitemap.xml', 'Seo::sitemap');
$routes->get('robots.txt', 'Seo::robots');

$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');

// Public catalogue and builder (guests can build before signing up)
$routes->get('templates', 'Templates::index');
$routes->get('templates/(:segment)', 'Templates::show/$1');
$routes->post('build/start/(:segment)', 'Builder::start/$1');
$routes->get('build/(:alphanum)', 'Builder::edit/$1');
$routes->post('build/(:alphanum)/save', 'Builder::save/$1');
$routes->post('build/(:alphanum)/preview', 'Builder::preview/$1');
$routes->post('build/(:alphanum)/upload', 'Builder::upload/$1');

// Checkout (account is created here)
$routes->get('checkout/(:alphanum)', 'Checkout::show/$1');
$routes->post('checkout/(:alphanum)/account', 'Checkout::account/$1');
$routes->post('checkout/(:alphanum)/verify', 'Checkout::verify/$1');
$routes->post('checkout/(:alphanum)/simulate', 'Checkout::simulate/$1');
$routes->post('webhooks/razorpay', 'Webhooks::razorpay');

// Ready-made Canva products
$routes->get('buy/(:num)', 'Buy::show/$1');
$routes->post('buy/(:num)/account', 'Buy::account/$1');
$routes->group('', ['filter' => 'login'], static function ($routes) {
    $routes->post('buy/(:num)', 'Buy::start/$1');
    $routes->post('buy/order/(:num)/verify', 'Buy::verify/$1');
    $routes->post('buy/order/(:num)/simulate', 'Buy::simulate/$1');
});

// Published invites
$routes->get('i/(:alphanum)', 'Invite::show/$1');
$routes->post('i/(:alphanum)/rsvp', 'Invite::rsvp/$1');

// Customer dashboard
$routes->group('dashboard', ['filter' => 'login'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('invites/(:num)/rsvps', 'Dashboard::rsvps/$1');
    $routes->get('orders/(:num)/open', 'Dashboard::openOrder/$1');
});

$routes->group('admin', ['filter' => 'adminauth', 'namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('event-types', 'Catalog::eventTypes');
    $routes->post('event-types', 'Catalog::saveEventType');
    $routes->get('event-types/(:num)/fields', 'Catalog::fields/$1');
    $routes->post('event-types/(:num)/fields', 'Catalog::saveField/$1');
    $routes->post('event-types/(:num)/fields/(:num)/delete', 'Catalog::deleteField/$1/$2');
    $routes->get('languages', 'Catalog::languages');
    $routes->post('languages', 'Catalog::saveLanguages');
    $routes->get('templates', 'Catalog::templates');
    $routes->post('templates', 'Catalog::saveTemplate');
});

// Short-link domain (wd2.in): wd2.in/aB3xK9 serves the invite. Only active on that host.
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if ($host === strtolower((string) env('app.shortDomain', 'wd2.in'))) {
    $routes->get('(:alphanum)', 'Invite::show/$1');
    $routes->post('(:alphanum)/rsvp', 'Invite::rsvp/$1');
}

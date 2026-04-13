<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StaticPageController extends Controller
{
    public function about(): Response
    {
        return Inertia::render('Storefront/About');
    }

    public function contact(): Response
    {
        return Inertia::render('Storefront/Contact');
    }

    public function terms(): Response
    {
        return Inertia::render('Storefront/Terms');
    }

    public function privacy(): Response
    {
        return Inertia::render('Storefront/Privacy');
    }

    public function refund(): Response
    {
        return Inertia::render('Storefront/Refund');
    }

    public function faq(): Response
    {
        return Inertia::render('Storefront/FAQ');
    }

    public function invoice(): Response
    {
        return Inertia::render('Storefront/Invoice');
    }

    public function profile(): Response
    {
        return Inertia::render('Storefront/Profile');
    }
}

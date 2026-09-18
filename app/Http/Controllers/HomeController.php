<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the public landing page.
     *
     * Phase 2 replaces this with the ported vCard template.
     */
    public function __invoke(): View
    {
        return view('frontend.home');
    }
}

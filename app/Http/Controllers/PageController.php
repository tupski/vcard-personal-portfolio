<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * About / landing page.
     */
    public function home(): View
    {
        return view('pages.home');
    }

    /**
     * Resume: education, experience and skills.
     */
    public function resume(): View
    {
        return view('pages.resume');
    }

    /**
     * Portfolio: filterable project grid.
     */
    public function portfolio(): View
    {
        return view('pages.portfolio');
    }

    /**
     * Blog: post listing.
     */
    public function blog(): View
    {
        return view('pages.blog');
    }

    /**
     * Contact: map and message form.
     */
    public function contact(): View
    {
        return view('pages.contact');
    }
}

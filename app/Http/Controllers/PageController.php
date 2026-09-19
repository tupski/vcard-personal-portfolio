<?php

namespace App\Http\Controllers;

use App\Support\Seo\SeoData;
use App\Support\Seo\SeoManager;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly SeoManager $seo,
    ) {}

    /**
     * About / landing page.
     */
    public function home(): View
    {
        return $this->page('home', 'pages.home');
    }

    /**
     * Resume: education, experience and skills.
     */
    public function resume(): View
    {
        return $this->page('resume', 'pages.resume');
    }

    /**
     * Portfolio: filterable project grid.
     */
    public function portfolio(): View
    {
        return $this->page('portfolio', 'pages.portfolio');
    }

    /**
     * Blog: post listing.
     */
    public function blog(): View
    {
        return $this->page('blog', 'pages.blog');
    }

    /**
     * Contact: map and message form.
     */
    public function contact(): View
    {
        return $this->page('contact', 'pages.contact');
    }

    /**
     * Render a public page with its resolved SEO metadata.
     *
     * Metadata is built here, once, from the route name — the page template
     * only forwards the value object to the layout, so a page can never
     * disagree with its own <head>.
     */
    private function page(string $name, string $view): View
    {
        return view($view, [
            'seo' => $this->seo->forPage($name),
        ]);
    }

    /**
     * Expose the metadata builder to the layout's fallback path.
     */
    public static function dataFor(string $name): SeoData
    {
        return app(SeoManager::class)->forPage($name);
    }
}

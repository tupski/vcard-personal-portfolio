<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminAccess;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AuthorizesAdminAccess;

    /**
     * Admin dashboard: real counts and the two most actionable recent lists.
     *
     * Every number comes straight from the database; nothing is invented.
     */
    public function __invoke(): View
    {
        $this->authorizeAdmin();

        return view('admin.dashboard', [
            'totals' => [
                'projects' => Project::query()->count(),
                'blogPosts' => BlogPost::query()->visible()->count(),
                'services' => Service::query()->count(),
                'testimonials' => Testimonial::query()->count(),
                'clients' => Client::query()->count(),
                'unreadMessages' => ContactMessage::query()->unread()->count(),
            ],
            'recentPosts' => BlogPost::query()
                ->with('category')
                ->orderBy('published_at', 'desc')
                ->limit(5)
                ->get(),
            'recentMessages' => ContactMessage::query()
                ->latest()
                ->unread()
                ->limit(5)
                ->get(),
        ]);
    }
}

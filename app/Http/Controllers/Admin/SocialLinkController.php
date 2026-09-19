<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return SocialLink::class;
    }

    protected function resource(): string
    {
        return 'admin.social-links';
    }

    protected function label(): string
    {
        return __('Social link');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}

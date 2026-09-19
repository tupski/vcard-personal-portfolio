<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return Service::class;
    }

    protected function resource(): string
    {
        return 'admin.services';
    }

    protected function label(): string
    {
        return __('Service');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'icon_path' => ['required', 'string', 'max:255'],
            'icon_alt' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}

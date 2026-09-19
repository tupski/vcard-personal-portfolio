<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Experience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return Experience::class;
    }

    protected function resource(): string
    {
        return 'admin.experience';
    }

    protected function label(): string
    {
        return __('Experience entry');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'period' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}

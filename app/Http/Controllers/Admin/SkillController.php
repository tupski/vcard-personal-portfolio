<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return Skill::class;
    }

    protected function resource(): string
    {
        return 'admin.skills';
    }

    protected function label(): string
    {
        return __('Skill');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'percent' => ['required', 'integer', 'between:1,100'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}

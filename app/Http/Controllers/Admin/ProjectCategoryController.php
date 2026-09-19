<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProjectCategoryController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return ProjectCategory::class;
    }

    protected function resource(): string
    {
        return 'admin.project-categories';
    }

    protected function label(): string
    {
        return __('Project category');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function with(): array
    {
        return ['projects'];
    }

    /**
     * Categories in use cannot be deleted (restrictOnDelete); the trait
     * surfaces the conflict, and the count badge shows why up front.
     */
    protected function listingQuery(): Builder
    {
        return ProjectCategory::query()
            ->withCount('projects')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

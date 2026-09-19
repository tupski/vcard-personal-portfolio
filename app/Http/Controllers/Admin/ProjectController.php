<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return Project::class;
    }

    protected function resource(): string
    {
        return 'admin.projects';
    }

    protected function label(): string
    {
        return __('Project');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

        if ($slug = $request->string('slug')->toString()) {
            $slugRule[] = Rule::unique('projects', 'slug')
                ->ignore($item?->getKey());
        }

        return [
            'project_category_id' => ['required', 'integer', 'exists:project_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'image_path' => ['required', 'string', 'max:255'],
            'image_alt' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client' => ['nullable', 'string', 'max:255'],
            'technologies' => ['nullable', 'string', 'max:255'],
            'display_date' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048', 'url'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function booleanFields(): array
    {
        return ['is_visible', 'featured'];
    }

    protected function with(): array
    {
        return ['category'];
    }

    protected function listingQuery(): Builder
    {
        return Project::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    protected function formExtras(): array
    {
        return [
            'categories' => ProjectCategory::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * The frontend filter compares lower-cased category names against
     * `data-category`, so a project's category drives the filter. Featured
     * and the extra meta fields are admin-side organisation for now.
     */
    protected function afterStore(Request $request, Model $item): RedirectResponse
    {
        return parent::afterStore($request, $item);
    }
}

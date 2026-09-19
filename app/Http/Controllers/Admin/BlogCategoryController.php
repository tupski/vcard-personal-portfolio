<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return BlogCategory::class;
    }

    protected function resource(): string
    {
        return 'admin.blog-categories';
    }

    protected function label(): string
    {
        return __('Blog category');
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
        return ['posts'];
    }

    protected function listingQuery(): Builder
    {
        return BlogCategory::query()
            ->withCount('posts')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

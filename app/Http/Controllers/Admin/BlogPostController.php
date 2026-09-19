<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return BlogPost::class;
    }

    protected function resource(): string
    {
        return 'admin.blog-posts';
    }

    protected function label(): string
    {
        return __('Blog post');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

        if ($slug = $request->string('slug')->toString()) {
            $slugRule[] = Rule::unique('blog_posts', 'slug')
                ->ignore($item?->getKey());
        }

        return [
            'blog_category_id' => ['required', 'integer', 'exists:blog_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'excerpt' => ['required', 'string'],
            'content' => ['nullable', 'string'],
            'image_path' => ['required', 'string', 'max:255'],
            'image_alt' => ['required', 'string', 'max:255'],
            'published_at' => ['required', 'date'],
            'display_date' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function with(): array
    {
        return ['category'];
    }

    protected function listingQuery(): Builder
    {
        return BlogPost::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    protected function formExtras(): array
    {
        return [
            'categories' => BlogCategory::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ];
    }
}

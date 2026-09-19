<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Support\SeedContent;
use Illuminate\Database\Seeder;

/**
 * Seeds the portfolio and blog taxonomies with their entries.
 *
 * Categories and entries are keyed by name so the FK wiring reads naturally,
 * and "All" stays a UI-level filter rather than a stored row, matching the
 * original template's behaviour.
 */
class PublicationSeeder extends Seeder
{
    /**
     * Seed projects, project categories, blog categories and blog posts.
     */
    public function run(): void
    {
        BlogPost::query()->delete();
        BlogCategory::query()->delete();
        Project::query()->delete();
        ProjectCategory::query()->delete();

        $categories = collect(SeedContent::projectCategories())
            ->reject(fn (string $name) => $name === 'All')
            ->values();

        foreach ($categories as $i => $name) {
            ProjectCategory::query()->create([
                'name' => $name,
                'slug' => str($name)->slug(),
                'sort_order' => $i,
            ]);
        }

        $categoriesByName = ProjectCategory::query()->pluck('id', 'name');

        foreach (SeedContent::projects() as $i => $project) {
            Project::query()->create([
                'project_category_id' => $categoriesByName[$project['category']],
                'title' => $project['title'],
                'image_path' => $project['image'],
                'image_alt' => $project['alt'],
                'url' => '#',
                'sort_order' => $i,
            ]);
        }

        $blogCategory = BlogCategory::query()->create([
            'name' => 'Design',
            'slug' => 'design',
        ]);

        foreach (SeedContent::posts() as $i => $post) {
            BlogPost::query()->create([
                'blog_category_id' => $blogCategory->id,
                'title' => $post['title'],
                'excerpt' => $post['text'],
                'content' => $post['content'],
                'image_path' => $post['image'],
                'image_alt' => $post['alt'],
                'published_at' => $post['date_iso'],
                'display_date' => $post['date'],
                'sort_order' => $i,
            ]);
        }
    }
}

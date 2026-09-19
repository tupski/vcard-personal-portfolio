<?php

namespace App\Support;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Client;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

/**
 * Drops the public content cache whenever admin-editable content changes.
 *
 * This is the whole invalidation strategy, and it is deliberately this simple:
 * one list of models, and any save or delete on one of them invalidates the
 * public namespace. That means an edit is visible on the very next request —
 * there is no TTL to wait out and no manual "clear cache" step for an editor.
 *
 * Why the whole namespace rather than a per-model key: a single edit can
 * legitimately affect several cached values. Renaming a blog category changes
 * the listing and every rendered post card; hiding a project changes the
 * portfolio grid and the filter's category list. Tracking those dependencies
 * by hand would be a cache-invalidation bug waiting to happen, and the cached
 * values are cheap to rebuild (a handful of small queries).
 *
 * `Media` is intentionally absent: content arrays store image *paths*, and
 * those paths are resolved to URLs through MediaService at render time, so a
 * media change is reflected immediately without invalidating anything.
 */
class ContentCacheInvalidator
{
    /**
     * Models whose changes affect public output.
     *
     * @var list<class-string<Model>>
     */
    private const MODELS = [
        Profile::class,
        Setting::class,
        SocialLink::class,
        Service::class,
        Testimonial::class,
        Client::class,
        Education::class,
        Experience::class,
        Skill::class,
        Project::class,
        ProjectCategory::class,
        BlogPost::class,
        BlogCategory::class,
    ];

    public function __construct(
        private readonly ContentCache $cache,
        private readonly ContentRepository $repository,
    ) {}

    /**
     * Attach the listeners.
     */
    public function register(): void
    {
        $flush = function (): void {
            $this->cache->invalidate();
            $this->repository->forget();
        };

        foreach (self::MODELS as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }
}

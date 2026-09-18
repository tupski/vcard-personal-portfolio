<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Service;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Testimonial;
use App\Support\SeedContent;
use Illuminate\Database\Seeder;

/**
 * Seeds the flat portfolio content collections: social links, services,
 * testimonials, clients, both resume timelines and skills.
 *
 * Every list is rebuilt from PortfolioContent in display order, so the seeded
 * database renders exactly what Phase 2 shipped.
 */
class ContentSeeder extends Seeder
{
    /**
     * Seed ordered content collections.
     */
    public function run(): void
    {
        SocialLink::query()->delete();
        Service::query()->delete();
        Testimonial::query()->delete();
        Client::query()->delete();
        Education::query()->delete();
        Experience::query()->delete();
        Skill::query()->delete();

        foreach (SeedContent::socialLinks() as $i => $link) {
            SocialLink::query()->create([
                'label' => $link['label'],
                'icon' => $link['icon'],
                'url' => $link['url'],
                'sort_order' => $i,
            ]);
        }

        foreach (SeedContent::services() as $i => $service) {
            Service::query()->create([
                'title' => $service['title'],
                'icon_path' => $service['icon'],
                'icon_alt' => $service['icon_alt'],
                'description' => $service['text'],
                'sort_order' => $i,
            ]);
        }

        foreach (SeedContent::testimonials() as $i => $testimonial) {
            Testimonial::query()->create([
                'name' => $testimonial['name'],
                'avatar_path' => $testimonial['avatar'],
                'testimonial_date' => $testimonial['date_iso'],
                'display_date' => $testimonial['date'],
                'content' => $testimonial['text'],
                'sort_order' => $i,
            ]);
        }

        foreach (SeedContent::clients() as $i => $logo) {
            Client::query()->create([
                'logo_path' => $logo,
                'url' => '#',
                'sort_order' => $i,
            ]);
        }

        foreach (SeedContent::education() as $i => $entry) {
            Education::query()->create([
                'title' => $entry['title'],
                'period' => $entry['period'],
                'description' => $entry['text'],
                'sort_order' => $i,
            ]);
        }

        foreach (SeedContent::experience() as $i => $entry) {
            Experience::query()->create([
                'title' => $entry['title'],
                'period' => $entry['period'],
                'description' => $entry['text'],
                'sort_order' => $i,
            ]);
        }

        foreach (SeedContent::skills() as $i => $skill) {
            Skill::query()->create([
                'title' => $skill['title'],
                'percent' => $skill['percent'],
                'sort_order' => $i,
            ]);
        }
    }
}

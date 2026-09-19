<?php

namespace App\Support;

/**
 * Phase 2 baseline content, frozen.
 *
 * This is the verbatim static data that Phase 2 shipped, kept as the seeding
 * fixture: the database is populated FROM it so the seeded database reproduces
 * the Phase 2 output by construction. Runtime reads go through
 * ContentRepository (database-backed); this class exists only for seeders.
 */
class SeedContent
{
    /**
     * Site / profile identity shown in the sidebar.
     */
    public static function profile(): array
    {
        return [
            'name' => 'Richard hanrick',
            'title' => 'Web developer',
            'avatar' => 'assets/images/my-avatar.png',
            'email' => 'richard@example.com',
            'phone' => '+1 (213) 352-2795',
            'phone_href' => 'tel:+12133522795',
            'birthday' => 'June 23, 1982',
            'birthday_iso' => '1982-06-23',
            'location' => 'Sacramento, California, USA',
        ];
    }

    /**
     * Primary navigation.
     *
     * Replaces the original `data-nav-link` buttons: each entry is now a real
     * named route so Turbo Drive can drive navigation and browser history.
     */
    public static function navigation(): array
    {
        return [
            ['label' => 'About', 'route' => 'home'],
            ['label' => 'Resume', 'route' => 'resume'],
            ['label' => 'Portfolio', 'route' => 'portfolio'],
            ['label' => 'Blog', 'route' => 'blog'],
            ['label' => 'Contact', 'route' => 'contact'],
        ];
    }

    /**
     * Sidebar social links. The original template pointed these at "#".
     */
    public static function socialLinks(): array
    {
        return [
            ['label' => 'Facebook', 'icon' => 'logo-facebook', 'url' => '#'],
            ['label' => 'Twitter', 'icon' => 'logo-twitter', 'url' => '#'],
            ['label' => 'Instagram', 'icon' => 'logo-instagram', 'url' => '#'],
        ];
    }

    /**
     * "About me" paragraphs.
     */
    public static function about(): array
    {
        return [
            "I'm Creative Director and UI/UX Designer from Sydney, Australia, working in web development and print media. I enjoy turning complex problems into simple, beautiful and intuitive designs.",
            'My job is to build your website so that it is functional and user-friendly but at the same time attractive. Moreover, I add personal touch to your product and make sure that is eye-catching and easy to use. My aim is to bring across your message and identity in the most creative way. I created web design for many famous brand companies.',
        ];
    }

    /**
     * Services shown on the About page.
     */
    public static function services(): array
    {
        return [
            [
                'title' => 'Web design',
                'icon' => 'assets/images/icon-design.svg',
                'icon_alt' => 'design icon',
                'text' => 'The most modern and high-quality design made at a professional level.',
            ],
            [
                'title' => 'Web development',
                'icon' => 'assets/images/icon-dev.svg',
                'icon_alt' => 'Web development icon',
                'text' => 'High-quality development of sites at the professional level.',
            ],
            [
                'title' => 'Mobile apps',
                'icon' => 'assets/images/icon-app.svg',
                'icon_alt' => 'mobile app icon',
                'text' => 'Professional development of applications for iOS and Android.',
            ],
            [
                'title' => 'Photography',
                'icon' => 'assets/images/icon-photo.svg',
                'icon_alt' => 'camera icon',
                'text' => 'I make high-quality photos of any category at a professional level.',
            ],
        ];
    }

    /**
     * Testimonials. The modal date comes from the original `datetime`.
     */
    public static function testimonials(): array
    {
        $body = 'Richard was hired to create a corporate identity. We were very pleased with the work done. She has a lot of experience and is very concerned about the needs of client. Lorem ipsum dolor sit amet, ullamcous cididt consectetur adipiscing elit, seds do et eiusmod tempor incididunt ut laborels dolore magnarels alia.';

        return [
            ['name' => 'Daniel lewis', 'avatar' => 'assets/images/avatar-1.png', 'date' => '14 June, 2021', 'date_iso' => '2021-06-14', 'text' => $body],
            ['name' => 'Jessica miller', 'avatar' => 'assets/images/avatar-2.png', 'date' => '14 June, 2021', 'date_iso' => '2021-06-14', 'text' => $body],
            ['name' => 'Emily evans', 'avatar' => 'assets/images/avatar-3.png', 'date' => '14 June, 2021', 'date_iso' => '2021-06-14', 'text' => $body],
            ['name' => 'Henry william', 'avatar' => 'assets/images/avatar-4.png', 'date' => '14 June, 2021', 'date_iso' => '2021-06-14', 'text' => $body],
        ];
    }

    /**
     * Client logos.
     */
    public static function clients(): array
    {
        return [
            'assets/images/logo-1-color.png',
            'assets/images/logo-2-color.png',
            'assets/images/logo-3-color.png',
            'assets/images/logo-4-color.png',
            'assets/images/logo-5-color.png',
            'assets/images/logo-6-color.png',
        ];
    }

    /**
     * Resume education entries.
     */
    public static function education(): array
    {
        return [
            [
                'title' => 'University school of the arts',
                'period' => '2007 — 2008',
                'text' => 'Nemo enims ipsam voluptatem, blanditiis praesentium voluptum delenit atque corrupti, quos dolores et quas molestias exceptur.',
            ],
            [
                'title' => 'New york academy of art',
                'period' => '2006 — 2007',
                'text' => 'Ratione voluptatem sequi nesciunt, facere quisquams facere menda ossimus, omnis voluptas assumenda est omnis..',
            ],
            [
                'title' => 'High school of art and design',
                'period' => '2002 — 2004',
                'text' => 'Duis aute irure dolor in reprehenderit in voluptate, quila voluptas mag odit aut fugit, sed consequuntur magni dolores eos.',
            ],
        ];
    }

    /**
     * Resume experience entries.
     */
    public static function experience(): array
    {
        return [
            [
                'title' => 'Creative director',
                'period' => '2015 — Present',
                'text' => 'Nemo enim ipsam voluptatem blanditiis praesentium voluptum delenit atque corrupti, quos dolores et qvuas molestias exceptur.',
            ],
            [
                'title' => 'Art director',
                'period' => '2013 — 2015',
                'text' => 'Nemo enims ipsam voluptatem, blanditiis praesentium voluptum delenit atque corrupti, quos dolores et quas molestias exceptur.',
            ],
            [
                'title' => 'Web designer',
                'period' => '2010 — 2013',
                'text' => 'Nemo enims ipsam voluptatem, blanditiis praesentium voluptum delenit atque corrupti, quos dolores et quas molestias exceptur.',
            ],
        ];
    }

    /**
     * Skill bars (percentage => label).
     */
    public static function skills(): array
    {
        return [
            ['title' => 'Web design', 'percent' => 80],
            ['title' => 'Graphic design', 'percent' => 70],
            ['title' => 'Branding', 'percent' => 90],
            ['title' => 'WordPress', 'percent' => 50],
        ];
    }

    /**
     * Portfolio projects.
     *
     * `category` holds the original `data-category` value verbatim so the
     * client-side filter keeps working against the same slugs.
     */
    public static function projects(): array
    {
        return [
            ['title' => 'Finance', 'category' => 'Web development', 'image' => 'assets/images/project-1.jpg', 'alt' => 'finance'],
            ['title' => 'Orizon', 'category' => 'Web development', 'image' => 'assets/images/project-2.png', 'alt' => 'orizon'],
            ['title' => 'Fundo', 'category' => 'Web design', 'image' => 'assets/images/project-3.jpg', 'alt' => 'fundo'],
            ['title' => 'Brawlhalla', 'category' => 'Applications', 'image' => 'assets/images/project-4.png', 'alt' => 'brawlhalla'],
            ['title' => 'DSM.', 'category' => 'Web design', 'image' => 'assets/images/project-5.png', 'alt' => 'dsm.'],
            ['title' => 'MetaSpark', 'category' => 'Web design', 'image' => 'assets/images/project-6.png', 'alt' => 'metaspark'],
            ['title' => 'Summary', 'category' => 'Web development', 'image' => 'assets/images/project-7.png', 'alt' => 'summary'],
            ['title' => 'Task Manager', 'category' => 'Applications', 'image' => 'assets/images/project-8.jpg', 'alt' => 'task manager'],
            ['title' => 'Arrival', 'category' => 'Web development', 'image' => 'assets/images/project-9.png', 'alt' => 'arrival'],
        ];
    }

    /**
     * Portfolio filter categories, in original order.
     */
    public static function projectCategories(): array
    {
        return ['All', 'Web design', 'Applications', 'Web development'];
    }

    /**
     * Blog posts.
     */
    /**
     * Blog posts.
     *
     * `text` is the card excerpt (Phase 2 copy, byte-identical). `content` is
     * the full article body used by the Phase 8 detail page: plain text with
     * blank-line paragraph separation, in the same filler register the rest of
     * the template uses. It is deliberately plain — the detail view escapes it
     * and never parses it as HTML.
     */
    public static function posts(): array
    {
        return [
            ['title' => 'Design conferences in 2022', 'category' => 'Design', 'date' => 'Fab 23, 2022', 'date_iso' => '2022-02-23', 'image' => 'assets/images/blog-1.jpg', 'alt' => 'Design conferences in 2022', 'text' => 'Veritatis et quasi architecto beatae vitae dicta sunt, explicabo.', 'content' => "Veritatis et quasi architecto beatae vitae dicta sunt, explicabo. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt.\n\nNemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.\n\nUt enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur."],
            ['title' => 'Best fonts every designer', 'category' => 'Design', 'date' => 'Fab 23, 2022', 'date_iso' => '2022-02-23', 'image' => 'assets/images/blog-2.jpg', 'alt' => 'Best fonts every designer', 'text' => 'Sed ut perspiciatis, nam libero tempore, cum soluta nobis est eligendi.', 'content' => "Sed ut perspiciatis, nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.\n\nTemporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae.\n\nItaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat."],
            ['title' => 'Design digest #80', 'category' => 'Design', 'date' => 'Fab 23, 2022', 'date_iso' => '2022-02-23', 'image' => 'assets/images/blog-3.jpg', 'alt' => 'Design digest #80', 'text' => 'Excepteur sint occaecat cupidatat no proident, quis nostrum exercitationem ullam corporis suscipit.', 'content' => "Excepteur sint occaecat cupidatat no proident, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.\n\nQuis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur.\n\nAt vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident."],
            ['title' => 'UI interactions of the week', 'category' => 'Design', 'date' => 'Fab 23, 2022', 'date_iso' => '2022-02-23', 'image' => 'assets/images/blog-4.jpg', 'alt' => 'UI interactions of the week', 'text' => 'Enim ad minim veniam, consectetur adipiscing elit, quis nostrud exercitation ullamco laboris nisi.', 'content' => "Enim ad minim veniam, consectetur adipiscing elit, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur, excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nSed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa quae ab illo inventore veritatis."],
            ['title' => 'The forgotten art of spacing', 'category' => 'Design', 'date' => 'Fab 23, 2022', 'date_iso' => '2022-02-23', 'image' => 'assets/images/blog-5.jpg', 'alt' => 'The forgotten art of spacing', 'text' => 'Maxime placeat, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'content' => "Maxime placeat, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nNam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus."],
            ['title' => 'Design digest #79', 'category' => 'Design', 'date' => 'Fab 23, 2022', 'date_iso' => '2022-02-23', 'image' => 'assets/images/blog-6.jpg', 'alt' => 'Design digest #79', 'text' => 'Optio cumque nihil impedit uo minus quod maxime placeat, velit esse cillum.', 'content' => "Optio cumque nihil impedit uo minus quod maxime placeat, velit esse cillum dolore eu fugiat nulla pariatur.\n\nAt vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi.\n\nEt harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus."],
        ];
    }

    /**
     * Contact map embed (Sacramento, CA — unchanged from the original).
     */
    public static function mapEmbedUrl(): string
    {
        return 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d199666.5651251294!2d-121.58334177520186!3d38.56165006739519!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x809ac672b28397f9%3A0x921f6aaa74197fdb!2sSacramento%2C%20CA%2C%20USA!5e0!3m2!1sen!2sbd!4v1647608789441!5m2!1sen!2sbd';
    }
}

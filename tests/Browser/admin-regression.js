/**
 * Phase 4 verification: admin panel behaviour + public regression.
 */
import { chromium } from 'playwright';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const CHROME = process.env.CHROME_PATH
    || 'C:\\Users\\Kakarama Room\\AppData\\Local\\ms-playwright\\chromium-1243\\chrome-win64\\chrome.exe';

const results = [];
const failures = [];

function check(ok, msg, extra) {
    results.push({ ok, msg });
    if (!ok) failures.push(msg + (extra ? ` :: ${extra}` : ''));
}

(async () => {
    const browser = await chromium.launch({ executablePath: CHROME });
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();

    const errs = [];
    page.on('pageerror', (e) => errs.push('pageerror: ' + e.message));
    page.on('console', (m) => { if (m.type() === 'error') errs.push('console: ' + m.text()); });

    // ---- Login flow -------------------------------------------------------
    await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin');
    await page.waitForSelector('aside a[href$="/admin/profile"]', { timeout: 15000 });
    check(true, 'admin: login redirects to dashboard');

    const dashTitle = await page.$eval('h1', (el) => el.textContent.trim());
    check(dashTitle.includes('Dashboard'), 'admin: dashboard heading', dashTitle);

    // Dashboard metric cards
    const cards = await page.$$eval('main a[class*="rounded-xl"] p.mt-2', (els) => els.map((e) => e.textContent.trim()));
    check(cards.length >= 6, 'dashboard: 6 metric cards', JSON.stringify(cards));
    check(cards[0] === '9', 'dashboard: 9 projects', cards[0]);
    check(cards[5] === '0', 'dashboard: 0 unread', cards[5]);

    // ---- Turbo navigation across all admin sections -----------------------
    const sections = [
        ['Profile', '/admin/profile'],
        ['Services', '/admin/services'],
        ['Experience', '/admin/experience'],
        ['Education', '/admin/education'],
        ['Skills', '/admin/skills'],
        ['Projects', '/admin/projects'],
        ['Project Categories', '/admin/project-categories'],
        ['Blog Posts', '/admin/blog-posts'],
        ['Blog Categories', '/admin/blog-categories'],
        ['Testimonials', '/admin/testimonials'],
        ['Clients', '/admin/clients'],
        ['Social Links', '/admin/social-links'],
        ['Messages', '/admin/contact-messages'],
        ['Settings', '/admin/settings'],
    ];

    await page.evaluate(() => { window.__turboMark = 'kept'; });

    for (const [label, path] of sections) {
        await page.click(`aside a[href$="${path}"]`);
        await page.waitForURL('**' + path);
        const h1 = await page.$eval('h1', (el) => el.textContent.trim());
        check(h1.length > 0, `admin ${path}: renders (h1="${h1.slice(0, 40)}")`);
    }

    const mark = await page.evaluate(() => window.__turboMark);
    check(mark === 'kept', 'admin: Turbo Drive preserved JS state (no reloads)', mark);

    // History back/forward: the loop ended on /admin/settings.
    await page.goBack();
    await page.waitForURL('**/admin/contact-messages');
    await page.goForward();
    await page.waitForURL('**/admin/settings');
    check(true, 'admin: back/forward history works');

    // ---- Active nav state --------------------------------------------------
    await page.click('aside a[href$="/admin/services"]');
    await page.waitForURL('**/admin/services');
    const activeText = await page.$eval('aside a[aria-current="page"]', (el) => el.textContent.trim());
    check(activeText.includes('Services'), 'admin: active nav marks Services', activeText);

    // ---- CRUD: create a service, verify on public, hide it, verify gone ----
    await page.click('a[href$="/admin/services/create"]');
    await page.waitForURL('**/admin/services/create');

    await page.fill('input[name="title"]', 'Browser QA Service');
    await page.fill('input[name="icon_path"]', 'assets/images/icon-dev.svg');
    await page.fill('input[name="icon_alt"]', 'qa icon');
    await page.fill('textarea[name="description"]', 'Created by the Playwright suite.');
    await page.fill('input[name="sort_order"]', '99');

    const createResp = page.waitForResponse(
        (r) => r.url().endsWith('/admin/services') && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.click('form[action$="/admin/services"] button[type="submit"]');
    await createResp;
    // Turbo navigates to the index after the 302; wait for the URL to
    // actually change before reloading (a premature reload would re-land
    // on the create page).
    await page.waitForURL('**/admin/services', { timeout: 15_000 });
    check(true, 'services: create redirects to index');

    await page.reload({ waitUntil: 'networkidle' });
    const rowVisible = await page.$$eval('td', (tds) => tds.some((td) => td.textContent.includes('Browser QA Service')));
    check(rowVisible, 'services: new row listed');

    // Public frontend shows it through the content layer
    const pub = await ctx.newPage();
    await pub.goto(BASE + '/', { waitUntil: 'networkidle' });
    check(await pub.$$eval('.service-item-title', (els) => els.some((e) => e.textContent.includes('Browser QA Service'))),
        'frontend: new service appears on public About page');
    await pub.close();

    // Validation error path: novalidate so the request reaches Laravel's
    // server-side validation instead of being blocked by HTML `required`.
    await page.click('a[href$="/admin/services/create"]');
    await page.waitForURL('**/admin/services/create');
    await page.fill('input[name="title"]', 'Invalid Title');
    await page.fill('input[name="icon_path"]', 'assets/images/icon-dev.svg');
    await page.fill('input[name="icon_alt"]', 'qa icon');
    await page.fill('textarea[name="description"]', 'Should fail validation.');
    await page.fill('input[name="sort_order"]', '-5');
    await page.$eval('form[action$="/admin/services"]', (f) => f.setAttribute('novalidate', ''));
    await page.click('form[action$="/admin/services"] button[type="submit"]');
    await page.waitForURL('**/admin/services/create');
    await page.waitForSelector('[id$="-error"]', { timeout: 8000 }).catch(() => {});
    const errCount = await page.$$eval('[id$="-error"]', (els) => els.length);
    check(errCount >= 1, 'services: server-side validation errors render inline', errCount);
    const oldInput = await page.inputValue('input[name="icon_path"]');
    check(oldInput === 'assets/images/icon-dev.svg', 'services: old input preserved', oldInput);
    await page.$eval('form[action$="/admin/services"]', (f) => f.removeAttribute('novalidate'));

    // ---- Edit + hide the QA service ----------------------------------------
    await page.goto(BASE + '/admin/services', { waitUntil: 'networkidle' });
    await page.click('tr:has-text("Browser QA Service") a[href$="/edit"]');
    await page.waitForURL('**/edit');
    await page.fill('input[name="title"]', 'Browser QA Service');
    await page.uncheck('input[name="is_visible"]');
    const updateResp = page.waitForResponse(
        (r) => /\/admin\/services\/\d+$/.test(r.url()) && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.click('main form button[type="submit"]');
    await updateResp;
    await page.waitForLoadState('networkidle');

    const pub2 = await ctx.newPage();
    await pub2.goto(BASE + '/', { waitUntil: 'networkidle' });
    check(await pub2.$$eval('.service-item-title', (els) => els.every((e) => !e.textContent.includes('Browser QA Service'))),
        'frontend: hidden service disappears from public page');
    await pub2.close();

    // ---- Delete with Turbo confirm -----------------------------------------
    await page.goto(BASE + '/admin/services', { waitUntil: 'networkidle' });

    // A dialog appears when the delete form is submitted.
    // The delete form uses Turbo's native data-turbo-confirm only.
    const qaDelete = page.locator('tr:has-text("Browser QA Service") form[data-turbo-confirm] button').first();

    // First click: dismiss the confirm -> submission must NOT happen.
    // (Dialogs block the click from completing, so the handler is armed
    // before clicking and the click promise is awaited afterwards.)
    const dialog1 = page.waitForEvent('dialog', { timeout: 10_000 });
    const click1 = qaDelete.click();
    const d1 = await dialog1;
    await d1.dismiss();
    await click1;
    check(true, 'services: delete shows confirmation dialog');

    // Second click: accept -> wait for the server response, then Turbo's
    // render, before asserting on the DOM. Same-URL redirects make
    // waitForURL a no-op, so the response IS the synchronization point.
    const dialog2 = page.waitForEvent('dialog', { timeout: 10_000 });
    const deleteResponse = page.waitForResponse(
        (r) => r.url().includes('/admin/services/') && /\d+$/.test(r.url()) && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    const click2 = qaDelete.click();
    const d2 = await dialog2;
    await d2.accept();
    await click2;
    await deleteResponse;
    await page.waitForLoadState('networkidle');
    await page.reload({ waitUntil: 'networkidle' });
    const gone = await page.$$eval('td', (tds) => tds.every((td) => !td.textContent.includes('Browser QA Service')));
    check(gone, 'services: accepted confirm deletes the row');

    // ---- FK protection: category in use ------------------------------------
    await page.goto(BASE + '/admin/project-categories', { waitUntil: 'networkidle' });

    const fkResponse = page.waitForResponse(
        (r) => r.url().includes('/admin/project-categories/') && /\d+$/.test(r.url()) && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    const dialog3 = page.waitForEvent('dialog', { timeout: 10_000 });
    const catDelete = page.locator('tbody form[data-turbo-confirm] button').first();
    const catClick = catDelete.click();
    const d3 = await dialog3;
    await d3.accept();
    await catClick;
    const resp = await fkResponse;
    // The 302 itself proves the server handled the conflict gracefully;
    // the flash banner is its user-facing echo.
    check(resp.status() === 302, 'project-categories: FK delete answered with redirect', resp.status());
    await page.waitForLoadState('networkidle');

    const fkFlashed = await page.locator('[role="alert"]').first()
        .waitFor({ state: 'visible', timeout: 15_000 })
        .then(() => true)
        .catch(() => false);
    check(fkFlashed, 'project-categories: FK conflict flashes an error');
    const cats = await page.$$eval('tbody tr', (rows) => rows.length);
    check(cats === 3, 'project-categories: all 3 categories still listed', cats);

    // ---- Settings sync -------------------------------------------------------
    await page.goto(BASE + '/admin/settings', { waitUntil: 'networkidle' });
    await page.fill('input[name="settings[site.name]"]', 'Browser Renamed Site');
    const settingsResp = page.waitForResponse(
        (r) => r.url().endsWith('/admin/settings') && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.click('form[action$="/admin/settings"] button[type="submit"]');
    await settingsResp;
    await page.waitForLoadState('networkidle');

    const pub3 = await ctx.newPage();
    await pub3.goto(BASE + '/', { waitUntil: 'networkidle' });
    const title = await pub3.title();
    check(title.includes('Browser Renamed Site'), 'frontend: settings rename reflects in <title>', title);

    // Restore the original name
    await page.goto(BASE + '/admin/settings', { waitUntil: 'networkidle' });
    await page.fill('input[name="settings[site.name]"]', 'Artupski Portfolio');
    const settingsResp2 = page.waitForResponse(
        (r) => r.url().endsWith('/admin/settings') && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.click('form[action$="/admin/settings"] button[type="submit"]');
    await settingsResp2;
    await page.waitForLoadState('networkidle');
    await pub3.close();

    // ---- Messages inbox ------------------------------------------------------
    await page.goto(BASE + '/admin/contact-messages', { waitUntil: 'networkidle' });
    const emptyState = await page.$eval('main', (el) => el.textContent.includes('No messages'));
    check(emptyState, 'messages: empty state renders');

    // ---- Public regression: Phase 2 baseline sweep ---------------------------
    const pubPage = await ctx.newPage();
    const pubErrs = [];
    pubPage.on('pageerror', (e) => pubErrs.push(e.message));

    for (const width of [375, 390, 768, 1024, 1280, 1440]) {
        await pubPage.setViewportSize({ width, height: 900 });

        for (const route of ['/', '/resume', '/portfolio', '/blog', '/contact']) {
            await pubPage.goto(BASE + route, { waitUntil: 'networkidle' });
            const geo = await pubPage.evaluate(() => ({
                sw: document.documentElement.scrollWidth,
                cw: document.documentElement.clientWidth,
                bg: getComputedStyle(document.body).backgroundColor,
                icons: document.querySelectorAll('.vcard-icon svg').length,
            }));

            const tag = `${route} @${width}`;
            check(geo.sw <= geo.cw + 1, `${tag}: no horizontal overflow`, `${geo.sw}/${geo.cw}`);
            check(geo.bg === 'rgb(18, 18, 18)', `${tag}: smoky-black body`);
            check(geo.icons > 0, `${tag}: icons render`);
        }
    }

    check(pubErrs.length === 0, 'public: no page errors during sweep', pubErrs.join(' | '));

    // ---- SEO: per-page metadata, canonical, robots --------------------------
    const SEO_PAGES = [
        { route: '/', title: 'About', canonical: '/' },
        { route: '/resume', title: 'Resume', canonical: '/resume' },
        { route: '/portfolio', title: 'Portfolio', canonical: '/portfolio' },
        { route: '/blog', title: 'Blog', canonical: '/blog' },
        { route: '/contact', title: 'Contact', canonical: '/contact' },
    ];

    await pubPage.setViewportSize({ width: 1280, height: 900 });

    for (const page of SEO_PAGES) {
        await pubPage.goto(BASE + page.route, { waitUntil: 'networkidle' });

        const meta = await pubPage.evaluate(() => ({
            title: document.title,
            description: document.querySelector('meta[name="description"]')?.content ?? '',
            robots: document.querySelector('meta[name="robots"]')?.content ?? '',
            canonical: document.querySelector('link[rel="canonical"]')?.href ?? '',
            ogTitle: document.querySelector('meta[property="og:title"]')?.content ?? '',
            ogType: document.querySelector('meta[property="og:type"]')?.content ?? '',
            ogUrl: document.querySelector('meta[property="og:url"]')?.content ?? '',
            ogImage: document.querySelector('meta[property="og:image"]')?.content ?? '',
            ogSiteName: document.querySelector('meta[property="og:site_name"]')?.content ?? '',
            twitterCard: document.querySelector('meta[name="twitter:card"]')?.content ?? '',
            twitterTitle: document.querySelector('meta[name="twitter:title"]')?.content ?? '',
            jsonLd: Array.from(document.querySelectorAll('script[type="application/ld+json"]')).map((s) => s.textContent),
        }));

        const tag = `seo ${page.route}`;
        check(meta.title === `${page.title} - Artupski Portfolio`, `${tag}: title`, meta.title);
        check(meta.description.length > 0, `${tag}: description present`);
        check(meta.canonical.endsWith(page.canonical), `${tag}: canonical`, meta.canonical);
        check(meta.ogTitle === page.title, `${tag}: og:title`, meta.ogTitle);
        check(meta.ogType === 'website', `${tag}: og:type`);
        check(meta.ogUrl === meta.canonical, `${tag}: og:url matches canonical`);
        check(meta.ogImage.length > 0, `${tag}: og:image present`);
        check(meta.ogSiteName === 'Artupski Portfolio', `${tag}: og:site_name`, meta.ogSiteName);
        check(meta.twitterCard.length > 0, `${tag}: twitter:card`);
        check(meta.twitterTitle === page.title, `${tag}: twitter:title`);
        check(meta.jsonLd.length > 0, `${tag}: JSON-LD present`);
        check(meta.jsonLd.every((j) => { try { return typeof JSON.parse(j) === 'object'; } catch { return false; } }),
            `${tag}: JSON-LD is valid JSON`);
    }

    // robots metadata: indexable in production, noindex locally (this suite
    // runs non-production, so the deterministic value is noindex,nofollow).
    await pubPage.goto(BASE + '/', { waitUntil: 'networkidle' });
    const robotsMeta = await pubPage.$eval('meta[name="robots"]', (el) => el.content);
    check(robotsMeta === 'noindex, nofollow', 'seo: non-production pages are noindex', robotsMeta);

    // ---- SEO: Turbo navigation updates <head> metadata ----------------------
    // Start on one page, navigate via a real link (Turbo Drive), and confirm
    // the head follows — no reload, no timers.
    await pubPage.goto(BASE + '/', { waitUntil: 'networkidle' });
    const homeCanonical = await pubPage.$eval('link[rel="canonical"]', (el) => el.href);

    await Promise.all([
        pubPage.waitForURL('**/resume'),
        pubPage.click('.navbar a[href$="/resume"]'),
    ]);

    // Turbo merges <head> during render, which happens after the URL changes.
    // Synchronise on the DOM itself (never on networkidle: a visit restored
    // from Turbo's snapshot cache makes no requests, so networkidle resolves
    // before the head has been swapped in).
    await pubPage.waitForFunction(() => document.title === 'Resume - Artupski Portfolio', null, { timeout: 15_000 });

    const afterNav = await pubPage.evaluate(() => ({
        title: document.title,
        canonical: document.querySelector('link[rel="canonical"]')?.href ?? '',
        ogUrl: document.querySelector('meta[property="og:url"]')?.content ?? '',
        ogTitle: document.querySelector('meta[property="og:title"]')?.content ?? '',
        description: document.querySelector('meta[name="description"]')?.content ?? '',
        robots: document.querySelector('meta[name="robots"]')?.content ?? '',
    }));

    check(afterNav.title === 'Resume - Artupski Portfolio', 'seo: Turbo nav updates title', afterNav.title);
    check(afterNav.canonical.endsWith('/resume'), 'seo: Turbo nav updates canonical', afterNav.canonical);
    check(afterNav.canonical !== homeCanonical, 'seo: canonical actually changed');
    check(afterNav.ogUrl === afterNav.canonical, 'seo: Turbo nav updates og:url');
    check(afterNav.ogTitle === 'Resume', 'seo: Turbo nav updates og:title');
    check(afterNav.description.includes('Education'), 'seo: Turbo nav updates description');
    check(afterNav.robots.length > 0, 'seo: Turbo nav keeps robots meta');

    // Browser back / forward must restore the previous page's metadata.
    // Each transition is synchronised on the resulting DOM state rather than
    // on network activity, because Turbo can restore a page from its snapshot
    // cache without issuing any request at all.
    await pubPage.goBack();
    await pubPage.waitForFunction(() => document.title === 'About - Artupski Portfolio', null, { timeout: 15_000 });
    check(await pubPage.title() === 'About - Artupski Portfolio', 'seo: back restores title');
    check(await pubPage.$eval('link[rel="canonical"]', (el) => el.href) === homeCanonical, 'seo: back restores canonical');

    await pubPage.goForward();
    await pubPage.waitForFunction(() => document.title === 'Resume - Artupski Portfolio', null, { timeout: 15_000 });
    check(await pubPage.title() === 'Resume - Artupski Portfolio', 'seo: forward restores title');
    check(await pubPage.$eval('link[rel="canonical"]', (el) => el.href).then((c) => c.endsWith('/resume')),
        'seo: forward restores canonical');

    // Hard refresh must render the same head as the Turbo visit.
    await pubPage.reload();
    await pubPage.waitForFunction(() => document.title === 'Resume - Artupski Portfolio', null, { timeout: 15_000 });
    check(await pubPage.title() === 'Resume - Artupski Portfolio', 'seo: hard refresh keeps title');

    // ---- SEO: crawler endpoints ---------------------------------------------
    const robotsResp = await pubPage.goto(BASE + '/robots.txt');
    check(robotsResp.status() === 200, 'seo: robots.txt responds 200');
    const robotsBody = await pubPage.$eval('body', (el) => el.textContent);
    check(robotsBody.includes('User-agent: *'), 'seo: robots.txt has user-agent');
    check(robotsBody.includes('Sitemap:'), 'seo: robots.txt references the sitemap');
    check(robotsBody.includes('/sitemap.xml'), 'seo: robots.txt sitemap URL present');

    const sitemapResp = await pubPage.goto(BASE + '/sitemap.xml');
    check(sitemapResp.status() === 200, 'seo: sitemap.xml responds 200');
    const sitemapBody = await pubPage.$eval('body', (el) => el.textContent);
    check(sitemapBody.includes('<urlset'), 'seo: sitemap.xml has urlset root');
    for (const route of ['/resume', '/portfolio', '/blog', '/contact']) {
        check(sitemapBody.includes(route + '</loc>'), `seo: sitemap lists ${route}`);
    }
    check(!sitemapBody.includes('/admin'), 'seo: sitemap excludes admin URLs');
    check(!sitemapBody.includes('/login'), 'seo: sitemap excludes login');

    const locCount = (sitemapBody.match(/<loc>/g) || []).length;
    const uniqueLocs = new Set(sitemapBody.match(/<loc>[^<]+<\/loc>/g) || []);
    check(locCount === uniqueLocs.size, 'seo: sitemap URLs are unique', `${locCount}/${uniqueLocs.size}`);

    // ---- Blog detail: listing -> article, SEO, JSON-LD, safety --------------
    await pubPage.setViewportSize({ width: 1280, height: 900 });
    await pubPage.goto(BASE + '/blog', { waitUntil: 'networkidle' });

    const firstPostHref = await pubPage.$eval('.blog-post-item > a', (el) => el.getAttribute('href'));
    check(/^\/blog\/[a-z0-9-]+$/.test(firstPostHref), 'blog: listing links to a slug URL', firstPostHref);
    check(await pubPage.$eval('.blog-post-item > a', (el) => el.getAttribute('href') !== '#'),
        'blog: listing no longer uses a placeholder href');

    // Turbo navigation from the listing into the article.
    await Promise.all([
        pubPage.waitForURL('**' + firstPostHref),
        pubPage.click(`.blog-post-item > a[href="${firstPostHref}"]`),
    ]);

    await pubPage.waitForFunction(
        () => document.querySelector('.blog-post-body') !== null,
        null,
        { timeout: 15_000 },
    );

    const article = await pubPage.evaluate(() => {
        const canonical = document.querySelector('link[rel="canonical"]');
        const graphs = Array.from(document.querySelectorAll('script[type="application/ld+json"]'))
            .map((s) => { try { return JSON.parse(s.textContent); } catch { return null; } })
            .filter(Boolean);

        return {
            url: location.pathname,
            title: document.title,
            heading: document.querySelector('.article-title')?.textContent?.trim() ?? '',
            canonical: canonical?.href ?? '',
            description: document.querySelector('meta[name="description"]')?.content ?? '',
            ogType: document.querySelector('meta[property="og:type"]')?.content ?? '',
            ogUrl: document.querySelector('meta[property="og:url"]')?.content ?? '',
            twitterCard: document.querySelector('meta[name="twitter:card"]')?.content ?? '',
            banner: document.querySelector('.blog-post-banner img')?.getAttribute('src') ?? '',
            bannerAlt: document.querySelector('.blog-post-banner img')?.getAttribute('alt') ?? '',
            bannerWidth: document.querySelector('.blog-post-banner img')?.getAttribute('width') ?? '',
            body: document.querySelector('.blog-post-body')?.textContent ?? '',
            bodyInnerHtml: document.querySelector('.blog-post-body')?.innerHTML ?? '',
            liveScripts: document.querySelectorAll('.blog-post-body script').length,
            related: Array.from(document.querySelectorAll('.blog-related-item > a')).map((a) => a.getAttribute('href')),
            back: document.querySelector('.blog-back a')?.getAttribute('href') ?? '',
            types: graphs.map((g) => g['@type']),
            posting: graphs.find((g) => g['@type'] === 'BlogPosting') ?? null,
        };
    });

    const tag = 'blog detail';
    check(article.url === firstPostHref, `${tag}: URL is the slug route`, article.url);
    check(article.title.endsWith(' - Artupski Portfolio'), `${tag}: title`, article.title);
    check(article.heading.length > 0, `${tag}: article heading present`, article.heading);
    check(article.canonical.endsWith(firstPostHref), `${tag}: canonical matches the route`, article.canonical);
    check(article.ogUrl === article.canonical, `${tag}: og:url matches canonical`);
    check(article.description.length > 0, `${tag}: description present`);
    check(article.ogType === 'article', `${tag}: og:type is article`, article.ogType);
    check(article.twitterCard === 'summary_large_image', `${tag}: twitter card`, article.twitterCard);
    check(article.banner.length > 0, `${tag}: featured image renders`, article.banner);
    check(article.bannerAlt.length > 0, `${tag}: featured image has alt text`);
    check(article.bannerWidth === '1200', `${tag}: featured image declares dimensions`);
    check(article.body.trim().length > 80, `${tag}: article body renders`);
    check(article.liveScripts === 0, `${tag}: body content is inert (no script elements)`);
    check(article.types.includes('BlogPosting'), `${tag}: BlogPosting JSON-LD present`);
    check(article.posting !== null && article.posting.headline === article.heading,
        `${tag}: BlogPosting headline matches the article`);
    check(article.posting !== null && typeof article.posting.datePublished === 'string',
        `${tag}: BlogPosting has datePublished`);
    check(article.posting !== null && !('dateModified' in article.posting),
        `${tag}: BlogPosting omits a fabricated dateModified`);
    check(article.related.length > 0 && !article.related.includes(firstPostHref),
        `${tag}: related posts exclude the current article`);
    check(article.back.endsWith('/blog'), `${tag}: back-to-blog affordance`, article.back);

    // The article body must never contain live markup from stored content.
    check(!/<script/i.test(article.bodyInnerHtml), `${tag}: no script markup in the body`);

    // Back / forward across the Turbo visit must keep the article and its head.
    await pubPage.goBack();
    await pubPage.waitForFunction(
        () => document.querySelector('.blog-posts-list') !== null,
        null,
        { timeout: 15_000 },
    );
    check((await pubPage.title()).startsWith('Blog - '), 'blog: back returns to the listing title');

    await pubPage.goForward();
    await pubPage.waitForFunction(
        () => document.querySelector('.blog-post-body') !== null,
        null,
        { timeout: 15_000 },
    );
    check(await pubPage.title() === article.title, 'blog: forward restores the article title');
    check(await pubPage.$eval('link[rel="canonical"]', (el) => el.href) === article.canonical,
        'blog: forward restores the canonical');

    // Hard refresh renders the same head as the Turbo visit.
    await pubPage.reload({ waitUntil: 'networkidle' });
    check(await pubPage.title() === article.title, 'blog: hard refresh keeps the title');
    check(await pubPage.$eval('.blog-post-body', (el) => el.textContent.trim().length > 80),
        'blog: hard refresh keeps the body');

    // A missing slug 404s, and the sitemap carries the published slugs.
    const missing = await pubPage.goto(BASE + '/blog/no-such-post-at-all');
    check(missing.status() === 404, 'blog: unknown slug returns 404', String(missing.status()));

    const sitemapForBlog = await pubPage.goto(BASE + '/sitemap.xml');
    const blogSitemap = await pubPage.$eval('body', (el) => el.textContent);
    check(sitemapForBlog.status() === 200, 'blog: sitemap still serves');
    check(blogSitemap.includes(firstPostHref + '</loc>'),
        'blog: sitemap lists the published detail URL');
    check(!blogSitemap.includes('/blog/no-such-post-at-all'), 'blog: sitemap excludes unknown slugs');

    // Responsive sweep for the detail page: the same Phase 2 invariants the
    // static routes are held to (no horizontal overflow, design tokens, icons).
    for (const width of [375, 390, 768, 1024, 1280, 1440]) {
        await pubPage.setViewportSize({ width, height: 900 });
        await pubPage.goto(BASE + firstPostHref, { waitUntil: 'networkidle' });

        const geo = await pubPage.evaluate(() => ({
            sw: document.documentElement.scrollWidth,
            cw: document.documentElement.clientWidth,
            bg: getComputedStyle(document.body).backgroundColor,
            icons: document.querySelectorAll('.vcard-icon svg').length,
        }));

        const detailTag = `blog detail @${width}`;
        check(geo.sw <= geo.cw + 1, `${detailTag}: no horizontal overflow`, `${geo.sw}/${geo.cw}`);
        check(geo.bg === 'rgb(18, 18, 18)', `${detailTag}: smoky-black body`);
        check(geo.icons > 0, `${detailTag}: icons render`);
    }

    // Portfolio filter still works
    await pubPage.setViewportSize({ width: 1280, height: 900 });
    await pubPage.goto(BASE + '/portfolio', { waitUntil: 'networkidle' });
    await pubPage.click('.filter-item button[data-category="Applications"]');
    const vis = await pubPage.$$eval('.project-item', (els) => els.filter((e) => getComputedStyle(e).display !== 'none').length);
    check(vis === 2, 'public: portfolio filter still works', vis);

    // Testimonials modal still works
    await pubPage.goto(BASE + '/', { waitUntil: 'networkidle' });
    await pubPage.click('.content-card');
    check(await pubPage.$eval('.modal-container', (el) => el.classList.contains('active')),
        'public: testimonial modal still opens');
    await pubPage.close();

    // ---- Contact form: validation, success, inbox ----------------------------
    const contactPage = await ctx.newPage();

    await contactPage.goto(BASE + '/contact', { waitUntil: 'networkidle' });
    check(await contactPage.$eval('form.form', (f) => f.getAttribute('action').endsWith('/contact')),
        'contact: form posts to /contact');
    check(await contactPage.$('input[name="subject"]') !== null, 'contact: subject field present');
    check(await contactPage.$('input[name="website"]') !== null, 'contact: honeypot field present');

    // The Stimulus controller keeps the original disabled-until-valid
    // behaviour, so an empty form cannot be submitted from the UI at all.
    check(await contactPage.$eval('button.form-btn', (el) => el.disabled),
        'contact: submit disabled while the form is incomplete');

    // Server-side validation is authoritative, so exercise it through a real
    // visitor path: fill values the browser accepts (required + email format)
    // but the server rejects (minimum lengths). This is the case a client
    // check cannot catch, which is exactly why the server validates.
    await contactPage.fill('input[name="name"]', 'A');
    await contactPage.fill('input[name="email"]', 'valid@example.com');
    await contactPage.fill('input[name="subject"]', 'B');
    await contactPage.fill('textarea[name="message"]', 'too short');

    await contactPage.waitForFunction(
        () => ! document.querySelector('button.form-btn').disabled,
        null,
        { timeout: 15_000 },
    );

    const invalidResp = contactPage.waitForResponse(
        (r) => r.url().endsWith('/contact') && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await contactPage.click('button.form-btn');
    await invalidResp;

    // Turbo renders the 422 response after the fetch completes, so wait for
    // the DOM to reflect it rather than reading a snapshot too early.
    await contactPage.waitForSelector('.form-error', { timeout: 15_000 });

    const invalidState = await contactPage.evaluate(() => ({
        errors: Array.from(document.querySelectorAll('.form-error')).map((e) => e.textContent.trim()),
        email: document.querySelector('input[name="email"]')?.value ?? '',
        invalid: document.querySelectorAll('.form-input.is-invalid').length,
        invalidAria: document.querySelectorAll('.form-input[aria-invalid="true"]').length,
    }));

    check(invalidState.errors.length > 0, 'contact: server validation errors rendered',
        invalidState.errors.join(' | ').slice(0, 140));
    check(invalidState.email === 'valid@example.com', 'contact: old input preserved', invalidState.email);
    check(invalidState.invalid >= 2, 'contact: invalid fields marked', String(invalidState.invalid));
    check(invalidState.invalidAria >= 2, 'contact: invalid fields exposed to assistive tech',
        String(invalidState.invalidAria));

    // Valid submission -> success notice.
    await contactPage.goto(BASE + '/contact', { waitUntil: 'networkidle' });
    await contactPage.fill('input[name="name"]', 'Browser QA Visitor');
    await contactPage.fill('input[name="email"]', 'qa-visitor@example.com');
    await contactPage.fill('input[name="subject"]', 'Browser QA subject');
    await contactPage.fill('textarea[name="message"]', 'This message was submitted by the Playwright suite.');

    await contactPage.waitForFunction(
        () => ! document.querySelector('button.form-btn').disabled,
        null,
        { timeout: 15_000 },
    );

    const validResp = contactPage.waitForResponse(
        (r) => r.url().endsWith('/contact') && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await contactPage.click('button.form-btn');
    await validResp;
    await contactPage.waitForSelector('.form-notice-success', { timeout: 15_000 });

    check(true, 'contact: success notice shown');
    check(await contactPage.$eval('input[name="name"]', (el) => el.value) === '',
        'contact: form is cleared after success');

    // Post/Redirect/Get: the response is a redirect, so a reload re-issues a
    // GET and cannot resubmit the form.
    const reloadResp = contactPage.waitForResponse(
        (r) => r.url().endsWith('/contact') && r.request().method() === 'GET',
        { timeout: 15_000 },
    );
    await contactPage.reload({ waitUntil: 'networkidle' });
    await reloadResp;

    check(await contactPage.$('.form-notice-success') === null,
        'contact: reload does not resubmit (no stale success notice)');
    check(await contactPage.$eval('input[name="name"]', (el) => el.value) === '',
        'contact: reload shows an empty form');

    // The message reached the existing Phase 4 inbox.
    await page.goto(BASE + '/admin/contact-messages', { waitUntil: 'networkidle' });
    check(await page.$eval('main', (el) => el.textContent.includes('Browser QA subject')),
        'contact: message appears in the admin inbox');
    check(await page.$eval('main', (el) => el.textContent.includes('qa-visitor@example.com')),
        'contact: inbox shows the visitor address');

    // Open the newest message (the row's own View link).
    const messageHref = await page.$eval('main a[href*="/contact-messages/"]',
        (el) => el.getAttribute('href'));
    await page.click(`main a[href="${messageHref}"]`);
    await page.waitForURL(/\/admin\/contact-messages\/\d+$/);

    await page.waitForFunction(
        () => document.querySelector('main article') !== null,
        null,
        { timeout: 15_000 },
    );

    check(await page.$eval('main', (el) => el.textContent.includes('This message was submitted by the Playwright suite.')),
        'contact: message body visible when opened');
    check(await page.$eval('main', (el) => el.textContent.includes('Browser QA Visitor')),
        'contact: sender name visible when opened');

    // The subject is the page heading, which lives in the admin header rather
    // than inside <main>.
    check(await page.$eval('header h1', (el) => el.textContent.trim()) === 'Browser QA subject',
        'contact: subject used as the page heading');

    await contactPage.close();

    // ---- Media: upload, metadata edit, delete -------------------------------
    await page.click('aside a[href$="/admin/media"]');
    await page.waitForURL('**/admin/media');
    const mediaEmpty = await page.$eval('main', (el) => el.textContent.includes('No media yet'));
    check(mediaEmpty, 'media: empty state renders');

    // Upload a real PNG (1x1 pixel) through the form input.
    await page.setInputFiles('input[name="file"]', {
        name: 'regression.png',
        mimeType: 'image/png',
        buffer: Buffer.from(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            'base64',
        ),
    });
    await page.fill('input[name="alt_text"]', 'Regression upload');
    const uploadResp = page.waitForResponse(
        (r) => r.url().endsWith('/admin/media') && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.click('form[enctype="multipart/form-data"] button[type="submit"]');
    await uploadResp;
    await page.waitForLoadState('networkidle');
    check(true, 'media: upload accepted');

    await page.reload({ waitUntil: 'networkidle' });
    const cardVisible = await page.$$eval('main img', (imgs) => imgs.some((img) => img.src.includes('/storage/media/')));
    check(cardVisible, 'media: uploaded image rendered from /storage');

    // Metadata edit
    await page.click('a[href$="/edit"]');
    await page.waitForURL('**/admin/media/**/edit');
    await page.fill('input[name="alt_text"]', 'Edited alt text');
    const editResp = page.waitForResponse(
        (r) => /\/admin\/media\/\d+$/.test(r.url()) && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.click('main form button[type="submit"]');
    await editResp;
    await page.waitForLoadState('networkidle');

    await page.reload({ waitUntil: 'networkidle' });
    check(await page.$eval('input[name="alt_text"]', (el) => el.value === 'Edited alt text'),
        'media: metadata edit persisted');

    // Delete (original + variants removed server-side)
    await page.click('aside a[href$="/admin/media"]');
    await page.waitForURL('**/admin/media');
    page.once('dialog', (d) => d.accept());
    const mediaDelete = page.waitForResponse(
        (r) => /\/admin\/media\/\d+$/.test(r.url()) && r.request().method() === 'POST',
        { timeout: 15_000 },
    );
    await page.locator('tbody form[data-turbo-confirm] button, form[data-turbo-confirm] button').last().click();
    await mediaDelete;
    await page.waitForLoadState('networkidle');
    await page.reload({ waitUntil: 'networkidle' });
    const cleared = await page.$eval('main', (el) => el.textContent.includes('No media yet'));
    check(cleared, 'media: delete clears the library');

    // ---- Auth guard after logout ---------------------------------------------
    await page.click('header form button');
    await page.waitForURL('**/login');
    await page.goto(BASE + '/admin/projects');
    check(page.url().includes('/login'), 'admin: logout revokes access', page.url());

    const realErrs = errs.filter((e) => !/failed to fetch/i.test(e));
    check(realErrs.length === 0, 'admin: no JS errors', realErrs.slice(0, 3).join(' | '));

    await browser.close();

    const passed = results.filter((r) => r.ok).length;
    console.log(`\nCHECKS: ${passed}/${results.length} passed`);
    if (failures.length) {
        console.log(`\nFAILURES (${failures.length}):`);
        failures.forEach((f) => console.log('  ✗ ' + f));
        process.exitCode = 1;
    } else {
        console.log('\nAll checks passed.');
    }
})();

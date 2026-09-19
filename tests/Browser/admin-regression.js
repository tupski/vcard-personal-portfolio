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

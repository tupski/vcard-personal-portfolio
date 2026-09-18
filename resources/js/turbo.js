/**
 * Turbo configuration.
 *
 * Progressive enhancement is the rule: every form and link must keep
 * working when Turbo is unavailable, so we only tune defaults here.
 */
import * as Turbo from '@hotwired/turbo';

// Cache is disabled in local development so freshly edited pages are
// never served from a stale Turbo snapshot.
if (import.meta.env.DEV) {
    Turbo.session.drive = true;
}

// Let the flash/toast layer react to every Turbo visit.
document.addEventListener('turbo:load', () => {
    document.documentElement.setAttribute('data-turbo-ready', 'true');
});

import { Controller } from '@hotwired/stimulus';

/**
 * Flash message behaviour.
 *
 * Messages rendered by `<x-ui.flash />` auto-dismiss so the admin UI does
 * not accumulate stale banners across Turbo visits.
 */
export default class extends Controller {
    static values = {
        timeout: { type: Number, default: 6000 },
    };

    connect() {
        if (this.timeoutValue > 0) {
            this.timer = setTimeout(() => this.dismiss(), this.timeoutValue);
        }
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    dismiss(event) {
        if (event) {
            event.preventDefault();
        }

        this.element.remove();
    }
}

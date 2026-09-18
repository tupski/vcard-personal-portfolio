import { Controller } from '@hotwired/stimulus';

/**
 * Turbo lifecycle bridge.
 *
 * Gives the rest of the app a stable hook for behaviour that must run
 * after every Turbo navigation (not just the first page load), and keeps
 * the turbo progress bar hidden in favour of our own flash messaging.
 */
export default class extends Controller {
    connect() {
        this.onLoad = () => {
            this.element.setAttribute('data-turbo-ready', 'true');
        };

        this.onBeforeCache = () => {
            this.element.removeAttribute('data-turbo-ready');
        };

        document.addEventListener('turbo:load', this.onLoad);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);
    }

    disconnect() {
        document.removeEventListener('turbo:load', this.onLoad);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);
    }
}

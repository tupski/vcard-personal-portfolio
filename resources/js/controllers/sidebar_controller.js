import { Controller } from '@hotwired/stimulus';

/**
 * Mobile sidebar disclosure.
 *
 * Port of the original `data-sidebar` / `data-sidebar-btn` toggle, which just
 * flipped an `active` class. The CSS drives the height/opacity transition.
 */
export default class extends Controller {
    static targets = ['toggle'];

    toggle() {
        const isOpen = this.element.classList.toggle('active');

        this.toggleTargets.forEach((button) => {
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }
}

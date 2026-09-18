import { Controller } from '@hotwired/stimulus';

/**
 * Portfolio category filter.
 *
 * Port of the original `data-filter-btn` / `data-select-item` logic. The
 * original compared each button's lower-cased label against a project's
 * `data-category`; we read an explicit `data-category` off the button instead
 * so the label can be localised without breaking filtering.
 */
export default class extends Controller {
    static targets = ['item', 'button', 'select', 'value'];

    connect() {
        this.apply({ currentTarget: this.buttonTargets[0] });
    }

    /** Toggle the custom select dropdown (mobile). */
    toggleSelect() {
        this.selectTarget.classList.toggle('active');
    }

    /** Apply a filter from either the tab list or the select list. */
    apply(event) {
        const trigger = event.currentTarget;
        const category = (trigger.dataset.category || '').toLowerCase();

        this.valueTargets.forEach((value) => {
            value.innerText = trigger.innerText;
        });

        this.itemTargets.forEach((item) => {
            const matches = category === 'all'
                || item.dataset.category === category;

            item.classList.toggle('active', matches);
        });

        // Only the tab list carries the persistent active state.
        if (this.buttonTargets.includes(trigger)) {
            this.buttonTargets.forEach((button) => button.classList.remove('active'));
            trigger.classList.add('active');
        }

        this.selectTarget.classList.remove('active');
    }

    /** Keyboard support for the custom select. */
    closeOnEscape(event) {
        if (event.key === 'Escape') {
            this.selectTarget.classList.remove('active');
        }
    }
}

import { Controller } from '@hotwired/stimulus';

/**
 * Contact form validation.
 *
 * Port of the original `data-form-input` / `data-form-btn` behaviour: the
 * submit button stays disabled until the browser reports the form valid.
 *
 * Submission itself is wired up in Phase 7 (contact storage + mail); until
 * then the form keeps the original template's inert `action="#"`.
 */
export default class extends Controller {
    static targets = ['submit'];

    validate() {
        if (this.element.checkValidity()) {
            this.submitTarget.removeAttribute('disabled');
        } else {
            this.submitTarget.setAttribute('disabled', '');
        }
    }
}

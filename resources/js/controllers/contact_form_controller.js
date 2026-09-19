import { Controller } from '@hotwired/stimulus';

/**
 * Contact form behaviour.
 *
 * Port of the original `data-form-input` / `data-form-btn` behaviour: with
 * JavaScript available the submit button stays disabled until the browser
 * reports the form valid, exactly like the template did.
 *
 * The button is rendered *enabled* in the markup so the form still submits
 * when JavaScript is unavailable — the original template could mark it
 * `disabled` because its form never submitted anything, but here that would
 * leave a no-JS visitor unable to send a message at all.
 *
 * Server-side validation remains authoritative. This controller never
 * duplicates Laravel's rules; it only mirrors the original UX and prevents a
 * double submission while a request is in flight.
 */
export default class extends Controller {
    static targets = ['submit', 'label'];

    static values = {
        sendingLabel: { type: String, default: 'Sending…' },
    };

    connect() {
        this.originalLabel = this.hasLabelTarget ? this.labelTarget.textContent : null;
        this.inFlight = false;

        // Apply the template's disabled-until-valid behaviour now that JS is
        // running. Before this point the button must stay usable.
        this.validate();
    }

    /**
     * Enable the button only when the browser considers the form valid.
     */
    validate() {
        if (this.inFlight || ! this.hasSubmitTarget) {
            return;
        }

        if (this.element.checkValidity()) {
            this.submitTarget.removeAttribute('disabled');
        } else {
            this.submitTarget.setAttribute('disabled', '');
        }
    }

    /**
     * Lock the form for the duration of the request.
     *
     * Turbo replaces the document body on both success and validation
     * failure, so the controller is torn down and this state resets itself.
     */
    submit() {
        if (this.inFlight) {
            return;
        }

        this.inFlight = true;

        if (this.hasSubmitTarget) {
            this.submitTarget.setAttribute('aria-busy', 'true');
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = this.sendingLabelValue;
        }
    }

    /**
     * Turbo finished the request (success, validation failure or error):
     * release the lock so the visitor can act on the response.
     */
    restore() {
        this.inFlight = false;

        if (this.hasSubmitTarget) {
            this.submitTarget.removeAttribute('aria-busy');
        }

        if (this.hasLabelTarget && this.originalLabel !== null) {
            this.labelTarget.textContent = this.originalLabel;
        }

        this.validate();
    }
}

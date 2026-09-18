import { Controller } from '@hotwired/stimulus';

/**
 * Testimonials modal.
 *
 * Port of the original `data-modal-container` behaviour. The original copied
 * the avatar/title/text out of the clicked card using `innerHTML`; we do the
 * same via targets so the markup stays declarative.
 */
export default class extends Controller {
    static targets = ['modal', 'overlay', 'img', 'title', 'date', 'text'];

    connect() {
        this.onKeydown = (event) => {
            if (event.key === 'Escape' && this.isOpen) {
                this.close();
            }
        };

        document.addEventListener('keydown', this.onKeydown);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKeydown);
    }

    get isOpen() {
        return this.modalTarget.classList.contains('active');
    }

    open(event) {
        // The cards are keyboard-activatable, so suppress Space page-scroll.
        if (event.type === 'keydown') {
            event.preventDefault();
        }

        const card = event.currentTarget;

        const avatar = card.querySelector('[data-testimonials-avatar]');
        const title = card.querySelector('[data-testimonials-title]');
        const text = card.querySelector('[data-testimonials-text]');
        const date = card.querySelector('[data-testimonials-date]');

        if (avatar) {
            this.imgTarget.src = avatar.src;
            this.imgTarget.alt = avatar.alt;
        }

        if (title) {
            this.titleTarget.innerHTML = title.innerHTML;
        }

        if (text) {
            this.textTarget.innerHTML = text.innerHTML;
        }

        if (date) {
            this.dateTarget.innerHTML = date.innerHTML;

            if (date.getAttribute('datetime')) {
                this.dateTarget.setAttribute('datetime', date.getAttribute('datetime'));
            }
        }

        this.modalTarget.classList.add('active');
        this.overlayTarget.classList.add('active');

        this.lastFocused = document.activeElement;

        const closeButton = this.modalTarget.querySelector('[data-testimonials-target="close"]');
        closeButton?.focus();
    }

    close() {
        this.modalTarget.classList.remove('active');
        this.overlayTarget.classList.remove('active');

        this.lastFocused?.focus();
    }
}

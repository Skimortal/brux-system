import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'menu'];

    connect() {
        this.outside = (event) => {
            if (!this.element.contains(event.target)) this.close();
        };
        this.escape = (event) => {
            if (event.key === 'Escape') this.close();
        };
        document.addEventListener('click', this.outside);
        document.addEventListener('keydown', this.escape);
    }

    disconnect() {
        document.removeEventListener('click', this.outside);
        document.removeEventListener('keydown', this.escape);
    }

    toggle() {
        this.menuTarget.hidden ? this.open() : this.close();
    }

    open() {
        this.menuTarget.hidden = false;
        this.buttonTarget.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.menuTarget.hidden = true;
        this.buttonTarget.setAttribute('aria-expanded', 'false');
    }
}

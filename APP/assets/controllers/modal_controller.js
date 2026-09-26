import { Controller } from '@hotwired/stimulus';

/* Appointment dialog on top of the calendar: Esc or a click on the backdrop closes it. */
export default class extends Controller {
    static targets = ['close'];

    connect() {
        document.documentElement.classList.add('has-modal');
        const field = this.element.querySelector('.modal input:not([type=hidden]):not([type=radio]), .modal select, .modal textarea');
        this.element.querySelector('.modal')?.focus?.();
        if (field && !field.value) field.focus();
    }

    disconnect() {
        document.documentElement.classList.remove('has-modal');
    }

    backdrop(event) {
        if (event.target === this.element) this.close();
    }

    escape(event) {
        if (event.key !== 'Escape' || document.querySelector('.ts-dropdown:not([style*="display: none"])')) return;
        this.close();
    }

    close() {
        if (this.hasCloseTarget) this.closeTarget.click();
    }
}

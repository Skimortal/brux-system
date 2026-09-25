import { Controller } from '@hotwired/stimulus';

/* Disables the submit button while a slow request is running. */
export default class extends Controller {
    static targets = ['button'];

    connect() {
        this.element.addEventListener('submit', () => {
            if (!this.hasButtonTarget) return;
            this.buttonTarget.disabled = true;
            this.buttonTarget.classList.add('is-disabled');
            this.buttonTarget.insertAdjacentText('beforeend', ' …');
        });
    }
}

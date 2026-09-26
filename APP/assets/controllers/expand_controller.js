import { Controller } from '@hotwired/stimulus';

/* Long text shown as a preview; the button appears only when there is more to read. */
export default class extends Controller {
    static targets = ['text', 'button'];

    connect() {
        requestAnimationFrame(() => {
            const overflowing = this.textTarget.scrollHeight > this.textTarget.clientHeight + 4;
            this.buttonTarget.hidden = !overflowing;
            if (!overflowing) this.element.classList.add('is-short');
        });
    }

    toggle() {
        const expanded = this.element.classList.toggle('is-expanded');
        this.buttonTarget.firstChild.textContent = expanded ? 'Weniger anzeigen ' : 'Mehr anzeigen ';
        this.buttonTarget.setAttribute('aria-expanded', String(expanded));
    }
}

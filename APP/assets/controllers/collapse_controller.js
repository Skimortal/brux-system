import { Controller } from '@hotwired/stimulus';

/* Folds a block open and shut; the browser remembers it per key (e.g. one todo group). */
export default class extends Controller {
    static values = { key: String };
    static targets = ['button'];

    connect() {
        this.apply(this.stored());
    }

    toggle() {
        const collapsed = !this.element.classList.contains('is-collapsed');
        this.apply(collapsed);
        try {
            collapsed ? localStorage.setItem(this.storageKey, '1') : localStorage.removeItem(this.storageKey);
        } catch (e) {
            // without storage it just does not stay folded after reloading
        }
    }

    apply(collapsed) {
        this.element.classList.toggle('is-collapsed', collapsed);
        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    }

    stored() {
        try {
            return localStorage.getItem(this.storageKey) === '1';
        } catch (e) {
            return false;
        }
    }

    get storageKey() {
        return `collapsed:${this.keyValue}`;
    }
}

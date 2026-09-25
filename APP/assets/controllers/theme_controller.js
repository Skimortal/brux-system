import { Controller } from '@hotwired/stimulus';

/* Light/dark switch, kept per browser (like the switch in the previous version). */
const KEY = 'brux.theme';

export default class extends Controller {
    static targets = ['label'];

    connect() {
        this.render();
    }

    toggle() {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = next;
        try { localStorage.setItem(KEY, next); } catch { /* ignore */ }
        this.render();
    }

    render() {
        if (this.hasLabelTarget) {
            this.labelTarget.textContent = document.documentElement.dataset.theme === 'dark' ? 'Heller Modus' : 'Dunkler Modus';
        }
    }
}

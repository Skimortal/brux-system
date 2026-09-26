import { Controller } from '@hotwired/stimulus';

/* Filter of the key list by status (Verliehen / Verfügbar / Verloren), kept per browser. */
const KEY = 'brux.keys.filter';

export default class extends Controller {
    static targets = ['box', 'row', 'empty'];

    connect() {
        let stored = null;
        try { stored = JSON.parse(localStorage.getItem(KEY) ?? 'null'); } catch { stored = null; }
        if (Array.isArray(stored)) {
            this.boxTargets.forEach((box) => { box.checked = stored.includes(box.value); });
        }
        this.apply();
    }

    update() {
        const on = this.boxTargets.filter((box) => box.checked).map((box) => box.value);
        try { localStorage.setItem(KEY, JSON.stringify(on)); } catch { /* ignore */ }
        this.apply();
    }

    apply() {
        const on = this.boxTargets.filter((box) => box.checked).map((box) => box.value);
        let visible = 0;
        this.rowTargets.forEach((row) => {
            row.hidden = !on.includes(row.dataset.status);
            visible += row.hidden ? 0 : 1;
        });
        if (this.hasEmptyTarget) this.emptyTarget.hidden = visible > 0 || this.rowTargets.length === 0;
    }
}

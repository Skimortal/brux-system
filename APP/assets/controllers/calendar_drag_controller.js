import { Controller } from '@hotwired/stimulus';

/*
 * Drag a performance onto another day (and, in room rows, another room) to move it.
 * The start time stays. Performances with reservations ask first, because the people
 * who booked are not told automatically.
 */
export default class extends Controller {
    static values = { token: String, return: String };

    connect() {
        this.dragged = null;
        this.target = null;
        this.listeners = {
            dragstart: (event) => this.start(event),
            dragend: () => this.end(),
            dragover: (event) => this.over(event),
            dragleave: (event) => this.leave(event),
            drop: (event) => this.drop(event),
        };
        Object.entries(this.listeners).forEach(([name, listener]) => this.element.addEventListener(name, listener));
    }

    disconnect() {
        Object.entries(this.listeners).forEach(([name, listener]) => this.element.removeEventListener(name, listener));
    }

    start(event) {
        const item = event.target.closest?.('[data-move-url]');
        if (!item) return;
        this.dragged = item;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.dataset.panel);
        requestAnimationFrame(() => item.classList.add('is-dragging'));
    }

    end() {
        this.dragged?.classList.remove('is-dragging');
        this.dragged = null;
        this.highlight(null);
    }

    over(event) {
        if (!this.dragged) return;
        const cell = event.target.closest('[data-date]:not([data-move-url])');
        if (!cell) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        this.highlight(cell);
    }

    leave(event) {
        if (this.target && !this.target.contains(event.relatedTarget)) this.highlight(null);
    }

    drop(event) {
        const item = this.dragged;
        const cell = event.target.closest('[data-date]:not([data-move-url])');
        if (!item || !cell) return;
        event.preventDefault();
        this.end();

        const date = cell.dataset.date;
        const room = cell.dataset.room || '';
        if (date === item.dataset.date && (room === '' || room === item.dataset.room)) return;

        const seats = Number(item.dataset.seats || 0);
        const when = new Date(`${date}T12:00`).toLocaleDateString('de-AT', { weekday: 'long', day: 'numeric', month: 'long' });
        if (seats > 0 && !window.confirm(`Für diese Vorstellung sind ${seats} Plätze reserviert. Die Reservierenden werden nicht automatisch informiert.\n\nTrotzdem auf ${when} verschieben?`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'post';
        form.action = item.dataset.moveUrl;
        form.dataset.turboFrame = '_top';
        form.hidden = true;
        Object.entries({ date, room, _token: this.tokenValue, _return: this.returnValue }).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.append(input);
        });
        document.body.append(form);
        form.requestSubmit();
    }

    highlight(cell) {
        if (this.target === cell) return;
        this.target?.classList.remove('is-drop-target');
        this.target = cell;
        cell?.classList.add('is-drop-target');
    }
}

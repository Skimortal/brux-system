import { Controller } from '@hotwired/stimulus';

/*
 * Click on an empty spot of a day/room cell opens a menu to create an entry with
 * date and room already filled in. Also highlights the selected entry.
 */
export default class extends Controller {
    static targets = ['popover', 'label', 'link'];
    static values = { appointmentUrl: String, loanUrl: String, performanceUrl: String };

    connect() {
        this.escape = (event) => { if (event.key === 'Escape') this.close(); };
        document.addEventListener('keydown', this.escape);
    }

    disconnect() {
        document.removeEventListener('keydown', this.escape);
    }

    open(event) {
        if (event.target.closest('a, button, .popover')) return;
        const cell = event.target.closest('[data-date]');
        if (!cell) {
            this.close();
            return;
        }
        this.show(cell.dataset.date, cell.dataset.room, cell.dataset.label, event.clientX, event.clientY);
    }

    openDefault(event) {
        const rect = event.currentTarget.getBoundingClientRect();
        const date = event.currentTarget.dataset.date;
        const label = new Date(`${date}T12:00`).toLocaleDateString('de-AT', { weekday: 'short', day: 'numeric', month: 'numeric' });
        this.show(date, '', label, rect.right - 300, rect.bottom + 8);
    }

    show(date, room, label, x, y) {
        const start = `${date}T${this.defaultHour(date)}`;
        this.labelTarget.textContent = label;
        this.linkTargets.forEach((link) => {
            if (link.dataset.kind === 'loan') {
                const url = new URL(this.loanUrlValue, window.location.origin);
                link.href = url.pathname + url.search;
                return;
            }
            const performance = link.dataset.kind === 'performance';
            const url = new URL(performance ? this.performanceUrlValue : this.appointmentUrlValue, window.location.origin);
            if (!performance) url.searchParams.set('type', link.dataset.type);
            url.searchParams.set('start', performance ? `${date}T19:30` : start);
            if (room) url.searchParams.set('room', room); else url.searchParams.delete('room');
            link.href = url.pathname + url.search;
        });
        const popover = this.popoverTarget;
        popover.hidden = false;
        const width = popover.offsetWidth;
        const height = popover.offsetHeight;
        popover.style.left = `${Math.max(8, Math.min(x, window.innerWidth - width - 8))}px`;
        popover.style.top = `${Math.max(8, Math.min(y, window.innerHeight - height - 8))}px`;
        popover.querySelector('a')?.focus();
    }

    defaultHour(date) {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const today = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
        if (date === today && now.getHours() >= 8 && now.getHours() < 22) {
            return `${String(now.getHours() + 1).padStart(2, '0')}:00`;
        }
        return '10:00';
    }

    close() {
        this.popoverTarget.hidden = true;
    }

    select(event) {
        this.close();
        this.element.querySelectorAll('.cal-item.is-selected').forEach((el) => el.classList.remove('is-selected'));
        event.currentTarget.classList.add('is-selected');
    }
}

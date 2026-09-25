import { Controller } from '@hotwired/stimulus';

/* Keeps the scroll position of the calendar board across Turbo visits. */
const positions = new Map();

export default class extends Controller {
    connect() {
        const saved = positions.get('board');
        if (saved) {
            this.element.scrollLeft = saved.left;
            this.element.scrollTop = saved.top;
        }
        this.save = () => positions.set('board', { left: this.element.scrollLeft, top: this.element.scrollTop });
        this.element.addEventListener('scroll', this.save, { passive: true });
    }

    disconnect() {
        this.save?.();
        this.element.removeEventListener('scroll', this.save);
    }
}

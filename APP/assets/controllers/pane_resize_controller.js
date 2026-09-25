import { Controller } from '@hotwired/stimulus';

/*
 * Drag handle between the calendar and the side column, like the pane handles in the
 * dataflow mapping workbench: drag to resize, double-click to reset, arrow keys to nudge.
 *
 * The width lives in a CSS variable on <html>, not on the shell: Turbo morphs the body on
 * every save, which would wipe an inline style there. It is kept in localStorage because it
 * belongs to the person and screen, not to the data. Every storage access is guarded,
 * private windows throw.
 */
const KEY = 'brux.pane.side-width';
const DEFAULT = 380;
const MIN = 300;
const STEP = 24;

export function readWidth() {
    try {
        const value = Number.parseInt(localStorage.getItem(KEY) ?? '', 10);
        return Number.isFinite(value) ? value : null;
    } catch {
        return null;
    }
}

function writeWidth(value) {
    try {
        localStorage.setItem(KEY, String(Math.round(value)));
    } catch {
        // not worth failing over
    }
}

export default class extends Controller {
    connect() {
        this.apply(readWidth() ?? DEFAULT);
    }

    get max() {
        // leave the calendar at least ~560px
        return Math.max(MIN, Math.min(900, window.innerWidth - 72 - 560));
    }

    clamp(value) {
        return Math.min(this.max, Math.max(MIN, value));
    }

    apply(value) {
        this.width = this.clamp(value);
        document.documentElement.style.setProperty('--panel-w', `${this.width}px`);
        this.element.setAttribute('aria-valuenow', String(this.width));
        this.element.setAttribute('aria-valuemin', String(MIN));
        this.element.setAttribute('aria-valuemax', String(this.max));
    }

    start(event) {
        if (event.detail >= 2) {
            event.preventDefault();
            this.reset();
            return;
        }
        this.drag = { x: event.clientX, width: this.width };
        try { this.element.setPointerCapture(event.pointerId); } catch { /* synthetic events have no active pointer */ }
        this.element.classList.add('is-dragging');
        document.body.classList.add('is-resizing');
        event.preventDefault();
    }

    move(event) {
        if (!this.drag) return;
        // the handle is on the left edge of the side column: dragging left widens it
        this.apply(this.drag.width - (event.clientX - this.drag.x));
    }

    end(event) {
        if (this.drag) {
            this.drag = null;
            writeWidth(this.width);
        }
        try { this.element.releasePointerCapture(event.pointerId); } catch { /* not captured */ }
        this.element.classList.remove('is-dragging');
        document.body.classList.remove('is-resizing');
    }

    reset() {
        this.apply(DEFAULT);
        writeWidth(DEFAULT);
    }

    key(event) {
        const delta = { ArrowLeft: STEP, ArrowRight: -STEP }[event.key];
        if (delta !== undefined) {
            event.preventDefault();
            this.apply(this.width + delta);
            writeWidth(this.width);
        } else if (event.key === 'Home' || event.key === 'Enter') {
            event.preventDefault();
            this.reset();
        }
    }
}

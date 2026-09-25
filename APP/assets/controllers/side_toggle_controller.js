import { Controller } from '@hotwired/stimulus';

/*
 * Hides the right column so the room calendars get the whole screen. Opening an entry
 * (the side frame loads a panel) brings the column back. Kept per browser.
 */
const KEY = 'brux.side.collapsed';

export function setCollapsed(collapsed) {
    if (collapsed) {
        document.documentElement.dataset.side = 'collapsed';
    } else {
        delete document.documentElement.dataset.side;
    }
    try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch { /* ignore */ }
    window.dispatchEvent(new Event('resize'));
}

export default class extends Controller {
    toggle() {
        setCollapsed(document.documentElement.dataset.side !== 'collapsed');
    }
}

document.addEventListener('turbo:frame-load', (event) => {
    if (event.target.id === 'side' && event.target.classList.contains('has-panel')) {
        setCollapsed(false);
    }
});

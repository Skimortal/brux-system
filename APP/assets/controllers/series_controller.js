import { Controller } from '@hotwired/stimulus';

/* Keeps the current performance visible in the scrollable list of its series. */
export default class extends Controller {
    static targets = ['list', 'current'];

    connect() {
        if (!this.hasCurrentTarget) return;
        const list = this.listTarget;
        const item = this.currentTarget;
        list.scrollTop = item.offsetTop - list.offsetTop - list.clientHeight / 2 + item.clientHeight / 2;
    }
}

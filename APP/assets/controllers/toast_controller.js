import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.timer = setTimeout(() => this.close(), this.element.classList.contains('is-danger') ? 9000 : 4500);
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    close() {
        this.element.remove();
    }
}

import { Controller } from '@hotwired/stimulus';

/* A switch on a saved record takes effect at once: flipping it saves its form. */
export default class extends Controller {
    save() {
        this.element.form?.requestSubmit();
    }
}

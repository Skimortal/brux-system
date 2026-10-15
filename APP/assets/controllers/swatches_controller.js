import { Controller } from '@hotwired/stimulus';

/* Picking a colour in the picker selects the own colour. */
export default class extends Controller {
    static targets = ['own', 'picker'];

    pick() {
        this.ownTarget.checked = true;
    }
}

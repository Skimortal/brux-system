import { Controller } from '@hotwired/stimulus';

/* Shows the holder field that matches "An wen". */
export default class extends Controller {
    static targets = ['contact', 'user', 'other'];

    connect() {
        this.update();
    }

    update() {
        const kind = this.element.querySelector('input[name$="[holderKind]"]:checked')?.value ?? 'contact';
        this.contactTargets.forEach((el) => { el.hidden = kind !== 'contact'; });
        this.userTargets.forEach((el) => { el.hidden = kind !== 'user'; });
        this.otherTargets.forEach((el) => { el.hidden = kind !== 'other'; });
    }
}

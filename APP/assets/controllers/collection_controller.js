import { Controller } from '@hotwired/stimulus';

/* Adds and removes rows of a Symfony CollectionType from its prototype. */
export default class extends Controller {
    static targets = ['list', 'prototype', 'item', 'empty'];
    static values = { index: Number, placeholder: { type: String, default: '__name__' } };

    add() {
        const html = this.prototypeTarget.innerHTML.replaceAll(this.placeholderValue, String(this.indexValue++));
        this.listTarget.insertAdjacentHTML('beforeend', html);
        this.refresh();
        const select = this.listTarget.lastElementChild?.querySelector('select, input');
        select?.focus();
    }

    remove(event) {
        event.currentTarget.closest('[data-collection-target="item"]')?.remove();
        this.refresh();
    }

    refresh() {
        if (this.hasEmptyTarget) this.emptyTarget.hidden = this.itemTargets.length > 0;
    }
}

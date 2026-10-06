import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/*
 * Searchable select. Always built fresh: a select restored from the Turbo cache or
 * kept by a morph still carries the old instance, whose markup is gone. The dropdown
 * opens on the body, so a scrolled side panel cannot clip it.
 */
export default class extends Controller {
    connect() {
        this.element.tomselect?.destroy();
        this.select = new TomSelect(this.element, {
            plugins: this.element.multiple ? ['remove_button'] : [],
            allowEmptyOption: true,
            maxOptions: 500,
            create: false,
            dropdownParent: 'body',
        });
        // keep other controllers (appointment-form) informed
        this.select.on('change', () => this.element.dispatchEvent(new Event('change', { bubbles: true })));
        this.beforeCache = () => this.teardown();
        document.addEventListener('turbo:before-cache', this.beforeCache);
    }

    disconnect() {
        document.removeEventListener('turbo:before-cache', this.beforeCache);
        this.teardown();
    }

    teardown() {
        this.select?.destroy();
        this.select = null;
    }
}

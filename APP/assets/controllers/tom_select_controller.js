import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    connect() {
        if (this.element.tomselect) return;
        this.select = new TomSelect(this.element, {
            plugins: this.element.multiple ? ['remove_button'] : [],
            allowEmptyOption: true,
            maxOptions: 500,
            create: false,
        });
        // keep other controllers (appointment-form) informed
        this.select.on('change', () => this.element.dispatchEvent(new Event('change', { bubbles: true })));
    }

    disconnect() {
        this.select?.destroy();
    }
}

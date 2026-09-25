import { Controller } from '@hotwired/stimulus';

/*
 * Shows the fields that belong to the selected appointment type, keeps the end after
 * the start and shows the requirements of the selected production.
 */
const ASSIGNMENT_TYPES = ['production', 'closed_event', 'school_event', 'internal'];

export default class extends Controller {
    static targets = ['title', 'production', 'room', 'cleaning', 'productionOnly', 'assign', 'start', 'end', 'repeat', 'repeatUntil', 'needs'];

    connect() {
        this.update();
    }

    get type() {
        return this.element.querySelector('input[name$="[type]"]:checked')?.value ?? 'private';
    }

    update() {
        const type = this.type;
        this.toggle(this.titleTargets, !['production', 'cleaning'].includes(type));
        this.toggle(this.productionTargets, type === 'production');
        this.toggle(this.productionOnlyTargets, type === 'production');
        this.toggle(this.roomTargets, type !== 'cleaning');
        this.toggle(this.cleaningTargets, type === 'cleaning');
        this.toggle(this.assignTargets, ASSIGNMENT_TYPES.includes(type));
        if (this.hasRepeatUntilTarget && this.hasRepeatTarget) {
            this.toggle(this.repeatUntilTargets, this.repeatTarget.value !== '');
        }
        this.showNeeds(type);
    }

    startChanged() {
        if (!this.hasStartTarget || !this.hasEndTarget || !this.startTarget.value) return;
        const start = new Date(this.startTarget.value);
        const end = this.endTarget.value ? new Date(this.endTarget.value) : null;
        if (!end || end <= start) {
            const next = new Date(start.getTime() + 60 * 60 * 1000);
            this.endTarget.value = this.toLocalInput(next);
        }
    }

    showNeeds(type) {
        if (!this.hasNeedsTarget) return;
        const select = this.element.querySelector('select[name$="[production]"]');
        const needs = select?.selectedOptions[0]?.dataset.needs;
        this.needsTarget.hidden = type !== 'production' || !needs;
        this.needsTarget.textContent = needs ? `Benötigt: ${needs}` : '';
    }

    toggle(elements, visible) {
        elements.forEach((element) => {
            element.hidden = !visible;
        });
    }

    toLocalInput(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }
}

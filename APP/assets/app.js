// JavaScript
import './bootstrap.js';
import './styles/app.css';
import 'adminator-admin-dashboard/src/assets/scripts/app.js';
import './dashboard.js';
import './datatables.js';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

function initAdminator() {
    if (window.AdminatorApp && typeof window.AdminatorApp.init === 'function') {
        window.AdminatorApp.refresh();
    }
}

function initTomSelect() {
    document.querySelectorAll('.tom-select').forEach((el) => {
        if (el.dataset.tomSelectInitialized === '1') return;
        el.dataset.tomSelectInitialized = '1';
        if (el.tomselect && typeof el.tomselect.destroy === 'function') {
            el.tomselect.destroy();
        }
        new TomSelect(el, {
            plugins: ['remove_button'],
            create: false,
            maxItems: null,
            placeholder: el.getAttribute('data-placeholder') || 'Bitte auswählen',
        });
    });
}

function boot() {
    initTomSelect();
    initAdminator();
}

document.addEventListener('turbo:visit', boot);

// Dein vorhandenes Setup bleibt
(function setupConfirmDeleteOnce() {
    if (window.__confirmDeleteBound) return;
    window.__confirmDeleteBound = true;
    document.addEventListener('click', (event) => {
        if (event.button !== 0) return;
        const trigger = event.target.closest('[data-confirm], [data-confirm-message], .confirmRemoveItem');
        if (!trigger || !document.contains(trigger)) return;
        const message =
            trigger.getAttribute('data-confirm-message') ||
            trigger.getAttribute('data-confirm') ||
            'Eintrag wirklich löschen?';
        const confirmed = window.confirm(message);
        if (!confirmed) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        if (trigger.tagName === 'A') {
            const href = trigger.getAttribute('href');
            if (href) {
                event.preventDefault();
                event.stopImmediatePropagation();
                if (window.Turbo && typeof window.Turbo.visit === 'function') {
                    window.Turbo.visit(href);
                } else {
                    window.location.assign(href);
                }
            }
        }
    }, true);
})();

// JavaScript
import './bootstrap.js';
import './styles/app.css';
import 'adminator-admin-dashboard/src/assets/scripts/app.js';
// import './dashboard.js';
import './datatables.js';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import Masonry from 'masonry-layout';
import { initDaterangepickers, destroyDaterangepickers } from './daterangepicker-init.js';
import { initProductionCalendar } from './production-calendar.js';

window.initProductionCalendar = initProductionCalendar;

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

window.initTomSelect = initTomSelect;

function initProductionTypeToggle() {
    const typeSelect = document.querySelector('#production_type');
    const groupFields = document.getElementById('group-fields');
    const individualFields = document.getElementById('individual-fields');

    if (!typeSelect || !groupFields || !individualFields) return;

    function toggleFields() {
        const selectedValue = typeSelect.value;
        console.log('toggleFields', selectedValue);

        // Hide both initially
        groupFields.style.display = 'none';
        individualFields.style.display = 'none';

        // Show based on selection
        if (selectedValue === 'group') {
            groupFields.style.display = 'block';
        } else if (selectedValue === 'individual') {
            individualFields.style.display = 'block';
        }
    }

    // Remove old event listener if exists
    if (typeSelect._productionTypeToggleListener) {
        typeSelect.removeEventListener('change', typeSelect._productionTypeToggleListener);
    }

    // Store reference to listener for cleanup
    typeSelect._productionTypeToggleListener = toggleFields;
    typeSelect.addEventListener('change', toggleFields);

    // Initial call to set correct state
    toggleFields();
}

function initBody() {
    if (window.innerWidth < 768) {
        document.body.classList.remove('is-collapsed');
    }
}

function hasDashboardCalendarOnPage() {
    return !!document.getElementById('global-calendar') || document.querySelectorAll('.room-calendar').length > 0;
}

async function bootDashboardIfPresent() {
    if (!hasDashboardCalendarOnPage()) return;

    // verhindert mehrfaches Import/Boot bei Turbo-Events
    if (window.__dashboardBootedOnce) return;
    window.__dashboardBootedOnce = true;

    const mod = await import('./dashboard.js');
    if (mod && typeof mod.bootDashboard === 'function') {
        mod.bootDashboard();
    }
}

function initContactCategoryFilter() {
    const filterButtons = document.querySelectorAll('.category-filter-btn');
    const clearButton = document.getElementById('clear-filters');
    const filterBlock = document.getElementById('contact-filter-block');

    if (filterButtons.length === 0 || !filterBlock) return;

    // Setze initialen Status der Buttons basierend auf data-selected
    filterButtons.forEach(btn => {
        if (btn.dataset.selected === '1') {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        }
    });

    function applyFilter() {
        const selectedIds = Array.from(filterButtons)
            .filter(btn => btn.classList.contains('btn-primary'))
            .map(btn => btn.dataset.categoryId);

        const queryString = selectedIds.length > 0 ? `?categories=${selectedIds.join(',')}` : '';

        // AJAX-Request
        fetch(`/contact${queryString}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(response => response.text())
            .then(html => {
                const contactsContainer = document.getElementById('contacts-container');
                if (contactsContainer) {
                    contactsContainer.outerHTML = html;

                    const masonryElement = document.querySelector('.masonry');
                    if (masonryElement) {
                        new Masonry(masonryElement, {
                            itemSelector: '.masonry-item',
                            columnWidth: '.masonry-sizer',
                            percentPosition: true,
                        });
                    }
                }
            })
            .catch(error => console.error('Filter error:', error));
    }

    // Guard: Eventlistener nur einmal hinzufügen
    if (filterBlock.dataset.filterInitialized === '1') return;
    filterBlock.dataset.filterInitialized = '1';

    // Eventlistener für Filter-Buttons
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.classList.toggle('btn-outline-primary');
            btn.classList.toggle('btn-primary');
            applyFilter();
        });
    });

    // Clear Button
    if (clearButton) {
        clearButton.addEventListener('click', () => {
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
                btn.dataset.selected = '0';
            });
            applyFilter();
        });
    }
}

window.initContactCategoryFilter = initContactCategoryFilter;

function boot() {
    initTomSelect();
    initAdminator();
    initProductionTypeToggle();
    initDaterangepickers();
    initBody();
}
function bootAlways() {
    initTomSelect();
    initProductionTypeToggle();
    initDaterangepickers();
    initBody();
    initContactCategoryFilter();
    bootDashboardIfPresent();
}

document.addEventListener('DOMContentLoaded', bootAlways);
document.addEventListener('turbo:load', bootAlways);
document.addEventListener('turbo:render', bootAlways);
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

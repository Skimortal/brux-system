import moment from 'moment';
import 'moment/locale/de';
import daterangepicker from 'daterangepicker';
import 'daterangepicker/daterangepicker.css';

// Moment auf Deutsch setzen
moment.locale('de');

if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
    const originalEnforceFocus = jQuery.fn.modal.Constructor.prototype._enforceFocus;
    jQuery.fn.modal.Constructor.prototype._enforceFocus = function() {
        // Nur enforcen wenn kein Daterangepicker offen ist
        if (document.querySelector('.daterangepicker:not(.show-calendar)')) {
            return;
        }
        originalEnforceFocus.call(this);
    };
}

// Globale Daterangepicker Optionen
const defaultOptions = {
    locale: {
        format: 'DD.MM.YYYY',
        separator: ' - ',
        applyLabel: 'Übernehmen',
        cancelLabel: 'Abbrechen',
        fromLabel: 'Von',
        toLabel: 'Bis',
        customRangeLabel: 'Benutzerdefiniert',
        weekLabel: 'W',
        daysOfWeek: moment.weekdaysMin(),
        monthNames: moment.months(),
        firstDay: 1
    },
    autoUpdateInput: true,
    showDropdowns: true,
    autoApply: false
};

// Konfigurationen für verschiedene Typen
export const datePickerConfig = {
    // Einzelner Datepicker ohne Uhrzeit
    singleDate: {
        ...defaultOptions,
        singleDatePicker: true,
        timePicker: false,
        locale: {
            ...defaultOptions.locale,
            format: 'DD.MM.YYYY'
        }
    },

    // Einzelner Datepicker mit Uhrzeit
    singleDateTime: {
        ...defaultOptions,
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        timePickerIncrement: 15,
        locale: {
            ...defaultOptions.locale,
            format: 'DD.MM.YYYY HH:mm'
        }
    },

    // Daterangepicker ohne Uhrzeit
    dateRange: {
        ...defaultOptions,
        singleDatePicker: false,
        timePicker: false,
        locale: {
            ...defaultOptions.locale,
            format: 'DD.MM.YYYY'
        }
    },

    // Daterangepicker mit Uhrzeit
    dateTimeRange: {
        ...defaultOptions,
        singleDatePicker: false,
        timePicker: true,
        timePicker24Hour: true,
        timePickerIncrement: 15,
        locale: {
            ...defaultOptions.locale,
            format: 'DD.MM.YYYY HH:mm'
        }
    }
};

// Store für Picker-Instanzen
const pickerInstances = new WeakMap();

/**
 * Hilfsfunktion zum sicheren Zerstören eines Daterangepickers
 */
function destroyPicker(element) {
    const picker = pickerInstances.get(element);
    if (picker && typeof picker.remove === 'function') {
        try {
            picker.remove();
        } catch (e) {
            console.warn('Error destroying picker:', e);
        }
    }
    pickerInstances.delete(element);
}

/**
 * Findet das beste Parent-Element für den Daterangepicker
 * @param {HTMLElement} element - Das Input-Element
 * @returns {string|HTMLElement} - jQuery Selector oder Element
 */
function findBestParentEl(element) {
    // Prüfe ob das Element in einem Modal ist
    const modal = element.closest('.modal');
    if (modal) {
        return modal;
    }

    // Prüfe ob das Element in einem offcanvas ist
    const offcanvas = element.closest('.offcanvas');
    if (offcanvas) {
        return offcanvas;
    }

    // Prüfe ob das Element in einem speziellen Container ist
    const container = element.closest('.container, .container-fluid, form');
    if (container) {
        return container;
    }

    // Fallback zu body
    return 'body';
}

/**
 * Erstellt die Konfiguration mit dynamischem parentEl
 */
function createConfig(baseConfig, element) {
    return {
        ...baseConfig,
        parentEl: findBestParentEl(element)
    };
}

/**
 * Initialisiert alle Daterangepicker im DOM
 */
export function initDaterangepickers() {
    // Bootstrap Focus-Enforcement global deaktivieren für Daterangepicker
    disableBootstrapFocusEnforcement();

    // Einzelnes Datum ohne Zeit
    document.querySelectorAll('[data-daterangepicker="single-date"]').forEach(element => {
        destroyPicker(element);
        const config = createConfig(datePickerConfig.singleDate, element);
        const picker = new daterangepicker(element, config);
        pickerInstances.set(element, picker);
        preventFocusIssues(picker);
    });

    // Einzelnes Datum mit Zeit
    document.querySelectorAll('[data-daterangepicker="single-datetime"]').forEach(element => {
        destroyPicker(element);
        const config = createConfig(datePickerConfig.singleDateTime, element);
        const picker = new daterangepicker(element, config);
        pickerInstances.set(element, picker);
        preventFocusIssues(picker);
    });

    // Datumsbereich ohne Zeit
    document.querySelectorAll('[data-daterangepicker="date-range"]').forEach(element => {
        destroyPicker(element);
        const config = createConfig(datePickerConfig.dateRange, element);
        const picker = new daterangepicker(element, config);
        pickerInstances.set(element, picker);
        preventFocusIssues(picker);
    });

    // Datumsbereich mit Zeit
    document.querySelectorAll('[data-daterangepicker="datetime-range"]').forEach(element => {
        destroyPicker(element);
        const config = createConfig(datePickerConfig.dateTimeRange, element);
        const picker = new daterangepicker(element, config);
        pickerInstances.set(element, picker);
        preventFocusIssues(picker);
    });
}

/**
 * Deaktiviert Bootstrap's Focus-Enforcement
 */
function disableBootstrapFocusEnforcement() {
    // Finde alle Modals und deaktiviere Focus-Enforcement
    document.querySelectorAll('.modal').forEach(modal => {
        modal.setAttribute('data-focus', 'false');
        modal.setAttribute('data-bs-focus', 'false');
    });

    // Event-Listener für dynamisch hinzugefügte Modals
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.classList && node.classList.contains('modal')) {
                    node.setAttribute('data-focus', 'false');
                    node.setAttribute('data-bs-focus', 'false');
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

/**
 * Verhindert Focus-Probleme mit dem Daterangepicker
 */
function preventFocusIssues(picker) {
    if (!picker || !picker.container) return;

    // picker.container ist ein jQuery-Objekt, wir brauchen das DOM-Element
    const containerEl = picker.container[0] || picker.container.get(0);
    if (!containerEl) return;

    containerEl.setAttribute('tabindex', '-1');

    containerEl.addEventListener('mousedown', (e) => {
        e.stopPropagation();
    }, true);

    containerEl.addEventListener('focusin', (e) => {
        e.stopPropagation();
    }, true);

    // Select-Elemente mit querySelectorAll auf dem DOM-Element
    const selects = containerEl.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('mousedown', (e) => {
            e.stopPropagation();
        }, true);

        select.addEventListener('click', (e) => {
            e.stopPropagation();
        }, true);
    });
}

/**
 * Zerstört alle Daterangepicker-Instanzen
 */
export function destroyDaterangepickers() {
    document.querySelectorAll('[data-daterangepicker]').forEach(element => {
        destroyPicker(element);
    });
}

/**
 * Hilfsfunktion zum manuellen Initialisieren eines Daterangepickers
 * @param {HTMLElement|string} element - DOM Element oder Selector
 * @param {string} config - Konfigurationsname (singleDate, singleDateTime, dateRange, dateTimeRange)
 * @returns {Object} Daterangepicker Instanz
 */
export function initDaterangepicker(element, config = 'dateTimeRange') {
    const el = typeof element === 'string' ? document.querySelector(element) : element;

    if (!el) {
        console.warn('Daterangepicker: Element not found', element);
        return null;
    }

    destroyPicker(el);
    const picker = new daterangepicker(el, datePickerConfig[config]);
    pickerInstances.set(el, picker);

    return picker;
}

/**
 * Gibt die Picker-Instanz für ein Element zurück
 */
export function getPickerInstance(element) {
    const el = typeof element === 'string' ? document.querySelector(element) : element;
    return pickerInstances.get(el);
}

import moment from 'moment';
import 'moment/locale/de';
import daterangepicker from 'daterangepicker';
import 'daterangepicker/daterangepicker.css';

// Moment auf Deutsch setzen
moment.locale('de');

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
 * Initialisiert alle Daterangepicker im DOM
 */
export function initDaterangepickers() {
    // Einzelnes Datum ohne Zeit
    document.querySelectorAll('[data-daterangepicker="single-date"]').forEach(element => {
        destroyPicker(element);
        const picker = new daterangepicker(element, datePickerConfig.singleDate);
        pickerInstances.set(element, picker);
    });

    // Einzelnes Datum mit Zeit
    document.querySelectorAll('[data-daterangepicker="single-datetime"]').forEach(element => {
        destroyPicker(element);
        const picker = new daterangepicker(element, datePickerConfig.singleDateTime);
        pickerInstances.set(element, picker);
    });

    // Datumsbereich ohne Zeit
    document.querySelectorAll('[data-daterangepicker="date-range"]').forEach(element => {
        destroyPicker(element);
        const picker = new daterangepicker(element, datePickerConfig.dateRange);
        pickerInstances.set(element, picker);
    });

    // Datumsbereich mit Zeit
    document.querySelectorAll('[data-daterangepicker="datetime-range"]').forEach(element => {
        destroyPicker(element);
        const picker = new daterangepicker(element, datePickerConfig.dateTimeRange);
        pickerInstances.set(element, picker);
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

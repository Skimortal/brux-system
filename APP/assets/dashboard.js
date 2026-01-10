
import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import deLocale from '@fullcalendar/core/locales/de';
import { Modal } from 'bootstrap';
import moment from 'moment';
import 'moment-timezone';
import { initDaterangepicker, getPickerInstance, initDaterangepickers } from './daterangepicker-init.js';

let allEvents = [];
let currentSelectedDate = new Date();
let calendarInstances = [];
let globalCalendar = null;
let currentView = 'global';

const colorToIconClass = {
    '#4285f4': 'c-blue-500',
    '#0f9d58': 'c-green-500',
    '#f4b400': 'c-orange-500',
    '#db4437': 'c-red-500',
    '#ab47bc': 'c-purple-500',
    '#00acc1': 'c-cyan-500',
    '#ff7043': 'c-deep-orange-500',
    '#9e9e9e': 'c-grey-500'
};

// Volunteer Task Labels
const volunteerTaskLabels = {
    'bar': 'Bar',
    'setup': 'Aufbau',
    'teardown': 'Abbau',
    'ticketing': 'Ticketing',
    'cleanup': 'Aufräumen',
    'other': 'Anderes'
};

function displayDate(date) {
    currentSelectedDate = new Date(date);
    const formattedDate = date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    const dayName = date.toLocaleDateString('de-DE', { weekday: 'long' });

    const numText = document.getElementById('day-number-text');
    if(numText) numText.textContent = formattedDate;

    const suffix = document.getElementById('day-suffix');
    if(suffix) suffix.textContent = '';

    const nameEl = document.getElementById('day-name');
    if(nameEl) nameEl.textContent = dayName.charAt(0).toUpperCase() + dayName.slice(1);
}

var appointmentModal;
var productionEventModal;
var keyModal;
var deleteConfirmModal;
let currentDeleteAppointmentId = null;
let lastOpenedAppointmentEvent = null;

function initCalendar() {
    const modalEl = document.getElementById('appointmentModal');
    if (modalEl) {
        appointmentModal = new Modal(modalEl);
    }

    const prodEventModalEl = document.getElementById('productionEventModal');
    if (prodEventModalEl) {
        productionEventModal = new Modal(prodEventModalEl);
    }

    const deleteModalEl = document.getElementById('deleteConfirmModal');
    if (deleteModalEl) {
        deleteConfirmModal = new Modal(deleteModalEl);
    }

    calendarInstances.forEach(cal => cal.destroy());
    calendarInstances = [];
    if (globalCalendar) {
        globalCalendar.destroy();
        globalCalendar = null;
    }

    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    filterCheckboxes.forEach(box => {
        const newBox = box.cloneNode(true);
        box.parentNode.replaceChild(newBox, box);
        newBox.addEventListener('change', refreshCurrentView);
    });

    const viewRadios = document.querySelectorAll('input[name="calendarView"]');
    viewRadios.forEach(radio => {
        const newRadio = radio.cloneNode(true);
        radio.parentNode.replaceChild(newRadio, radio);
        newRadio.addEventListener('change', function() {
            currentView = this.value;
            switchCalendarView(this.value);
        });
    });

    const syncBtn = document.getElementById('syncApiBtn');
    if (syncBtn) {
        const newSyncBtn = syncBtn.cloneNode(true);
        syncBtn.parentNode.replaceChild(newSyncBtn, syncBtn);
        newSyncBtn.addEventListener('click', triggerApiSync);
    }

    const isMobile = window.innerWidth <= 767;

    initGlobalCalendar(isMobile);
    initRoomCalendars(isMobile);

    fetch('/appointments/all')
        .then(response => response.json())
        .then(data => {
            allEvents = data;
            displayDate(currentSelectedDate);
        })
        .catch(e => console.error(e));

    setupModalListeners();
}

function triggerApiSync() {
    const btn = document.getElementById('syncApiBtn');
    if (!btn) return;

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ti-reload spin"></i> Synchronisiere...';

    const style = document.createElement('style');
    style.textContent = `
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    if (!document.querySelector('style[data-sync-spinner]')) {
        style.setAttribute('data-sync-spinner', 'true');
        document.head.appendChild(style);
    }

    fetch('/dashboard/sync-api', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalContent;

            if (data.success) {
                showToast('success', data.message);
                refreshCurrentView();
                fetch('/appointments/all')
                    .then(r => r.json())
                    .then(events => {
                        allEvents = events;
                    });
            } else {
                showToast('error', data.message || 'Fehler bei der Synchronisation');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            console.error('Sync error:', error);
            showToast('error', 'Netzwerkfehler bei der Synchronisation');
        });
}

function showToast(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function initGlobalCalendar(isMobile) {
    const calendarEl = document.getElementById('global-calendar');
    if (!calendarEl) return;

    globalCalendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: isMobile ? 'timeGridWeek' : 'dayGridMonth',
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        firstDay: 1,
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'today,dayGridMonth,timeGridWeek,timeGridDay'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        height: 1000,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            meridiem: false
        },
        eventDisplay: 'block',

        // Icons/Titel als HTML rendern (statt Klartext)
        eventContent: function(arg) {
            const titleHtml = arg.event.title || '';
            const wrapper = document.createElement('div');
            wrapper.className = 'fc-event-title-container';
            wrapper.innerHTML = titleHtml; // bewusst: Titel kommt aus unserem Backend/Icons
            return { domNodes: [wrapper] };
        },

        events: function(info, successCallback, failureCallback) {
            const activeFilters = Array.from(document.querySelectorAll('.filter-checkbox:checked'))
                .map(cb => cb.value)
                .join(',');

            const url = `/dashboard/events?roomId=&start=${info.startStr}&end=${info.endStr}&filters=${activeFilters}`;

            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => {
                    console.error('Error loading events:', error);
                    failureCallback(error);
                });
        },
        dateClick: function(info) {
            openAppointmentModal(info.dateStr, null, info.date, null);
        },
        eventClick: function(info) {
            handleEventClick(info);
        },
        eventDidMount: function(info) {
            const now = new Date();
            const eventEnd = info.event.end || info.event.start;
            const isKey = info.event.extendedProps.type === 'key';
            if (isKey) {
                // Schlüssel-Logik: Rot unterlegen wenn überfällig, niemals durchstreichen
                if (info.event.extendedProps.isOverdue) {
                    info.el.style.backgroundColor = '#dc3545'; // Rot
                    info.el.style.borderColor = '#bd2130';
                    info.el.style.opacity = '0.5';
                }
            } else if (eventEnd < now) {
                // Standard-Logik für andere Events: Durchstreichen wenn vergangen
                info.el.style.textDecoration = 'line-through';
                info.el.style.opacity = '0.5';
            }
        },
        editable: false
    });

    globalCalendar.render();
}

function initRoomCalendars(isMobile) {
    const calendarEls = document.querySelectorAll('.room-calendar');

    calendarEls.forEach(calendarEl => {
        const calendar = createRoomCalendar(calendarEl, isMobile);
        calendarInstances.push(calendar);
    });
}

function refreshCurrentView() {
    if (currentView === 'global') {
        if (globalCalendar) {
            globalCalendar.refetchEvents();
        }
    } else {
        calendarInstances.forEach(cal => cal.refetchEvents());
    }
}

function refreshAllCalendars() {
    refreshCurrentView();
}

function createRoomCalendar(calendarEl, isMobile) {
    const roomId = calendarEl.dataset.roomId || '';

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: isMobile ? 'timeGridWeek' : 'dayGridMonth',
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        firstDay: 1,
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'today,dayGridMonth,timeGridWeek,timeGridDay'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        height: 1000,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            meridiem: false
        },
        eventDisplay: 'block',

        // Icons/Titel als HTML rendern (statt Klartext)
        eventContent: function(arg) {
            const titleHtml = arg.event.title || '';
            const wrapper = document.createElement('div');
            wrapper.className = 'fc-event-title-container';
            wrapper.innerHTML = titleHtml;
            return { domNodes: [wrapper] };
        },

        events: function(info, successCallback, failureCallback) {
            const activeFilters = Array.from(document.querySelectorAll('.filter-checkbox:checked'))
                .map(cb => cb.value)
                .join(',');

            const url = `/dashboard/events?roomId=${roomId}&start=${info.startStr}&end=${info.endStr}&filters=${activeFilters}`;

            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => {
                    console.error('Error loading events:', error);
                    failureCallback(error);
                });
        },
        dateClick: function(info) {
            openAppointmentModal(info.dateStr, null, info.date, roomId);
        },
        eventClick: function(info) {
            handleEventClick(info);
        },
        eventDidMount: function(info) {
            const now = new Date();
            const eventEnd = info.event.end || info.event.start;
            const isKey = info.event.extendedProps.type === 'key';
            if (isKey) {
                if (info.event.extendedProps.isOverdue) {
                    info.el.style.backgroundColor = '#dc3545';
                    info.el.style.borderColor = '#bd2130';
                    info.el.style.opacity = '0.5';
                }
            } else if (eventEnd < now) {
                info.el.style.textDecoration = 'line-through';
                info.el.style.opacity = '0.5';
            }
        },
        editable: false,
        datesSet: function(dateInfo) {
            syncAllRoomCalendars(calendar, dateInfo);
        }
    });

    calendar.render();
    return calendar;
}

function syncAllRoomCalendars(sourceCalendar, dateInfo) {
    if (sourceCalendar._isSyncing) {
        return;
    }

    calendarInstances.forEach(cal => {
        if (cal === sourceCalendar) {
            return;
        }

        cal._isSyncing = true;

        const currentView = sourceCalendar.view.type;
        const currentDate = sourceCalendar.getDate();

        if (cal.view.type !== currentView) {
            cal.changeView(currentView);
        }

        cal.gotoDate(currentDate);

        setTimeout(() => {
            cal._isSyncing = false;
        }, 100);
    });
}

function switchCalendarView(view) {
    const globalContainer = document.getElementById('globalCalendarContainer');
    const roomsContainer = document.getElementById('roomCalendarsContainer');

    if (view === 'global') {
        if (globalContainer) globalContainer.style.display = 'block';
        if (roomsContainer) roomsContainer.style.display = 'none';

        if (globalCalendar) {
            globalCalendar.updateSize();
            globalCalendar.refetchEvents();
        }
    } else {
        if (globalContainer) globalContainer.style.display = 'none';
        if (roomsContainer) roomsContainer.style.display = 'block';

        calendarInstances.forEach(cal => {
            cal.updateSize();
            cal.refetchEvents();
        });
    }
}

function handleEventClick(info) {
    const type = info.event.extendedProps.type;

    if (type === 'production_event') {
        const productionEventId = info.event.extendedProps.productionEventId;
        if (productionEventId) {
            openProductionEventModal(productionEventId);
        }
    } else if (type === 'key') {
        const keyId = info.event.extendedProps.keyId;
        if (keyId) {
            openKeyModal(keyId);
        }
    } else if (['private', 'cleaning', 'production'].includes(type)) {
        openAppointmentModal(null, info.event);
    } else {
        alert(info.event.title + '\n' + (info.event.extendedProps.description || ''));
    }
}

function openProductionEventModal(eventId) {
    const modalBody = document.getElementById('productionEventModalBody');
    const modalTitle = document.getElementById('productionEventModalTitle');
    const editBtn = document.getElementById('editProductionEventBtn');

    if (!productionEventModal) return;

    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
        </div>
    `;

    productionEventModal.show();

    fetch(`/dashboard/production-event/${eventId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            const event = data.event;
            const production = data.production;

            modalTitle.textContent = production ? production.title : 'Veranstaltung';

            if (editBtn) {
                editBtn.href = `/production-event/${event.id}/edit`;
                editBtn.style.display = 'inline-block';
            }

            let html = '';

            if (production && production.postThumbnailUrl) {
                html += `
                    <div class="text-center mb-4">
                        <img src="${production.postThumbnailUrl}"
                             alt="${production.title}"
                             class="img-fluid rounded"
                             style="max-height: 300px; object-fit: cover;">
                    </div>
                `;
            }

            html += `<div class="row mb-4">`;
            html += `<div class="col-md-6">`;
            html += `<h6 class="text-muted mb-3">Veranstaltungsinformationen</h6>`;

            if (event.date) {
                html += `
                    <div class="mb-2">
                        <strong><i class="ti-calendar me-2"></i>Datum:</strong>
                        ${event.date}
                    </div>
                `;
            }

            if (event.timeFrom) {
                html += `
                    <div class="mb-2">
                        <strong><i class="ti-time me-2"></i>Uhrzeit:</strong>
                        ${event.timeFrom}${event.timeTo ? ' - ' + event.timeTo : ''} Uhr
                    </div>
                `;
            }

            if (event.room) {
                html += `
                    <div class="mb-2">
                        <strong><i class="ti-location-pin me-2"></i>Raum:</strong>
                        ${event.room}
                    </div>
                `;
            }

            if (event.status) {
                const statusBadge = event.status.includes('Aktiv') ? 'success' : 'secondary';
                html += `
                    <div class="mb-2">
                        <strong>Status:</strong>
                        <span class="badge bg-${statusBadge}">${event.status}</span>
                    </div>
                `;
            }

            // NEU: Ansprechpartner-Mehrfachauswahl (aus übergeordneter Produktion)
            const cps = Array.isArray(event.productionContactPersons) ? event.productionContactPersons : [];
            const assignedIds = Array.isArray(event.assignedContactPersonIds) ? event.assignedContactPersonIds : [];

            if (cps.length > 0) {
                const optionsHtml = cps.map(cp => {
                    const labelParts = [cp.name];
                    if (cp.email) labelParts.push(cp.email);
                    if (cp.phone) labelParts.push(cp.phone);
                    const label = labelParts.join(' • ');
                    const selected = assignedIds.includes(cp.id) ? 'selected' : '';
                    return `<option value="${cp.id}" ${selected}>${label}</option>`;
                }).join('');

                html += `
                    <div class="mb-2">
                        <strong>Ansprechpartner:</strong>
                        <select
                            id="productionEventContactPersons"
                            class="form-select tom-select"
                            multiple
                            data-placeholder="Bitte auswählen"
                        >
                            ${optionsHtml}
                        </select>
                        <small class="text-muted d-block mt-1">
                            Zuordnung gilt für dieses Event.
                        </small>
                    </div>
                `;
            } else {
                html += `
                    <div class="mb-2">
                        <strong>Ansprechpartner:</strong>
                        <div class="text-muted">Keine Ansprechpartner in der Produktion hinterlegt.</div>
                    </div>
                `;
            }

            html += `</div>`;
            html += `<div class="col-md-6">`;
            html += `<h6 class="text-muted mb-3">Plätze & Reservierungen</h6>`;

            if (event.quota !== null) {
                html += `
                    <div class="mb-2">
                        <strong><i class="ti-package me-2"></i>Kontingent:</strong>
                        ${event.quota} Plätze
                    </div>
                `;
            }

            if (event.incomingTotal !== null) {
                html += `
                    <div class="mb-2">
                        <strong><i class="ti-check me-2"></i>Reserviert:</strong>
                        ${event.incomingTotal}
                    </div>
                `;
            }

            if (event.freeSeats !== null) {
                const freeSeatsBadge = event.freeSeats > 10 ? 'success' : (event.freeSeats > 0 ? 'warning' : 'danger');
                html += `
                    <div class="mb-2">
                        <strong><i class="ti-ticket me-2"></i>Frei:</strong>
                        <span class="badge bg-${freeSeatsBadge}">${event.freeSeats}</span>
                    </div>
                `;
            }

            html += `</div>`;
            html += `</div>`;

            if (event.prices && event.prices.length > 0) {
                html += `
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Preise</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kategorie</th>
                                        <th>Preis</th>
                                        <th>Reservierungen</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                event.prices.forEach(price => {
                    html += `
                        <tr>
                            <td>${price.categoryLabel || '-'}</td>
                            <td><strong>${price.priceLabel || '0'}€</strong></td>
                            <td>${price.incomingReservations || 0}</td>
                        </tr>
                    `;
                });

                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            if (event.categories && event.categories.length > 0) {
                html += `
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Hinweise</h6>
                        <div>
                `;
                event.categories.forEach(cat => {
                    const badgeColor = cat.slug === 'ausverkauft-event' ? 'danger' : 'info';
                    html += `<span class="badge bg-${badgeColor} me-2">${cat.name}</span>`;
                });
                html += `
                        </div>
                    </div>
                `;
            }

            if (event.reservationNote) {
                const isUrl = event.reservationNote.startsWith('http');
                html += `
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Reservierung</h6>
                        ${isUrl ?
                    `<a href="${event.reservationNote}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="ti-new-window me-1"></i>Zur Reservierung
                            </a>` :
                    `<p class="mb-0">${event.reservationNote}</p>`
                }
                    </div>
                `;
            }

            if (production && production.contentHtml) {
                html += `
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="text-muted mb-3">Über die Produktion</h6>
                        <div class="production-content">
                            ${production.contentHtml}
                        </div>
                    </div>
                `;
            }

            if (production && production.permalink) {
                html += `
                    <div class="mt-3">
                        <a href="${production.permalink}" target="_blank" class="btn btn-sm btn-secondary">
                            <i class="ti-link me-1"></i>Zur Website
                        </a>
                    </div>
                `;
            }

            modalBody.innerHTML = html;

            // TomSelect nachträglich initialisieren (weil HTML dynamisch kommt)
            if (window.initTomSelect) {
                window.initTomSelect();
            }

            // Change-Handler: sofort speichern
            const cpSelect = document.getElementById('productionEventContactPersons');
            if (cpSelect) {
                cpSelect.addEventListener('change', () => {
                    const selected = Array.from(cpSelect.selectedOptions).map(o => o.value);

                    fetch(`/dashboard/production-event/${event.id}/contact-persons`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ contactPersonIds: selected })
                    })
                        .then(r => r.json())
                        .then(result => {
                            if (result && result.success) {
                                showToast('success', 'Ansprechpartner gespeichert');
                            } else {
                                showToast('error', (result && result.message) ? result.message : 'Speichern fehlgeschlagen');
                            }
                        })
                        .catch(() => {
                            showToast('error', 'Netzwerkfehler beim Speichern');
                        });
                });
            }

        })
        .catch(error => {
            console.error('Error loading event details:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Fehler beim Laden der Event-Details.
                </div>
            `;
        });
}

function setupModalListeners() {
    const saveBtn = document.getElementById('saveAppointmentBtn');
    if (saveBtn) {
        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', saveAppointment);
    }

    const duplicateBtn = document.getElementById('duplicateAppointmentBtn');
    if (duplicateBtn) {
        const newBtn = duplicateBtn.cloneNode(true);
        duplicateBtn.parentNode.replaceChild(newBtn, duplicateBtn);
        newBtn.addEventListener('click', function() {
            if (!lastOpenedAppointmentEvent) return;
            // Modal erneut öffnen, aber als "duplicate" (neuer Termin, keine ID)
            openAppointmentModal(null, lastOpenedAppointmentEvent, null, null, 'duplicate');
        });
    }

    const deleteBtn = document.getElementById('deleteAppointmentBtn');
    if (deleteBtn) {
        const newBtn = deleteBtn.cloneNode(true);
        deleteBtn.parentNode.replaceChild(newBtn, deleteBtn);
        newBtn.addEventListener('click', () => {
            const appointmentId = document.getElementById('appointmentId').value;
            showDeleteConfirmation(appointmentId);
        });
    }

    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        const newBtn = confirmDeleteBtn.cloneNode(true);
        confirmDeleteBtn.parentNode.replaceChild(newBtn, confirmDeleteBtn);
        newBtn.addEventListener('click', confirmDelete);
    }

    const allDayCheckbox = document.getElementById('appointmentAllDay');
    if (allDayCheckbox) {
        const newBtn = allDayCheckbox.cloneNode(true);
        allDayCheckbox.parentNode.replaceChild(newBtn, allDayCheckbox);
        newBtn.addEventListener('change', toggleAllDay);
    }

    const recurringCheckbox = document.getElementById('appointmentRecurring');
    if (recurringCheckbox) {
        const newBtn = recurringCheckbox.cloneNode(true);
        recurringCheckbox.parentNode.replaceChild(newBtn, recurringCheckbox);
        newBtn.addEventListener('change', toggleRecurrence);
    }

    document.querySelectorAll('.appointment-type-radio').forEach(radio => {
        const newRadio = radio.cloneNode(true);
        radio.parentNode.replaceChild(newRadio, radio);
        newRadio.addEventListener('change', updateTypeSpecificFields);
    });

    const addBtn = document.getElementById('add-appointment-btn');
    if (addBtn) {
        const newAdd = addBtn.cloneNode(true);
        addBtn.parentNode.replaceChild(newAdd, addBtn);
        newAdd.addEventListener('click', function() {
            const dateStr = currentSelectedDate.toISOString().split('T')[0];
            openAppointmentModal(dateStr);
        });
    }
}

function buildDuplicateTitle(originalTitle) {
    const title = (originalTitle || '').trim();
    if (!title) return '';
    if (title.endsWith(' (Kopie)')) return title;
    return `${title} (Kopie)`;
}

function toggleRecurrence(e) {
    const recurrenceOptions = document.getElementById('recurrenceOptions');
    const endDateInput = document.getElementById('recurrenceEndDate');

    if (recurrenceOptions) {
        recurrenceOptions.style.display = e.target.checked ? 'block' : 'none';
    }

    // Fix: Picker initialisieren sobald sichtbar (sonst übernimmt Klick kein Datum)
    if (e.target.checked && endDateInput) {
        initDaterangepicker(endDateInput, 'singleDate');
    }

    // Optional: Beim Ausschalten Wert/Picker zurücksetzen
    if (!e.target.checked && endDateInput) {
        endDateInput.value = '';
    }
}

function updateTypeSpecificFields() {
    const selectedType = document.querySelector('input[name="appointmentType"]:checked');
    if (!selectedType) return;

    const type = selectedType.value;
    const container = document.getElementById('typeSpecificFields');
    if (!container) return;

    container.innerHTML = '';

    // Title Feld abhängig vom Typ steuern
    toggleTitleVisibilityByType(type);

    switch(type) {
        case 'private':
            break;

        case 'production':
            container.innerHTML = buildProductionFields();
            initializeProductionFieldListeners();
            break;

        case 'closed_event':
            // Geschl. Veranstaltung: Ereignisart/Status/Interne Techniker AUSBLENDEN,
            // aber Techniker/Volunteers zuweisen wie bei Produktion
            container.innerHTML = buildAssignmentsOnlyFields('Veranstaltungs-Details');
            initializeProductionFieldListeners();
            break;

        case 'school_event':
            // Schulveranstaltung: Techniker/Volunteer Zuordnung einblenden
            container.innerHTML = buildAssignmentsOnlyFields('Schulveranstaltung');
            initializeProductionFieldListeners();
            break;

        case 'internal':
            // Intern: Techniker/Volunteer Zuordnung einblenden
            container.innerHTML = buildAssignmentsOnlyFields('Interner Termin');
            initializeProductionFieldListeners();
            break;

        case 'cleaning':
            container.innerHTML = buildCleaningFields();
            break;
    }
}

function toggleTitleVisibilityByType(type) {
    const titleInput = document.getElementById('appointmentTitle');
    if (!titleInput) return;

    const titleWrapper = titleInput.closest('.mb-3'); // aus Twig: Titel steckt in .mb-3
    const hideTitle = (type === 'production' || type === 'cleaning');

    if (titleWrapper) {
        titleWrapper.style.display = hideTitle ? 'none' : 'block';
    }

    titleInput.required = !hideTitle;

    if (hideTitle) {
        // Leeren Titel erlauben, wir setzen später automatisch einen sinnvollen Default
        titleInput.value = '';
    }
}

function buildAssignmentsOnlyFields(headline) {
    return `
        <div class="border-top pt-3 mt-3">
            <h6 class="fw-bold mb-3">${headline}</h6>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assignTechniciansCheckbox">
                    <label class="form-check-label fw-bold" for="assignTechniciansCheckbox">
                        Techniker zuweisen
                    </label>
                </div>
            </div>

            <div id="techniciansContainer" style="display: none;">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Techniker</th>
                            <th style="width: 25%;">Bestätigt</th>
                            <th style="width: 25%;"></th>
                        </tr>
                    </thead>
                    <tbody id="techniciansList"></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="addTechnicianBtn">
                    <i class="ti-plus"></i> Techniker hinzufügen
                </button>
            </div>

            <div class="mb-3 mt-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assignVolunteersCheckbox">
                    <label class="form-check-label fw-bold" for="assignVolunteersCheckbox">
                        Volunteers zuweisen
                    </label>
                </div>
            </div>

            <div id="volunteersContainer" style="display: none;">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Volunteer</th>
                            <th style="width: 20%;">Bestätigt</th>
                            <th style="width: 35%;">Aufgaben</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="volunteersList"></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="addVolunteerBtn">
                    <i class="ti-plus"></i> Volunteer hinzufügen
                </button>
            </div>
        </div>
    `;
}

function buildProductionFields() {
    const productions = window.dashboardData.allProductions || [];
    const technicians = window.dashboardData.allTechnicians || [];
    const volunteers = window.dashboardData.allVolunteers || [];

    return `
        <div class="border-top pt-3 mt-3">
            <h6 class="fw-bold mb-3">Produktions-Details</h6>

            <div class="mb-3">
                <label for="productionSelect" class="form-label">Produktion</label>
                <select class="form-select" id="productionSelect">
                    <option value="">Bitte wählen...</option>
                    ${productions.map(p => `<option value="${p.id}">${p.title || p.displayName}</option>`).join('')}
                </select>
            </div>

            <div id="productionRequirementsInfo" class="mb-3" style="display:none;">
                <div class="alert alert-info py-2 px-3 small mb-0">
                    <strong>Produktion benötigt:</strong> <span id="reqFlagsList"></span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Ereignisart</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="eventType" id="eventTypeRehearsal" value="rehearsal">
                    <label class="btn btn-outline-secondary" for="eventTypeRehearsal">
                        <i class="ti-music-alt"></i> Probe
                    </label>

                    <input type="radio" class="btn-check" name="eventType" id="eventTypeSetup" value="setup_teardown">
                    <label class="btn btn-outline-secondary" for="eventTypeSetup">
                        <i class="ti-package"></i> Aufbau/Abbau
                    </label>

                    <input type="radio" class="btn-check" name="eventType" id="eventTypeEvent" value="event">
                    <label class="btn btn-outline-secondary" for="eventTypeEvent">
                        <i class="ti-flag-alt"></i> Veranstaltung
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="appointmentStatus" id="statusReserved" value="reserved">
                    <label class="btn btn-outline-warning" for="statusReserved">Reserviert</label>

                    <input type="radio" class="btn-check" name="appointmentStatus" id="statusConfirmed" value="confirmed">
                    <label class="btn btn-outline-success" for="statusConfirmed">Fix</label>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="internalTechniciansAttending">
                <label class="form-check-label" for="internalTechniciansAttending">
                    Externe Techniker kommen
                </label>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assignTechniciansCheckbox">
                    <label class="form-check-label fw-bold" for="assignTechniciansCheckbox">
                        Techniker zuweisen
                    </label>
                </div>
            </div>

            <div id="techniciansContainer" style="display: none;">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Techniker</th>
                            <th style="width: 25%;">Bestätigt</th>
                            <th style="width: 25%;"></th>
                        </tr>
                    </thead>
                    <tbody id="techniciansList">
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="addTechnicianBtn">
                    <i class="ti-plus"></i> Techniker hinzufügen
                </button>
            </div>

            <div class="mb-3 mt-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assignVolunteersCheckbox">
                    <label class="form-check-label fw-bold" for="assignVolunteersCheckbox">
                        Volunteers zuweisen
                    </label>
                </div>
            </div>

            <div id="volunteersContainer" style="display: none;">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Volunteer</th>
                            <th style="width: 20%;">Bestätigt</th>
                            <th style="width: 35%;">Aufgaben</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="volunteersList">
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="addVolunteerBtn">
                    <i class="ti-plus"></i> Volunteer hinzufügen
                </button>
            </div>
        </div>
    `;
}

function buildClosedEventFields() {
    const technicians = window.dashboardData.allTechnicians || [];
    const volunteers = window.dashboardData.allVolunteers || [];

    return `
        <div class="border-top pt-3 mt-3">
            <h6 class="fw-bold mb-3">Veranstaltungs-Details</h6>

            <div class="mb-3">
                <label class="form-label d-block">Ereignisart</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="eventType" id="eventTypeRehearsal" value="rehearsal">
                    <label class="btn btn-outline-secondary" for="eventTypeRehearsal">
                        <i class="ti-music-alt"></i> Probe
                    </label>

                    <input type="radio" class="btn-check" name="eventType" id="eventTypeSetup" value="setup_teardown">
                    <label class="btn btn-outline-secondary" for="eventTypeSetup">
                        <i class="ti-package"></i> Aufbau/Abbau
                    </label>

                    <input type="radio" class="btn-check" name="eventType" id="eventTypeEvent" value="event">
                    <label class="btn btn-outline-secondary" for="eventTypeEvent">
                        <i class="ti-flag-alt"></i> Veranstaltung
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="appointmentStatus" id="statusReserved" value="reserved">
                    <label class="btn btn-outline-warning" for="statusReserved">Reserviert</label>

                    <input type="radio" class="btn-check" name="appointmentStatus" id="statusConfirmed" value="confirmed">
                    <label class="btn btn-outline-success" for="statusConfirmed">Fix</label>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="internalTechniciansAttending">
                <label class="form-check-label" for="internalTechniciansAttending">
                    Externe Techniker kommen
                </label>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assignTechniciansCheckbox">
                    <label class="form-check-label fw-bold" for="assignTechniciansCheckbox">
                        Techniker zuweisen
                    </label>
                </div>
            </div>

            <div id="techniciansContainer" style="display: none;">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Techniker</th>
                            <th style="width: 25%;">Bestätigt</th>
                            <th style="width: 25%;"></th>
                        </tr>
                    </thead>
                    <tbody id="techniciansList">
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="addTechnicianBtn">
                    <i class="ti-plus"></i> Techniker hinzufügen
                </button>
            </div>

            <div class="mb-3 mt-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assignVolunteersCheckbox">
                    <label class="form-check-label fw-bold" for="assignVolunteersCheckbox">
                        Volunteers zuweisen
                    </label>
                </div>
            </div>

            <div id="volunteersContainer" style="display: none;">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Volunteer</th>
                            <th style="width: 20%;">Bestätigt</th>
                            <th style="width: 35%;">Aufgaben</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="volunteersList">
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="addVolunteerBtn">
                    <i class="ti-plus"></i> Volunteer hinzufügen
                </button>
            </div>
        </div>
    `;
}

function buildCleaningFields() {
    const cleanings = window.dashboardData.allCleanings || [];

    return `
        <div class="border-top pt-3 mt-3">
            <h6 class="fw-bold mb-3">Reinigungs-Details</h6>

            <div class="mb-3">
                <label for="cleaningSelect" class="form-label">Reinigung</label>
                <select class="form-select" id="cleaningSelect">
                    <option value="">Bitte wählen...</option>
                    ${cleanings.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                </select>
            </div>
        </div>
    `;
}

function initializeProductionFieldListeners() {
    const prodSelect = document.getElementById('productionSelect');
    if (prodSelect) {
        prodSelect.addEventListener('change', function() {
            updateProductionRequirementsInfo(this.value);
        });
    }

    const assignTechCheckbox = document.getElementById('assignTechniciansCheckbox');
    const techContainer = document.getElementById('techniciansContainer');
    const addTechBtn = document.getElementById('addTechnicianBtn');

    if (assignTechCheckbox && techContainer) {
        assignTechCheckbox.addEventListener('change', function() {
            techContainer.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (addTechBtn) {
        addTechBtn.addEventListener('click', addTechnicianRow);
    }

    const assignVolCheckbox = document.getElementById('assignVolunteersCheckbox');
    const volContainer = document.getElementById('volunteersContainer');
    const addVolBtn = document.getElementById('addVolunteerBtn');

    if (assignVolCheckbox && volContainer) {
        assignVolCheckbox.addEventListener('change', function() {
            volContainer.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (addVolBtn) {
        addVolBtn.addEventListener('click', addVolunteerRow);
    }
}

function updateProductionRequirementsInfo(productionId) {
    const infoDiv = document.getElementById('productionRequirementsInfo');
    const listSpan = document.getElementById('reqFlagsList');
    const productions = window.dashboardData.allProductions || [];
    const prod = productions.find(p => p.id == productionId);

    if (!prod || (!prod.needsLighting && !prod.needsSound && !prod.needsSetup && !prod.grandstand)) {
        if (infoDiv) infoDiv.style.display = 'none';
    } else {
        const reqs = [];
        if (prod.needsLighting) reqs.push('Licht');
        if (prod.needsSound) reqs.push('Ton');
        if (prod.needsSetup) reqs.push('Aufbau');

        let text = reqs.join(', ');
        if (prod.grandstand) {
            text += (text ? ' | ' : '') + prod.grandstand;
        }

        if (listSpan) listSpan.textContent = text;
        if (infoDiv) infoDiv.style.display = 'block';
    }

    // Update existing rows
    document.querySelectorAll('#techniciansList tr').forEach(row => {
        updateTechnicianRowVisibility(row, prod);
    });
}

function updateTechnicianRowVisibility(row, prod) {
    const lWrap = row.querySelector('.lighting-wrap');
    const sWrap = row.querySelector('.sound-wrap');
    const aWrap = row.querySelector('.setup-wrap');

    if (lWrap) lWrap.style.display = (prod && prod.needsLighting) ? 'inline-block' : 'none';
    if (sWrap) sWrap.style.display = (prod && prod.needsSound) ? 'inline-block' : 'none';
    if (aWrap) aWrap.style.display = (prod && prod.needsSetup) ? 'inline-block' : 'none';
}

function initializeClosedEventFieldListeners() {
    initializeProductionFieldListeners(); // Gleiche Logik
}

function addTechnicianRow(technicianData = null) {
    const technicians = window.dashboardData.allTechnicians || [];
    const list = document.getElementById('techniciansList');
    if (!list) return;

    const prodSelect = document.getElementById('productionSelect');
    const currentProdId = prodSelect ? prodSelect.value : null;
    const productions = window.dashboardData.allProductions || [];
    const prod = productions.find(p => p.id == currentProdId);

    const row = document.createElement('tr');
    const rowId = 'tech_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    row.dataset.rowId = rowId;

    row.innerHTML = `
            <td>
                <select class="form-select form-select-sm technician-select" data-row-id="${rowId}">
                    <option value="">Wählen...</option>
                    ${technicians.map(t => `
                        <option value="${t.id}" ${technicianData && technicianData.id == t.id ? 'selected' : ''}>
                            ${t.name}
                        </option>
                    `).join('')}
                </select>
            </td>
            <td>
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input technician-confirmed" data-row-id="${rowId}" ${technicianData && technicianData.confirmed ? 'checked' : ''}>
                    <label class="form-check-label small">Bestätigt</label>
                </div>
                <div class="form-check form-check-inline lighting-wrap" style="display:none;">
                    <input type="checkbox" class="form-check-input technician-lighting" data-row-id="${rowId}" ${technicianData && technicianData.lighting ? 'checked' : ''}>
                    <label class="form-check-label small"><i class="ti-light-bulb" title="Licht"></i></label>
                </div>
                <div class="form-check form-check-inline sound-wrap" style="display:none;">
                    <input type="checkbox" class="form-check-input technician-sound" data-row-id="${rowId}" ${technicianData && technicianData.sound ? 'checked' : ''}>
                    <label class="form-check-label small"><i class="ti-announcement" title="Ton"></i></label>
                </div>
                <div class="form-check form-check-inline setup-wrap" style="display:none;">
                    <input type="checkbox" class="form-check-input technician-setup" data-row-id="${rowId}" ${technicianData && technicianData.setup ? 'checked' : ''}>
                    <label class="form-check-label small"><i class="ti-settings" title="Aufbau"></i></label>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-technician-btn" data-row-id="${rowId}">
                    <i class="ti-trash"></i>
                </button>
            </td>
        `;

    list.appendChild(row);
    updateTechnicianRowVisibility(row, prod);

    row.querySelector('.remove-technician-btn').addEventListener('click', function() {
        row.remove();
    });
}

function addVolunteerRow(volunteerData = null) {
    const volunteers = window.dashboardData.allVolunteers || [];
    const list = document.getElementById('volunteersList');
    if (!list) return;

    const row = document.createElement('tr');
    const rowId = 'vol_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    row.dataset.rowId = rowId;

    const tasksHtml = Object.entries(volunteerTaskLabels).map(([value, label]) => {
        const checked = volunteerData && volunteerData.tasks && volunteerData.tasks.includes(value) ? 'checked' : '';
        return `
            <div class="form-check form-check-inline">
                <input class="form-check-input volunteer-task" type="checkbox"
                       value="${value}"
                       data-row-id="${rowId}"
                       ${checked}>
                <label class="form-check-label" style="font-size: 0.85rem;">${label}</label>
            </div>
        `;
    }).join('');

    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm volunteer-select" data-row-id="${rowId}">
                <option value="">Wählen...</option>
                ${volunteers.map(v => `
                    <option value="${v.id}" ${volunteerData && volunteerData.id == v.id ? 'selected' : ''}>
                        ${v.name}
                    </option>
                `).join('')}
            </select>
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input volunteer-confirmed"
                   data-row-id="${rowId}"
                   ${volunteerData && volunteerData.confirmed ? 'checked' : ''}>
        </td>
        <td>
            ${tasksHtml}
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger remove-volunteer-btn" data-row-id="${rowId}">
                <i class="ti-trash"></i>
            </button>
        </td>
    `;

    list.appendChild(row);

    row.querySelector('.remove-volunteer-btn').addEventListener('click', function() {
        row.remove();
    });
}

function openAppointmentModal(dateStr = null, event = null, clickedDateTime = null, roomId = null, mode = 'edit') {
    const modalTitle = document.getElementById('appointmentModalLabel');
    const deleteBtn = document.getElementById('deleteAppointmentBtn');
    const duplicateBtn = document.getElementById('duplicateAppointmentBtn');
    const roomSelect = document.getElementById('appointmentRoom');
    const titleInput = document.getElementById('appointmentTitle');
    const descInput = document.getElementById('appointmentDescription');
    const dateRangeInput = document.getElementById('appointmentDateRange');
    const allDayCheckbox = document.getElementById('appointmentAllDay');
    const recurringCheckbox = document.getElementById('appointmentRecurring');

    // Reset
    lastOpenedAppointmentEvent = null;

    if (roomSelect) roomSelect.value = '';
    if (titleInput) titleInput.value = '';
    if (descInput) descInput.value = '';
    if (allDayCheckbox) allDayCheckbox.checked = false;
    if (recurringCheckbox) recurringCheckbox.checked = false;
    document.getElementById('recurrenceOptions').style.display = 'none';

    // Duplizieren-Button default aus
    if (duplicateBtn) duplicateBtn.style.display = 'none';

    // Reset Type to Private
    const privateRadio = document.getElementById('typePrivate');
    if (privateRadio) {
        privateRadio.checked = true;
        updateTypeSpecificFields();
    }

    const picker = initDaterangepicker(dateRangeInput, 'dateTimeRange');

    if (event) {
        // BEARBEITEN oder DUPLIZIEREN
        lastOpenedAppointmentEvent = event;

        const isDuplicate = (mode === 'duplicate');

        if (isDuplicate) {
            modalTitle.textContent = 'Termin duplizieren';
            if (deleteBtn) deleteBtn.style.display = 'none';
        } else {
            modalTitle.textContent = 'Termin bearbeiten';
            if (deleteBtn) deleteBtn.style.display = 'inline-block';
            if (duplicateBtn) duplicateBtn.style.display = 'inline-block';
        }

        const rawId = String(event.id);
        let cleanId = rawId;
        if (rawId.includes('_')) {
            cleanId = rawId.split('_')[1];
        }

        // WICHTIG: beim Duplizieren keine ID setzen => create statt edit
        document.getElementById('appointmentId').value = isDuplicate ? '' : cleanId;

        const originalTitle = event.extendedProps?.originalTitle || event.title;
        if (titleInput) {
            titleInput.value = isDuplicate ? buildDuplicateTitle(originalTitle) : originalTitle;
        }
        if (descInput) descInput.value = event.extendedProps?.description || '';

        if (event.extendedProps && event.extendedProps.roomId && roomSelect) {
            roomSelect.value = event.extendedProps.roomId;
        }

        // Type setzen
        const appointmentType = event.extendedProps?.appointmentType || 'private';
        const typeRadio = document.querySelector(`input[name="appointmentType"][value="${appointmentType}"]`);
        if (typeRadio) {
            typeRadio.checked = true;
            updateTypeSpecificFields();
        }

        // Datum setzen
        let startDate = moment(event.startStr);
        let endDate = moment(event.endStr || event.startStr);

        if (event.allDay) {
            endDate.subtract(1, 'days');
        }

        picker.setStartDate(startDate);
        picker.setEndDate(endDate);

        if (allDayCheckbox) allDayCheckbox.checked = event.allDay;
        toggleAllDay({ target: { checked: event.allDay } });

        // Typ-spezifische Daten laden
        setTimeout(() => {
            loadTypeSpecificData(event);
        }, 100);

    } else {
        // NEU ERSTELLEN
        modalTitle.textContent = 'Neuer Termin';
        if (deleteBtn) deleteBtn.style.display = 'none';
        document.getElementById('appointmentId').value = '';

        if (roomId && roomSelect) {
            roomSelect.value = roomId;
        }

        let startDate, endDate;
        if (clickedDateTime) {
            startDate = moment(clickedDateTime);
            endDate = moment(clickedDateTime).add(1, 'hours');
            if (allDayCheckbox) allDayCheckbox.checked = false;
        } else {
            if (dateStr) {
                startDate = moment(dateStr);
            } else {
                startDate = moment();
            }
            startDate.hours(9).minutes(0).seconds(0);
            endDate = moment(startDate).add(1, 'hours');
            if (allDayCheckbox) allDayCheckbox.checked = false;
        }

        picker.setStartDate(startDate);
        picker.setEndDate(endDate);

        toggleAllDay({ target: { checked: false } });
    }

    if (appointmentModal) appointmentModal.show();
}

// Ersetze die loadTypeSpecificData Funktion mit dieser erweiterten Version:

function loadTypeSpecificData(event) {
    const type = event.extendedProps?.appointmentType;

    if (type === 'production' || type === 'closed_event' || type === 'school_event' || type === 'internal') {
        // Production setzen
        if (type === 'production' && event.extendedProps?.productionId) {
            const prodSelect = document.getElementById('productionSelect');
            if (prodSelect) prodSelect.value = event.extendedProps.productionId;
            updateProductionRequirementsInfo(event.extendedProps.productionId);
        }

        // Event Type
        if (event.extendedProps?.eventType) {
            const eventTypeRadio = document.querySelector(`input[name="eventType"][value="${event.extendedProps.eventType}"]`);
            if (eventTypeRadio) eventTypeRadio.checked = true;
        }

        // Status
        if (event.extendedProps?.status) {
            const statusRadio = document.querySelector(`input[name="appointmentStatus"][value="${event.extendedProps.status}"]`);
            if (statusRadio) statusRadio.checked = true;
        }

        // Internal Technicians
        const internalTechCheckbox = document.getElementById('internalTechniciansAttending');
        if (internalTechCheckbox) {
            internalTechCheckbox.checked = event.extendedProps?.internalTechniciansAttending || false;
        }

        // Techniker laden
        if (event.extendedProps?.technicians && event.extendedProps.technicians.length > 0) {
            const assignTechCheckbox = document.getElementById('assignTechniciansCheckbox');
            if (assignTechCheckbox) {
                assignTechCheckbox.checked = true;
                assignTechCheckbox.dispatchEvent(new Event('change'));
            }

            setTimeout(() => {
                event.extendedProps.technicians.forEach(tech => {
                    addTechnicianRow(tech);
                });
            }, 100);
        }

        // Volunteers laden
        if (event.extendedProps?.volunteers && event.extendedProps.volunteers.length > 0) {
            const assignVolCheckbox = document.getElementById('assignVolunteersCheckbox');
            if (assignVolCheckbox) {
                assignVolCheckbox.checked = true;
                assignVolCheckbox.dispatchEvent(new Event('change'));
            }

            setTimeout(() => {
                event.extendedProps.volunteers.forEach(vol => {
                    addVolunteerRow(vol);
                });
            }, 100);
        }

    } else if (type === 'cleaning') {
        if (event.extendedProps?.cleaningId) {
            const cleanSelect = document.getElementById('cleaningSelect');
            if (cleanSelect) cleanSelect.value = event.extendedProps.cleaningId;
        }
    }
}

function toggleAllDay(e) {
    const dateRangeInput = document.getElementById('appointmentDateRange');
    if (!dateRangeInput) return;

    const picker = getPickerInstance(dateRangeInput);
    if (!picker) return;

    if (e.target.checked) {
        const currentStart = picker.startDate;
        const currentEnd = picker.endDate;

        picker.timePicker = false;
        picker.locale.format = 'DD.MM.YYYY';

        picker.setStartDate(currentStart.startOf('day'));
        picker.setEndDate(currentEnd.startOf('day'));
    } else {
        picker.timePicker = true;
        picker.timePicker24Hour = true;
        picker.timePickerIncrement = 15;
        picker.locale.format = 'DD.MM.YYYY HH:mm';
    }

    picker.updateView();
}

function saveAppointment() {
    const id = document.getElementById('appointmentId').value;
    let title = document.getElementById('appointmentTitle').value;
    const description = document.getElementById('appointmentDescription').value;
    const roomId = document.getElementById('appointmentRoom') ? document.getElementById('appointmentRoom').value : null;

    const selectedRadio = document.querySelector('input[name="appointmentType"]:checked');
    const type = selectedRadio ? selectedRadio.value : 'private';

    // Titel für Produktion/Reinigung automatisch setzen (Title ist dort ausgeblendet)
    if (!title && (type === 'production' || type === 'cleaning')) {
        if (type === 'production') {
            const prodSelect = document.getElementById('productionSelect');
            const selectedText = prodSelect && prodSelect.selectedOptions && prodSelect.selectedOptions[0]
                ? prodSelect.selectedOptions[0].textContent.trim()
                : '';
            title = selectedText || 'Produktion';
        } else if (type === 'cleaning') {
            const cleanSelect = document.getElementById('cleaningSelect');
            const selectedText = cleanSelect && cleanSelect.selectedOptions && cleanSelect.selectedOptions[0]
                ? cleanSelect.selectedOptions[0].textContent.trim()
                : '';
            title = selectedText || 'Reinigung';
        }
    }

    // Nur bei Typen wo Titel sichtbar ist, hart validieren
    if (!title && !(type === 'production' || type === 'cleaning')) {
        alert('Bitte geben Sie einen Titel ein.');
        return;
    }

    const allDay = document.getElementById('appointmentAllDay').checked;
    const dateRangeInput = document.getElementById('appointmentDateRange');

    const picker = getPickerInstance(dateRangeInput);

    if (!picker || !picker.startDate || !picker.endDate) {
        alert('Bitte wählen Sie einen Zeitraum aus.');
        return;
    }

    let startDate = picker.startDate;
    let endDate = picker.endDate;

    let start, end;

    if (allDay) {
        start = startDate.format('YYYY-MM-DD') + 'T00:00:00';
        end = endDate.clone().add(1, 'days').format('YYYY-MM-DD') + 'T00:00:00';
    } else {
        start = startDate.format('YYYY-MM-DDTHH:mm:ss');
        end = endDate.format('YYYY-MM-DDTHH:mm:ss');
    }

    const data = {
        title,
        description,
        start,
        end,
        allDay,
        roomId,
        type
    };

    // Typ-spezifische Daten sammeln
    if (type === 'production') {
        const prodSelect = document.getElementById('productionSelect');
        data.productionId = prodSelect ? prodSelect.value : null;

        const eventTypeRadio = document.querySelector('input[name="eventType"]:checked');
        data.eventType = eventTypeRadio ? eventTypeRadio.value : null;

        const statusRadio = document.querySelector('input[name="appointmentStatus"]:checked');
        data.status = statusRadio ? statusRadio.value : null;

        const internalTechCheckbox = document.getElementById('internalTechniciansAttending');
        data.internalTechniciansAttending = internalTechCheckbox ? internalTechCheckbox.checked : false;

        data.technicians = collectTechnicians();
        data.volunteers = collectVolunteers();

    } else if (['closed_event', 'school_event', 'internal', 'private'].includes(type)) {
        const assignTechCheckbox = document.getElementById('assignTechniciansCheckbox');
        const assignVolCheckbox = document.getElementById('assignVolunteersCheckbox');

        data.technicians = (assignTechCheckbox && assignTechCheckbox.checked) ? collectTechnicians() : [];
        data.volunteers = (assignVolCheckbox && assignVolCheckbox.checked) ? collectVolunteers() : [];

        // sicherheitshalber leer setzen
        data.eventType = null;
        data.status = null;
        data.internalTechniciansAttending = false;

    } else if (type === 'cleaning') {
        const cleanSelect = document.getElementById('cleaningSelect');
        data.cleaningId = cleanSelect ? cleanSelect.value : null;
    }

    // Wiederholung
    const recurringCheckbox = document.getElementById('appointmentRecurring');
    if (recurringCheckbox && recurringCheckbox.checked) {
        const freqSelect = document.getElementById('recurrenceFrequency');
        const endDateInput = document.getElementById('recurrenceEndDate');

        if (freqSelect) {
            data.recurrenceFrequency = freqSelect.value;
        }

        if (endDateInput) {
            // Fix: Enddatum-Picker ist singleDate → startDate verwenden
            const endPicker = getPickerInstance(endDateInput) || initDaterangepicker(endDateInput, 'singleDate');
            if (endPicker && endPicker.startDate) {
                data.recurrenceEndDate = endPicker.startDate.format('YYYY-MM-DD');
            }
        }

        if (!data.recurrenceEndDate) {
            alert('Bitte geben Sie ein Enddatum für die Wiederholung an.');
            return;
        }
    }

    const url = id ? `/appointment/${id}/edit` : '/appointment/create';
    const method = id ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                appointmentModal.hide();
                refreshAllCalendars();
                fetch('/appointments/all').then(r=>r.json()).then(d => {
                    allEvents = d;
                });
                showToast('success', 'Termin erfolgreich gespeichert');
            } else {
                alert('Fehler beim Speichern: ' + (result.message || 'Unbekannter Fehler'));
            }
        })
        .catch(error => {
            console.error(error);
            alert('Fehler beim Speichern.');
        });
}

function collectTechnicians() {
    const rows = document.querySelectorAll('#techniciansList tr');
    const technicians = [];

    rows.forEach(row => {
        const rowId = row.dataset.rowId;
        const select = row.querySelector(`.technician-select[data-row-id="${rowId}"]`);
        const confirmedCheckbox = row.querySelector(`.technician-confirmed[data-row-id="${rowId}"]`);
        const lightingCheckbox = row.querySelector(`.technician-lighting[data-row-id="${rowId}"]`);
        const soundCheckbox = row.querySelector(`.technician-sound[data-row-id="${rowId}"]`);
        const setupCheckbox = row.querySelector(`.technician-setup[data-row-id="${rowId}"]`);

        if (select && select.value) {
            technicians.push({
                id: select.value,
                confirmed: confirmedCheckbox ? confirmedCheckbox.checked : false,
                lighting: lightingCheckbox ? lightingCheckbox.checked : false,
                sound: soundCheckbox ? soundCheckbox.checked : false,
                setup: setupCheckbox ? setupCheckbox.checked : false
            });
        }
    });

    return technicians;
}

function collectVolunteers() {
    const rows = document.querySelectorAll('#volunteersList tr');
    const volunteers = [];

    rows.forEach(row => {
        const rowId = row.dataset.rowId;
        const select = row.querySelector(`.volunteer-select[data-row-id="${rowId}"]`);
        const confirmedCheckbox = row.querySelector(`.volunteer-confirmed[data-row-id="${rowId}"]`);
        const taskCheckboxes = row.querySelectorAll(`.volunteer-task[data-row-id="${rowId}"]:checked`);

        if (select && select.value) {
            const tasks = Array.from(taskCheckboxes).map(cb => cb.value);
            volunteers.push({
                id: select.value,
                confirmed: confirmedCheckbox ? confirmedCheckbox.checked : false,
                tasks: tasks
            });
        }
    });

    return volunteers;
}

function showDeleteConfirmation(appointmentId) {
    currentDeleteAppointmentId = appointmentId;

    const message = document.getElementById('deleteConfirmMessage');
    const recurringOptions = document.getElementById('deleteRecurringOptions');

    // Check if recurring (ToDo: Backend muss Info liefern ob recurring)
    // Für jetzt: immer beide Optionen anzeigen wenn es ein Edit ist
    if (appointmentId) {
        recurringOptions.style.display = 'block';
        message.textContent = 'Möchten Sie diesen Termin löschen?';
    } else {
        recurringOptions.style.display = 'none';
        message.textContent = 'Möchten Sie diesen Termin wirklich löschen?';
    }

    if (deleteConfirmModal) {
        deleteConfirmModal.show();
    }
}

function confirmDelete() {
    if (!currentDeleteAppointmentId) return;

    const deleteMode = document.querySelector('input[name="deleteMode"]:checked');
    const mode = deleteMode ? deleteMode.value : 'single';

    const rawId = String(currentDeleteAppointmentId);
    let cleanId = rawId;
    if(rawId.includes('_')) {
        cleanId = rawId.split('_')[1];
    }

    fetch(`/appointment/${cleanId}/delete`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mode: mode })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (deleteConfirmModal) deleteConfirmModal.hide();
                if (appointmentModal && appointmentModal._isShown) appointmentModal.hide();
                refreshAllCalendars();
                fetch('/appointments/all').then(r=>r.json()).then(d => {
                    allEvents = d;
                });
                showToast('success', 'Termin erfolgreich gelöscht');
            } else {
                alert('Fehler beim Löschen.');
            }
        })
        .catch(error => {
            console.error(error);
            alert('Fehler beim Löschen.');
        });
}

function initKeyManagement() {
    const modalEl = document.getElementById('keyManagementModal');
    if (!modalEl) return;

    keyModal = new Modal(modalEl);

    document.querySelectorAll('.key-item-btn').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            openKeyModal(this);
        });
    });

    const statusSelect = document.getElementById('keyStatus');
    if (statusSelect) {
        const newStatus = statusSelect.cloneNode(true);
        statusSelect.parentNode.replaceChild(newStatus, statusSelect);

        newStatus.addEventListener('change', function() {
            const details = document.getElementById('borrowDetails');
            if (this.value === 'borrowed') {
                if(details) details.style.display = 'block';
            } else {
                if(details) details.style.display = 'none';
            }
        });
    }

    const typeSelect = document.getElementById('borrowerType');
    if (typeSelect) {
        const newType = typeSelect.cloneNode(true);
        typeSelect.parentNode.replaceChild(newType, typeSelect);

        newType.addEventListener('change', function() {
            document.querySelectorAll('.borrower-select').forEach(el => el.style.display = 'none');
            if (this.value) {
                const targetId = this.value + 'SelectDiv';
                const target = document.getElementById(targetId);
                if(target) target.style.display = 'block';
            }
        });
    }

    const saveBtn = document.getElementById('saveKeyBtn');
    if (saveBtn) {
        const newSave = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSave, saveBtn);
        newSave.addEventListener('click', saveKeyData);
    }
}

function openKeyModal(keyId) {
    var btn = document.querySelector(`.key-item-btn[data-key-id="${keyId}"]`);

    if (!btn) {
        btn = keyId;
    }

    const id = btn.dataset.keyId;
    const name = btn.dataset.keyName;
    const status = btn.dataset.keyStatus;

    const keyIdEl = document.getElementById('keyId');
    if(keyIdEl) keyIdEl.value = id;

    const titleEl = document.getElementById('modalKeyNameTitle');
    if(titleEl) titleEl.textContent = name;

    const statusEl = document.getElementById('keyStatus');
    if(statusEl) {
        statusEl.value = status;
        statusEl.dispatchEvent(new Event('change'));
    }

    const borrowEl = document.getElementById('keyBorrowDate');
    const returnEl = document.getElementById('keyReturnDate');

    if (borrowEl) {
        const borrowPicker = initDaterangepicker(borrowEl, 'singleDateTime');
        const borrowDate = btn.dataset.borrowDate;
        if (borrowDate && borrowPicker) {
            borrowPicker.setStartDate(moment(borrowDate));
        }
    }

    if (returnEl) {
        const returnPicker = initDaterangepicker(returnEl, 'singleDateTime');
        const returnDate = btn.dataset.returnDate;
        if (returnDate && returnPicker) {
            returnPicker.setStartDate(moment(returnDate));
        }
    }

    const uId = btn.dataset.userId;
    const tId = btn.dataset.techId;
    const pId = btn.dataset.prodId;
    const cId = btn.dataset.cleanId;

    const typeSelect = document.getElementById('borrowerType');
    if(typeSelect) {
        const uEl = document.getElementById('userId'); if(uEl) uEl.value = '';
        const tEl = document.getElementById('technicianId'); if(tEl) tEl.value = '';
        const pEl = document.getElementById('productionId'); if(pEl) pEl.value = '';
        const cEl = document.getElementById('cleaningId'); if(cEl) cEl.value = '';

        if (uId && uEl) { typeSelect.value = 'user'; uEl.value = uId; }
        else if (tId && tEl) { typeSelect.value = 'technician'; tEl.value = tId; }
        else if (pId && pEl) { typeSelect.value = 'production'; pEl.value = pId; }
        else if (cId && cEl) { typeSelect.value = 'cleaning'; cEl.value = cId; }
        else { typeSelect.value = ''; }

        typeSelect.dispatchEvent(new Event('change'));
    }

    if(keyModal) keyModal.show();
}

function saveKeyData() {
    const idEl = document.getElementById('keyId');
    const statusEl = document.getElementById('keyStatus');

    if(!idEl || !statusEl) return;

    const id = idEl.value;
    const status = statusEl.value;

    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value : null;
    };

    const borrowEl = document.getElementById('keyBorrowDate');
    const returnEl = document.getElementById('keyReturnDate');

    let borrowDate = null;
    let returnDate = null;

    if (borrowEl) {
        const borrowPicker = getPickerInstance(borrowEl);
        if (borrowPicker && borrowPicker.startDate) {
            borrowDate = borrowPicker.startDate.format('YYYY-MM-DD HH:mm:ss');
        }
    }

    if (returnEl) {
        const returnPicker = getPickerInstance(returnEl);
        if (returnPicker && returnPicker.startDate) {
            returnDate = returnPicker.startDate.format('YYYY-MM-DD HH:mm:ss');
        }
    }

    const data = {
        status: status,
        userId: getVal('userId'),
        technicianId: getVal('technicianId'),
        productionId: getVal('productionId'),
        cleaningId: getVal('cleaningId'),
        borrowDate: borrowDate,
        returnDate: returnDate
    };

    fetch(`/dashboard/key/${id}/update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => {
        if(res.ok) {
            keyModal.hide();
            window.location.reload();
        } else {
            alert('Fehler beim Speichern');
        }
    }).catch(e => console.error(e));
}

function bootDashboard() {
    initCalendar();
    initKeyManagement();
}

// Export für Lazy-Import aus app.js
export { bootDashboard };

// Doppelte Bindings vermeiden (Turbo feuert Events mehrfach)
function bindDashboardOnce() {
    if (window.__dashboardBound) return;
    window.__dashboardBound = true;

    document.addEventListener('DOMContentLoaded', bootDashboard);
    document.addEventListener('turbo:load', bootDashboard);
    document.addEventListener('turbo:render', bootDashboard);
    document.addEventListener('turbo:visit', bootDashboard);
}

bindDashboardOnce();

import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import deLocale from '@fullcalendar/core/locales/de';
import { Modal } from 'bootstrap';

var doughnutChart;
function initDoughnutChart() {
    // if(typeof doughnutChart !== "undefined") {
    //     doughnutChart.destroy();
    // }
    // ... chart logic ...
}

let allEvents = [];
let currentSelectedDate = new Date();
let calendarInstances = []; // Array für Raum-Kalender
let globalCalendar = null; // Globaler Kalender
let currentView = 'global'; // Aktuelle Ansicht

// Mapping für Farben zu Icon-Farben (Adminator CSS Klassen)
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

// Funktion um Datum im Header anzuzeigen
function displayDate(date) {
    currentSelectedDate = new Date(date);

    const dayNumber = date.getDate();
    const dayName = date.toLocaleDateString('de-DE', { weekday: 'long' });

    // Formatiere das Datum im Format dd.mm.yyyy
    const formattedDate = date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    const numText = document.getElementById('day-number-text');
    if(numText) numText.textContent = formattedDate;

    const suffix = document.getElementById('day-suffix');
    if(suffix) suffix.textContent = '';

    const nameEl = document.getElementById('day-name');
    if(nameEl) nameEl.textContent = dayName.charAt(0).toUpperCase() + dayName.slice(1);
}

// Funktion um Events eines bestimmten Tages anzuzeigen
function displayEventsForDate(date, events) {
    const listEl = document.getElementById('day-events-list');

    if (!listEl) return;

    const targetDate = new Date(date);
    targetDate.setHours(0, 0, 0, 0);

    const dayEvents = events.filter(event => {
        const eventStartDate = new Date(event.start);
        eventStartDate.setHours(0, 0, 0, 0);

        const eventEndDate = event.end ? new Date(event.end) : new Date(event.start);
        eventEndDate.setHours(0, 0, 0, 0);

        return (eventStartDate.getTime() <= targetDate.getTime() &&
            eventEndDate.getTime() >= targetDate.getTime());
    });

    if (dayEvents.length === 0) {
        listEl.innerHTML = `
            <li class="bdB peers ai-c jc-sb fxw-nw p-20">
                <div class="c-grey-600">
                    <span>Keine Termine an diesem Tag</span>
                </div>
            </li>
        `;
    } else {
        const now = new Date();

        listEl.innerHTML = dayEvents.map(event => {
            const eventColor = event.color || event.backgroundColor || '#4285f4';
            const iconClass = colorToIconClass[eventColor] || 'c-blue-500';
            const eventTime = event.start && !event.allDay ? new Date(event.start).toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }) : '';

            const dateStr = new Date(event.start).toLocaleDateString('de-DE', {
                day: '2-digit',
                month: 'short'
            });

            // Prüfe, ob Event in der Vergangenheit liegt
            const eventEnd = event.end ? new Date(event.end) : new Date(event.start);
            const isPast = eventEnd < now;
            const pastClass = isPast ? 'style="opacity: 0.5;"' : '';
            const pastTextDecoration = isPast ? 'style="text-decoration: line-through;"' : '';

            return `
                <li class="bdB peers ai-c jc-sb fxw-nw" ${pastClass}>
                    <a class="td-n p-20 peers fxw-nw mR-20 peer-greed c-grey-900 event-item"
                       href="javascript:void(0);"
                       data-event-id="${event.id}">
                        <div class="peer mR-15">
                            <i class="fa fa-fw fa-clock-o ${iconClass}"></i>
                        </div>
                        <div class="peer">
                            <span class="fw-600" ${pastTextDecoration}>${event.title}</span>
                            <div class="c-grey-600">
                                <span class="c-grey-700">${dateStr}${eventTime ? ' - ' + eventTime + ' Uhr' : ''}</span>
                                ${event.allDay ? '<i class="mL-5">(Ganztägig)</i>' : ''}
                                ${event.description ? '<br><i>' + event.description + '</i>' : ''}
                            </div>
                        </div>
                    </a>
                    ${(event.extendedProps && ['private', 'cleaning', 'technician'].includes(event.extendedProps.type)) ? `
                    <div class="peers mR-15">
                        <div class="peer">
                            <a href="javascript:void(0);"
                               class="td-n c-deep-purple-500 cH-blue-500 fsz-md p-5 edit-event"
                               data-event-id="${event.id}">
                                <i class="ti-pencil"></i>
                            </a>
                        </div>
                        <div class="peer">
                            <a href="javascript:void(0);"
                               class="td-n c-red-500 cH-blue-500 fsz-md p-5 delete-event"
                               data-event-id="${event.id}">
                                <i class="ti-trash"></i>
                            </a>
                        </div>
                    </div>` : ''}
                </li>
            `;
        }).join('');

        // Event Listener für Klicks auf Events
        listEl.querySelectorAll('.event-item, .edit-event').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const eventId = this.getAttribute('data-event-id');
                // Versuche Event in allEvents zu finden
                const event = allEvents.find(evt => evt.id == eventId);
                if (event) {
                    openAppointmentModal(null, event);
                }
            });
        });

        // Event Listener für Löschen
        listEl.querySelectorAll('.delete-event').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const eventId = this.getAttribute('data-event-id');
                if (confirm('Möchten Sie diesen Termin wirklich löschen?')) {
                    deleteAppointmentById(eventId);
                }
            });
        });
    }
}

var appointmentModal;
var productionEventModal;

function initCalendar() {
    const modalEl = document.getElementById('appointmentModal');
    if (modalEl) {
        appointmentModal = new Modal(modalEl);
    }

    const prodEventModalEl = document.getElementById('productionEventModal');
    if (prodEventModalEl) {
        productionEventModal = new Modal(prodEventModalEl);
    }

    // Aufräumen alter Instanzen
    calendarInstances.forEach(cal => cal.destroy());
    calendarInstances = [];
    if (globalCalendar) {
        globalCalendar.destroy();
        globalCalendar = null;
    }

    // Filter-Listener initialisieren
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    filterCheckboxes.forEach(box => {
        const newBox = box.cloneNode(true);
        box.parentNode.replaceChild(newBox, box);
        newBox.addEventListener('change', refreshCurrentView);
    });

    // View Switcher Listener
    const viewRadios = document.querySelectorAll('input[name="calendarView"]');
    viewRadios.forEach(radio => {
        const newRadio = radio.cloneNode(true);
        radio.parentNode.replaceChild(newRadio, radio);
        newRadio.addEventListener('change', function() {
            currentView = this.value;
            switchCalendarView(this.value);
        });
    });

    // NEU: Sync Button Listener
    const syncBtn = document.getElementById('syncApiBtn');
    if (syncBtn) {
        const newSyncBtn = syncBtn.cloneNode(true);
        syncBtn.parentNode.replaceChild(newSyncBtn, syncBtn);
        newSyncBtn.addEventListener('click', triggerApiSync);
    }

    // Mobile-Check
    const isMobile = window.innerWidth <= 767;

    // Beide Kalender initialisieren
    initGlobalCalendar(isMobile);
    initRoomCalendars(isMobile);

    // Sidebar initialisieren
    fetch('/appointments/all')
        .then(response => response.json())
        .then(data => {
            allEvents = data;
            displayDate(currentSelectedDate);
            displayEventsForDate(currentSelectedDate, data);
        })
        .catch(e => console.error(e));

    setupModalListeners();
}

// NEU: API Sync triggern
function triggerApiSync() {
    const btn = document.getElementById('syncApiBtn');
    if (!btn) return;

    // Button deaktivieren und Loading-State anzeigen
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ti-reload spin"></i> Synchronisiere...';

    // Add spinning animation via CSS (optional)
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
                // Erfolg anzeigen
                showToast('success', data.message);

                // Kalender neu laden
                refreshCurrentView();

                // Sidebar neu laden
                fetch('/appointments/all')
                    .then(r => r.json())
                    .then(events => {
                        allEvents = events;
                        displayEventsForDate(currentSelectedDate, events);
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
    // Verwende Bootstrap Alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-remove nach 5 Sekunden
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function initGlobalCalendar(isMobile) {
    const calendarEl = document.getElementById('global-calendar');
    if (!calendarEl) return;

    globalCalendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: isMobile ? 'timeGridDay' : 'dayGridMonth',
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        firstDay: 1,
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        height: 700,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            meridiem: false
        },
        eventDisplay: 'block',
        events: function(info, successCallback, failureCallback) {
            const activeFilters = Array.from(document.querySelectorAll('.filter-checkbox:checked'))
                .map(cb => cb.value)
                .join(',');

            // Kein roomId Filter - alle Räume anzeigen
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
            const type = info.event.extendedProps.type;

            if (type === 'production_event') {
                const productionEventId = info.event.extendedProps.productionEventId;
                if (productionEventId) {
                    openProductionEventModal(productionEventId);
                }
            } else if (['private', 'cleaning', 'technician', 'production'].includes(type)) {
                openAppointmentModal(null, info.event);
            } else {
                alert(info.event.title + '\n' + (info.event.extendedProps.description || ''));
            }
        },
        eventDidMount: function(info) {
            const now = new Date();
            const eventEnd = info.event.end || info.event.start;
            if (eventEnd < now) {
                info.el.style.opacity = '0.5';
                info.el.style.textDecoration = 'line-through';
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

// NEU: Aktualisiert die aktuelle Ansicht
function refreshCurrentView() {
    if (currentView === 'global') {
        if (globalCalendar) {
            globalCalendar.refetchEvents();
        }
    } else {
        calendarInstances.forEach(cal => cal.refetchEvents());
    }
}

// ÄNDERN: Alte Funktion wird umbenannt
function refreshAllCalendars() {
    refreshCurrentView();
}

function createRoomCalendar(calendarEl, isMobile) {
    const roomId = calendarEl.dataset.roomId || '';

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: isMobile ? 'timeGridDay' : 'dayGridMonth',
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        firstDay: 1,
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        height: 600,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            meridiem: false
        },
        eventDisplay: 'block',
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
            const type = info.event.extendedProps.type;

            if (type === 'production_event') {
                const productionEventId = info.event.extendedProps.productionEventId;
                if (productionEventId) {
                    openProductionEventModal(productionEventId);
                }
            } else if (['private', 'cleaning', 'technician', 'production'].includes(type)) {
                openAppointmentModal(null, info.event);
            } else {
                alert(info.event.title + '\n' + (info.event.extendedProps.description || ''));
            }
        },
        eventDidMount: function(info) {
            const now = new Date();
            const eventEnd = info.event.end || info.event.start;
            if (eventEnd < now) {
                info.el.style.opacity = '0.5';
                info.el.style.textDecoration = 'line-through';
            }
        },
        editable: false
    });

    calendar.render();
    return calendar;
}

// NEU: Zwischen Ansichten wechseln
function switchCalendarView(view) {
    const globalContainer = document.getElementById('globalCalendarContainer');
    const roomsContainer = document.getElementById('roomCalendarsContainer');

    if (view === 'global') {
        // Zeige globalen Kalender
        if (globalContainer) globalContainer.style.display = 'block';
        if (roomsContainer) roomsContainer.style.display = 'none';

        // Refresh global calendar
        if (globalCalendar) {
            globalCalendar.updateSize();
            globalCalendar.refetchEvents();
        }
    } else {
        // Zeige Raum-Kalender
        if (globalContainer) globalContainer.style.display = 'none';
        if (roomsContainer) roomsContainer.style.display = 'block';

        // Refresh room calendars
        calendarInstances.forEach(cal => {
            cal.updateSize();
            cal.refetchEvents();
        });
    }
}

function initSingleCalendar(calendarEl, isMobile) {
    const roomId = calendarEl.dataset.roomId || '';

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: isMobile ? 'timeGridDay' : 'dayGridMonth',
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        firstDay: 1,
        headerToolbar: isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        height: 600,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            meridiem: false
        },
        eventDisplay: 'block', // WICHTIG: Erzwingt Block-Darstellung statt Dot-Darstellung
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
            const type = info.event.extendedProps.type;

            if (type === 'production_event') {
                // ProductionEvent Modal öffnen (nicht editierbar über Dashboard)
                const productionEventId = info.event.extendedProps.productionEventId;
                if (productionEventId) {
                    openProductionEventModal(productionEventId);
                }
            } else if (['private', 'cleaning', 'technician', 'production'].includes(type)) {
                // Appointment Modal
                openAppointmentModal(null, info.event);
            } else {
                alert(info.event.title + '\n' + (info.event.extendedProps.description || ''));
            }
        },
        eventDidMount: function(info) {
            const now = new Date();
            const eventEnd = info.event.end || info.event.start;
            if (eventEnd < now) {
                info.el.style.opacity = '0.5';
                info.el.style.textDecoration = 'line-through';
            }
        },
        editable: false
    });

    calendar.render();
    calendarInstances.push(calendar);
}

function openProductionEventModal(eventId) {
    const modalBody = document.getElementById('productionEventModalBody');
    const modalTitle = document.getElementById('productionEventModalTitle');
    const editBtn = document.getElementById('editProductionEventBtn');

    if (!productionEventModal) return;

    // Loading State
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
        </div>
    `;

    productionEventModal.show();

    // Daten laden
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

            // Edit Button konfigurieren
            if (editBtn) {
                editBtn.href = `/production-event/${event.id}/edit`;
                editBtn.style.display = 'inline-block';
            }

            // Modal Content aufbauen
            let html = '';

            // Produktionsbild falls vorhanden
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

            // Event Details
            html += `<div class="row mb-4">`;

            // Linke Spalte: Event Info
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

            html += `</div>`; // Ende linke Spalte

            // Rechte Spalte: Plätze & Preise
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

            html += `</div>`; // Ende rechte Spalte
            html += `</div>`; // Ende row

            // Preise anzeigen
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

            // Kategorien (z.B. "Ausverkauft")
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

            // Reservierungshinweis
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

            // Produktionsbeschreibung
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

            // Link zur Website
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
    // Helper um Listener nicht mehrfach zu binden
    const saveBtn = document.getElementById('saveAppointmentBtn');
    if (saveBtn) {
        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', saveAppointment);
    }

    const deleteBtn = document.getElementById('deleteAppointmentBtn');
    if (deleteBtn) {
        const newBtn = deleteBtn.cloneNode(true);
        deleteBtn.parentNode.replaceChild(newBtn, deleteBtn);
        newBtn.addEventListener('click', deleteAppointment);
    }

    const allDayCheckbox = document.getElementById('appointmentAllDay');
    if (allDayCheckbox) {
        const newBtn = allDayCheckbox.cloneNode(true);
        allDayCheckbox.parentNode.replaceChild(newBtn, allDayCheckbox);
        newBtn.addEventListener('change', toggleAllDay);
    }

    // Farbauswahl Listener
    // document.querySelectorAll('.color-option').forEach(option => {
    //     const newOpt = option.cloneNode(true);
    //     option.parentNode.replaceChild(newOpt, option);
    //     newOpt.addEventListener('click', function() {
    //         document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
    //         this.classList.add('selected');
    //         document.getElementById('appointmentColor').value = this.getAttribute('data-color');
    //     });
    // });

    // Typ Auswahl Listener (Radio Buttons)
    document.querySelectorAll('.type-radio').forEach(radio => {
        const newRadio = radio.cloneNode(true);
        radio.parentNode.replaceChild(newRadio, radio);
        newRadio.addEventListener('change', toggleTypeFields);
    });

    // Plus Button
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

function toggleTypeFields() {
    const selectedRadio = document.querySelector('input[name="appointmentType"]:checked');
    const type = selectedRadio ? selectedRadio.value : 'private';

    const cleanGroup = document.getElementById('cleaningSelectGroup');
    const techGroup = document.getElementById('technicianSelectGroup');
    const prodGroup = document.getElementById('productionSelectGroup');
    const titleGroup = document.getElementById('titleGroup');
    const titleInput = document.getElementById('appointmentTitle');

    if (cleanGroup) cleanGroup.style.display = (type === 'cleaning') ? 'block' : 'none';
    if (techGroup) techGroup.style.display = (type === 'technician') ? 'block' : 'none';
    if (prodGroup) prodGroup.style.display = (type === 'production') ? 'block' : 'none';

    // Titel immer anzeigen (auch bei Production)
    if (titleGroup) {
        titleGroup.style.display = 'block';
        if(titleInput) titleInput.setAttribute('required', 'required');
    }
}

function openAppointmentModal(dateStr = null, event = null, clickedDateTime = null, roomId = null) {
    const modalTitle = document.getElementById('appointmentModalLabel');
    const deleteBtn = document.getElementById('deleteAppointmentBtn');
    const roomSelect = document.getElementById('appointmentRoom');
    const cleanSelect = document.getElementById('appointmentCleaning');
    const techSelect = document.getElementById('appointmentTechnician');
    const prodSelect = document.getElementById('appointmentProduction');
    const titleInput = document.getElementById('appointmentTitle');
    const descInput = document.getElementById('appointmentDescription');

    // Reset All Fields
    if(roomSelect) roomSelect.value = '';
    if(cleanSelect) cleanSelect.value = '';
    if(techSelect) techSelect.value = '';
    if(prodSelect) prodSelect.value = '';
    if(titleInput) titleInput.value = '';
    if(descInput) descInput.value = '';

    // Reset Radios to Private
    const privateRadio = document.getElementById('typePrivate');
    if(privateRadio) {
        privateRadio.checked = true;
        toggleTypeFields();
    }

    if (event) {
        modalTitle.textContent = 'Termin bearbeiten';
        deleteBtn.style.display = 'inline-block';

        const rawId = String(event.id);
        let cleanId = rawId;
        if(rawId.includes('_')) {
            cleanId = rawId.split('_')[1];
        }
        document.getElementById('appointmentId').value = cleanId;

        // WICHTIG: Verwende originalTitle statt title für das Modal
        const originalTitle = event.extendedProps?.originalTitle || event.title;
        if(titleInput) titleInput.value = originalTitle;
        if(descInput) descInput.value = event.extendedProps?.description || '';

        // Raum setzen
        if (event.extendedProps && event.extendedProps.roomId && roomSelect) {
            roomSelect.value = event.extendedProps.roomId;
        }

        // Typ & Relationen setzen
        const type = event.extendedProps.type || 'private';
        const typeRadio = document.querySelector(`input[name="appointmentType"][value="${type}"]`);
        if(typeRadio) {
            typeRadio.checked = true;
            toggleTypeFields();
        }

        if (type === 'cleaning' && event.extendedProps.cleaningId && cleanSelect) {
            cleanSelect.value = event.extendedProps.cleaningId;
        }
        if (type === 'technician' && event.extendedProps.technicianId && techSelect) {
            techSelect.value = event.extendedProps.technicianId;
        }
        if (type === 'production' && event.extendedProps.productionId && prodSelect) {
            prodSelect.value = event.extendedProps.productionId;
        }

        // Datum/Zeit
        let startDate = event.start;
        let endDate = event.end || event.start;

        if (event.allDay) {
            const adjustedEnd = new Date(endDate);
            adjustedEnd.setDate(adjustedEnd.getDate() - 1);
            endDate = adjustedEnd;
        }

        document.getElementById('appointmentStart').value = formatDateForInput(startDate, event.allDay);
        document.getElementById('appointmentEnd').value = formatDateForInput(endDate, event.allDay);
        document.getElementById('appointmentAllDay').checked = event.allDay;

        toggleAllDay({target: {checked: event.allDay}});

    } else {
        // Neu erstellen
        modalTitle.textContent = 'Neuer Termin';
        deleteBtn.style.display = 'none';
        document.getElementById('appointmentId').value = '';

        const startInput = document.getElementById('appointmentStart');
        const endInput = document.getElementById('appointmentEnd');
        startInput.value = '';
        endInput.value = '';

        if(roomId && roomSelect) {
            roomSelect.value = roomId;
        }

        let startDate, endDate;
        if (clickedDateTime) {
            startDate = clickedDateTime;
            endDate = new Date(startDate.getTime());
            endDate.setHours(endDate.getHours() + 1);
            document.getElementById('appointmentAllDay').checked = false;
        } else {
            if(dateStr) {
                startDate = new Date(dateStr);
            } else {
                startDate = new Date();
            }
            startDate.setHours(9,0,0,0);
            endDate = new Date(startDate);
            endDate.setHours(10,0,0,0);
            document.getElementById('appointmentAllDay').checked = false;
        }

        startInput.value = formatDateForInput(startDate);
        endInput.value = formatDateForInput(endDate);

        toggleAllDay({target: {checked: false}});
    }

    if(appointmentModal) appointmentModal.show();
}

function formatDateForInput(date, isAllDay = false) {
    if (!date) return '';
    const d = date instanceof Date ? date : new Date(date);

    // Verwende lokale Zeit für die Darstellung im Input-Feld
    const pad = n => String(n).padStart(2, '0');
    const datePart = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (isAllDay) {
        return datePart;
    }

    // Für zeitbasierte Events: Verwende lokale Zeit (nicht UTC)
    return `${datePart}T${pad(d.getHours() - 1)}:${pad(d.getMinutes())}`;
}

function toggleAllDay(e) {
    const startInput = document.getElementById('appointmentStart');
    const endInput = document.getElementById('appointmentEnd');
    if (e.target.checked) {
        startInput.type = 'date';
        endInput.type = 'date';
        if(startInput.value.includes('T')) startInput.value = startInput.value.split('T')[0];
        if(endInput.value.includes('T')) endInput.value = endInput.value.split('T')[0];
    } else {
        startInput.type = 'datetime-local';
        endInput.type = 'datetime-local';
    }
}

function saveAppointment() {
    const id = document.getElementById('appointmentId').value;
    let title = document.getElementById('appointmentTitle').value;
    const description = document.getElementById('appointmentDescription').value;
    const roomId = document.getElementById('appointmentRoom') ? document.getElementById('appointmentRoom').value : null;

    const selectedRadio = document.querySelector('input[name="appointmentType"]:checked');
    const type = selectedRadio ? selectedRadio.value : 'private';

    let cleaningId = null;
    let technicianId = null;
    let productionId = null;

    if (type === 'cleaning') {
        cleaningId = document.getElementById('appointmentCleaning').value;
        if (!title) title = 'Reinigung'; // Fallback
    } else if (type === 'technician') {
        technicianId = document.getElementById('appointmentTechnician').value;
        if (!title) title = 'Techniker'; // Fallback
    } else if (type === 'production') {
        productionId = document.getElementById('appointmentProduction').value;
        if (!title) title = 'Produktion'; // Fallback
    }

    let start = document.getElementById('appointmentStart').value;
    let end = document.getElementById('appointmentEnd').value;
    const allDay = document.getElementById('appointmentAllDay').checked;

    if (!title || !start || !end) {
        alert('Bitte füllen Sie alle Pflichtfelder aus.');
        return;
    }

    if (allDay) {
        start = start + 'T00:00:00';
        const parts = end.split('-');
        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        d.setDate(d.getDate() + 1);

        const nextDayStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        end = nextDayStr + 'T00:00:00';
    }

    // FARBE WIRD NICHT MEHR VOM CLIENT GESENDET - Server bestimmt sie automatisch
    const data = { title, description, start, end, allDay, roomId, cleaningId, technicianId, productionId };
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
                    displayEventsForDate(currentSelectedDate, d);
                });
            } else {
                alert('Fehler beim Speichern.');
            }
        })
        .catch(error => console.error(error));
}

function deleteAppointmentById(id) {
    const rawId = String(id);
    let cleanId = rawId;
    if(rawId.includes('_')) {
        cleanId = rawId.split('_')[1];
    }

    fetch(`/appointment/${cleanId}/delete`, { method: 'DELETE' })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (appointmentModal._isShown) appointmentModal.hide();
                refreshAllCalendars();
                fetch('/appointments/all').then(r=>r.json()).then(d => {
                    allEvents = d;
                    displayEventsForDate(currentSelectedDate, d);
                });
            } else {
                alert('Fehler beim Löschen.');
            }
        });
}

function deleteAppointment() {
    const id = document.getElementById('appointmentId').value;
    if (!id || !confirm('Wirklich löschen?')) return;
    deleteAppointmentById(id);
}

var keyModal;
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

function openKeyModal(btn) {
    if (!btn) return;

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
    if(borrowEl) borrowEl.value = btn.dataset.borrowDate;

    const returnEl = document.getElementById('keyReturnDate');
    if(returnEl) returnEl.value = btn.dataset.returnDate;

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

    const data = {
        status: status,
        userId: getVal('userId'),
        technicianId: getVal('technicianId'),
        productionId: getVal('productionId'),
        cleaningId: getVal('cleaningId'),
        borrowDate: getVal('keyBorrowDate'),
        returnDate: getVal('keyReturnDate')
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

document.addEventListener('DOMContentLoaded', function() {
    initDoughnutChart();
    initCalendar();
    initKeyManagement();
});

document.addEventListener('turbo:render', function() {
    initDoughnutChart();
    initCalendar();
    initKeyManagement();
});

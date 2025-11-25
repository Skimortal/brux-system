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
    //
    // const canvas = document.getElementById('doughnut-chart');
    //
    // if(canvas) {
    //     var invoicesOpen = canvas.getAttribute('data-invoices-open')
    //     var invoicesOpenLabel = canvas.getAttribute('data-invoices-open-label')
    //     var invoicesSent = canvas.getAttribute('data-invoices-sent')
    //     var invoicesSentLabel = canvas.getAttribute('data-invoices-sent-label')
    //     var invoicesPayed = canvas.getAttribute('data-invoices-payed')
    //     var invoicesPayedLabel = canvas.getAttribute('data-invoices-payed-label')
    //
    //     var data = {
    //         labels: [
    //             invoicesOpenLabel,
    //             invoicesSentLabel,
    //             invoicesPayedLabel
    //         ],
    //         datasets: [{
    //             label: '',
    //             data: [invoicesOpen, invoicesSent, invoicesPayed],
    //             backgroundColor: [
    //                 'rgb(255, 99, 132)',
    //                 'rgb(54, 162, 235)',
    //                 'rgb(255, 205, 86)'
    //             ],
    //             hoverOffset: 4
    //         }]
    //     };
    //
    //     new Chart(canvas, {
    //         type: 'doughnut',
    //         data: data,
    //         options: {
    //             responsive: true,
    //             maintainAspectRatio: false,
    //             cutout: '60%',
    //             plugins: {
    //                 legend: {position: 'bottom'}
    //             }
    //         }
    //     });
    // }
}

let allEvents = [];
let currentSelectedDate = new Date();
let calendarInstances = []; // Array für alle Kalender-Instanzen

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
                    ${(event.extendedProps && event.extendedProps.type === 'private') ? `
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

function initCalendar() {
    const modalEl = document.getElementById('appointmentModal');
    if (modalEl) {
        appointmentModal = new Modal(modalEl);
    }

    // Aufräumen alter Instanzen
    calendarInstances.forEach(cal => cal.destroy());
    calendarInstances = [];

    // Alle Kalender-Container finden
    const calendarEls = document.querySelectorAll('.room-calendar');

    // Falls keine Raum-Kalender da sind, versuche den alten Dashboard-Kalender als Fallback
    if (calendarEls.length === 0) {
        const fallbackEl = document.getElementById('dashboard-calendar');
        if (fallbackEl) {
            initSingleCalendar(fallbackEl, window.innerWidth <= 767);
            return;
        }
        // Wenn gar keine Kalender da sind, abbrechen (aber Modal-Events und Sidebar trotzdem laden)
    }

    // Filter-Listener initialisieren
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    filterCheckboxes.forEach(box => {
        // Clone Node um alte Event Listener zu entfernen
        const newBox = box.cloneNode(true);
        box.parentNode.replaceChild(newBox, box);
        newBox.addEventListener('change', refreshAllCalendars);
    });

    // Mobile-Check
    const isMobile = window.innerWidth <= 767;

    // Für jeden Raum einen Kalender initialisieren
    calendarEls.forEach(calendarEl => {
        initSingleCalendar(calendarEl, isMobile);
    });

    // Sidebar initialisieren (Lade allgemeine Termine oder User-Termine)
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

function initSingleCalendar(calendarEl, isMobile) {
    const roomId = calendarEl.dataset.roomId || '';

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: isMobile ? 'timeGridDay' : 'dayGridMonth', // Standard auf Woche geändert für Raumplanung
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
        events: function(info, successCallback, failureCallback) {
            // Filter sammeln
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
            // Wenn man in den Kalender klickt -> Termin erstellen
            openAppointmentModal(info.dateStr, null, info.date);
        },
        eventClick: function(info) {
            // Nur bearbeiten wenn 'private' (User Termin)
            if (info.event.extendedProps.type === 'private') {
                openAppointmentModal(null, info.event);
            } else {
                // Nur Info Alert für andere
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
        editable: false // Erstmal deaktivieren, da DragDrop komplexer ist mit Filtern
    });

    calendar.render();
    calendarInstances.push(calendar);
}

function refreshAllCalendars() {
    calendarInstances.forEach(cal => cal.refetchEvents());
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
    document.querySelectorAll('.color-option').forEach(option => {
        const newOpt = option.cloneNode(true);
        option.parentNode.replaceChild(newOpt, option);
        newOpt.addEventListener('click', function() {
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('appointmentColor').value = this.getAttribute('data-color');
        });
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

function openAppointmentModal(dateStr = null, event = null, clickedDateTime = null) {
    const modalTitle = document.getElementById('appointmentModalLabel');
    const deleteBtn = document.getElementById('deleteAppointmentBtn');

    if (event) {
        modalTitle.textContent = 'Termin bearbeiten';
        deleteBtn.style.display = 'inline-block';

        // ID bereinigen (z.B. 'appt_123' -> '123')
        const rawId = String(event.id);
        let cleanId = rawId;
        if(rawId.includes('_')) {
            cleanId = rawId.split('_')[1];
        }
        document.getElementById('appointmentId').value = cleanId;

        document.getElementById('appointmentTitle').value = event.title;
        document.getElementById('appointmentDescription').value = event.extendedProps?.description || '';

        let endDate = event.end || event.start;
        if (event.allDay) {
            const adjustedEnd = new Date(endDate);
            adjustedEnd.setDate(adjustedEnd.getDate() - 1);
            endDate = adjustedEnd;
        }

        document.getElementById('appointmentStart').value = formatDateForInput(event.start, event.allDay);
        document.getElementById('appointmentEnd').value = formatDateForInput(endDate, event.allDay);
        document.getElementById('appointmentAllDay').checked = event.allDay;

        // Toggle Input Types
        toggleAllDay({target: {checked: event.allDay}});

        const color = event.backgroundColor || '#4285f4';
        document.getElementById('appointmentColor').value = color;

        // Color Auswahl UI updaten
        document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
        const sel = document.querySelector(`.color-option[data-color="${color}"]`);
        if(sel) sel.classList.add('selected');

    } else {
        modalTitle.textContent = 'Neuer Termin';
        deleteBtn.style.display = 'none';
        document.getElementById('appointmentId').value = '';
        document.getElementById('appointmentTitle').value = '';
        document.getElementById('appointmentDescription').value = '';

        let startDate, endDate;
        if (clickedDateTime) {
            startDate = clickedDateTime;
            // startDate.setHours(startDate.getHours()); // FullCalendar liefert UTC/Local mix
            endDate = new Date(startDate.getTime());
            endDate.setHours(endDate.getHours() + 1);
            document.getElementById('appointmentAllDay').checked = false;
        } else {
            // Fallback: Datum String oder Heute
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

        document.getElementById('appointmentStart').value = formatDateForInput(startDate);
        document.getElementById('appointmentEnd').value = formatDateForInput(endDate);

        toggleAllDay({target: {checked: false}});

        // Reset Color
        document.getElementById('appointmentColor').value = '#4285f4';
        document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
        const def = document.querySelector(`.color-option[data-color="#4285f4"]`);
        if(def) def.classList.add('selected');
    }

    if(appointmentModal) appointmentModal.show();
}

function formatDateForInput(date, isAllDay = false) {
    if (!date) return '';
    const d = date instanceof Date ? date : new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    if (isAllDay) return `${year}-${month}-${day}`;
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function toggleAllDay(e) {
    const startInput = document.getElementById('appointmentStart');
    const endInput = document.getElementById('appointmentEnd');
    if (e.target.checked) {
        startInput.type = 'date';
        endInput.type = 'date';
        // Wenn von DateTime zu Date gewechselt wird, schneide Uhrzeit ab
        if(startInput.value.includes('T')) startInput.value = startInput.value.split('T')[0];
        if(endInput.value.includes('T')) endInput.value = endInput.value.split('T')[0];
    } else {
        startInput.type = 'datetime-local';
        endInput.type = 'datetime-local';
    }
}

function saveAppointment() {
    const id = document.getElementById('appointmentId').value;
    const title = document.getElementById('appointmentTitle').value;
    const description = document.getElementById('appointmentDescription').value;
    let start = document.getElementById('appointmentStart').value;
    let end = document.getElementById('appointmentEnd').value;
    const allDay = document.getElementById('appointmentAllDay').checked;
    const color = document.getElementById('appointmentColor').value;

    if (!title || !start || !end) {
        alert('Bitte füllen Sie alle Pflichtfelder aus.');
        return;
    }

    if (allDay) {
        // Sicherstellen dass wir ISO format senden
        start = start + 'T00:00:00';
        const endDateObj = new Date(end);
        endDateObj.setDate(endDateObj.getDate() + 1);
        end = endDateObj.toISOString().split('T')[0] + 'T00:00:00';
    }

    const data = { title, description, start, end, allDay, color };
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
                refreshAllCalendars(); // Alle Kalender aktualisieren!
                // Auch Sidebar neu laden
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
    // ID bereinigen (z.B. 'appt_123' -> '123')
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
                // Auch Sidebar neu laden
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
    if (!modalEl) return; // Abbrechen wenn Modal nicht da ist

    keyModal = new Modal(modalEl);

    // Buttons binden
    document.querySelectorAll('.key-item-btn').forEach(btn => {
        // Alten Listener entfernen um doppelte Bindings bei Turbo zu vermeiden
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            openKeyModal(this);
        });
    });

    // Status Change Listener
    const statusSelect = document.getElementById('keyStatus');
    if (statusSelect) {
        // Cleanup old listener
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

    // Type Change Listener
    const typeSelect = document.getElementById('borrowerType');
    if (typeSelect) {
        const newType = typeSelect.cloneNode(true);
        typeSelect.parentNode.replaceChild(newType, typeSelect);

        newType.addEventListener('change', function() {
            // Alle Selects verstecken
            document.querySelectorAll('.borrower-select').forEach(el => el.style.display = 'none');
            // Passenden anzeigen
            if (this.value) {
                const targetId = this.value + 'SelectDiv'; // z.B. userSelectDiv
                const target = document.getElementById(targetId);
                if(target) target.style.display = 'block';
            }
        });
    }

    // Save Button
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
    const color = btn.dataset.keyColor;
    const status = btn.dataset.keyStatus;

    const keyIdEl = document.getElementById('keyId');
    if(keyIdEl) keyIdEl.value = id;

    const titleEl = document.getElementById('modalKeyColorTitle');
    if(titleEl) titleEl.textContent = color;

    const statusEl = document.getElementById('keyStatus');
    if(statusEl) {
        statusEl.value = status;
        statusEl.dispatchEvent(new Event('change'));
    }

    const borrowEl = document.getElementById('keyBorrowDate');
    if(borrowEl) borrowEl.value = btn.dataset.borrowDate;

    const returnEl = document.getElementById('keyReturnDate');
    if(returnEl) returnEl.value = btn.dataset.returnDate;

    // Selektieren wer den Schlüssel hat
    const uId = btn.dataset.userId;
    const tId = btn.dataset.techId;
    const pId = btn.dataset.prodId;
    const cId = btn.dataset.cleanId;

    const typeSelect = document.getElementById('borrowerType');
    if(typeSelect) {
        // Reset Values first
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

    // Safe get value helper
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

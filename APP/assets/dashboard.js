
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

    document.getElementById('day-number-text').textContent = formattedDate;
    document.getElementById('day-suffix').textContent = '';
    document.getElementById('day-name').textContent = dayName.charAt(0).toUpperCase() + dayName.slice(1);
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
                    </div>
                </li>
            `;
        }).join('');

        // Event Listener für Klicks auf Events
        listEl.querySelectorAll('.event-item, .edit-event').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const eventId = this.getAttribute('data-event-id');
                const event = calendar.getEventById(eventId);
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

var calendar;
var appointmentModal;
var clickTimeout = null;

function initCalendar() {
    const calendarEl = document.getElementById('dashboard-calendar');
    const modalEl = document.getElementById('appointmentModal');

    if (!calendarEl || !modalEl) return;

    appointmentModal = new Modal(modalEl);

    calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: deLocale,
        timeZone: 'Europe/Berlin',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridDay,timeGridWeek,dayGridMonth,dayGridYear'
        },
        buttonText: {
            today: 'Heute',
            month: 'Monat',
            week: 'Woche',
            day: 'Tag',
            timeGridWeek: 'Woche (Zeit)',
            timeGridDay: 'Tag (Zeit)'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '24:00:00',
        allDaySlot: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            meridiem: false
        },
        editable: true,
        selectable: false,  // Deaktivieren, da wir dateClick verwenden
        selectMirror: false,  // Deaktivieren
        dayMaxEvents: true,
        weekends: true,
        eventDidMount: function(info) {
            // Prüfe, ob das Event in der Vergangenheit liegt
            const now = new Date();
            const eventEnd = info.event.end || info.event.start;

            if (eventEnd < now) {
                // Füge Style für vergangene Events hinzu
                info.el.style.opacity = '0.5';
                info.el.style.textDecoration = 'line-through';
            }
        },
        events: function(info, successCallback, failureCallback) {
            fetch('/appointments/all')
                .then(response => response.json())
                .then(data => {
                    allEvents = data;
                    successCallback(data);
                    displayDate(currentSelectedDate);
                    displayEventsForDate(currentSelectedDate, data);
                })
                .catch(error => {
                    console.error('Error loading appointments:', error);
                    failureCallback(error);
                });
        },
        dateClick: function(info) {
            // Für dayGrid-Ansichten
            if (info.view.type.startsWith('dayGrid')) {
                if (clickTimeout !== null) {
                    clearTimeout(clickTimeout);
                    clickTimeout = null;
                    openAppointmentModal(info.dateStr);
                } else {
                    clickTimeout = setTimeout(() => {
                        clickTimeout = null;
                        const clickedDate = new Date(info.dateStr);
                        displayDate(clickedDate);
                        displayEventsForDate(clickedDate, allEvents);
                    }, 300);
                }
            } else if (info.view.type.startsWith('timeGrid')) {
                // Für timeGrid-Ansichten: Doppelklick zum Erstellen
                if (clickTimeout !== null) {
                    clearTimeout(clickTimeout);
                    clickTimeout = null;
                    // Doppelklick erkannt - Modal mit vorausgewählter Zeit öffnen
                    // info.date ist bereits in lokaler Zeit
                    openAppointmentModal(null, null, info.date);
                } else {
                    clickTimeout = setTimeout(() => {
                        clickTimeout = null;
                        // Einzelklick - nur Datum anzeigen
                        const clickedDate = new Date(info.date);
                        displayDate(clickedDate);
                        displayEventsForDate(clickedDate, allEvents);
                    }, 300);
                }
            }
        },
        eventClick: function(info) {
            openAppointmentModal(null, info.event);
        },
        eventDrop: function(info) {
            updateAppointment(info.event);
        },
        eventResize: function(info) {
            updateAppointment(info.event);
        }
    });

    calendar.render();

    // Event Listener für Plus-Button
    document.getElementById('add-appointment-btn').addEventListener('click', function() {
        const dateStr = currentSelectedDate.toISOString().split('T')[0];
        openAppointmentModal(dateStr);
    });

    // Event Listeners für Modal
    document.getElementById('saveAppointmentBtn').addEventListener('click', saveAppointment);
    document.getElementById('deleteAppointmentBtn').addEventListener('click', deleteAppointment);
    document.getElementById('appointmentAllDay').addEventListener('change', toggleAllDay);

    // Event Listener für Farbauswahl
    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('appointmentColor').value = this.getAttribute('data-color');
        });
    });
}

function openAppointmentModal(dateStr = null, event = null, clickedDateTime = null) {
    const modalTitle = document.getElementById('appointmentModalLabel');
    const deleteBtn = document.getElementById('deleteAppointmentBtn');

    if (event) {
        modalTitle.textContent = 'Termin bearbeiten';
        deleteBtn.style.display = 'inline-block';

        document.getElementById('appointmentId').value = event.id;
        document.getElementById('appointmentTitle').value = event.title;
        document.getElementById('appointmentDescription').value = event.extendedProps.description || '';

        let endDate = event.end || event.start;
        if (event.allDay) {
            const adjustedEnd = new Date(endDate);
            adjustedEnd.setDate(adjustedEnd.getDate() - 1);
            endDate = adjustedEnd;
        }

        // event.start und event.end sind FullCalendar Date-Objekte
        // Die müssen wir korrekt behandeln
        const startDate = event.start;
        const finalEndDate = endDate;

        document.getElementById('appointmentStart').value = formatDateForInput(startDate, event.allDay);
        document.getElementById('appointmentEnd').value = formatDateForInput(finalEndDate, event.allDay);
        document.getElementById('appointmentAllDay').checked = event.allDay;

        if (event.allDay) {
            document.getElementById('appointmentStart').type = 'date';
            document.getElementById('appointmentEnd').type = 'date';
        } else {
            document.getElementById('appointmentStart').type = 'datetime-local';
            document.getElementById('appointmentEnd').type = 'datetime-local';
        }

        const color = event.backgroundColor || '#4285f4';
        document.getElementById('appointmentColor').value = color;

        document.querySelectorAll('.color-option').forEach(option => {
            if (option.getAttribute('data-color') === color) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    } else {
        modalTitle.textContent = 'Neuer Termin';
        deleteBtn.style.display = 'none';

        document.getElementById('appointmentId').value = '';
        document.getElementById('appointmentTitle').value = '';
        document.getElementById('appointmentDescription').value = '';

        let startDate, endDate;

        if (clickedDateTime) {
            // Doppelklick in timeGrid - verwende die angeklickte Zeit
            // clickedDateTime ist bereits ein lokales Date-Objekt von FullCalendar
            startDate = clickedDateTime;
            startDate.setHours(startDate.getHours());
            endDate = new Date(clickedDateTime.getTime());
            endDate.setHours(endDate.getHours() + 1); // 1 Stunde später als Endzeit

            document.getElementById('appointmentStart').type = 'datetime-local';
            document.getElementById('appointmentEnd').type = 'datetime-local';
            document.getElementById('appointmentAllDay').checked = false;
        } else {
            // Normales Erstellen (z.B. über Plus-Button oder dayGrid)
            startDate = new Date(dateStr);
            startDate.setHours(9, 0, 0, 0);
            endDate = new Date(dateStr);
            endDate.setHours(10, 0, 0, 0);

            document.getElementById('appointmentStart').type = 'datetime-local';
            document.getElementById('appointmentEnd').type = 'datetime-local';
            document.getElementById('appointmentAllDay').checked = false;
        }

        document.getElementById('appointmentStart').value = formatDateForInput(startDate);
        document.getElementById('appointmentEnd').value = formatDateForInput(endDate);
        document.getElementById('appointmentColor').value = '#4285f4';

        document.querySelectorAll('.color-option').forEach(option => {
            if (option.getAttribute('data-color') === '#4285f4') {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }

    appointmentModal.show();
}

function formatDateForInput(date, isAllDay = false) {
    if (!date) return '';

    // Wenn es ein FullCalendar Date-Objekt ist, konvertiere es
    const d = date instanceof Date ? date : new Date(date);

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    if (isAllDay) {
        return `${year}-${month}-${day}`;
    }

    const hours = String(d.getHours()-1).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function toggleAllDay(e) {
    const startInput = document.getElementById('appointmentStart');
    const endInput = document.getElementById('appointmentEnd');

    if (e.target.checked) {
        startInput.type = 'date';
        endInput.type = 'date';
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
        start = start.split('T')[0];
        end = end.split('T')[0];

        start = start + 'T00:00:00';
        const endDateObj = new Date(end);
        endDateObj.setDate(endDateObj.getDate() + 1);
        end = endDateObj.toISOString().split('T')[0] + 'T00:00:00';
    }

    const data = {
        title,
        description,
        start,
        end,
        allDay,
        color
    };

    const url = id ? `/appointment/${id}/edit` : '/appointment/create';
    const method = id ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                appointmentModal.hide();
                calendar.refetchEvents();
            } else {
                alert('Fehler beim Speichern des Termins.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Speichern des Termins.');
        });
}

function deleteAppointment() {
    const id = document.getElementById('appointmentId').value;

    if (!id || !confirm('Möchten Sie diesen Termin wirklich löschen?')) {
        return;
    }

    deleteAppointmentById(id);
}

function deleteAppointmentById(id) {
    fetch(`/appointment/${id}/delete`, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (appointmentModal._isShown) {
                    appointmentModal.hide();
                }
                calendar.refetchEvents();
            } else {
                alert('Fehler beim Löschen des Termins.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Löschen des Termins.');
        });
}

function updateAppointment(event) {
    const data = {
        start: event.start.toISOString(),
        end: event.end ? event.end.toISOString() : event.start.toISOString()
    };

    fetch(`/appointment/${event.id}/edit`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                alert('Fehler beim Aktualisieren des Termins.');
                calendar.refetchEvents();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Aktualisieren des Termins.');
            calendar.refetchEvents();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    initDoughnutChart();
    initCalendar();
});

document.addEventListener('turbo:render', function() {
    initDoughnutChart();
    initCalendar();
});

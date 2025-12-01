// APP/assets/production-calendar.js
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import deLocale from '@fullcalendar/core/locales/de';

export function initProductionCalendar(calendarElId, productionId, editRoutePattern) {
    const calendarEl = document.getElementById(calendarElId);

    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: deLocale,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Heute',
            month: 'Monat',
            week: 'Woche',
            day: 'Tag'
        },
        height: 'auto',
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        events: function(info, successCallback, failureCallback) {
            const url = `/production/${productionId}/events?start=${info.startStr.split('+')[0]}&end=${info.endStr.split('+')[0]}`;

            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => {
                    console.error('Error loading events:', error);
                    failureCallback(error);
                });
        },
        eventClick: function(info) {
            const eventId = info.event.extendedProps.productionEventId;
            if (eventId && editRoutePattern) {
                window.location.href = editRoutePattern.replace('__ID__', eventId);
            }
        },
        eventDidMount: function(info) {
            const props = info.event.extendedProps;
            let tooltipContent = '<strong>' + info.event.title + '</strong><br>';

            if (props.roomName) {
                tooltipContent += 'Raum: ' + props.roomName + '<br>';
            }
            if (props.timeFrom) {
                tooltipContent += 'Zeit: ' + props.timeFrom;
                if (props.timeTo) {
                    tooltipContent += ' - ' + props.timeTo;
                }
                tooltipContent += '<br>';
            }
            if (props.status) {
                tooltipContent += 'Status: ' + props.status;
            }

            // Bootstrap Tooltip
            if (window.bootstrap && window.bootstrap.Tooltip) {
                new window.bootstrap.Tooltip(info.el, {
                    title: tooltipContent,
                    html: true,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            }
        }
    });

    calendar.render();

    return calendar;
}

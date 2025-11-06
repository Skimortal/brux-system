import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';


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

var calendar;
function initCalendar() {
    const calendarEl = document.getElementById('dashboard-calendar');

    if (calendarEl) {
        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            locale: 'de',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek'
            },
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            events: [
                // Beispiel-Events - hier kannst du später deine echten Events einfügen
                {
                    title: 'Meeting',
                    start: new Date().toISOString().split('T')[0],
                    color: '#4285f4'
                }
            ],
            dateClick: function(info) {
                console.log('Datum geklickt: ' + info.dateStr);
                // Hier kannst du später eine Funktion zum Hinzufügen von Events einbauen
            },
            eventClick: function(info) {
                console.log('Event geklickt: ' + info.event.title);
                // Hier kannst du später Event-Details anzeigen
            }
        });

        calendar.render();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initDoughnutChart();
    initCalendar();
});

document.addEventListener('turbo:render', function() {
    initDoughnutChart();
    initCalendar();
});

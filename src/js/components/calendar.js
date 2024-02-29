// src/js/calendar.js
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import esLocale from '@fullcalendar/core/locales/es';

export function initCalendar() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: esLocale,
    // Aquí puedes añadir más opciones según necesites
  });
  calendar.render();
}

import './bootstrap.js';
import 'tom-select/dist/css/tom-select.default.min.css';
import './styles/app.css';

// Calendar forms carry the calendar URL they came from, so actions can return to the
// same week, filters and panel even after the calendar was navigated inside its frame.
document.addEventListener('submit', (event) => {
    const input = event.target.querySelector?.('input[name="_return"]');
    if (input && window.location.pathname === '/') {
        input.value = window.location.pathname + window.location.search;
    }
}, true);

// "Wird gespeichert …" while any form is on its way, so a click never looks like nothing happened.
const savingNote = () => {
    let note = document.getElementById('saving-note');
    if (!note) {
        note = document.createElement('div');
        note.id = 'saving-note';
        note.className = 'saving-note';
        note.setAttribute('role', 'status');
        note.innerHTML = '<span class="saving-spinner" aria-hidden="true"></span> Wird gespeichert …';
        document.body.appendChild(note);
    }
    return note;
};
let savingTimer = null;
const hideSaving = () => {
    clearTimeout(savingTimer);
    const note = document.getElementById('saving-note');
    if (note) note.hidden = true;
};
document.addEventListener('turbo:submit-start', () => {
    savingNote().hidden = false;
    // never stay forever, e.g. when a download answers the form
    clearTimeout(savingTimer);
    savingTimer = setTimeout(hideSaving, 20000);
});
// gone once the answer is on screen (after the redirect, or the form with its errors)
document.addEventListener('turbo:submit-end', (event) => { if (!event.detail.success) hideSaving(); });
document.addEventListener('turbo:load', hideSaving);
document.addEventListener('turbo:render', hideSaving);
document.addEventListener('turbo:frame-render', hideSaving);
// plain (non-Turbo) forms, e.g. the pages reached from mails
document.addEventListener('submit', (event) => {
    if (event.target instanceof HTMLFormElement && event.target.dataset.turbo === 'false' && !event.defaultPrevented) {
        savingNote().hidden = false;
    }
});

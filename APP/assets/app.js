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

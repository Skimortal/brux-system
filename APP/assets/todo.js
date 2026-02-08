import { Modal } from 'bootstrap';

var todoModal;

function renderTodoList() {
    const todoItemList = document.getElementById('todo-list');
    if (!todoItemList) return;

    fetch('/dashboard/all-todos')
        .then(response => response.json())
        .then(todos => {
            // HTML generieren
            todoItemList.innerHTML = '';

            todos.forEach(todo => {
                const description = todo.description || '';

                const li = document.createElement('li');
                li.className = 'bdB peers ai-c jc-sb fxw-nw todo-item-btn';
                li.style.cursor = 'pointer';
                li.dataset.todoId = todo.id;
                li.dataset.todoDescription = description;
                li.dataset.todoDescription = description;

                li.innerHTML = `
                    <div class="td-n p-20 peers fxw-nw me-20 peer-greed c-grey-900">
                        <div class="peer mR-15">
                            <i class="ti-todo"></i>
                        </div>
                        <div class="peer">
                            <span class="fw-600">Todo</span>
                            <div class="c-grey-600">
                                <span class="c-grey-700">${description}</span>
                            </div>
                        </div>
                    </div>
                    <div class="peers mR-15">
                        <div class="peer">
                            <a class="checkTodo badge rounded-pill fl-r bg-success lh-0 p-10" data-todo-id="${todo.id}">Done</a>
                        </div>
                    </div>
                `;

                li.addEventListener('click', () => {
                    openTodoModal(todo.id);
                });

                li.querySelector('.checkTodo').addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const todoId = e.currentTarget.dataset.todoId;
                    const confirmed = confirm('Willst du dieses Todo wirklich als erledigt markieren?');
                    if (confirmed) {
                        checkTodo(todoId);
                    }
                });

                todoItemList.appendChild(li);
            });
        })
        .catch(e => console.error('Error loading todos:', e));
}

function initTodo() {
    const modalEl = document.getElementById('todoModal');
    if (!modalEl) return;

    renderTodoList();

    todoModal = new Modal(modalEl);

    document.querySelectorAll('.todo-item-btn').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            openTodoModal(this);
        });
    });

    const saveBtn = document.getElementById('saveTodoBtn');
    if (saveBtn) {
        const newSave = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSave, saveBtn);
        newSave.addEventListener('click', saveTodoData);
    }

    const createNewBtn = document.querySelector('.btn-success[data-bs-target="#todoModal"]');
    if (createNewBtn) {
        const newCreateBtn = createNewBtn.cloneNode(true);
        createNewBtn.parentNode.replaceChild(newCreateBtn, createNewBtn);
        newCreateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            resetTodoModal();
            // Modal wird durch data-bs-toggle schon geöffnet
        });
    }
}

function resetTodoModal() {
    const todoIdEl = document.getElementById('todoId');
    const descriptionEl = document.getElementById('todoDescription');

    if (todoIdEl) todoIdEl.value = '';
    if (descriptionEl) descriptionEl.value = '';
}

function openTodoModal(todoId) {
    var btn = document.querySelector(`.todo-item-btn[data-todo-id="${todoId}"]`);

    if (!btn) {
        btn = todoId;
    }

    const id = btn.dataset.todoId;
    const description = btn.dataset.todoDescription;

    const todoIdEl = document.getElementById('todoId');
    if(todoIdEl) todoIdEl.value = id;

    const descriptionEl = document.getElementById('todoDescription');
    if(descriptionEl) descriptionEl.textContent = description;

    if(todoModal) todoModal.show();
}

function saveTodoData(checkTodo = false) {
    const idEl = document.getElementById('todoId');
    const descriptionEl = document.getElementById('todoDescription');

    if(!idEl || !descriptionEl) return;

    const id = idEl.value;
    const description = descriptionEl.value;

    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value : null;
    };

    const data = {
        description: description,
    };

    let fetchUrl = ''
    if(id) {
        fetchUrl = `/dashboard/todo/${id}/update`;
    } else {
        fetchUrl = `/dashboard/todo/create`;
    }

    fetch(fetchUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => {
        if(res.ok) {
            todoModal.hide();
            renderTodoList();
        } else {
            alert('Fehler beim Speichern');
        }
    }).catch(e => console.error(e));
}

function checkTodo(id) {
    if(!id) return;

    const data = {
        done: true,
    };

    let fetchUrl = ''
    if(id) {
        fetchUrl = `/dashboard/todo/${id}/update`;
    } else {
        fetchUrl = `/dashboard/todo/create`;
    }

    fetch(fetchUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => {
        if(res.ok) {
            todoModal.hide();
            renderTodoList();
        } else {
            alert('Fehler beim Speichern');
        }
    }).catch(e => console.error(e));
}

function bootTodo() {
    initTodo();
}

// Export für Lazy-Import aus app.js
export { bootTodo };

// Doppelte Bindings vermeiden (Turbo feuert Events mehrfach)
function bootTodoOnce() {
    if (window.todoBound) return;
    window.__todoBound = true;

    document.addEventListener('DOMContentLoaded', bootTodo);
    document.addEventListener('turbo:load', bootTodo);
    document.addEventListener('turbo:render', bootTodo);
    document.addEventListener('turbo:visit', bootTodo);
}

bootTodoOnce();

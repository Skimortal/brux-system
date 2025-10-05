import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list', 'prototype'];
    static values = { index: Number };

    connect() {
        if (this._connected) return;
        this._connected = true;

        if (!this.hasIndexValue) {
            this.indexValue = this.listTarget.querySelectorAll('[data-collection-item]').length;
        }

        console.log("connect");

        this._onClick = (e) => {
            const addBtn = e.target.closest('.js-collection-add,[data-action~="click->form-collection#add"]');
            if (addBtn && this.element.contains(addBtn)) {
                e.preventDefault();
                this._handleAddOnce();
                return;
            }
            const removeBtn = e.target.closest('.js-collection-remove,[data-action~="click->form-collection#remove"]');
            if (removeBtn && this.element.contains(removeBtn)) {
                e.preventDefault();
                this.remove({ currentTarget: removeBtn });
            }
        };

        this.element.addEventListener('click', this._onClick, { passive: false });

        // Aufräumen für verschiedene Turbo/Navigationspfade
        this._onBeforeCache = () => this.disconnect();
        this._onBeforeRender = () => this.disconnect();
        this._onPageShow = (ev) => { if (ev.persisted) this.disconnect(); };

        document.addEventListener('turbo:before-cache', this._onBeforeCache);
        document.addEventListener('turbo:before-render', this._onBeforeRender);
        window.addEventListener('pageshow', this._onPageShow);

        this.renumber();
    }

    disconnect() {
        console.log("dis-connect");
        if (this._onClick) {
            this.element.removeEventListener('click', this._onClick);
        }
        if (this._onBeforeCache) {
            document.removeEventListener('turbo:before-cache', this._onBeforeCache);
        }
        if (this._onBeforeRender) {
            document.removeEventListener('turbo:before-render', this._onBeforeRender);
        }
        if (this._onPageShow) {
            window.removeEventListener('pageshow', this._onPageShow);
        }
        this._onClick = null;
        this._onBeforeCache = null;
        this._onBeforeRender = null;
        this._onPageShow = null;
        this._bound = false;
        this._connected = false;
    }

    _handleAddOnce() {
        if (this._clickLocked) return;
        this._clickLocked = true;
        try {
            this.add();
        } finally {
            setTimeout(() => { this._clickLocked = false; }, 0);
        }
    }

    add() {
        const frag = this.prototypeTarget.content.cloneNode(true);
        this._replaceInFragment(frag, '__name__', String(this.indexValue++));
        const row = frag.querySelector('tr[data-collection-item]') || frag.firstElementChild;
        if (!row) return;
        console.log('add', frag, row, this.listTarget);
        this.listTarget.appendChild(row);
        this.renumber();
    }

    remove(e) {
        const row = e.currentTarget.closest('[data-collection-item]');
        if (row) row.remove();
        this.renumber();
    }

    renumber() {
        this.listTarget.querySelectorAll('[data-collection-item] [data-collection-counter]')
            .forEach((el, i) => el.textContent = String(i + 1));
    }

    _replaceInFragment(fragment, search, replace) {
        const treeWalker = document.createTreeWalker(fragment, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (treeWalker.nextNode()) nodes.push(treeWalker.currentNode);

        nodes.forEach(node => {
            if (node.nodeType === Node.TEXT_NODE) {
                node.nodeValue = node.nodeValue.replaceAll(search, replace);
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                for (const attr of Array.from(node.attributes)) {
                    if (attr.value.includes(search)) {
                        node.setAttribute(attr.name, attr.value.replaceAll(search, replace));
                    }
                }
            }
        });
    }
}

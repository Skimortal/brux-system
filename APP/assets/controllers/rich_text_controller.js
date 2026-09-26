import { Controller } from '@hotwired/stimulus';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

/*
 * Rich text editor for website descriptions, on top of a hidden textarea that the form
 * submits. The textarea is only written when the text is actually edited, so saving the
 * form never rewrites imported WordPress markup by itself. A disabled textarea (field
 * owned by WordPress) shows the formatted text read-only.
 */
export default class extends Controller {
    static targets = ['input', 'editor', 'toolbar', 'linkBar', 'linkInput', 'sourceButton'];

    connect() {
        const input = this.inputTarget;
        this.editor = new Editor({
            element: this.editorTarget,
            editable: !input.disabled,
            content: input.value,
            extensions: [
                StarterKit.configure({
                    heading: { levels: [2, 3] },
                    code: false,
                    codeBlock: false,
                    // no autolink: it makes links grow with the text typed after them;
                    // target and rel are left to the website
                    link: { openOnClick: false, autolink: false, defaultProtocol: 'https', HTMLAttributes: { target: null, rel: null } },
                }),
                Image,
            ],
            editorProps: { attributes: { class: 'rte-content about-text', 'aria-label': 'Beschreibung' } },
            onUpdate: ({ editor }) => {
                input.value = editor.isEmpty ? '' : editor.getHTML();
            },
            onTransaction: () => this.refresh(),
        });
        this.refresh();
    }

    disconnect() {
        this.editor?.destroy();
        this.editor = null;
    }

    /*
     * A save morphs the page, which empties the editor element. Rebuild it from the stored
     * (sanitized) text the server sent, in the textarea's default value.
     */
    reload() {
        this.disconnect();
        const input = this.inputTarget;
        input.value = input.defaultValue;
        input.hidden = true;
        this.editorTarget.hidden = false;
        if (this.hasToolbarTarget) this.toolbarTarget.classList.remove('is-disabled');
        if (this.hasSourceButtonTarget) this.sourceButtonTarget.setAttribute('aria-pressed', 'false');
        this.connect();
    }

    run(event) {
        const chain = this.editor.chain().focus();
        const { command, level } = event.params;
        switch (command) {
            case 'paragraph': chain.setParagraph().run(); break;
            case 'heading': chain.toggleHeading({ level }).run(); break;
            case 'bold': chain.toggleBold().run(); break;
            case 'italic': chain.toggleItalic().run(); break;
            case 'bulletList': chain.toggleBulletList().run(); break;
            case 'orderedList': chain.toggleOrderedList().run(); break;
            case 'blockquote': chain.toggleBlockquote().run(); break;
            case 'undo': chain.undo().run(); break;
            case 'redo': chain.redo().run(); break;
            default: break;
        }
    }

    openLink() {
        this.linkInputTarget.value = this.editor.getAttributes('link').href ?? '';
        this.linkBarTarget.hidden = false;
        this.linkInputTarget.focus();
    }

    applyLink(event) {
        event?.preventDefault();
        const href = this.linkInputTarget.value.trim();
        const chain = this.editor.chain().focus().extendMarkRange('link');
        if (href === '') {
            chain.unsetLink().run();
        } else {
            chain.setLink({ href: /^(https?:|mailto:|tel:)/.test(href) ? href : `https://${href}` }).run();
        }
        this.linkBarTarget.hidden = true;
    }

    removeLink() {
        this.editor.chain().focus().extendMarkRange('link').unsetLink().run();
        this.linkBarTarget.hidden = true;
    }

    linkKey(event) {
        if (event.key === 'Enter') this.applyLink(event);
        if (event.key === 'Escape') {
            event.preventDefault();
            this.linkBarTarget.hidden = true;
            this.editor.commands.focus();
        }
    }

    /* Switch between the editor and the raw HTML in the textarea. */
    toggleSource() {
        const input = this.inputTarget;
        const showSource = input.hidden;
        if (!showSource) {
            this.editor.commands.setContent(input.value, { emitUpdate: false });
        }
        input.hidden = !showSource;
        this.editorTarget.hidden = showSource;
        if (this.hasToolbarTarget) this.toolbarTarget.classList.toggle('is-disabled', showSource);
        this.sourceButtonTarget.setAttribute('aria-pressed', String(showSource));
        (showSource ? input : this.editor).focus?.();
    }

    refresh() {
        if (!this.hasToolbarTarget || !this.editor) return;
        this.toolbarTarget.querySelectorAll('[data-rich-text-command-param]').forEach((button) => {
            const { richTextCommandParam: command, richTextLevelParam: level } = button.dataset;
            const active = command === 'heading'
                ? this.editor.isActive('heading', { level: Number(level) })
                : ['bold', 'italic', 'bulletList', 'orderedList', 'blockquote'].includes(command) && this.editor.isActive(command);
            button.classList.toggle('is-active', Boolean(active));
        });
        this.toolbarTarget.querySelector('[data-link-button]')?.classList.toggle('is-active', this.editor.isActive('link'));
    }
}

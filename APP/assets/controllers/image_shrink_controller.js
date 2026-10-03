import { Controller } from '@hotwired/stimulus';

/*
 * Shrinks a chosen image in the browser before it is uploaded: at most `maxSize`
 * pixels on the long side, saved as JPEG at `quality` (PNGs with transparency stay
 * PNG). The website never shows images larger than that, so nothing visible is lost,
 * while camera photos drop from several MB to a few hundred KB. Images that are
 * already small enough are left untouched.
 */
export default class extends Controller {
    static targets = ['input', 'note'];
    static values = { maxSize: { type: Number, default: 2400 }, quality: { type: Number, default: 0.88 }, minBytes: { type: Number, default: 500000 } };

    async shrink() {
        const input = this.inputTarget;
        const file = input.files[0];
        this.note('');
        if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) return;

        let bitmap;
        try {
            bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch {
            return;
        }
        const scale = Math.min(1, this.maxSizeValue / Math.max(bitmap.width, bitmap.height));
        if (scale === 1 && file.size <= this.minBytesValue) {
            bitmap.close();
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        const context = canvas.getContext('2d');
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const keepPng = file.type === 'image/png' && this.hasTransparency(context, canvas);
        const type = keepPng ? 'image/png' : 'image/jpeg';
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, type, this.qualityValue));
        if (!blob || blob.size >= file.size) return;

        const name = file.name.replace(/\.[^.]+$/, '') + (keepPng ? '.png' : '.jpg');
        const transfer = new DataTransfer();
        transfer.items.add(new File([blob], name, { type, lastModified: Date.now() }));
        input.files = transfer.files;
        this.note(`Für die Website verkleinert: ${this.size(file.size)} → ${this.size(blob.size)} (${canvas.width} × ${canvas.height} px)`);
    }

    hasTransparency(context, canvas) {
        const { data } = context.getImageData(0, 0, canvas.width, canvas.height);
        for (let i = 3; i < data.length; i += 4) {
            if (data[i] < 255) return true;
        }
        return false;
    }

    size(bytes) {
        return bytes >= 1000000 ? `${(bytes / 1000000).toFixed(1).replace('.', ',')} MB` : `${Math.round(bytes / 1000)} KB`;
    }

    note(text) {
        if (!this.hasNoteTarget) return;
        this.noteTarget.textContent = text;
        this.noteTarget.hidden = text === '';
    }
}

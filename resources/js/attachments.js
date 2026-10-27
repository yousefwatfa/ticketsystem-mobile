/**
 * Attachment picker with client-side image compression.
 *
 * Phone photos are usually 3-5 MB while the server accepts max 2 MB per file,
 * so images are resized (longest side 1600px) and re-encoded as JPEG before
 * they are placed into the real <input type="file" name="attachments[]">.
 */
const MAX_BYTES = 2 * 1024 * 1024;
const MAX_DIMENSION = 1600;
const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'xls'];

function extensionOf(name) {
    return (name.split('.').pop() || '').toLowerCase();
}

function formatSize(bytes) {
    return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)}MB` : `${Math.max(1, Math.round(bytes / 1024))}KB`;
}

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode failed')); };
        img.src = url;
    });
}

function canvasToBlob(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}

export async function compressImage(file) {
    const img = await loadImage(file);
    const scale = Math.min(1, MAX_DIMENSION / Math.max(img.naturalWidth, img.naturalHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.naturalWidth * scale);
    canvas.height = Math.round(img.naturalHeight * scale);

    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff'; // flatten PNG transparency for JPEG
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    let blob = null;
    for (const quality of [0.82, 0.7, 0.55]) {
        blob = await canvasToBlob(canvas, quality);
        if (blob && blob.size <= MAX_BYTES) break;
    }

    const baseName = file.name.replace(/\.[^.]+$/, '') || 'photo';
    return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
}

/**
 * @param {HTMLFormElement} form
 * @param {object} opts { target: input[name="attachments[]"], pickers: input[], previews: element, error: element }
 */
export function initAttachmentPicker(form, opts) {
    const { target, pickers, previews, error } = opts;
    if (!form || !target || typeof DataTransfer === 'undefined') {
        return; // old browser: the plain file input keeps working
    }

    let files = [];
    let pending = 0;

    const showError = (message) => {
        error.textContent = message;
        error.hidden = !message;
    };

    const sync = () => {
        const dt = new DataTransfer();
        files.forEach((f) => dt.items.add(f));
        target.files = dt.files;
        render();
    };

    const render = () => {
        previews.innerHTML = '';
        files.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'photo-previews__item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                img.onload = () => URL.revokeObjectURL(img.src);
                item.appendChild(img);
            } else {
                const chip = document.createElement('div');
                chip.className = 'file-chip';
                chip.textContent = file.name;
                item.appendChild(chip);
            }

            const size = document.createElement('span');
            size.className = 'photo-previews__size';
            size.textContent = formatSize(file.size);
            item.appendChild(size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'photo-previews__remove';
            remove.setAttribute('aria-label', `حذف ${file.name}`);
            remove.innerHTML = '<i class="bi bi-x" aria-hidden="true"></i>';
            remove.addEventListener('click', () => {
                files.splice(index, 1);
                sync();
            });
            item.appendChild(remove);

            previews.appendChild(item);
        });
    };

    const addFiles = async (list) => {
        showError('');
        const rejected = [];

        for (const original of Array.from(list)) {
            const ext = extensionOf(original.name);
            const isImage = original.type.startsWith('image/');

            if (!isImage && !ALLOWED_EXT.includes(ext)) {
                rejected.push(`${original.name}: نوع الملف غير مسموح`);
                continue;
            }

            let file = original;
            if (isImage) {
                pending++;
                try {
                    file = await compressImage(original);
                } catch (e) {
                    file = original; // could not decode (e.g. unsupported format): send as-is
                } finally {
                    pending--;
                }
            }

            if (file.size > MAX_BYTES) {
                rejected.push(`${original.name}: الحجم ${formatSize(file.size)} أكبر من 2MB`);
                continue;
            }
            files.push(file);
        }

        sync();
        if (rejected.length) showError(rejected.join(' — '));
    };

    pickers.forEach((picker) => {
        picker.addEventListener('change', async () => {
            await addFiles(picker.files);
            picker.value = '';
        });
    });

    form.addEventListener('submit', (event) => {
        if (pending > 0) {
            event.preventDefault();
            showError('يرجى الانتظار حتى انتهاء تجهيز الصور...');
        }
    });
}

window.TicketAttachments = { initAttachmentPicker, compressImage };

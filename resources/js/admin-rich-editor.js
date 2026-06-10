import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const toolbarOptions = [
    [{ header: [1, 2, 3, false] }],
    [{ size: ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link', 'image'],
    ['clean'],
];

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function createImageHandler(quill, uploadUrl) {
    return () => {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();

        input.onchange = async () => {
            const file = input.files?.[0];

            if (! file) {
                return;
            }

            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: formData,
            });

            if (! response.ok) {
                window.Swal?.fire?.({
                    icon: 'error',
                    title: 'Upload failed',
                    text: 'Could not upload image.',
                });

                return;
            }

            const data = await response.json();
            const range = quill.getSelection(true) ?? { index: quill.getLength(), length: 0 };
            quill.insertEmbed(range.index, 'image', data.url, 'user');
            quill.setSelection(range.index + 1);
        };
    };
}

function initRichEditors() {
    const form = document.querySelector('[data-settings-form]');

    if (! form) {
        return;
    }

    const uploadUrl = form.dataset.cmsImageUploadUrl ?? '';
    const editors = [];

    form.querySelectorAll('[data-rich-editor]').forEach((container) => {
        const inputId = container.dataset.input;
        const input = inputId ? document.getElementById(inputId) : null;

        if (! input) {
            return;
        }

        const quill = new Quill(container, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: toolbarOptions,
                    handlers: {
                        image: createImageHandler(null, uploadUrl),
                    },
                },
            },
        });

        quill.root.innerHTML = input.value ?? '';
        quill.getModule('toolbar').handlers.image = createImageHandler(quill, uploadUrl);

        editors.push({ quill, input });
    });

    form.addEventListener('submit', () => {
        editors.forEach(({ quill, input }) => {
            input.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRichEditors);
} else {
    initRichEditors();
}

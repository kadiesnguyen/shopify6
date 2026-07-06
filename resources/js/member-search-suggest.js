function escapeHtml(value) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function suggestFieldRoot(input) {
    return input.closest('.portal-search-field') ?? input.closest('.portal-home-search');
}

function initSuggestInput(input) {
    const field = suggestFieldRoot(input);
    const list = field?.querySelector('[data-suggest-list]');
    const hiddenInput = field?.querySelector('input[data-suggest-hidden]');

    if (!list) {
        return;
    }

    const suggestUrl = input.dataset.suggestUrl || '';
    const suggestTarget = input.dataset.suggestTarget || 'product';
    const suggestContext = input.dataset.suggestContext || 'portal';
    const minLength = Number.parseInt(input.dataset.suggestMin || '1', 10);
    const emptyText = input.dataset.suggestNoResults || 'No suggestions';

    let debounceTimer = null;
    let abortController = null;
    let items = [];
    let activeIndex = -1;

    function hideList() {
        list.classList.add('hidden');
        list.innerHTML = '';
        items = [];
        activeIndex = -1;
    }

    function submitForm() {
        const form = input.closest('form');
        if (form) {
            form.requestSubmit();
        }
    }

    function pickItem(index) {
        const item = items[index];

        if (!item) {
            return;
        }

        input.value = item.value;
        if (hiddenInput instanceof HTMLInputElement) {
            hiddenInput.value = item.id ? String(item.id) : '';
        }
        hideList();
        submitForm();
    }

    function renderList() {
        if (items.length === 0) {
            list.innerHTML = `<p class="px-3 py-2 text-xs text-gray-500">${escapeHtml(emptyText)}</p>`;
            list.classList.remove('hidden');
            return;
        }

        list.innerHTML = items
            .map((item, index) => {
                const activeClass = index === activeIndex ? 'bg-emerald-50 text-emerald-700' : 'text-gray-700';
                const meta = item.meta
                    ? `<span class="mt-0.5 block text-xs text-gray-400">${escapeHtml(String(item.meta))}</span>`
                    : '';

                return `<button type="button" data-suggest-item="${index}" class="block w-full px-3 py-2 text-left text-sm transition-colors hover:bg-gray-50 ${activeClass}">
                    <span>${escapeHtml(String(item.value))}</span>
                    ${meta}
                </button>`;
            })
            .join('');

        list.classList.remove('hidden');
    }

    async function fetchSuggestions(keyword) {
        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();

        const url = new URL(suggestUrl, window.location.origin);
        url.searchParams.set('q', keyword);
        url.searchParams.set('target', suggestTarget);
        url.searchParams.set('context', suggestContext);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: abortController.signal,
            });

            if (!response.ok) {
                hideList();
                return;
            }

            const payload = await response.json();
            items = Array.isArray(payload?.items) ? payload.items : [];
            activeIndex = -1;
            renderList();
        } catch (error) {
            if (error?.name !== 'AbortError') {
                hideList();
            }
        }
    }

    input.addEventListener('input', () => {
        const keyword = input.value.trim();
        if (hiddenInput instanceof HTMLInputElement) {
            hiddenInput.value = '';
        }

        if (keyword.length < minLength) {
            hideList();
            return;
        }

        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            fetchSuggestions(keyword);
        }, 120);
    });

    input.addEventListener('keydown', (event) => {
        if (list.classList.contains('hidden') || items.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            renderList();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            renderList();
            return;
        }

        if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            pickItem(activeIndex);
            return;
        }

        if (event.key === 'Escape') {
            hideList();
        }
    });

    list.addEventListener('mousedown', (event) => {
        const button = event.target.closest('[data-suggest-item]');

        if (!(button instanceof HTMLElement)) {
            return;
        }

        event.preventDefault();
        pickItem(Number.parseInt(button.dataset.suggestItem || '-1', 10));
    });

    input.addEventListener('blur', () => {
        window.setTimeout(hideList, 140);
    });

    document.addEventListener('click', (event) => {
        if (!field?.contains(event.target)) {
            hideList();
        }
    });
}

function initMemberSearchSuggest() {
    document.querySelectorAll('input[data-member-suggest="1"]').forEach((input) => {
        if (input instanceof HTMLInputElement) {
            initSuggestInput(input);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMemberSearchSuggest);
} else {
    initMemberSearchSuggest();
}

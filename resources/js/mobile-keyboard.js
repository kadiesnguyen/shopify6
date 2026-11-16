const MOBILE_QUERY = '(max-width: 767px)';
const FIELD_SELECTOR = 'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), textarea, select';

function isMobileViewport() {
    return window.matchMedia(MOBILE_QUERY).matches;
}

function isField(element) {
    return element instanceof HTMLElement && element.matches(FIELD_SELECTOR);
}

function syncAppHeight() {
    document.documentElement.style.setProperty('--app-height', `${window.innerHeight}px`);
}

function sheetOf(element) {
    return element instanceof HTMLElement ? element.closest('.portal-keyboard-sheet') : null;
}

function pinOpenSheet() {
    const sheet = sheetOf(document.activeElement);
    const viewport = window.visualViewport;

    document.querySelectorAll('.portal-keyboard-sheet').forEach((element) => {
        if (element !== sheet || !viewport) {
            element.style.top = '';
            element.style.height = '';
            element.style.bottom = '';

            return;
        }

        element.style.top = `${viewport.offsetTop}px`;
        element.style.height = `${viewport.height}px`;
        element.style.bottom = 'auto';
    });
}

function lockLayoutHeight() {
    if (!document.documentElement.dataset.lockedHeight) {
        document.documentElement.dataset.lockedHeight = String(window.innerHeight);
    }

    document.documentElement.style.setProperty(
        '--app-height',
        `${document.documentElement.dataset.lockedHeight}px`,
    );
    document.documentElement.classList.add('keyboard-open');
}

function unlockLayoutHeight() {
    delete document.documentElement.dataset.lockedHeight;
    document.documentElement.classList.remove('keyboard-open');
    pinOpenSheet();
    syncAppHeight();
}

function initMobileKeyboardFix() {
    if (!isMobileViewport()) {
        return;
    }

    syncAppHeight();

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', pinOpenSheet);
        window.visualViewport.addEventListener('scroll', pinOpenSheet);
    }

    window.addEventListener('resize', () => {
        if (document.documentElement.classList.contains('keyboard-open')) {
            return;
        }

        syncAppHeight();
    });

    document.addEventListener(
        'focusin',
        (event) => {
            if (!isMobileViewport() || !isField(event.target)) {
                return;
            }

            // A price sheet is pinned to the visible screen. Locking the page
            // height here is what shoves that sheet under the keyboard.
            if (sheetOf(event.target)) {
                document.documentElement.classList.add('keyboard-open');
                pinOpenSheet();

                return;
            }

            lockLayoutHeight();
        },
        true,
    );

    document.addEventListener(
        'focusout',
        () => {
            if (!isMobileViewport()) {
                return;
            }

            window.setTimeout(() => {
                if (isField(document.activeElement)) {
                    return;
                }

                unlockLayoutHeight();
            }, 80);
        },
        true,
    );

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            unlockLayoutHeight();
        }
    });

    window.addEventListener('pageshow', unlockLayoutHeight);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileKeyboardFix);
} else {
    initMobileKeyboardFix();
}

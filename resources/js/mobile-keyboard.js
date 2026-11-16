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
        element.style.top = element === sheet && viewport ? `${viewport.offsetTop}px` : '';
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
        if (document.documentElement.classList.contains('keyboard-open') || sheetOf(document.activeElement)) {
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

            // A price sheet sits at the top of the screen, so the keyboard never
            // has to scroll the page to reach it. Leave the page behind it as is:
            // locking its height drops the list back to the top.
            if (sheetOf(event.target)) {
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

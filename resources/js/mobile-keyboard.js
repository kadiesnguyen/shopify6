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
    syncAppHeight();
}

function initMobileKeyboardFix() {
    if (!isMobileViewport()) {
        return;
    }

    syncAppHeight();

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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileKeyboardFix);
} else {
    initMobileKeyboardFix();
}

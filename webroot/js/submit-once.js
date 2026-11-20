/**
 * Forms with a data-submit-once attribute are sent only once: a slow upload (e.g. a picture from a phone)
 * must not be sent again when the user taps the button a second time.
 * The submit buttons are disabled and show the text of the attribute (e.g. "Sending...") with a spinner.
 * Coming back with the browser "back" button (page restored from the cache) enables the form again.
 */
(function () {
    'use strict';

    const SENDING = 'is-sending';

    function buttons(form) {
        return form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]');
    }

    function lock(form) {
        form.classList.add(SENDING);
        const text = form.dataset.submitOnce;
        buttons(form).forEach(function (button) {
            button.disabled = true;
            if (text && button.tagName === 'BUTTON') {
                button.dataset.submitOnceLabel = button.innerHTML;
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm me-1';
                spinner.setAttribute('aria-hidden', 'true');
                button.replaceChildren(spinner, document.createTextNode(text));
            }
        });
    }

    function unlock(form) {
        form.classList.remove(SENDING);
        buttons(form).forEach(function (button) {
            button.disabled = false;
            if (button.dataset.submitOnceLabel !== undefined) {
                button.innerHTML = button.dataset.submitOnceLabel;
                delete button.dataset.submitOnceLabel;
            }
        });
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-submit-once')) {
            return;
        }
        if (form.classList.contains(SENDING)) {
            event.preventDefault();

            return;
        }
        // After the browser built the data to send: a disabled button would not be sent
        window.setTimeout(function () {
            lock(form);
        }, 0);
        form.classList.add(SENDING);
    });

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            document.querySelectorAll('form[data-submit-once]').forEach(unlock);
        }
    });
})();

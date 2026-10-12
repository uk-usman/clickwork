/* ==========================================================================
   CircleWork Africa — shared front-end behaviour (vanilla JavaScript)
   ========================================================================== */
(function () {
    'use strict';

    /** Confirm destructive actions. */
    function bindConfirmations() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var message = form.getAttribute('data-confirm') || 'Are you sure?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-confirm-link]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                var message = link.getAttribute('data-confirm-link') || 'Are you sure?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    /** Disable submit buttons after submission to prevent double posting. */
    function bindSubmitLocks() {
        document.querySelectorAll('form[data-lock-submit]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');
                if (!button) { return; }
                window.setTimeout(function () {
                    button.disabled = true;
                    var label = button.getAttribute('data-loading-text');
                    if (label) {
                        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + label;
                    }
                }, 0);
            });
        });
    }

    /** Toggle dependent fields based on a select value. */
    function bindConditionalFields() {
        document.querySelectorAll('[data-toggles]').forEach(function (control) {
            var targetSelector = control.getAttribute('data-toggles');
            var showWhen = control.getAttribute('data-toggles-value') || '';
            var targets = document.querySelectorAll(targetSelector);

            function update() {
                var matches = control.value === showWhen;
                targets.forEach(function (target) {
                    target.classList.toggle('d-none', !matches);
                });
            }

            control.addEventListener('change', update);
            update();
        });
    }

    /** Character counters for textareas with data-counter. */
    function bindCounters() {
        document.querySelectorAll('textarea[data-counter]').forEach(function (area) {
            var target = document.querySelector(area.getAttribute('data-counter'));
            if (!target) { return; }

            function update() {
                target.textContent = area.value.length + ' / ' + (area.getAttribute('maxlength') || '—');
            }

            area.addEventListener('input', update);
            update();
        });
    }

    /** Auto-dismiss flash alerts. */
    function bindAlerts() {
        document.querySelectorAll('[data-auto-dismiss]').forEach(function (alert) {
            window.setTimeout(function () {
                if (window.bootstrap && window.bootstrap.Alert) {
                    window.bootstrap.Alert.getOrCreateInstance(alert).close();
                } else {
                    alert.remove();
                }
            }, parseInt(alert.getAttribute('data-auto-dismiss'), 10) || 6000);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindConfirmations();
        bindSubmitLocks();
        bindConditionalFields();
        bindCounters();
        bindAlerts();
    });
})();

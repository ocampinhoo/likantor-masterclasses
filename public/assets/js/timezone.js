/**
 * Likantor — Zonas horarias con Intl.DateTimeFormat (IANA)
 */
var LikantorTimezone = (function () {
    'use strict';

    function parseUtcDateTime(utcString) {
        if (!utcString) return null;
        var normalized = utcString.trim();
        if (!normalized.endsWith('Z') && normalized.indexOf('+') === -1) {
            normalized = normalized.replace(' ', 'T') + 'Z';
        }
        var date = new Date(normalized);
        return isNaN(date.getTime()) ? null : date;
    }

    function formatDateTime(date, timeZone, locale) {
        try {
            return new Intl.DateTimeFormat(locale || 'es-MX', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                timeZone: timeZone,
                timeZoneName: 'short'
            }).format(date);
        } catch (e) {
            return '—';
        }
    }

    function getUserTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            return 'UTC';
        }
    }

    function initPanel(selector) {
        var panel = document.querySelector(selector);
        if (!panel) return;

        var utcString = panel.getAttribute('data-event-utc');
        var baseTz = panel.getAttribute('data-event-timezone') || 'America/Mexico_City';
        var date = parseUtcDateTime(utcString);
        if (!date) return;

        var select = panel.querySelector('#tz-select');
        var selectedDisplay = panel.querySelector('#tz-selected-display');
        var localDisplay = panel.querySelector('#tz-local-display');

        function updateSelected(tz) {
            if (selectedDisplay) {
                selectedDisplay.textContent = formatDateTime(date, tz);
            }
        }

        function updateLocal() {
            if (localDisplay) {
                var userTz = getUserTimezone();
                localDisplay.textContent = formatDateTime(date, userTz);
            }
        }

        if (select) {
            var userTz = getUserTimezone();
            var options = select.querySelectorAll('option');
            var found = false;
            options.forEach(function (opt) {
                if (opt.value === userTz) {
                    opt.selected = true;
                    found = true;
                }
            });
            if (!found && userTz) {
                var opt = document.createElement('option');
                opt.value = userTz;
                opt.textContent = userTz + ' (tu zona)';
                opt.selected = true;
                select.appendChild(opt);
            } else if (!select.value) {
                select.value = baseTz;
            }

            select.addEventListener('change', function () {
                updateSelected(select.value);
            });

            updateSelected(select.value);
        }

        updateLocal();
    }

    function renderEventDateTime(selector) {
        initPanel(selector);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.querySelector('#timezone-panel');
        if (panel) initPanel('#timezone-panel');
    });

    return {
        initPanel: initPanel,
        renderEventDateTime: renderEventDateTime,
        formatDateTime: formatDateTime,
        parseUtcDateTime: parseUtcDateTime
    };
})();

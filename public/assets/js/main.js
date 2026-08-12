/**
 * Likantor — JavaScript principal
 */
(function () {
    'use strict';

    function initNavToggle() {
        var toggle = document.querySelector('.nav-toggle');
        var nav = document.querySelector('#main-nav');

        if (!toggle || !nav) {
            return;
        }

        toggle.addEventListener('click', function () {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            nav.classList.toggle('is-open');
        });

        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !nav.contains(e.target)) {
                toggle.setAttribute('aria-expanded', 'false');
                nav.classList.remove('is-open');
            }
        });
    }

    function initFlashDismiss() {
        document.querySelectorAll('.flash').forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity 0.4s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 400);
            }, 6000);
        });
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function setFieldError(input, message) {
        if (!input) return;
        var group = input.closest('.form-group');
        if (!group) return;
        group.classList.add('has-error');
        var existing = group.querySelector('.field-error');
        if (existing) existing.remove();
        var span = document.createElement('span');
        span.className = 'field-error';
        span.textContent = message;
        group.appendChild(span);
    }

    function clearFieldError(input) {
        if (!input) return;
        var group = input.closest('.form-group');
        if (!group) return;
        group.classList.remove('has-error');
        var existing = group.querySelector('.field-error');
        if (existing) existing.remove();
    }

    function initLeadFormValidation() {
        document.querySelectorAll('.js-lead-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var valid = true;
                var nameInput = form.querySelector('input[name="name"]');
                var emailInput = form.querySelector('input[name="email"]');
                var privacyInput = form.querySelector('input[name="privacy"]');

                clearFieldError(nameInput);
                clearFieldError(emailInput);
                clearFieldError(privacyInput);

                if (nameInput && nameInput.value.trim().length < 2) {
                    setFieldError(nameInput, 'Ingresa tu nombre completo.');
                    valid = false;
                }

                if (emailInput && !isValidEmail(emailInput.value.trim())) {
                    setFieldError(emailInput, 'Ingresa un correo electrónico válido.');
                    valid = false;
                }

                if (privacyInput && !privacyInput.checked) {
                    setFieldError(privacyInput, 'Debes aceptar el aviso de privacidad.');
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                    var firstError = form.querySelector('.has-error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    }

    function initSmoothAnchors() {
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var id = anchor.getAttribute('href');
                if (!id || id === '#') return;
                var target = document.querySelector(id);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    var nav = document.querySelector('#main-nav');
                    var toggle = document.querySelector('.nav-toggle');
                    if (nav) nav.classList.remove('is-open');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initNavToggle();
        initFlashDismiss();
        initLeadFormValidation();
        initSmoothAnchors();
    });
})();

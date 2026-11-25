/**
 * SpiderWaves Universal Inline Form Validation
 *
 * 1. Enforces 'novalidate' across all forms to suppress default browser validation popups.
 * 2. Validates all field types: text, number, select (native & Select2), textarea, radio/checkbox, date.
 * 3. Shows small red validation messages directly below invalid fields.
 * 4. Highlights invalid inputs with red borders (#ef4444).
 * 5. Automatically clears errors in real-time when the user inputs a valid value or selects an option.
 * 6. Intercepts form submissions via capture phase so it runs before any custom submit / AJAX handler.
 */

(function(window, document, $) {
    'use strict';

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function cleanLabelText(txt) {
        if (!txt) return '';
        return txt
            .replace(/[\*\:\|]/g, ' ')
            .replace(/\([^)]*\)/g, ' ')
            .replace(/\brequired\b/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getSelect2Container($field) {
        if (!$field || !$field.length) return null;
        var isSelect2 = $field.hasClass('select2') || 
                        $field.hasClass('select2-hidden-accessible') || 
                        $field.hasClass('select2-offscreen') || 
                        $field.hasClass('select2-modal') || 
                        $field.hasClass('select');
        if (!isSelect2) return null;

        var $next = $field.next('.select2-container');
        if ($next.length) return $next;

        var $sib = $field.siblings('.select2-container').first();
        if ($sib.length) return $sib;

        return null;
    }

    function getFieldLabel(field) {
        var $field = $(field);

        // 1. Explicit data-label or data-name
        var label = $field.attr('data-label') || $field.attr('data-name');
        if (label) return cleanLabelText(label);

        // 2. Explicit <label for="id">
        var id = field.id;
        if (id) {
            var $forLabel = $('label[for="' + id + '"]');
            if ($forLabel.length) {
                var txt = cleanLabelText($forLabel.text());
                if (txt) return txt;
            }
        }

        // 3. Closest form-group / sw-form-group label
        var $group = $field.closest('.sw-form-group, .form-group');
        if ($group.length) {
            var $groupLabel = $group.find('> label, .sw-label, .control-label, .input-label').first();
            if ($groupLabel.length) {
                var groupTxt = cleanLabelText($groupLabel.text());
                if (groupTxt) return groupTxt;
            }
        }

        // 4. Preceding sibling label
        var $prev = $field.prev('label');
        if ($prev.length) {
            var prevTxt = cleanLabelText($prev.text());
            if (prevTxt) return prevTxt;
        }

        // 5. Placeholder
        var ph = $field.attr('placeholder');
        if (ph) {
            var phTxt = cleanLabelText(ph);
            if (phTxt) return phTxt;
        }

        // 6. Name or ID formatted to Title Case
        var name = $field.attr('name') || id || 'Field';
        name = name.replace(/\[\]$/, '').replace(/_id$/i, '');
        return name.replace(/[_-]/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }

    function getFieldErrorMessage(field, label) {
        var $field = $(field);
        var customMsg = $field.attr('data-error-msg') || $field.attr('data-msg-required');
        if (customMsg) return customMsg;

        var clean = label.replace(/^(select|choose|enter)\s+/i, '').trim();
        if (!clean) clean = 'Field';

        var isSelect = field.tagName === 'SELECT' || $field.hasClass('select2') || $field.hasClass('select');
        var isRadioOrCheckbox = field.type === 'radio' || field.type === 'checkbox';

        if (isSelect || isRadioOrCheckbox) {
            return 'Please select ' + clean;
        }

        // Number min validation check
        if (field.type === 'number' && field.value && field.value.trim() !== '') {
            var min = parseFloat(field.getAttribute('min'));
            if (!isNaN(min) && parseFloat(field.value) < min) {
                if (min === 0.001 || min === 1) {
                    return clean + ' must be greater than 0';
                }
                return clean + ' must be at least ' + min;
            }
        }

        return clean + ' is required';
    }

    function isFieldRequired(field) {
        var $field = $(field);

        // Never validate Select2 internal focus/search inputs or plugin UI containers
        if ($field.hasClass('select2-focusser') || 
            $field.hasClass('select2-input') || 
            $field.closest('.select2-container, .select2-drop, .select2-dropdown').length) {
            return false;
        }

        // Native required attribute or property
        if (field.hasAttribute('required') || field.required === true) return true;

        // Custom data-required or required class
        if ($field.attr('data-required') === 'true' || $field.hasClass('required')) return true;

        // Check if parent group label has asterisk indicator (* or req or text-danger)
        var $group = $field.closest('.sw-form-group, .form-group');
        if ($group.length) {
            var $label = $group.find('> label, .sw-label, .control-label, .input-label').first();
            if ($label.length && ($label.find('.req, .text-danger').length > 0 || $label.text().indexOf('*') !== -1)) {
                return true;
            }
        }

        return false;
    }

    function checkFieldValidity(field) {
        var $field = $(field);

        if (field.tagName === 'SELECT') {
            var val = $field.val();
            if (val === null || val === undefined || val === '') return false;
            if (Array.isArray(val) && val.length === 0) return false;
            return true;
        }

        if (field.type === 'radio' || field.type === 'checkbox') {
            var name = field.name;
            if (!name) return field.checked;
            var form = field.form || $field.closest('form')[0];
            if (form) {
                return form.querySelectorAll('input[name="' + name + '"]:checked').length > 0;
            }
            return field.checked;
        }

        var textVal = field.value ? field.value.trim() : '';
        if (textVal === '') return false;

        // Number min check
        if (field.type === 'number') {
            var num = parseFloat(textVal);
            if (isNaN(num)) return false;
            if (field.hasAttribute('min')) {
                var min = parseFloat(field.getAttribute('min'));
                if (!isNaN(min) && num < min) return false;
            }
            // Strict check for weight and purity inputs that cannot be 0
            var fid = (field.id || '').toLowerCase();
            var fname = (field.name || '').toLowerCase();
            if (fid === 'weight' || fid === 'gross_weight' || fname === 'gross_weight' || fid === 'net_weight' || fname === 'net_weight') {
                if (num <= 0) return false;
            }
        }

        return true;
    }

    function showFieldError(field, message) {
        var $field = $(field);
        var $s2 = getSelect2Container($field);

        $field.addClass('sw-input-error is-invalid');
        if ($s2 && $s2.length) {
            $s2.addClass('sw-input-error is-invalid');
            $s2.find('.select2-choice, .select2-choices, .select2-selection').addClass('sw-input-error is-invalid');
        }

        $field.closest('.sw-form-group, .form-group').addClass('has-error');

        // Target where error message is placed
        var $placementTarget = $field;
        if ($s2 && $s2.length) {
            $placementTarget = $s2;
        } else if ($field.closest('.input-group').length) {
            $placementTarget = $field.closest('.input-group');
        }

        var $existing = $placementTarget.next('.sw-field-error');
        if ($existing.length) {
            $existing.text(message).show();
        } else {
            $placementTarget.after('<div class="sw-field-error">' + escapeHtml(message) + '</div>');
        }
    }

    function clearFieldError(field) {
        var $field = $(field);
        var $s2 = getSelect2Container($field);

        $field.removeClass('sw-input-error is-invalid');
        if ($s2 && $s2.length) {
            $s2.removeClass('sw-input-error is-invalid');
            $s2.find('.select2-choice, .select2-choices, .select2-selection').removeClass('sw-input-error is-invalid');
        }

        var $placementTarget = $field;
        if ($s2 && $s2.length) {
            $placementTarget = $s2;
        } else if ($field.closest('.input-group').length) {
            $placementTarget = $field.closest('.input-group');
        }

        $placementTarget.next('.sw-field-error').remove();

        var $group = $field.closest('.sw-form-group, .form-group');
        if ($group.length && $group.find('.sw-input-error').length === 0) {
            $group.removeClass('has-error');
        }
    }

    function clearFormErrors(form) {
        var $form = $(form);
        $form.find('.sw-input-error, .is-invalid').removeClass('sw-input-error is-invalid');
        $form.find('.has-error').removeClass('has-error');
        $form.find('.sw-field-error').remove();
    }

    function focusField($field) {
        if (!$field || !$field.length) return;
        var $s2 = getSelect2Container($field);

        if ($s2 && $s2.length) {
            $s2.find('.select2-selection').focus();
        } else {
            $field.focus();
        }

        try {
            var raw = ($s2 && $s2.length) ? $s2[0] : $field[0];
            var rect = raw.getBoundingClientRect();
            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                raw.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (e) {}
    }

    function validateForm(form) {
        if (!form) return true;
        var $form = $(form);

        // Always enforce novalidate on form
        if (!form.hasAttribute('novalidate')) {
            form.setAttribute('novalidate', 'novalidate');
        }

        var isValid = true;
        var $firstInvalid = null;

        var elements = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];

            // Ignore Select2 internal search/focus inputs and dropdown helpers
            if ($(el).hasClass('select2-focusser') || 
                $(el).hasClass('select2-input') || 
                $(el).closest('.select2-container, .select2-drop, .select2-dropdown').length) {
                continue;
            }

            // Ignore hidden inputs (unless it's a select2 select)
            if (el.type === 'hidden' && !$(el).hasClass('select2-hidden-accessible')) continue;
            if (el.type === 'submit' || el.type === 'button' || el.type === 'reset') continue;
            if (el.disabled) continue;

            // Visibility check
            var $s2 = getSelect2Container($(el));
            var isVisible = false;

            if ($s2 && $s2.length) {
                isVisible = $s2.is(':visible');
            } else {
                isVisible = $(el).is(':visible');
            }
            if (!isVisible) continue;

            // Check if required
            var isRequired = isFieldRequired(el);
            if (!isRequired) continue;

            var valid = checkFieldValidity(el);
            if (!valid) {
                isValid = false;
                var label = getFieldLabel(el);
                var msg = getFieldErrorMessage(el, label);
                showFieldError(el, msg);

                if (!$firstInvalid) {
                    $firstInvalid = $(el);
                }
            } else {
                clearFieldError(el);
            }
        }

        if (!isValid) {
            $form.addClass('sw-form-submitted');
            if ($firstInvalid) {
                focusField($firstInvalid);
            }
        }

        return isValid;
    }

    // Real-time input clearing
    $(document).on('input change keyup changeDate dp.change', 'input, select, textarea', function() {
        var el = this;
        var $el = $(el);
        if ($el.hasClass('select2-focusser') || $el.hasClass('select2-input') || $el.closest('.select2-container, .select2-drop, .select2-dropdown').length) {
            return;
        }
        var $s2 = getSelect2Container($el);
        var $placementTarget = ($s2 && $s2.length) ? $s2 : ($el.closest('.input-group').length ? $el.closest('.input-group') : $el);

        if ($el.hasClass('sw-input-error') || 
            $placementTarget.next('.sw-field-error').length || 
            $el.closest('form').hasClass('sw-form-submitted')) {
            if (checkFieldValidity(el)) {
                clearFieldError(el);
            }
        }
    });

    // Select2 specific selection change
    $(document).on('change select2:select select2:clear select2:close select2-selecting select2-selected select2-close', 'select', function() {
        var el = this;
        setTimeout(function() {
            if (checkFieldValidity(el)) {
                clearFieldError(el);
            }
        }, 40);
    });

    // Auto clear on AJAX completion if fields were populated
    $(document).ajaxComplete(function() {
        $('.sw-input-error').each(function() {
            if (checkFieldValidity(this)) {
                clearFieldError(this);
            }
        });
        enforceNovalidate();
    });

    // Submit Event Interception (Capture Phase)
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // Skip GET filter/search forms without required fields
        if (form.getAttribute('data-no-validate') === 'true' || 
            (form.getAttribute('method') && form.getAttribute('method').toLowerCase() === 'get' && form.querySelectorAll('[required]').length === 0)) {
            return;
        }

        if (!validateForm(form)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    }, true);

    // Submit Button Click Interception (Capture Phase)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('button[type="submit"], input[type="submit"], .btn-submit, [data-form-submit]');
        if (!btn) return;

        var form = btn.form || btn.closest('form');
        if (!form) return;

        if (form.getAttribute('data-no-validate') === 'true' || 
            (form.getAttribute('method') && form.getAttribute('method').toLowerCase() === 'get' && form.querySelectorAll('[required]').length === 0)) {
            return;
        }

        if (!validateForm(form)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    }, true);

    // Enforce novalidate on all forms
    function enforceNovalidate() {
        var forms = document.querySelectorAll('form:not([novalidate])');
        for (var i = 0; i < forms.length; i++) {
            forms[i].setAttribute('novalidate', 'novalidate');
        }
    }

    $(document).ready(enforceNovalidate);
    $(document).on('shown.bs.modal', enforceNovalidate);

    // Export public helper methods
    window.SpiderWaves = window.SpiderWaves || {};
    window.SpiderWaves.validateForm = validateForm;
    window.SpiderWaves.clearFormErrors = clearFormErrors;
    window.SpiderWaves.showFieldError = showFieldError;
    window.SpiderWaves.clearFieldError = clearFieldError;
    window.validateForm = validateForm;
    window.clearFormErrors = clearFormErrors;

})(window, document, window.jQuery || window.$);

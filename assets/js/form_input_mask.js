(function () {
    'use strict';

    function formatCNIC(value) {
        // Remove everything except digits
        const digits = value.replace(/\D/g, '').slice(0, 13);

        let formatted = '';

        if (digits.length > 0) {
            formatted += digits.substring(0, 5);
        }
        if (digits.length >= 6) {
            formatted += '-' + digits.substring(5, 12);
        }
        if (digits.length >= 13) {
            formatted += '-' + digits.substring(12, 13);
        }

        return formatted;
    }

    function isCompleteCNIC(value) {
        return /^\d{5}-\d{7}-\d{1}$/.test(value);
    }

    function attachCNICMask(input) {
        if (!input) return;

        input.setAttribute('maxlength', '15');
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('placeholder', '00000-0000000-0');

        input.addEventListener('input', function (e) {
            const cursorPos = input.selectionStart;
            const before = input.value;

            input.value = formatCNIC(input.value);

            // Maintain cursor position
            const after = input.value;
            const diff = after.length - before.length;
            input.setSelectionRange(cursorPos + diff, cursorPos + diff);
        });

        input.addEventListener('blur', function () {
            // Clear incomplete CNIC on blur
            if (input.value && !isCompleteCNIC(input.value)) {
                input.value = '';
            }
        });

        input.addEventListener('keypress', function (e) {
            // Allow only digits
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Auto-attach to any input with data-mask="cnic"
        document.querySelectorAll('input[data-mask="cnic"]').forEach(function (input) {
            attachCNICMask(input);
        });
    });

})();


/* =========================================================
 * PHONE INPUT MASK (COUNTRY AWARE)
 * ======================================================= */

(function () {
  'use strict';

  // Mask definitions (10 digits after country for both)
  const PHONE_MASKS = {
    US: {
      prefix: '+1 ',
      pattern: '+1 (___) ___-____',
      slots: [4,5,6, 9,10,11, 13,14,15,16], // positions in pattern for digits
      digitsRequired: 10
    },
    PK: {
      prefix: '+92',
      pattern: '+92__________',
      slots: [3,4,5,6,7,8,9,10,11,12], // positions for digits
      digitsRequired: 10
    }
  };

  function onlyDigits(str) {
    return (str || '').replace(/\D/g, '');
  }

  function buildMaskedValue(country, digits) {
    const cfg = PHONE_MASKS[country];
    if (!cfg) return '';

    const d = onlyDigits(digits).slice(0, cfg.digitsRequired);
    let out = cfg.pattern.split('');

    for (let i = 0; i < cfg.slots.length; i++) {
      const pos = cfg.slots[i];
      out[pos] = d[i] ? d[i] : out[pos]; // fill digits, keep "_" for remaining
    }
    return out.join('');
  }

  function extractDigitsFromMasked(country, maskedValue) {
    const cfg = PHONE_MASKS[country];
    if (!cfg) return '';
    const chars = (maskedValue || '').split('');
    let digits = '';
    for (let i = 0; i < cfg.slots.length; i++) {
      const pos = cfg.slots[i];
      const ch = chars[pos];
      if (ch && /\d/.test(ch)) digits += ch;
    }
    return digits;
  }

  function isComplete(country, maskedValue) {
    const cfg = PHONE_MASKS[country];
    if (!cfg) return false;
    const digits = extractDigitsFromMasked(country, maskedValue);
    return digits.length === cfg.digitsRequired && maskedValue.indexOf('_') === -1;
  }

  function setValidity(input, ok, msg) {
    if (!input) return;
    input.setCustomValidity(ok ? '' : (msg || 'Invalid value'));
  }

  function attachPhoneMask(input, countrySelect) {
    let country = '';

    function applyCountry(newCountry) {
      country = newCountry || '';
      if (!country || !PHONE_MASKS[country]) {
        input.value = '';
        input.placeholder = 'Select country first';
        setValidity(input, true, '');
        return;
      }

      // Set initial empty mask
      input.value = buildMaskedValue(country, '');
      input.placeholder = PHONE_MASKS[country].pattern;

      // If required, it must be complete before submit
      if (input.required) {
        setValidity(input, false, 'Please enter a valid phone number.');
      }
    }

    function updateFromUserDigits(digits) {
      if (!country || !PHONE_MASKS[country]) return;

      input.value = buildMaskedValue(country, digits);

      // Enforce completeness for required fields
      if (input.required) {
        setValidity(input, isComplete(country, input.value), 'Please enter a valid phone number.');
      } else {
        // If not required, empty is OK; partial should still be blocked only if user typed something
        const anyTyped = extractDigitsFromMasked(country, input.value).length > 0;
        setValidity(input, !anyTyped || isComplete(country, input.value), 'Please enter a complete phone number or leave blank.');
      }
    }

    // Country change
    countrySelect.addEventListener('change', function () {
      applyCountry(this.value);
    });

    // Block non-digits on keypress (but allow backspace etc.)
    input.addEventListener('keydown', function (e) {
      if (!country || !PHONE_MASKS[country]) return;

      const allowedKeys = [
        'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'
      ];
      if (allowedKeys.includes(e.key)) return;

      // Allow Ctrl/Cmd shortcuts
      if (e.ctrlKey || e.metaKey) return;

      // Only digits
      if (!/^\d$/.test(e.key)) {
        e.preventDefault();
      }
    });

    // Handle input (typing + paste)
    input.addEventListener('input', function () {
      if (!country || !PHONE_MASKS[country]) {
        input.value = '';
        return;
      }

      // Get digits from current value
      let digits = onlyDigits(input.value);

      // Strip country digits if user pastes full number:
      // US country digits = "1", PK = "92"
      if (country === 'US' && digits.startsWith('1')) digits = digits.slice(1);
      if (country === 'PK' && digits.startsWith('92')) digits = digits.slice(2);

      digits = digits.slice(0, PHONE_MASKS[country].digitsRequired);
      updateFromUserDigits(digits);
    });

    // On focus, ensure mask is present
    input.addEventListener('focus', function () {
      if (!country || !PHONE_MASKS[country]) return;
      if (!input.value) input.value = PHONE_MASKS[country].pattern;
    });

    // On blur: if incomplete, clear (keeps UX clean)
    input.addEventListener('blur', function () {
      if (!country || !PHONE_MASKS[country]) return;

      const digitsLen = extractDigitsFromMasked(country, input.value).length;
      if (digitsLen === 0) {
        // empty is fine if not required; if required, validity already set
        if (!input.required) {
          input.value = '';
          setValidity(input, true, '');
        }
        return;
      }

      if (!isComplete(country, input.value)) {
        input.value = '';
        if (input.required) {
          setValidity(input, false, 'Please enter a valid phone number.');
        } else {
          setValidity(input, true, '');
        }
      }
    });

    // Initialize: do not force a default country
    applyCountry(countrySelect.value || '');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('input[data-mask="phone"]');
    inputs.forEach(function (input) {
      const group = input.closest('.input-group');
      if (!group) return;

      const countrySelect = group.querySelector('select[data-phone-country]');
      if (!countrySelect) return;

      attachPhoneMask(input, countrySelect);
    });
  });

})();

/* =========================================================
 * EMP ID Numeric Only
 * ======================================================= */
 
(function () {
    'use strict';

    function enforceNumericOnly(el) {
        el.value = el.value.replace(/\D+/g, '');
    }

    // Typing / paste / drag / autofill
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('numeric-only')) {
            enforceNumericOnly(e.target);
        }
    });

    // Block non-numeric keys (extra safety)
    document.addEventListener('keydown', function (e) {
        if (!e.target.classList.contains('numeric-only')) return;

        const allowedKeys = [
            'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight',
            'Home', 'End'
        ];

        if (allowedKeys.includes(e.key)) return;

        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault();
        }
    });
})();


/* =========================================================
 * IBAN / ACCOUNT NUMBER MASK (ALPHANUMERIC ONLY)
 * ======================================================= */

(function () {
    'use strict';

    function sanitize(value) {
        return value.replace(/[^a-zA-Z0-9]/g, '');
    }

    function handleInput(el) {
        const min = parseInt(el.dataset.min || 0, 10);
        const max = parseInt(el.dataset.max || 0, 10);

        let val = sanitize(el.value);

        if (max > 0 && val.length > max) {
            val = val.slice(0, max);
        }

        el.value = val;

        if (min > 0 && val.length < min) {
            el.setCustomValidity(`IBAN must be at least ${min} characters`);
        } else {
            el.setCustomValidity('');
        }
    }

    // INPUT + PASTE SAFE
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (el && el.matches('input[data-mask="iban"]')) {
            handleInput(el);
        }
    });

    // KEYDOWN GUARD (NO SPECIAL CHARS)
    document.addEventListener('keydown', function (e) {
        const el = e.target;
        if (!el || !el.matches('input[data-mask="iban"]')) return;

        const allowed = [
            'Backspace', 'Delete', 'Tab',
            'ArrowLeft', 'ArrowRight',
            'Home', 'End'
        ];

        if (allowed.includes(e.key)) return;

        if (!/^[a-zA-Z0-9]$/.test(e.key)) {
            e.preventDefault();
        }
    });

})();


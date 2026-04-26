document.addEventListener("DOMContentLoaded", function() {
  const toggleBtn = document.getElementById("sidebarToggleBtn");
  const sidebar  = document.querySelector(".sidebar");
  const pageWrap = document.getElementById("page-wrapper");

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("active");
      // On small screens, we want page content to fill full width, so margin-left resets:
      if (window.innerWidth <= 767) {
        if (sidebar.classList.contains("active")) {
          pageWrap.style.marginLeft = "var(--sidebar-width)";
        } else {
          pageWrap.style.marginLeft = "0";
        }
      }
    });
  }
});



// sidebar 


// Sidebar toggle for mobile view
document.addEventListener("DOMContentLoaded", function() {
  var toggleBtn = document.getElementById("sidebarToggleBtn");
  var sidebar   = document.querySelector(".sidebar");
  var pageWrap  = document.getElementById("page-wrapper");

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener("click", function() {
      sidebar.classList.toggle("active");
      if (sidebar.classList.contains("active")) {
        pageWrap.style.marginLeft = "250px";
        pageWrap.style.width = "calc(100% - 250px)";
      } else {
        pageWrap.style.marginLeft = "0";
        pageWrap.style.width = "100%";
      }
    });
  }

  // Handle submenu opening/closing
  var topLinks = document.querySelectorAll(".sidebar .nav > li > a");
  topLinks.forEach(function(link) {
    link.addEventListener("click", function(e) {
      var parentLi = this.parentElement;
      if (parentLi.classList.contains("open")) {
        parentLi.classList.remove("open");
      } else {
        // Close any other open submenus
        document.querySelectorAll(".sidebar .nav > li.open").forEach(function(openLi) {
          openLi.classList.remove("open");
        });
        // Open this submenu if it exists
        if (this.nextElementSibling && this.nextElementSibling.classList.contains("nav-second-level")) {
          parentLi.classList.add("open");
          e.preventDefault();
        }
      }
    });
  });
});




// Tables Js Code //


  $(function() {
    var $label = $('#users-table_filter label');

    // 1) Remove the text node ("Search:") inside the label
    $label.contents().filter(function() {
      return this.nodeType === 3; // text nodes only
    }).remove();

    // 2) Add a real placeholder to the input
    $label.find('input')
      .attr('placeholder', 'Search…')
      .prop('spellcheck', false)
      .css({
        /* optional: tweak the input’s look if needed */
        'opacity': 1
      });
  });
  
$(function(){
  // find the label around the length selector…
  var $label = $('#users-table_length label');

  // remove all text nodes (nodeType === 3) from it
  $label.contents()
        .filter(function(){ return this.nodeType === 3; })
        .remove();
});  


$(function(){
  var $len = $('#users-table_length');

  // Build your icon‐only buttons (using FontAwesome here)
  var importBtn = '<button type="button" id="btn-import"'
                + ' class="dt-action-btn" title="Import">'
                + '  <i class="fas fa-file-import" aria-hidden="true"></i>'
                + '</button>';
  var exportBtn = '<button type="button" id="btn-export"'
                + ' class="dt-action-btn" title="Export">'
                + '  <i class="fas fa-download" aria-hidden="true"></i>'
                + '</button>';

  // Append them if not already present
  if (!$len.find('#btn-import').length) {
    $len.append(importBtn).append(exportBtn);
  }

  // (Optional) Hook up click handlers
  $('#btn-import').on('click', function(){
    // your import logic here
    alert('Import clicked');
  });
  $('#btn-export').on('click', function(){
    // your export logic here
    alert('Export clicked');
  });
});


// Alert  Js Code Ends //


// Tiny MCE Js Code Start

/* =========================================================================
 * Global Rich Text bootstrap for <textarea class="rte"> or [data-editor="tinymce"]
 * - Single source of truth for TinyMCE init/destroy
 * - Auto inits on new DOM (AJAX/modals) via MutationObserver
 * - Call window.RichText.triggerSave() before submit (we also auto-hook)
 * ========================================================================= */
(function () {
  var SELECTOR = 'textarea.rte, textarea[data-editor="tinymce"]';
  var BASE_URL = (window.APP_BASE_URL || document.querySelector('base')?.href || '/')
                   .replace(/\/+$/, '') + '/assets/plugins/tinymce';

  // Idempotent guard
  if (window.RichText && window.RichText.__installed) return;

  function isTinyReady() { return !!(window.tinymce && typeof tinymce.init === 'function'); }

  function mark(el) { el.setAttribute('data-rte-initialized', '1'); }
  function unmark(el) { el.removeAttribute('data-rte-initialized'); }
  function isMarked(el) { return el.getAttribute('data-rte-initialized') === '1'; }

  function shouldSkip(el) {
    // allow opt-out with data-no-editor or disabled attribute
    return el.hasAttribute('data-no-editor') || el.disabled;
  }

  function initOne(el) {
    if (!isTinyReady() || !el || isMarked(el) || shouldSkip(el)) return;

    // if an instance exists with the same id, remove it first
    var id = el.id || ('rte_' + Math.random().toString(36).slice(2));
    if (!el.id) el.id = id;
    try { if (window.tinymce.get(id)) tinymce.execCommand('mceRemoveEditor', false, id); } catch (e) {}

    tinymce.init({
      selector: '#' + id,

      // where to load skins/plugins from (your local folder)
      base_url: BASE_URL,
      suffix: '.min',

      // look & feel
      menubar: 'file edit view insert format tools table',
      plugins: 'link lists table code advlist autoresize',
      toolbar: [
        'fontfamily fontsize | forecolor backcolor | bold italic underline |',
        'bullist numlist outdent indent | alignleft aligncenter alignright alignjustify |',
        'link removeformat | code'
      ].join(' '),
      font_family_formats: [
        'System Font=system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";',
        'Arial=Arial,Helvetica,sans-serif;',
        'Georgia=Georgia,serif;',
        'Times New Roman="Times New Roman",Times,serif;',
        'Courier New="Courier New",Courier,monospace;'
      ].join(''),
      fontsize_formats: '10pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt',
      content_style: 'body{font:12pt system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";} a{color:#0b57d0;text-decoration:underline;} p{margin:0 0 10px;}',
      min_height: 320,
      autoresize_bottom_margin: 24,
      
      // preserve authoring HTML exactly
      convert_urls: false,
      relative_urls: false,
      remove_script_host: false,
      valid_elements: '*[*]',
      extended_valid_elements: '*[*]',
      paste_data_images: true,
      paste_tab_spaces: 2,

      // Kill Tiny branding/promo
      branding: false,     // hides “Powered by Tiny”
      promotion: false,    // hides the “Upgrade” button/banner
      
      setup: function (editor) {
        editor.on('init', function () { mark(el); });
        // optional: support “insert merge field” links anywhere in the page
        editor.on('click', function (e) {
          // no-op; kept for future hooks
        });
      }
    });
  }

  function destroyOne(el) {
    if (!isTinyReady() || !el) return;
    var ed = tinymce.get(el.id);
    if (ed) { try { ed.remove(); } catch (e) {} }
    unmark(el);
  }

  function initAll(root) {
    if (!isTinyReady()) return;
    (root || document).querySelectorAll(SELECTOR).forEach(initOne);
  }

  // Initialize when TinyMCE is ready (in case script loads slowly)
  function bootWhenReady(retries) {
    if (isTinyReady()) { initAll(); return; }
    if ((retries || 0) > 60) { console.warn('TinyMCE not ready; skipped init'); return; }
    setTimeout(function(){ bootWhenReady((retries||0)+1); }, 250);
  }
  bootWhenReady(0);

  // Re-init on modal show (Bootstrap 5)
  document.addEventListener('shown.bs.modal', function (e) {
    initAll(e.target);
  });

  // Observe DOM changes (AJAX, SPA fragments)
  var mo;
  try {
    mo = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        m.addedNodes && m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return; // ELEMENT_NODE
          if (n.matches && n.matches(SELECTOR)) initOne(n);
          if (n.querySelectorAll) n.querySelectorAll(SELECTOR).forEach(initOne);
        });
        m.removedNodes && m.removedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.matches && n.matches(SELECTOR)) destroyOne(n);
          if (n.querySelectorAll) n.querySelectorAll(SELECTOR).forEach(destroyOne);
        });
      });
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
  } catch (e) { /* older browsers: ignore */ }

  // Auto triggerSave on any form submit so <textarea> contains editor HTML
  document.addEventListener('submit', function (e) {
    if (!isTinyReady()) return;
    try { tinymce.triggerSave(); } catch (err) {}
  }, true);

  // Optional: support clicking any ".add_merge_field" link to insert into the focused editor
  document.addEventListener('click', function (e) {
    var a = e.target.closest('.add_merge_field');
    if (!a) return;
    e.preventDefault();
    var key = a.getAttribute('data-key') || a.textContent || '';
    if (!key) return;
    if (isTinyReady() && tinymce.activeEditor) {
      tinymce.activeEditor.execCommand('mceInsertContent', false, key);
    } else {
      // fall back to last textarea
      var ta = document.querySelector(SELECTOR);
      if (ta) {
        var start = ta.selectionStart || 0, end = ta.selectionEnd || 0, v = ta.value;
        ta.value = v.slice(0, start) + key + v.slice(end);
        ta.setSelectionRange(start + key.length, start + key.length);
        ta.focus();
      }
    }
  });

  // Public API
  window.RichText = {
    __installed: true,
    init: initAll,
    initOne: initOne,
    destroy: function (root) {
      (root || document).querySelectorAll(SELECTOR).forEach(destroyOne);
    },
    getHTML: function (elOrId) {
      if (!isTinyReady()) return '';
      var id = typeof elOrId === 'string' ? elOrId : (elOrId?.id || null);
      var ed = id ? tinymce.get(id) : tinymce.activeEditor;
      return ed ? ed.getContent({ format: 'html' }) : '';
    },
    triggerSave: function () { if (isTinyReady()) try { tinymce.triggerSave(); } catch(e) {} }
  };
})();

// Tiny MCE Js Code Ends


/**
 * Global Fullscreen Toggle
 * Works anywhere in the app as long as the button exists in the DOM.
 * Button ID: #btn-fullscreen
 * Icon ID: #fullscreen-icon
 */

(function () {
    console.log('[FS] Script loaded');

    var btn  = document.getElementById('btn-fullscreen');
    var icon = document.getElementById('fullscreen-icon');

    if (!btn) {
        console.warn('[FS] btn-fullscreen not found in DOM');
        return;
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('[FS] Button clicked');

        var doc   = document;
        var docEl = doc.documentElement;

        var isFullscreen =
            doc.fullscreenElement ||
            doc.webkitFullscreenElement ||
            doc.mozFullScreenElement ||
            doc.msFullscreenElement;

        if (!isFullscreen) {
            // ENTER fullscreen
            console.log('[FS] Entering fullscreen');
            if (docEl.requestFullscreen) {
                docEl.requestFullscreen();
            } else if (docEl.webkitRequestFullscreen) {
                docEl.webkitRequestFullscreen();
            } else if (docEl.mozRequestFullScreen) {
                docEl.mozRequestFullScreen();
            } else if (docEl.msRequestFullscreen) {
                docEl.msRequestFullscreen();
            }
        } else {
            // EXIT fullscreen
            console.log('[FS] Exiting fullscreen');
            if (doc.exitFullscreen) {
                doc.exitFullscreen();
            } else if (doc.webkitExitFullscreen) {
                doc.webkitExitFullscreen();
            } else if (doc.mozCancelFullScreen) {
                doc.mozCancelFullScreen();
            } else if (doc.msExitFullscreen) {
                doc.msExitFullscreen();     
            }
        }
    });

    // Optional: keep icon in sync when user presses ESC to exit fullscreen
    document.addEventListener('fullscreenchange', function () {
        var isFullscreen = !!document.fullscreenElement;
        if (!icon) return;

        if (isFullscreen) {
            icon.classList.remove('ti-maximize');
            icon.classList.add('ti-minimize');
        } else {
            icon.classList.remove('ti-minimize');
            icon.classList.add('ti-maximize');
        }
    });
})();

// Search for dropdown fields
(function () {
    'use strict';

    const MAX_VISIBLE = 500;

    function enhanceSelect(select) {
        if (select.dataset.enhanced === '1') return;
        select.dataset.enhanced = '1';

        /* Wrapper */
        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select-wrapper';

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        /* Hide original select */
        select.style.display = 'none';

        /* Display */
        const display = document.createElement('div');
        display.className = 'form-control searchable-select-display';
        display.tabIndex = 0;
        display.textContent =
            select.options[select.selectedIndex]?.text || 'Select…';

        /* Dropdown */
        const dropdown = document.createElement('div');
        dropdown.className = 'searchable-select-dropdown d-none';

        /* Search input */
        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'form-control form-control-sm searchable-select-search';
        search.placeholder = 'Search…';

        /* Options list */
        const list = document.createElement('div');
        list.className = 'searchable-select-list';

        dropdown.appendChild(search);
        dropdown.appendChild(list);

        wrapper.appendChild(display);
        wrapper.appendChild(dropdown);

        function renderOptions(filter = '') {
            list.innerHTML = '';
            let shown = 0;

            Array.from(select.options).forEach(opt => {
                if (!opt.value) return;

                const text = opt.text.toLowerCase();
                if (filter && !text.includes(filter.toLowerCase())) return;
                if (!filter && shown >= MAX_VISIBLE) return;

                shown++;

                const item = document.createElement('div');
                item.className = 'searchable-select-option';
                item.textContent = opt.text;

                if (opt.selected) {
                    item.classList.add('active');
                }

                item.addEventListener('click', () => {
                    select.value = opt.value;
                    display.textContent = opt.text;
                    dropdown.classList.add('d-none');
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });

                list.appendChild(item);
            });

            if (!list.children.length) {
                const empty = document.createElement('div');
                empty.className = 'searchable-select-empty';
                empty.textContent = 'No results';
                list.appendChild(empty);
            }
        }

        /* Toggle dropdown */
        display.addEventListener('click', () => {
            dropdown.classList.toggle('d-none');
            search.value = '';
            renderOptions();
            search.focus();
        });

        /* Filter */
        search.addEventListener('input', () => {
            renderOptions(search.value);
        });

        /* Click outside */
        document.addEventListener('click', e => {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });

        renderOptions();
    }

    function initSearchableSelects() {
        document
            .querySelectorAll('select.js-searchable-select')
            .forEach(enhanceSelect);
    }

    document.addEventListener('DOMContentLoaded', initSearchableSelects);
})();


/* ============================================================
 * App Date Rules Utility (Global / Conflict-Safe)
 * - Future dates only
 * - Disable weekends (Sat/Sun)
 * - Universal for any input[type="date"] or text input
 * - No dependency (pure JS)
 * - Namespaced to avoid conflicts
 * ============================================================ */

(function (window, document) {
  "use strict";

  // Prevent re-declaration conflicts
  if (window.AppDateRules) return;

  const AppDateRules = {
    version: "1.0.0",

    /**
     * Format Date => YYYY-MM-DD (local)
     */
    toYMD(dateObj) {
      const y = dateObj.getFullYear();
      const m = String(dateObj.getMonth() + 1).padStart(2, "0");
      const d = String(dateObj.getDate()).padStart(2, "0");
      return `${y}-${m}-${d}`;
    },

    /**
     * Returns true if date is Saturday or Sunday
     */
    isWeekend(dateObj) {
      const day = dateObj.getDay(); // 0=Sun, 6=Sat
      return day === 0 || day === 6;
    },

    /**
     * Parse YYYY-MM-DD safely (local)
     */
    parseYMD(value) {
      if (!value || typeof value !== "string") return null;
      const parts = value.split("-");
      if (parts.length !== 3) return null;

      const y = parseInt(parts[0], 10);
      const m = parseInt(parts[1], 10);
      const d = parseInt(parts[2], 10);

      if (!y || !m || !d) return null;

      const dt = new Date(y, m - 1, d);
      if (isNaN(dt.getTime())) return null;

      // Validate it didn't overflow (ex: 2026-02-99)
      if (dt.getFullYear() !== y || dt.getMonth() !== m - 1 || dt.getDate() !== d) {
        return null;
      }

      return dt;
    },

    /**
     * Returns today's date in local timezone (midnight)
     */
    todayLocal() {
      const now = new Date();
      return new Date(now.getFullYear(), now.getMonth(), now.getDate());
    },

    /**
     * Apply rule set to one input element
     */
    applyToInput(input, options = {}) {
      if (!input || !(input instanceof HTMLElement)) return;

      const defaults = {
        allowToday: false,     // false = future only
        disableWeekends: true, // Sat/Sun disabled
        autoClearInvalid: true,
        addHint: true,
      };

      const config = Object.assign({}, defaults, options);

      // Only apply once per element
      if (input.dataset.appDateRulesApplied === "1") return;
      input.dataset.appDateRulesApplied = "1";

      // Ensure it behaves like a date input even if it's text
      // (We don't force type change to avoid breaking existing plugins)
      input.setAttribute("autocomplete", "off");
      input.setAttribute("inputmode", "numeric");
      input.setAttribute("placeholder", input.getAttribute("placeholder") || "YYYY-MM-DD");

      // Set minimum date (HTML5 date inputs respect this)
      const minDateObj = this.todayLocal();
      if (!config.allowToday) {
        minDateObj.setDate(minDateObj.getDate() + 1);
      }
      input.setAttribute("min", this.toYMD(minDateObj));

      // Optional hint (non-intrusive)
      if (config.addHint && !input.dataset.appDateHintAdded) {
        input.dataset.appDateHintAdded = "1";
        input.title = input.title || "Only future weekdays allowed (Mon–Fri).";
      }

      const validate = () => {
        const val = (input.value || "").trim();
        if (!val) return;

        const dt = this.parseYMD(val);
        if (!dt) {
          if (config.autoClearInvalid) input.value = "";
          return;
        }

        const today = this.todayLocal();
        const minAllowed = new Date(today);
        if (!config.allowToday) minAllowed.setDate(minAllowed.getDate() + 1);

        // Block past/today (if future only)
        if (dt < minAllowed) {
          if (config.autoClearInvalid) input.value = "";
          return;
        }

        // Block weekends
        if (config.disableWeekends && this.isWeekend(dt)) {
          if (config.autoClearInvalid) input.value = "";
          return;
        }
      };

      // Validate on change + blur (safe)
      input.addEventListener("change", validate);
      input.addEventListener("blur", validate);

      // If value already exists, validate immediately
      validate();
    },

    /**
     * Apply to multiple fields by selector
     */
    apply(selector = ".future-date", options = {}) {
      const inputs = document.querySelectorAll(selector);
      inputs.forEach((el) => this.applyToInput(el, options));
    },

    /**
     * Auto-bind for dynamically added inputs (advanced)
     * Works for modals, ajax content, etc.
     */
    watch(selector = ".future-date", options = {}) {
      // Apply once now
      this.apply(selector, options);

      // MutationObserver for dynamic DOM
      const observer = new MutationObserver(() => {
        this.apply(selector, options);
      });

      observer.observe(document.body, { childList: true, subtree: true });

      return observer;
    }
  };

  // Expose globally under a safe namespace
  window.AppDateRules = AppDateRules;

})(window, document);

document.addEventListener("DOMContentLoaded", function () {
  if (window.AppDateRules) {
    AppDateRules.watch(".future-date");
  }
});


/**
 * NEXUS CRM — main.js
 * Modular JavaScript for the CRM UI Kit
 */

/* ── DOM Ready ──────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  Sidebar.init();
  Topbar.init();
  Dropdowns.init();
  Notifications.init();
  ProfileDropdown.init();
  Tabs.init();
  Counters.init();
  FakeCharts.init();
  MobileOverlay.init();
  TableSelect.init();
});

/* ── Sidebar Module ─────────────────────────────────────────── */
const Sidebar = {
  init() {
    const sidebar = document.querySelector('.app-sidebar');
    const main    = document.querySelector('.app-main');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');

    if (!sidebar) return;

    // Restore state
    const collapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (collapsed) {
      sidebar.classList.add('collapsed');
      if (main) main.classList.add('sidebar-collapsed');
    }

    // Toggle buttons (desktop)
    toggles.forEach(btn => {
      btn.addEventListener('click', () => {
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
          // On mobile, use mobile-open
          sidebar.classList.toggle('mobile-open');
          MobileOverlay.toggle(sidebar.classList.contains('mobile-open'));
        } else {
          sidebar.classList.toggle('collapsed');
          if (main) main.classList.toggle('sidebar-collapsed');
          localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        }
      });
    });

    // Nested submenu toggles
    const navLinks = sidebar.querySelectorAll('.sidebar-link[data-submenu]');
    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const submenuId = link.dataset.submenu;
        const submenu   = document.getElementById(submenuId);
        if (!submenu) return;

        const isOpen = submenu.classList.contains('open');
        // Close all submenus
        sidebar.querySelectorAll('.sidebar-submenu.open').forEach(el => el.classList.remove('open'));
        sidebar.querySelectorAll('.sidebar-link.open').forEach(el => el.classList.remove('open'));

        if (!isOpen) {
          submenu.classList.add('open');
          link.classList.add('open');
        }
      });
    });

    // Mark active based on current page
    this.setActive();
  },

  setActive() {
    const current = window.location.pathname.split('/').pop() || 'dashboard.html';
    document.querySelectorAll('.sidebar-link[href], .sidebar-sublink[href]').forEach(link => {
      const href = link.getAttribute('href');
      if (href && (href === current || href.includes(current.replace('.html', '')))) {
        link.classList.add('active');
        // Open parent submenu if active link is inside one
        const parentSubmenu = link.closest('.sidebar-submenu');
        if (parentSubmenu) {
          parentSubmenu.classList.add('open');
          const parentLink = document.querySelector(`[data-submenu="${parentSubmenu.id}"]`);
          if (parentLink) parentLink.classList.add('open');
        }
      }
    });
  }
};

/* ── Mobile Overlay ─────────────────────────────────────────── */
const MobileOverlay = {
  overlay: null,

  init() {
    this.overlay = document.querySelector('.mobile-overlay');
    if (!this.overlay) return;

    this.overlay.addEventListener('click', () => {
      const sidebar = document.querySelector('.app-sidebar');
      if (sidebar) sidebar.classList.remove('mobile-open');
      this.toggle(false);
    });
  },

  toggle(show) {
    if (!this.overlay) return;
    if (show) {
      this.overlay.style.display = 'block';
      requestAnimationFrame(() => this.overlay.classList.add('active'));
    } else {
      this.overlay.classList.remove('active');
      setTimeout(() => { this.overlay.style.display = 'none'; }, 200);
    }
  }
};

/* ── Topbar Module ──────────────────────────────────────────── */
const Topbar = {
  init() {
    this.initSearch();
  },

  initSearch() {
    const input = document.querySelector('.topbar-search-input');
    if (!input) return;

    // Keyboard shortcut: Cmd/Ctrl + K
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        input.focus();
        input.select();
      }
      if (e.key === 'Escape' && document.activeElement === input) {
        input.blur();
      }
    });

    // Clear on Escape
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') input.value = '';
    });
  }
};

/* ── Notifications Module ───────────────────────────────────── */
const Notifications = {
  init() {
    const trigger = document.querySelector('[data-notif-trigger]');
    const panel   = document.querySelector('.notif-panel');
    if (!trigger || !panel) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = panel.classList.contains('open');
      this.closeAll();
      if (!isOpen) panel.classList.add('open');
    });

    document.addEventListener('click', () => this.closeAll());
    panel.addEventListener('click', (e) => e.stopPropagation());

    // Mark all read
    const markAll = panel.querySelector('[data-mark-all]');
    if (markAll) {
      markAll.addEventListener('click', () => {
        panel.querySelectorAll('.notif-item.unread').forEach(item => item.classList.remove('unread'));
        const dot = document.querySelector('[data-notif-trigger] .topbar-dot');
        if (dot) dot.style.display = 'none';
        const badge = document.querySelector('.notif-count-badge');
        if (badge) badge.textContent = '0';
      });
    }
  },

  closeAll() {
    document.querySelectorAll('.notif-panel').forEach(p => p.classList.remove('open'));
  }
};

/* ── Profile Dropdown Module ────────────────────────────────── */
const ProfileDropdown = {
  init() {
    const trigger  = document.querySelector('.profile-trigger');
    const dropdown = document.querySelector('.profile-dropdown');
    if (!trigger || !dropdown) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = dropdown.classList.contains('open');
      this.closeAll();
      Notifications.closeAll();
      if (!isOpen) {
        dropdown.classList.add('open');
        trigger.classList.add('open');
      }
    });

    document.addEventListener('click', () => this.closeAll());
    dropdown.addEventListener('click', (e) => e.stopPropagation());
  },

  closeAll() {
    document.querySelectorAll('.profile-dropdown').forEach(d => d.classList.remove('open'));
    document.querySelectorAll('.profile-trigger').forEach(t => t.classList.remove('open'));
  }
};

/* ── Generic Dropdown Module ────────────────────────────────── */
const Dropdowns = {
  init() {
    // Any element with [data-dropdown-trigger] and [data-dropdown-menu]
    document.querySelectorAll('[data-dropdown-trigger]').forEach(trigger => {
      const menuId = trigger.dataset.dropdownTrigger;
      const menu   = document.getElementById(menuId);
      if (!menu) return;

      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = menu.classList.contains('open');
        this.closeAll();
        if (!isOpen) menu.classList.add('open');
      });
    });

    document.addEventListener('click', () => this.closeAll());

    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.addEventListener('click', (e) => e.stopPropagation());
    });
  },

  closeAll() {
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
  }
};

/* ── Tabs Module ────────────────────────────────────────────── */
const Tabs = {
  init() {
    document.querySelectorAll('.tabs').forEach(tabGroup => {
      const items = tabGroup.querySelectorAll('.tab-item');
      items.forEach(item => {
        item.addEventListener('click', () => {
          items.forEach(i => i.classList.remove('active'));
          item.classList.add('active');

          // Tab panel switching
          const targetId = item.dataset.tab;
          if (targetId) {
            const panels = document.querySelectorAll('[data-tab-panel]');
            panels.forEach(panel => {
              panel.style.display = panel.dataset.tabPanel === targetId ? '' : 'none';
            });
          }
        });
      });
    });
  }
};

/* ── Animated Counters ──────────────────────────────────────── */
const Counters = {
  init() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    counters.forEach(el => observer.observe(el));
  },

  animateCounter(el) {
    const target   = parseFloat(el.dataset.counter.replace(/[^0-9.]/g, ''));
    const prefix   = el.dataset.counterPrefix  || '';
    const suffix   = el.dataset.counterSuffix  || '';
    const decimals = el.dataset.counterDecimals ? parseInt(el.dataset.counterDecimals) : 0;
    const duration = 1200;
    const startTime = performance.now();

    const update = (currentTime) => {
      const elapsed  = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const eased    = 1 - Math.pow(1 - progress, 3);
      const current  = eased * target;
      el.textContent = prefix + this.formatNumber(current, decimals) + suffix;
      if (progress < 1) requestAnimationFrame(update);
    };

    requestAnimationFrame(update);
  },

  formatNumber(num, decimals) {
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(1) + 'M';
    if (num >= 1_000)     return (num / 1_000).toFixed(1)     + 'K';
    return num.toFixed(decimals);
  }
};

/* ── Fake Charts / Bars ─────────────────────────────────────── */
const FakeCharts = {
  init() {
    this.renderBars();
    this.renderSparklines();
    this.renderMiniDonut();
  },

  renderBars() {
    document.querySelectorAll('.fake-chart').forEach(chart => {
      const bars = chart.querySelectorAll('.fake-bar');
      const heights = this.generateHeights(bars.length);
      bars.forEach((bar, i) => {
        bar.style.height = heights[i] + '%';
      });
    });
  },

  renderSparklines() {
    document.querySelectorAll('.fake-sparkline').forEach(sparkline => {
      const spans = sparkline.querySelectorAll('span');
      const heights = this.generateHeights(spans.length);
      spans.forEach((span, i) => {
        span.style.height = heights[i] + '%';
        span.style.minHeight = '4px';
      });
    });
  },

  renderMiniDonut() {
    document.querySelectorAll('[data-donut]').forEach(el => {
      const val = parseInt(el.dataset.donut) || 65;
      const color = el.dataset.donutColor || '#1a56db';
      const r = 20, circ = 2 * Math.PI * r;
      const dash = (val / 100) * circ;
      el.innerHTML = `
        <svg width="56" height="56" viewBox="0 0 56 56">
          <circle cx="28" cy="28" r="${r}" fill="none" stroke="#f1f3f7" stroke-width="6"/>
          <circle cx="28" cy="28" r="${r}" fill="none" stroke="${color}" stroke-width="6"
            stroke-dasharray="${dash} ${circ - dash}"
            stroke-dashoffset="${circ * 0.25}"
            stroke-linecap="round"/>
        </svg>`;
    });
  },

  generateHeights(count) {
    const base = [30, 55, 45, 70, 60, 85, 50, 75, 65, 90, 40, 80];
    return Array.from({ length: count }, (_, i) => base[i % base.length]);
  }
};

/* ── Table Row Selection ────────────────────────────────────── */
const TableSelect = {
  init() {
    document.querySelectorAll('[data-select-all]').forEach(selectAll => {
      const tableId = selectAll.dataset.selectAll;
      const rows    = document.querySelectorAll(`[data-select-row="${tableId}"]`);

      selectAll.addEventListener('change', () => {
        rows.forEach(row => {
          row.checked = selectAll.checked;
          const tr = row.closest('tr');
          if (tr) tr.style.background = row.checked ? 'var(--color-primary-light)' : '';
        });
        this.updateBulkActions(tableId);
      });

      rows.forEach(row => {
        row.addEventListener('change', () => {
          const all   = rows.length;
          const checked = [...rows].filter(r => r.checked).length;
          selectAll.indeterminate = checked > 0 && checked < all;
          selectAll.checked = checked === all;
          const tr = row.closest('tr');
          if (tr) tr.style.background = row.checked ? 'var(--color-primary-light)' : '';
          this.updateBulkActions(tableId);
        });
      });
    });
  },

  updateBulkActions(tableId) {
    const rows    = document.querySelectorAll(`[data-select-row="${tableId}"]`);
    const checked = [...rows].filter(r => r.checked).length;
    const bulkBar = document.querySelector(`[data-bulk-bar="${tableId}"]`);
    if (!bulkBar) return;
    bulkBar.style.display = checked > 0 ? 'flex' : 'none';
    const countEl = bulkBar.querySelector('[data-bulk-count]');
    if (countEl) countEl.textContent = `${checked} selected`;
  }
};

/* ── Tooltip (simple title-based) ──────────────────────────── */
const Tooltips = {
  init() {
    // Simple CSS-only approach via data-label on sidebar items
    // Extended tooltips handled via CSS ::after pseudo-elements
  }
};

/* ── Utility Helpers ────────────────────────────────────────── */
const Utils = {
  /**
   * Format currency
   * @param {number} amount
   * @param {string} [currency='USD']
   */
  formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount);
  },

  /**
   * Format date to readable string
   * @param {string|Date} date
   */
  formatDate(date) {
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date));
  },

  /**
   * Debounce utility
   * @param {Function} fn
   * @param {number} delay
   */
  debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  },

  /**
   * Simple client-side filter for table rows
   * @param {string} inputSelector
   * @param {string} tableSelector
   */
  initTableFilter(inputSelector, tableSelector) {
    const input = document.querySelector(inputSelector);
    const table = document.querySelector(tableSelector);
    if (!input || !table) return;

    const filter = this.debounce(() => {
      const q = input.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    }, 200);

    input.addEventListener('input', filter);
  }
};

// Expose to window for optional external usage
window.NexusCRM = { Sidebar, Topbar, Notifications, Dropdowns, Tabs, Counters, FakeCharts, TableSelect, Utils };

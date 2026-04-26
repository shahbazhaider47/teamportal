<?php
/**
 * layouts/includes/sidebar.php
 * ─────────────────────────────────────────────────────────────
 * Nexus CRM — Default sidebar partial for CodeIgniter master layout.
 * Used for general / non-module-specific pages.
 *
 * Active link detection uses the current URI segment so no extra
 * view data is required. Just load this view and it handles itself.
 *
 * To add a new menu item:
 *   1. Add a .sidebar-item block below.
 *   2. Set href to site_url('your/route').
 *   3. The JS Sidebar.setActive() will mark the correct link active
 *      based on window.location.
 */
$CI  =& get_instance();
$uri = trim($CI->uri->uri_string(), '/');

/**
 * Helper: returns ' active' if current URI starts with $segment.
 */
function nav_active(string $segment, string $uri): string {
    if ($segment === '' && $uri === '') return ' active';
    return ($segment !== '' && strpos($uri, $segment) === 0) ? ' active' : '';
}
?>

<aside class="app-sidebar" id="appSidebar">

  <!-- Logo -->
  <a href="<?= site_url('dashboard') ?>" class="sidebar-logo">
    <div class="sidebar-logo-mark">
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
        <path d="M3 14V4L9 12V4M9 12V14"
              stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M9 4L15 14"
              stroke="white" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    <span class="sidebar-logo-text"><?= defined('APP_NAME') ? html_escape(APP_NAME) : 'Nexus CRM' ?></span>
  </a>

  <nav class="sidebar-nav" role="navigation" aria-label="Main navigation">

    <!-- ── Overview ──────────────────────────────────────── -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Overview</span>

      <div class="sidebar-item">
        <a href="<?= site_url('dashboard') ?>"
           class="sidebar-link<?= nav_active('dashboard', $uri) ?>"
           data-label="Dashboard">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2"  y="2"  width="7" height="7" rx="1.5"/>
            <rect x="11" y="2"  width="7" height="7" rx="1.5"/>
            <rect x="2"  y="11" width="7" height="7" rx="1.5"/>
            <rect x="11" y="11" width="7" height="7" rx="1.5"/>
          </svg>
          <span class="sidebar-link-text">Dashboard</span>
        </a>
      </div>

      <div class="sidebar-item">
        <a href="<?= site_url('calendar') ?>"
           class="sidebar-link<?= nav_active('calendar', $uri) ?>"
           data-label="Calendar">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3.5" width="16" height="14" rx="2"/>
            <path d="M6 2v3M14 2v3M2 8h16"/>
          </svg>
          <span class="sidebar-link-text">Calendar</span>
        </a>
      </div>

    </div>

    <!-- ── CRM ───────────────────────────────────────────── -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">CRM</span>

      <div class="sidebar-item">
        <a href="<?= site_url('crm/leads') ?>"
           class="sidebar-link<?= nav_active('crm/leads', $uri) ?>"
           data-label="Leads">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="7" r="3.5"/>
            <path d="M2 17.5c0-3.038 2.686-5.5 6-5.5"/>
            <path d="M15 12v6M12 15h6"/>
          </svg>
          <span class="sidebar-link-text">Leads</span>
          <?php
            // Replace with a real count from your model
            // $lead_count = $CI->lead_model->count_open();
            $lead_count = 24;
          ?>
          <?php if ($lead_count > 0): ?>
            <span class="sidebar-badge"><?= $lead_count ?></span>
          <?php endif; ?>
        </a>
      </div>

      <div class="sidebar-item">
        <a href="<?= site_url('crm/customers') ?>"
           class="sidebar-link<?= nav_active('crm/customers', $uri) ?>"
           data-label="Customers">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="7.5" cy="7" r="3"/>
            <path d="M1 17c0-2.761 2.91-5 6.5-5"/>
            <circle cx="14" cy="7" r="3"/>
            <path d="M19 17c0-2.761-2.91-5-6.5-5"/>
          </svg>
          <span class="sidebar-link-text">Customers</span>
        </a>
      </div>

      <div class="sidebar-item">
        <a href="#"
           class="sidebar-link<?= nav_active('crm/deals', $uri) ?>"
           data-submenu="submenu-deals"
           data-label="Deals">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="16" height="11" rx="2"/>
            <path d="M7 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
            <path d="M2 12h16"/>
          </svg>
          <span class="sidebar-link-text">Deals</span>
          <svg class="sidebar-chevron" viewBox="0 0 16 16" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 4l4 4-4 4"/>
          </svg>
        </a>
        <div class="sidebar-submenu" id="submenu-deals">
          <a href="<?= site_url('crm/deals/pipeline') ?>"
             class="sidebar-sublink<?= nav_active('crm/deals/pipeline', $uri) ?>">Pipeline</a>
          <a href="<?= site_url('crm/deals') ?>"
             class="sidebar-sublink<?= nav_active('crm/deals', $uri) && !strpos($uri, '/') ? ' active' : '' ?>">All Deals</a>
          <a href="<?= site_url('crm/deals/won') ?>"
             class="sidebar-sublink<?= nav_active('crm/deals/won', $uri) ?>">Won Deals</a>
          <a href="<?= site_url('crm/deals/lost') ?>"
             class="sidebar-sublink<?= nav_active('crm/deals/lost', $uri) ?>">Lost Deals</a>
        </div>
      </div>

      <div class="sidebar-item">
        <a href="<?= site_url('tasks') ?>"
           class="sidebar-link<?= nav_active('tasks', $uri) ?>"
           data-label="Tasks">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 11l2.5 2.5L16 7"/>
            <rect x="2" y="3" width="16" height="14" rx="2"/>
          </svg>
          <span class="sidebar-link-text">Tasks</span>
          <?php
            // Replace: $task_count = $CI->task_model->count_pending();
            $task_count = 7;
          ?>
          <?php if ($task_count > 0): ?>
            <span class="sidebar-badge"><?= $task_count ?></span>
          <?php endif; ?>
        </a>
      </div>

    </div>

    <!-- ── Sales ─────────────────────────────────────────── -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Sales</span>

      <div class="sidebar-item">
        <a href="#"
           class="sidebar-link<?= nav_active('sales', $uri) ?>"
           data-submenu="submenu-sales"
           data-label="Sales">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 13l4.5-4.5L11 12l5.5-5.5"/>
            <path d="M13 6.5h3.5V10"/>
          </svg>
          <span class="sidebar-link-text">Sales</span>
          <svg class="sidebar-chevron" viewBox="0 0 16 16" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 4l4 4-4 4"/>
          </svg>
        </a>
        <div class="sidebar-submenu" id="submenu-sales">
          <a href="<?= site_url('sales') ?>"
             class="sidebar-sublink<?= nav_active('sales', $uri) ?>">Overview</a>
          <a href="<?= site_url('sales/quotations') ?>"
             class="sidebar-sublink<?= nav_active('sales/quotations', $uri) ?>">Quotations</a>
          <a href="<?= site_url('sales/orders') ?>"
             class="sidebar-sublink<?= nav_active('sales/orders', $uri) ?>">Orders</a>
          <a href="<?= site_url('sales/forecasting') ?>"
             class="sidebar-sublink<?= nav_active('sales/forecasting', $uri) ?>">Forecasting</a>
        </div>
      </div>

      <div class="sidebar-item">
        <a href="<?= site_url('finance') ?>"
           class="sidebar-link<?= nav_active('finance', $uri) ?>"
           data-label="Finance">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="10" cy="10" r="8"/>
            <path d="M10 5.5v9M7.5 7.5c0-1.105.895-2 2-2h1a2 2 0 0 1 0 4h-1a2 2 0 0 0 0 4h1.5a2 2 0 0 0 2-2"/>
          </svg>
          <span class="sidebar-link-text">Finance</span>
        </a>
      </div>

      <div class="sidebar-item">
        <a href="<?= site_url('expenses') ?>"
           class="sidebar-link<?= nav_active('expenses', $uri) ?>"
           data-label="Expenses">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="16" height="12" rx="2"/>
            <path d="M2 9h16"/>
            <path d="M6 14h2M11 14h3"/>
          </svg>
          <span class="sidebar-link-text">Expenses</span>
        </a>
      </div>

    </div>

    <!-- ── Insights ───────────────────────────────────────── -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Insights</span>

      <div class="sidebar-item">
        <a href="#"
           class="sidebar-link<?= nav_active('reports', $uri) ?>"
           data-submenu="submenu-reports"
           data-label="Reports">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 14V8M8 14V5M13 14V9M18 14V3"/>
            <path d="M2 17h16"/>
          </svg>
          <span class="sidebar-link-text">Reports</span>
          <svg class="sidebar-chevron" viewBox="0 0 16 16" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 4l4 4-4 4"/>
          </svg>
        </a>
        <div class="sidebar-submenu" id="submenu-reports">
          <a href="<?= site_url('reports/sales') ?>"
             class="sidebar-sublink<?= nav_active('reports/sales', $uri) ?>">Sales Reports</a>
          <a href="<?= site_url('reports/leads') ?>"
             class="sidebar-sublink<?= nav_active('reports/leads', $uri) ?>">Lead Reports</a>
          <a href="<?= site_url('reports/revenue') ?>"
             class="sidebar-sublink<?= nav_active('reports/revenue', $uri) ?>">Revenue Analysis</a>
          <a href="<?= site_url('reports/custom') ?>"
             class="sidebar-sublink<?= nav_active('reports/custom', $uri) ?>">Custom Reports</a>
        </div>
      </div>

    </div>

    <!-- ── Admin ──────────────────────────────────────────── -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Admin</span>

      <div class="sidebar-item">
        <a href="<?= site_url('settings') ?>"
           class="sidebar-link<?= nav_active('settings', $uri) ?>"
           data-label="Settings">
          <svg class="sidebar-icon" viewBox="0 0 20 20" fill="none"
               stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="10" cy="10" r="2.5"/>
            <path d="M10 1.5v2M10 16.5v2M1.5 10h2M16.5 10h2
                     M4.1 4.1l1.4 1.4M14.5 14.5l1.4 1.4
                     M4.1 15.9l1.4-1.4M14.5 5.5l1.4-1.4"/>
          </svg>
          <span class="sidebar-link-text">Settings</span>
        </a>
      </div>

    </div>

  </nav><!-- /sidebar-nav -->

  <!-- Collapse toggle -->
  <div class="sidebar-footer">
    <button class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Toggle sidebar">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M13 4l-6 6 6 6"/>
        <path d="M18 10H7"/>
      </svg>
      <span class="sidebar-toggle-label">Collapse</span>
    </button>
  </div>

</aside><!-- /app-sidebar -->
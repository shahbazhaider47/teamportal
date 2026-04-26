<?php
/**
 * layouts/includes/topbar.php
 * ─────────────────────────────────────────────────────────────
 * Nexus CRM — Topbar partial for CodeIgniter master layout.
 * Loaded inside <header class="app-topbar"> in master.php.
 *
 * Available CI view data (passed from controller or base_controller):
 *   $logged_user_name    string  Display name of the current user
 *   $logged_user_email   string  Email address of the current user
 *   $logged_user_role    string  Role label  (e.g. "Sales Manager")
 *   $logged_user_avatar  string  URL to avatar image (optional)
 *   $notifications       array   Recent notification objects
 *   $unread_notif_count  int     Number of unread notifications
 */
$CI =& get_instance();

// Safe defaults — replace with your own session/model helpers
$user_name   = isset($logged_user_name)   ? html_escape($logged_user_name)   : 'User';
$user_email  = isset($logged_user_email)  ? html_escape($logged_user_email)  : '';
$user_role   = isset($logged_user_role)   ? html_escape($logged_user_role)   : '';
$user_avatar = isset($logged_user_avatar) ? html_escape($logged_user_avatar) : '';
$unread      = isset($unread_notif_count) ? (int) $unread_notif_count        : 0;
$notifs      = isset($notifications)      ? $notifications                   : [];

// Build initials fallback from name
$initials = '';
foreach (explode(' ', $user_name) as $word) {
    $initials .= strtoupper(mb_substr($word, 0, 1));
}
$initials = mb_substr($initials, 0, 2);
?>

<div class="topbar-inner">

  <!-- Left: sidebar hamburger toggle -->
  <div class="topbar-left">
    <button class="topbar-hamburger" data-sidebar-toggle aria-label="Toggle sidebar">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
        <path d="M3 5h14M3 10h14M3 15h14"/>
      </svg>
    </button>
  </div>

  <!-- Centre: global search -->
  <div class="topbar-search">
    <svg class="topbar-search-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="7" cy="7" r="4.5"/>
      <path d="M10.5 10.5L14 14"/>
    </svg>
    <input
      type="text"
      class="topbar-search-input"
      id="topbarGlobalSearch"
      placeholder="Search leads, customers, deals…"
      aria-label="Global search"
      autocomplete="off"
    />
    <div class="topbar-search-kbd">
      <span class="kbd">⌘</span>
      <span class="kbd">K</span>
    </div>
  </div>

  <!-- Right: actions cluster -->
  <div class="topbar-right">

    <!-- Quick-add button -->
    <a href="#" class="btn btn-primary btn-sm">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none"
           stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <path d="M8 2v12M2 8h12"/>
      </svg>
      New
    </a>

    <div class="topbar-divider"></div>

    <!-- Help -->
    <a href="#" class="topbar-action" aria-label="Help &amp; support">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"
           stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="10" cy="10" r="8"/>
        <path d="M7.5 7.5a2.5 2.5 0 0 1 5 0c0 2-2.5 2.5-2.5 4"/>
        <circle cx="10" cy="15" r="0.5" fill="currentColor"/>
      </svg>
    </a>

    <!-- Notifications trigger -->
    <button class="topbar-action" data-notif-trigger
            aria-label="Notifications (<?= $unread ?> unread)" aria-haspopup="true">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"
           stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 2a6 6 0 0 1 6 6v3l1.5 2.5H2.5L4 11V8a6 6 0 0 1 6-6z"/>
        <path d="M8 17a2 2 0 0 0 4 0"/>
      </svg>
      <?php if ($unread > 0): ?>
        <span class="topbar-dot"></span>
      <?php endif; ?>
    </button>

    <!-- Notifications panel -->
    <div class="notif-panel" role="dialog" aria-label="Notifications">

      <div class="notif-header">
        <div class="notif-title">Notifications</div>
        <div class="flex items-center gap-3">
          <?php if ($unread > 0): ?>
            <span class="notif-count-badge"><?= $unread ?></span>
          <?php endif; ?>
          <a href="#" class="notif-mark-all" data-mark-all>Mark all read</a>
        </div>
      </div>

      <div class="notif-list">
        <?php if (!empty($notifs)): ?>
          <?php foreach ($notifs as $notif): ?>
            <div class="notif-item <?= !empty($notif['is_read']) ? '' : 'unread' ?>">
              <div class="notif-icon-wrap <?= html_escape($notif['type'] ?? 'info') ?>">
                <!-- Icon injected per type by JS or use a static SVG here -->
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="8" cy="8" r="6.5"/>
                  <path d="M8 7v5M8 5v.5"/>
                </svg>
              </div>
              <div class="notif-body">
                <div class="notif-text"><?= $notif['message'] ?></div>
                <div class="notif-time"><?= html_escape($notif['time'] ?? '') ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding: 32px 24px; text-align:center; color:var(--color-text-muted); font-size:13px;">
            No new notifications
          </div>
        <?php endif; ?>
      </div>

      <div class="notif-footer">
        <a href="<?= site_url('notifications') ?>">View all notifications</a>
      </div>

    </div><!-- /notif-panel -->

    <!-- Settings shortcut -->
    <a href="<?= site_url('settings') ?>" class="topbar-action" aria-label="Settings">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"
           stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="10" cy="10" r="2.5"/>
        <path d="M10 1.5v2M10 16.5v2M1.5 10h2M16.5 10h2
                 M4.1 4.1l1.4 1.4M14.5 14.5l1.4 1.4
                 M4.1 15.9l1.4-1.4M14.5 5.5l1.4-1.4"/>
      </svg>
    </a>

    <div class="topbar-divider"></div>

    <!-- Profile dropdown -->
    <div class="topbar-profile">

      <button class="profile-trigger" aria-label="Account menu" aria-haspopup="true">
        <div class="profile-avatar">
          <?php if ($user_avatar): ?>
            <img src="<?= $user_avatar ?>" alt="<?= $user_name ?>">
          <?php else: ?>
            <?= $initials ?>
          <?php endif; ?>
        </div>
        <div class="profile-info">
          <span class="profile-name"><?= $user_name ?></span>
          <?php if ($user_role): ?>
            <span class="profile-role"><?= $user_role ?></span>
          <?php endif; ?>
        </div>
        <svg class="profile-chevron" viewBox="0 0 16 16" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 6l4 4 4-4"/>
        </svg>
      </button>

      <div class="profile-dropdown" role="menu">

        <div class="profile-dropdown-header">
          <div class="profile-dropdown-name"><?= $user_name ?></div>
          <?php if ($user_email): ?>
            <div class="profile-dropdown-email"><?= $user_email ?></div>
          <?php endif; ?>
        </div>

        <a href="<?= site_url('profile') ?>" class="profile-dropdown-item" role="menuitem">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="5.5" r="3"/>
            <path d="M2 14.5c0-3.038 2.686-5.5 6-5.5s6 2.462 6 5.5"/>
          </svg>
          My Profile
        </a>

        <a href="<?= site_url('settings/preferences') ?>" class="profile-dropdown-item" role="menuitem">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="8" r="2.5"/>
            <path d="M8 1v2M8 13v2M1 8h2M13 8h2
                     M3.05 3.05l1.41 1.41M11.54 11.54l1.41 1.41
                     M3.05 12.95l1.41-1.41M11.54 4.46l1.41-1.41"/>
          </svg>
          Preferences
        </a>

        <a href="<?= site_url('billing') ?>" class="profile-dropdown-item" role="menuitem">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="12" height="9" rx="1.5"/>
            <path d="M5 4V3a2 2 0 0 1 4 0v1"/>
          </svg>
          Billing
        </a>

        <div class="profile-dropdown-divider"></div>

        <a href="<?= site_url('logout') ?>" class="profile-dropdown-item danger" role="menuitem">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 2h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-3"/>
            <path d="M7 11l-4-3 4-3M3 8h8"/>
          </svg>
          Sign Out
        </a>

      </div><!-- /profile-dropdown -->

    </div><!-- /topbar-profile -->

  </div><!-- /topbar-right -->

</div><!-- /topbar-inner -->
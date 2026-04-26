<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<li class="header-apps">
  <div class="flex-shrink-0 app-dropdown">
    <a href="#" class="d-block head-icon" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Apps & Shortcuts">
      <i class="ti ti-apps"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end headerapps-dropdown bg-transparent border-0">
      <div class="app-shortcuts-popover shadow-sm">
        <div class="app-shortcuts-header">
          <h3 class="app-shortcuts-title">Apps &amp; Shortcuts</h3>
          <div class="app-search">
            <i class="ti ti-search"></i>
            <input type="text" id="appSearch" placeholder="Search apps..." aria-label="Search apps">
          </div>
        </div>
        <div class="app-grid" id="appShortcuts">

          <?php
          if (!function_exists('render_app_tile')) {
            function render_app_tile($href, $label, $icon = 'ti ti-apps', $pinned = false, $imgUrl = null, $badge = null, $attrs = [])
            {
              $attrs   = array_merge(['href' => $href, 'class' => 'app-tile'], (array)$attrs);
              $badgeHtml = $badge ? '<span class="app-badge">'.html_escape($badge).'</span>' : '';
              $iconHtml  = $imgUrl
                ? '<span class="app-icon"><img src="'.html_escape($imgUrl).'" alt="'.html_escape($label).'"></span>'
                : '<span class="app-icon"><i class="'.html_escape($icon).'"></i></span>';

              $attrStr = '';
              foreach ($attrs as $k => $v) {
                if ($v === null || $v === false) continue;
                if ($v === true) $v = $k;
                $attrStr .= ' '.html_escape($k).'="'.html_escape($v).'"';
              }

              return '
                <div class="app-cell" data-app-name="'.strtolower(html_escape($label)).'">
                  <a'.$attrStr.' title="'.html_escape($label).'">
                    '.$iconHtml.$badgeHtml.'
                    <div class="app-label">'.html_escape($label).'</div>
                  </a>
                </div>';
            }
          }

          echo render_app_tile(base_url('calendar'),              'Calendar',       'ti ti-calendar',          false, null, '');
          echo render_app_tile('#',                               'Notepad',        'ti ti-notebook',          true,  null, null, ['data-action' => 'open-notepad']);
          echo render_app_tile('#',                               'Add Todo',       'ti ti-checklist',         true,  null, null, ['data-action' => 'open-todo']);
          echo render_app_tile(base_url('login_vault'),           'Vault',          'ti ti-wallet',            false, null, 'New');

          if (staff_can('view_global', 'announcements') || staff_can('view_own', 'announcements')) {
            echo render_app_tile(base_url('announcements'),       'Updates',        'ti ti-speakerphone',      false, null, 'New');
          }

          $extraShortcuts = hooks()->apply_filters('app_shortcut_icons_raw', []);
          if (is_array($extraShortcuts)) {
            foreach ($extraShortcuts as $shortcutHtml) {
              echo '<div class="app-cell">'.$shortcutHtml.'</div>';
            }
          }

          echo render_app_tile(base_url('requests/new'),          'HR Desk',        'ti ti-layout-dashboard',  false, null, 'New');
          echo render_app_tile('#',                               'Ask AI',   'ti ti-robot',             false, null, 'New', ['data-action' => 'open-ai-chat']);

          echo render_app_tile('#',                               'Report Bug',     'ti ti-bug',               true,  null, null, ['data-action' => 'open-report-bug']);
          echo render_app_tile(base_url('policies/company_policy'), 'Company Policy','ti ti-file-certificate', false, null, '');
          ?>

        </div>
        <div class="app-shortcuts-footer">
          <a href="<?= base_url('apps') ?>" class="view-all-apps text-primary">
            <span>View all apps</span>
            <i class="ti ti-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</li>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('appSearch');
  const appCells    = document.querySelectorAll('.app-cell');

  if (searchInput) {
    searchInput.addEventListener('input', function (e) {
      const q = e.target.value.toLowerCase().trim();
      appCells.forEach(function (cell) {
        const name = cell.getAttribute('data-app-name') ||
                     cell.querySelector('.app-label')?.textContent.toLowerCase() || '';
        cell.classList.toggle('hidden', q !== '' && !name.includes(q));
      });
    });
  }
});
</script>

<script>
(function () {
  function openModalById(id) {
    var el = document.getElementById(id);
    if (!el) { console.warn('Modal #' + id + ' not found'); return; }
    if (window.bootstrap && bootstrap.Modal) {
      bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
      el.classList.add('show');
      el.style.display = 'block';
      el.removeAttribute('aria-hidden');
    }
  }

  function closeAppsDropdown(fromEl) {
    var menu = fromEl.closest('.dropdown-menu');
    if (!menu) return;
    try {
      var parent = menu.closest('.app-dropdown');
      var toggle = parent ? parent.querySelector('[data-bs-toggle="dropdown"]') : null;
      if (window.bootstrap && bootstrap.Dropdown && toggle) {
        bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
      } else {
        menu.classList.remove('show');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
      }
    } catch (e) { /* no-op */ }
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a.app-tile');
    if (!a) return;

    var action = a.getAttribute('data-action');
    if (!action) return;

    e.preventDefault();
    closeAppsDropdown(a);

    switch (action) {
      case 'open-notepad':
        window.openQuickNoteModal
          ? window.openQuickNoteModal()
          : openModalById('quickNoteModal');
        break;

      case 'open-report-bug':
        openModalById('reportBugModal');
        break;

      case 'open-todo':
        openModalById('todoModal');
        break;

      case 'open-ai-chat':
        // Delegate to the widget — defined in apps/ai/widget.php
        if (window.RcmAiChat) {
          window.RcmAiChat.open();
        }
        break;
    }
  }, { passive: false });
})();
</script>
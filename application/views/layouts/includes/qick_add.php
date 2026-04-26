<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<li class="header-apps">
  <div class="flex-shrink-0 app-dropdown">
    <a href="#"
       class="d-block head-icon"
       data-bs-toggle="dropdown"
       data-bs-auto-close="true"
       aria-expanded="false"
       title="Quick Add">
      <i class="ti ti-plus"></i>
    </a>

    <div class="dropdown-menu dropdown-menu-end headerapps-dropdown bg-transparent border-0">
      <div class="quickadd-popover shadow-sm">

        <div class="quickadd-header">
          <h3 class="quickadd-title">Quick Add</h3>
        </div>

        <div class="quickadd-list">

          <?php
          if (!function_exists('render_quick_add_item')) {
            /**
             * @param string $label
             * @param string $icon
             * @param array  $options [
             *   'url' => string|null,
             *   'modal_id' => string|null
             * ]
             */
            function render_quick_add_item($label, $icon, array $options = [])
            {
              $isModal = !empty($options['modal_id']);
              $href   = $isModal ? '#' : ($options['url'] ?? '#');

              $attrs = [
                'href'  => $href,
                'class' => 'quickadd-item',
              ];

              if ($isModal) {
                $attrs['data-qa-modal'] = ltrim($options['modal_id'], '#');
              }

              $attrStr = '';
              foreach ($attrs as $k => $v) {
                $attrStr .= ' '.html_escape($k).'="'.html_escape($v).'"';
              }

              return '
                <a'.$attrStr.'>
                  <span class="quickadd-icon"><i class="'.html_escape($icon).'"></i></span>
                  <span class="quickadd-label">'.html_escape($label).'</span>
                  <i class="ti ti-chevron-right quickadd-arrow"></i>
                </a>';
            }
          }

          // URL-based actions
          echo render_quick_add_item('Add Task',      'ti ti-checklist',      ['url' => site_url('tasks/create')]);
          echo render_quick_add_item('New Project',   'ti ti-briefcase',      ['url' => site_url('projects/create')]);
          echo render_quick_add_item('Apply Leave',   'ti ti-calendar-event', ['url' => site_url('attendance/apply_leave')]);
          echo render_quick_add_item('New Ticket',    'ti ti-headset',        ['url' => site_url('support/create')]);

          // Modal-based actions (IDs must already exist in DOM)
          echo render_quick_add_item('Quick Note',    'ti ti-notes',      ['modal_id' => 'quickNoteModal']);
          echo render_quick_add_item('Add Todo',      'ti ti-checklist',  ['modal_id' => 'todoModal']);

          if (staff_can('create','users')) {
            echo render_quick_add_item('Add Employee','ti ti-user-plus',  ['modal_id' => 'addUserModal']);
          }
          ?>

        </div>

      </div>
    </div>
  </div>
</li>

<style>
.quickadd-popover {
  background: #fff;
  border-radius: 10px;
  min-width: 220px;          /* compact but usable */
  max-width: 240px;          /* hard cap */
  border: 1px solid #e8eaef;
  overflow: hidden;          /* no scrollbars */
  
  /* spacing from header + edges */
  margin-top: 55px;
}

/* Header */
.quickadd-header {
  padding: 8px 12px;
  border-bottom: 1px solid #f0f2f7;
}

.quickadd-title {
  font-size: 13px;
  font-weight: 600;
  margin: 0;
}

/* List */
.quickadd-list {
  display: flex;
  flex-direction: column;
}

/* Item row */
.quickadd-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  text-decoration: none;
  color: #2b2f36;
  transition: background 0.15s ease;
}

.quickadd-item:hover {
  background: rgba(59, 130, 246, 0.06);
}

/* Icon */
.quickadd-icon {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.quickadd-icon i {
  font-size: 14px;
}

/* Label */
.quickadd-label {
  flex: 1;
  font-size: 12.5px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Arrow */
.quickadd-arrow {
  font-size: 13px;
  color: #9ca3af;
  flex-shrink: 0;
}
  
</style>
<script>
(function () {
  document.addEventListener('click', function (e) {
    const item = e.target.closest('.quickadd-item');
    if (!item) return;

    const modalId = item.getAttribute('data-qa-modal');
    if (!modalId) return;

    e.preventDefault();

    // Close dropdown safely
    const dropdownToggle = item.closest('.app-dropdown')
      ?.querySelector('[data-bs-toggle="dropdown"]');

    if (dropdownToggle && window.bootstrap?.Dropdown) {
      bootstrap.Dropdown.getOrCreateInstance(dropdownToggle).hide();
    }

    // Open modal by ID
    const modalEl = document.getElementById(modalId);
    if (!modalEl) {
      console.warn('Quick Add modal not found:', modalId);
      return;
    }

    if (window.bootstrap?.Modal) {
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else {
      modalEl.classList.add('show');
      modalEl.style.display = 'block';
      modalEl.removeAttribute('aria-hidden');
    }
  });
})();
</script>

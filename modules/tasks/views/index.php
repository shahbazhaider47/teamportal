<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    .project-meta-chip {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px 2px 10px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        font-size: 10px;
        margin: 7px 4px 4px 0;
    }

.task-title{
    font-size: 14px;
    font-weight: 400;    
} 

.task-title:hover{
    font-weight: bold;
} 

</style>


<?php
// From controller
$rows       = $rows       ?? [];
$total      = (int)($total ?? 0);
$limit      = (int)($limit ?? 50);
$offset     = (int)($offset ?? 0);
$has_prev   = !empty($has_prev);
$has_next   = !empty($has_next);
$view       = $view ?? 'list';

// Helpers for avatars (kept local for convenience)
if (!function_exists('__avatar_html')) {
  function __avatar_html($name, $url = null, $size = 32) {
    $title = html_escape($name ?: '—');
    if (!empty($url)) {
      return '<img src="'.html_escape($url).'" class="rounded-circle border" width="'.$size.'" height="'.$size.'" alt="'.$title.'" title="'.$title.'">';
    }
    // initials fallback
    $parts = preg_split('/\s+/', trim((string)$name));
    $ini   = '';
    if ($parts && is_array($parts)) {
        $ini = strtoupper(substr($parts[0] ?? '',0,1) . substr($parts[1] ?? '',0,1));
    }
    if ($ini === '') $ini = 'U';
    $fs = max(10, (int)floor($size/2.4));
    return '<div class="rounded-circle d-inline-flex align-items-center justify-content-center border bg-light text-muted" '.
           'style="width:'.$size.'px;height:'.$size.'px;font-size:'.$fs.'px;" title="'.$title.'">'.$ini.'</div>';
  }
}
if (!function_exists('__avatar_stack')) {
  function __avatar_stack(array $cards, $maxShow = 5, $size = 26) {
    if (!$cards) return '<span class="text-muted">—</span>';
    $show = array_slice($cards, 0, $maxShow);
    $more = max(0, count($cards) - count($show));
    $html = '<div class="d-inline-flex align-items-center">';
    foreach ($show as $i => $u) {
      $ml = $i === 0 ? 0 : - (int)floor($size * 0.30);
      $name = $u['name'] ?? ('User#'.((int)($u['id'] ?? 0)));
      $url  = $u['avatar'] ?? null;
      $html .= '<span class="position-relative" style="margin-left:'.$ml.'px;">'.__avatar_html($name, $url, $size).'</span>';
    }
    if ($more > 0) {
      $ml = - (int)floor($size * 0.30);
      $fs = max(10, (int)floor($size/2.6));
      $html .= '<span class="position-relative" style="margin-left:'.$ml.'px;">'.
               '<div class="rounded-circle d-inline-flex align-items-center justify-content-center border bg-light text-muted" '.
               'style="width:'.$size.'px;height:'.$size.'px;font-size:'.$fs.'px;" title="'.html_escape($more.' more').'">+'.(int)$more.'</div>'.
               '</span>';
    }
    $html .= '</div>';
    return $html;
  }
}
?>
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Tasks') ?> List</h1>

    </div>

        <?php
          $canCreate  = staff_can('create', 'tasks');
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
        
    <div class="d-flex align-items-center gap-2 flex-wrap">

        <button type="button"
                id="btn-add-user"
                class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#taskCreateModal" onclick=\"clearUserForm()\"' : 'disabled' ?>
                title="Add New Task">
          <i class="fas fa-plus me-1"></i> Add Task
        </button>
        
      <div class="btn-divider"></div>

      <div class="btn-group" role="group" aria-label="Views">
        <a class="btn btn-primary btn-header" href="<?= site_url('tasks?view=list') ?>">
          <i class="ti ti-list-details me-1"></i> List
        </a>
        <a class="btn btn-outline-primary btn-header" href="<?= site_url('tasks?view=kanban') ?>">
          <i class="ti ti-layout-kanban me-1"></i> Kanban
        </a>
        <a class="btn btn-outline-primary btn-header" href="<?= site_url('tasks?view=gantt') ?>">
          <i class="ti ti-chart-bar me-1"></i> Gantt
        </a>
      </div>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'tasksTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
    
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
        
    </div>
  </div>

<?php
$CI = &get_instance();

// status => [Label, badge class]
$statusMap = [
  'not_started' => ['Not Started',  'secondary'],
  'in_progress' => ['In Progress',  'primary'],
  'in_review'   => ['In Review',    'info'],  
  'on_hold'     => ['On Hold',      'warning'],
  'completed'   => ['Completed',    'success'],
  'cancelled'   => ['Cancelled',      'danger'],
];


// status => [Label, badge class]
$priorityMap = [
  'low'     => ['Low',      'low'],
  'normal'  => ['Normal',   'normal'],
  'high'    => ['High',     'high'],  
  'urgent'  => ['Urgent',   'urgent'],
  'no'      => ['N/A',      'no'],
];

// order to cycle through when clicking
$statusOrder = array_keys($statusMap);
?>



  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bottom-border table-sm align-middle" id="tasksTable">
          <thead class="bg-light-primary">
            <tr class="small text-muted">
              <th style="width:300px"><i class="ti ti-check me-2"></i>Task Title</th>
              <th><i class="ti ti-user me-2"></i>Assignee</th>
              <th><i class="ti ti-calendar me-2"></i>Start Date</th>
              <th><i class="ti ti-clock me-2"></i>Due Date</th>
              <th><i class="ti ti-flag-3 me-2"></i>Priority</th>
              <th><i class="ti ti-route me-2"></i>Status</th>              
              <th><i class="ti ti-brand-google-analytics me-2"></i>Task Meta</th>
              <th><i class="ti ti-brand-google-analytics me-2"></i>Progress</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php if (empty($rows)): ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">No tasks found.</td></tr>
            <?php else: ?>
              <?php
                // Permissions (server-side; keeps JS light)
                $canEdit   = function_exists('staff_can') ? staff_can('edit','tasks')   : false;
                $canDelete = function_exists('staff_can') ? staff_can('delete','tasks') : false;
              ?>
              <?php foreach ($rows as $r): ?>
                <?php
                  $id      = (int)($r['id'] ?? 0);
                  $title   = trim((string)($r['name'] ?? $r['title'] ?? 'Untitled Task'));
                  $desc    = trim(strip_tags((string)($r['description'] ?? '')));
                  $added     = $r['dateadded'] ?? $r['date_added'] ?? '—';
                  $start   = $r['startdate'] ?? $r['start_date'] ?? '—';
                  $due     = $r['duedate'] ?? $r['due_date'] ?? '—';
        
                    // Priority
                    $p = strtolower((string)($r['priority'] ?? 'normal'));
                    if (!isset($priorityMap[$p])) {
                        $p = 'normal';
                    }
                    list($priorityLabel, $priorityClass) = $priorityMap[$p];
                    
                    // Status
                    $s = strtolower((string)($r['status'] ?? 'not_started'));
                    if (!isset($statusMap[$s])) {
                        $s = 'not_started';
                    }
                    list($statusLabel, $statusClass) = $statusMap[$s];

                  $assigneeName   = $r['assignee_name']   ?? (($r['assignee_id'] ?? null) ? ('User#'.$r['assignee_id']) : '—');
                  $assigneeAvatar = $r['assignee_avatar'] ?? null;
                  $followersCards = $r['followers_cards'] ?? [];
                ?>
                <tr class="task-row">
                    
                    <td class="task-title">
                        <a href="<?= site_url('tasks/view/'.$id) ?>" class="text-primary">
                          <?= html_escape(mb_substr($title, 0, 40)) ?><?= mb_strlen($title) > 40 ? '…' : '' ?>
                        </a>
                    </td>
                  
                  <td>
                    <div class="d-flex align-items-center">
                      <?= __avatar_html($assigneeName, $assigneeAvatar, 32) ?>
                      <span class="ms-2"><?= html_escape($assigneeName) ?></span>
                    </div>
                  </td>
                  
                 <!-- <td class="text-center"><?= __avatar_stack($followersCards, 5, 26) ?></td> -->
                  <td><?= format_date(($start === null || $start === '') ? 'N/A' : $start) ?></td>
                  <td><?= format_date(($due === null || $due === '') ? 'N/A' : $due) ?></td>

                    <td>
                      <span class="priority pr-<?= html_escape($priorityClass) ?>">
                        <i class="ti ti-flag-3-filled me-1"></i> <?= html_escape($priorityLabel) ?>
                      </span>
                    </td>

                <td>
                  <?php $isEditable = function_exists('staff_can') ? staff_can('edit','tasks') : false; ?>
                  <div class="clickup-status-wrapper" style="position: relative; display: inline-block;">
                    <select class="form-select form-select-sm bg-<?= $statusClass ?> text-white border-0 shadow-none"
                            style="
                              background-color: transparent;
                              color: inherit;
                              cursor: <?= $isEditable ? 'pointer' : 'default' ?>;
                              padding: 0.45rem 0.8rem;
                              min-width: 100px;
                              appearance: none;
                              -webkit-appearance: none;
                              -moz-appearance: none;
                              font-size: 0.65rem;
                              font-weight: 600;
                              text-transform: uppercase;
                              border-radius: 0rem;
                              background-image: none !important;
                            "
                            data-task-id="<?= $id ?>"
                            data-current-status="<?= $s ?>"
                            <?= $isEditable ? '' : 'disabled' ?>
                            onfocus="this.style.boxShadow='0 0 0 2px rgba(13, 110, 253, 0.25)'"
                            onblur="this.style.boxShadow='none'">
                      <?php foreach ($statusMap as $key => $pair): 
                        $isSelected = $s === $key;
                        $badgeClass = $pair[1];
                      ?>
                        <option value="<?= $key ?>" 
                                <?= $isSelected ? 'selected' : '' ?>
                                data-badge-class="bg-<?= $badgeClass ?>"
                                style="
                                  background-color: white;
                                  color: #212529;
                                  text-transform: uppercase;
                                  font-weight: 600;
                                  font-size: 0.75rem;
                                  padding: 0.5rem;
                                ">
                          <?= $pair[0] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    
                    <!-- Custom focus indicator -->
                    <div class="clickup-status-focus" style="
                      position: absolute;
                      top: 0;
                      left: 0;
                      right: 0;
                      bottom: 0;
                      pointer-events: none;
                      border-radius: 0.375rem;
                      border: 2px solid transparent;
                      transition: border-color 0.15s ease-in-out;
                    "></div>
                  </div>
                </td>

                    <td>
                    
                      <?php
                        // Meta counts for this task only
                        $meta = $meta_counts[$id] ?? [];
                    
                        $attachmentsCount = (int)($meta['attachments']   ?? 0);
                        $commentsCount    = (int)($meta['comments']      ?? 0);
                        $checklistsCount  = (int)($meta['checklists']    ?? 0);
                        $membersCount     = (int)($meta['members']       ?? 0);
                      ?>
                    
                      <div class="mt-1 d-flex flex-wrap">
                        <div class="project-meta-chip" title="Attachments">
                          <i class="ti ti-paperclip me-1"></i><?= $attachmentsCount ?>
                        </div>
                        <div class="project-meta-chip" title="Comments">
                          <i class="ti ti-message me-1"></i><?= $commentsCount ?>
                        </div>
                        <div class="project-meta-chip" title="Checklists">
                          <i class="ti ti-checks me-1"></i><?= $checklistsCount ?>
                        </div>
                        <div class="project-meta-chip" title="Members">
                          <i class="ti ti-users me-1"></i><?= $membersCount ?>
                        </div>
                      </div>
                    </td>
                    
                <?php
                  $stat = $checkstats[$r['id']] ?? ['total'=>0,'done'=>0,'pending'=>0,'percent'=>0];
                  $pct  = max(0, min(100, (int)$stat['percent']));
                  $done = (int)$stat['done'];
                  $tot  = (int)$stat['total'];
                
                  // One color at a time, low → high
                  if ($pct < 25) {
                    $barClass = 'bg-danger';
                  } elseif ($pct < 50) {
                    $barClass = 'bg-warning';
                  } elseif ($pct < 75) {
                    $barClass = 'bg-primary';
                  } else {
                    $barClass = 'bg-success';
                  }
                ?>
                <td>
                  <div class="progress mt-1" style="height:12px;">
                    <div class="progress-bar progress-bar-striped <?= $barClass ?>"
                         role="progressbar"
                         style="width: <?= $pct ?>%"
                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                      <?= $pct ?>%
                    </div>
                  </div>
                
                  <div class="small text-muted mt-1">
                    <?= $done ?> of <?= $tot ?> Checklist Completed
                  </div>
                </td>

                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Simple pager -->
      <?php if ($total > $limit): ?>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="small text-muted">
            Showing <?= (int)($offset+1) ?>–<?= (int)min($offset+$limit, $total) ?> of <?= (int)$total ?>
          </div>
          <div class="btn-group">
            <a class="btn btn-outline-secondary btn-sm<?= $has_prev?'':' disabled' ?>"
               href="<?= $has_prev ? site_url('tasks?view=list&offset='.max(0,$offset-$limit).'&limit='.$limit) : '#' ?>">
               ‹ Prev
            </a>
            <a class="btn btn-outline-secondary btn-sm<?= $has_next?'':' disabled' ?>"
               href="<?= $has_next ? site_url('tasks?view=list&offset='.($offset+$limit).'&limit='.$limit) : '#' ?>">
               Next ›
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// Keep your create modal
$CI = &get_instance();
$CI->load->view('tasks/modals/create_modal', compact('assignees','followers'));
?>
<script>
  document.addEventListener('click', function(e){
    const form = e.target.closest('.delete-task-form');
    if (!form) return;
    const msg = form.getAttribute('data-confirm') || 'Are you sure?';
    if (!confirm(msg)) {
      e.preventDefault();
    }
  });
</script>


<script>
(function() {
  // Must mirror the PHP map: status -> badge color class
  const STATUS_BADGES = {
    not_started: 'secondary',
    in_progress: 'info',
    in_review:      'warning',
    on_hold:     'dark',
    completed:   'success',
    cancelled:   'danger'
  };

  function humanize(key) {
    return (key || '').replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function applyBadge(el, status) {
    const keep = el.className.split(/\s+/).filter(c => !/^bg-/.test(c));
    el.className = keep.concat('bg-' + (STATUS_BADGES[status] || 'secondary'), 'badge', 'text-uppercase').join(' ');
    el.textContent = humanize(status);
    el.dataset.status = status;
  }

  async function postStatus(taskId, status) {
    const url  = <?= json_encode(site_url('tasks/status/')) ?> + taskId;
    const body = new URLSearchParams();
    body.set('status', status);
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });
    let data = null;
    try {
      const ct = res.headers.get('content-type') || '';
      if (ct.includes('application/json')) data = await res.json();
    } catch(_) {}
    return { ok: res.ok, data };
  }

  document.querySelectorAll('.js-status-select').forEach(sel => {
    let busy = false;
    sel.addEventListener('change', async () => {
      if (busy) return;
      busy = true;

      const taskId = sel.dataset.taskId;
      const next   = sel.value;
      const badge  = sel.closest('td')?.querySelector('.js-status-badge');

      // Optimistic UI
      if (badge) applyBadge(badge, next);

      try {
        const resp = await postStatus(taskId, next);
        if (!resp.ok || (resp.data && resp.data.success === false)) {
          // revert on failure
          const prev = badge?.dataset.status || sel.querySelector('option[selected]')?.value || 'not_started';
          if (badge) applyBadge(badge, prev);
          // revert select too
          sel.value = prev;
          alert((resp.data && resp.data.message) ? resp.data.message : 'Failed to update status.');
        }
      } catch (e) {
        const prev = badge?.dataset.status || sel.querySelector('option[selected]')?.value || 'not_started';
        if (badge) applyBadge(badge, prev);
        sel.value = prev;
        alert('Network error. Please try again.');
      } finally {
        busy = false;
      }
    });
  });
})();
</script>

<script>
(function() {
  const STATUS_COLORS = {
    not_started: { bg: '#6c757d', text: 'white' },
    in_progress: { bg: '#0dcaf0', text: 'white' },
    in_review:      { bg: '#ffc107', text: '#212529' },
    completed:   { bg: '#198754', text: 'white' },
    on_hold:     { bg: '#212529', text: 'white' },
    cancelled:   { bg: '#dc3545', text: 'white' }
  };

  const STATUS_HOVER_COLORS = {
    not_started: '#5a6268',
    in_progress: '#0baccc', 
    in_review:   '#e6b400',
    completed:   '#157347',
    on_hold:     '#1a1e21',
    cancelled:   '#c82333'
  };

  function updateSelectAppearance(select, status) {
    const colors = STATUS_COLORS[status] || STATUS_COLORS.not_started;
    select.style.backgroundColor = colors.bg;
    select.style.color = colors.text;
    select.dataset.currentStatus = status;
  }

  async function postStatus(taskId, status) {
    const url  = <?= json_encode(site_url('tasks/status/')) ?> + taskId;
    const body = new URLSearchParams();
    body.set('status', status);
    
    const res = await fetch(url, {
      method: 'POST',
      headers: { 
        'X-Requested-With': 'XMLHttpRequest', 
        'Content-Type': 'application/x-www-form-urlencoded' 
      },
      body
    });
    
    let data = null;
    try {
      const ct = res.headers.get('content-type') || '';
      if (ct.includes('application/json')) data = await res.json();
    } catch(_) {}
    
    return { ok: res.ok, data };
  }

  // Initialize all selects
  document.querySelectorAll('.clickup-status-select').forEach(select => {
    const currentStatus = select.dataset.currentStatus;
    updateSelectAppearance(select, currentStatus);
    
    // Add change event listener
    select.addEventListener('change', async function() {
      const taskId = this.dataset.taskId;
      const newStatus = this.value;
      const originalStatus = this.dataset.currentStatus;
      
      // Optimistic UI update
      updateSelectAppearance(this, newStatus);
      
      try {
        const response = await postStatus(taskId, newStatus);
        
        if (!response.ok || (response.data && response.data.success === false)) {
          // Revert on failure
          updateSelectAppearance(this, originalStatus);
          this.value = originalStatus;
          
          const message = (response.data && response.data.message) 
            ? response.data.message 
            : 'Failed to update status.';
          alert(message);
        } else {
          // Success - update the data attribute
          this.dataset.currentStatus = newStatus;
        }
      } catch (error) {
        // Revert on network error
        updateSelectAppearance(this, originalStatus);
        this.value = originalStatus;
        alert('Network error. Please try again.');
      }
    });
    
    // Add hover effects
    select.addEventListener('mouseenter', function() {
      if (this.disabled) return;
      
      const currentStatus = this.dataset.currentStatus;
      const hoverColor = STATUS_HOVER_COLORS[currentStatus] || STATUS_HOVER_COLORS.not_started;
      this.style.backgroundColor = hoverColor;
    });
    
    select.addEventListener('mouseleave', function() {
      if (this.disabled) return;
      
      const currentStatus = this.dataset.currentStatus;
      const colors = STATUS_COLORS[currentStatus] || STATUS_COLORS.not_started;
      this.style.backgroundColor = colors.bg;
    });
  });
})();
</script>
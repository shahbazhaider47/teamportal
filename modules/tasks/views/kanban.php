<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
:root {
  --status-not-started: #6c757d;
  --status-in-progress: #0dcaf0;
  --status-in_review: #ffc107;
  --status-on_hold: #ffc107;  
  --status-completed: #198754;
  --status-cancelled: #dc3545;
  
  --pr-normal: #6c757d;
  --pr-low: #0dcaf0;
  --pr-medium: #ffc107;
  --pr-high: #dc3545;
  --pr-urgent: #dc3545;
  
  --card-shadow: 0 2px 4px rgba(0,0,0,0.08);
  --card-shadow-hover: 0 4px 12px rgba(0,0,0,0.12);
  --border-radius: 8px;
  --transition: all 0.2s ease;
}

/* Kanban Board Layout */
/* Kanban Board Layout - single row, horizontal scroll */
.kb-board {
  display: flex;
  flex-wrap: nowrap;          /* do not wrap to next line */
  gap: 0.8rem;
  overflow-x: auto;           /* horizontal scroll when not enough space */
  padding-bottom: 0.5rem;     /* some space above scrollbar */
}

/* Optional: smooth scrolling on trackpads/mouse wheels */
.kb-board::-webkit-scrollbar {
  height: 6px;
}
.kb-board::-webkit-scrollbar-track {
  background: #f3f4f6;
}
.kb-board::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}


/* Kanban Column */
/* Kanban Column */
.kb-col {
  background: #f8fafc;
  border-radius: var(--border-radius);
  padding: 8px;
  min-height: 700px;
  border: 1px solid #e2e8f0;
  transition: var(--transition);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);

  /* key for horizontal board */
  flex: 0 0 260px;   /* fixed width column – adjust 260px as you like */
  max-width: 280px;  /* prevent too wide on large screens */
}


.kb-col:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.kb-col-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.kb-col-header .fw-semibold {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #374151;
}

.status-icon {
  flex-shrink: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.status-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 11px;
  font-weight: 600;
}

.kb-col-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-height: 400px;
}

/* Enhanced Kanban Cards */
.kb-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: var(--border-radius);
  padding: 16px;
  cursor: grab;
  transition: var(--transition);
  box-shadow: var(--card-shadow);
  break-inside: avoid;
  position: relative;
}

.kb-card:hover {
  box-shadow: var(--card-shadow-hover);
  transform: translateY(-2px);
  border-color: #cbd5e1;
}

.kb-card.dragging {
  opacity: 0.8;
  transform: rotate(3deg) scale(1.02);
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  z-index: 1000;
}

.kb-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 5px;
  gap: 2px;
}

.kb-card-title {
  font-size: 0.675rem;
  font-weight: 600;
  line-height: 1.4;
  color: #1f2937;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.kb-card-badge {
  font-size: 0.65rem;
  flex-shrink: 0;
  margin-left: 4px;
}

.kb-card-description {
  font-size: 0.65rem;
  color: #6b7280;
  line-height: 1.4;
  margin-bottom: 5px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Enhanced Card Meta Section */
.kb-card-meta {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
  margin-top: 10px;
}

.kb-card-dates {
  display: flex;
  justify-content: space-between;
  font-size: 0.7rem;
  color: #6b7280;
}

.kb-date-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.kb-date-icon {
  font-size: 0.65rem;
  opacity: 0.7;
}

.kb-card-assignee {
  display: flex;
  align-items: center;
  gap: 8px;
}

.assignee-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
  background-color: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.6rem;
  font-weight: 600;
  color: #374151;
}

.assignee-name {
  font-size: 0.60rem;
  color: #374151;
  font-weight: 600;
}

.kb-card-followers {
  display: flex;
  align-items: center;
  gap: 4px;
}

.follower-avatar {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid white;
  background-color: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.55rem;
  font-weight: 600;
  color: #374151;
}

.follower-avatar:not(:first-child) {
  margin-left: -6px;
}

.more-followers {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background-color: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.55rem;
  font-weight: 600;
  color: #6b7280;
  margin-left: -6px;
  border: 2px solid white;
}

.kb-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.7rem;
  color: #9ca3af;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f3f4f6;
}

.kb-card-id {
  font-weight: 500;
}

.kb-card-attachments {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
}

.attachment-icon {
  font-size: 0.7rem;
  opacity: 0.7;
}

.kb-card-link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  gap: 4px;
}

.kb-card-link:hover {
  text-decoration: underline;
}

/* Priority Indicators */
.kb-card-priority {
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  border-radius: var(--border-radius) 0 0 var(--border-radius);
}

.priority-normal { background-color: var(--pr-normal); }
.priority-low { background-color: var(--pr-low); }
.priority-medium { background-color: var(--pr-medium); }
.priority-high { background-color: var(--pr-high); }
.priority-urgent { background-color: var(--pr-urgent); }

/* Drop Zones */
.kb-drop {
  border: 2px dotted #d1d5db;
  border-radius: var(--border-radius);
  padding: 5px;
  min-height: 80px;
  transition: var(--transition);
}

.kb-drop-over {
  background-color: #e7f3ff !important;
  border-color: #3b82f6 !important;
  border-style: solid;
}

.kb-drop-zone {
  min-height: 20px;
  border: 2px transparent;
  border-radius: 8px;
  margin: 4px 0;
}

.kb-drop-zone.active {
  border-color: #3b82f6;
  background: #e7f1ff;
}

/* Badges */
.badge {
  font-size: 0.55rem;
  font-weight: 600;
  padding: 0.25em 0.5em;
}

.count-badge {
  font-size: 0.7rem;
  min-width: 30px;
  text-align: center;
  background-color: #e5e7eb;
  color: #374151;
  font-weight: 600;
}

/* Status Colors */
.kb-col[data-status="not_started"] .status-icon { background: rgba(var(--light), 1); }
.kb-col[data-status="in_progress"] .status-icon { background: rgba(var(--primary), 1); }
.kb-col[data-status="in_review"] .status-icon { background: rgba(var(--info), 1); }
.kb-col[data-status="on_hold"] .status-icon { background: rgba(var(--warning), 1); }
.kb-col[data-status="completed"] .status-icon { background: rgba(var(--success), 1); }
.kb-col[data-status="cancelled"] .status-icon { background: rgba(var(--danger), 1); }

/* Mobile Optimizations */
@media (max-width: 575.98px) {
  .kb-col {
    min-height: 300px;
    padding: 12px;
  }
  
  .kb-card {
    padding: 12px;
  }
  
  .kb-card-title {
    font-size: 0.8rem;
  }
  
  .kb-card-description {
    font-size: 0.7rem;
    -webkit-line-clamp: 1;
  }
  
  .kb-col-header .fw-semibold {
    font-size: 0.8rem;
  }
  
  .kb-card-meta {
    gap: 8px;
  }
}

/* Hide columns on mobile when filtered */
.kb-col.mobile-hidden {
  display: none;
}

/* Loading State */
.kb-loading {
  opacity: 0.6;
  pointer-events: none;
}

/* Empty State */
.kb-col-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100px;
  color: #9ca3af;
  font-size: 0.675rem;
  text-align: center;
  padding: 0px;
  border: 1px #d1d5db;
  border-radius: var(--border-radius);
}

/* Quick Actions */
.kb-card-actions {
  position: absolute;
  top: 8px;
  right: 8px;
  opacity: 0;
  transition: var(--transition);
  display: flex;
  gap: 4px;
}

.kb-card:hover .kb-card-actions {
  opacity: 1;
}

.kb-action-btn {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  background: white;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  color: #6b7280;
  cursor: pointer;
  transition: var(--transition);
}

.kb-action-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

/* Task Preview Modal */
.task-preview-modal .modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.task-preview-header {
  padding: 20px 24px 0;
  border-bottom: none;
}

.task-preview-body {
  padding: 0 24px 20px;
}

.task-preview-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.task-preview-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.task-preview-meta-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.task-preview-meta-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.task-preview-meta-value {
  font-size: 0.875rem;
  color: #374151;
  font-weight: 500;
}

.task-preview-description {
  font-size: 0.875rem;
  line-height: 1.6;
  color: #4b5563;
}

.kb-filters {
  display: flex;
  gap: 8px;
}

.kb-filter-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.8rem;
  color: #374151;
  cursor: pointer;
  transition: var(--transition);
}

.kb-filter-btn:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.kb-filter-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

/* Progress Bar for Tasks */
.kb-progress {
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 8px;
}

.kb-progress-bar {
  height: 100%;
  background: #10b981;
  border-radius: 2px;
  transition: width 0.3s ease;
}

/* Subtask Indicators */
.kb-subtasks {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.7rem;
  color: #6b7280;
  margin-bottom: 8px;
}

.kb-subtask-progress {
  flex: 1;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.kb-subtask-progress-bar {
  height: 100%;
  background: #10b981;
  border-radius: 2px;
}
</style>
                      
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Tasks Kanban') ?> Kanban</h1>
    </div>

    <?php
      $canExport     = (function_exists('staff_can') && staff_can('export', 'general'));
      $canPrint      = (function_exists('staff_can') && staff_can('print', 'general'));
    ?>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">

      <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#taskCreateModal">
        <i class="ti ti-plus"></i> <span class="d-none d-sm-inline">New Task</span>
      </button>
      
      <div class="btn-divider d-none d-sm-block"></div>

      <div class="btn-group" role="group" aria-label="Views">
        <a href="<?= site_url('tasks?view=list') ?>" class="btn btn-outline-primary btn-header">
          <i class="ti ti-list-details me-1"></i> <span class="d-none d-md-inline">List</span>
        </a>
        <a href="<?= site_url('tasks?view=kanban') ?>" class="btn btn-primary btn-header">
          <i class="ti ti-layout-kanban me-1"></i> <span class="d-none d-md-inline">Kanban</span>
        </a>
        <a href="<?= site_url('tasks?view=gantt') ?>" class="btn btn-outline-primary btn-header">
          <i class="ti ti-chart-bar me-1"></i> <span class="d-none d-md-inline">Gantt</span>
        </a>
      </div>

      <!-- Export -->
      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table d-none d-sm-flex"
                title="Export to Excel"
                data-export-filename="<?= html_escape($page_title ?? 'export') ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <!-- Print -->
      <?php if ($canPrint): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-print-table d-none d-sm-flex"
                title="Print Table">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>

    </div>
  </div>

  <div class="kb-board" id="kbBoard">
    <?php
      $statuses = [
        'not_started'   => 'Not Started',
        'in_progress'   => 'In Progress',
        'in_review'     => 'In Review',
        'on_hold'       => 'On Hold',
        'completed'     => 'Completed',
        'cancelled'     => 'Cancelled',
      ];
      foreach ($statuses as $key=>$label): ?>
      <div class="kb-col" data-status="<?= $key ?>">
        <div class="kb-col-header p-1">
          <div class="fw-semibold text-primary d-flex align-items-center">
            <span class="status-icon d-inline-block me-1"></span>
            <span class="status-text"><?= html_escape($label) ?></span>
          </div>
          <span class="badge bg-light-primary count-badge" id="count-<?= $key ?>">0</span>
        </div>
        <div class="kb-col-body kb-drop" id="col-<?= $key ?>" data-status="<?= $key ?>"></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php
$CI = &get_instance();
$CI->load->view('tasks/modals/create_modal', compact('assignees','followers'));
?>

<!-- Task Preview Modal -->
<div class="modal fade" id="taskPreviewModal" tabindex="-1" aria-labelledby="taskPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content task-preview-modal">
      <div class="task-preview-header">
        <button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="task-preview-body">
        <h3 class="task-preview-title" id="previewTaskTitle">Task Title</h3>
        
        <div class="task-preview-meta">
          <div class="task-preview-meta-item">
            <span class="task-preview-meta-label">Status</span>
            <span class="task-preview-meta-value" id="previewTaskStatus">Not Started</span>
          </div>
          <div class="task-preview-meta-item">
            <span class="task-preview-meta-label">Priority</span>
            <span class="task-preview-meta-value" id="previewTaskPriority">Medium</span>
          </div>
          <div class="task-preview-meta-item">
            <span class="task-preview-meta-label">Start Date</span>
            <span class="task-preview-meta-value" id="previewTaskStartDate">-</span>
          </div>
          <div class="task-preview-meta-item">
            <span class="task-preview-meta-label">Due Date</span>
            <span class="task-preview-meta-value" id="previewTaskDueDate">-</span>
          </div>
        </div>
        
        <div class="task-preview-description" id="previewTaskDescription">
          No description available.
        </div>
        
        <div class="mt-4">
          <a href="#" class="btn btn-primary" id="previewTaskLink">Open Task</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toastArea" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;"></div>

<script>
(function(){
  'use strict';

  // ==================== CONFIGURATION & UTILITIES ====================
  
  /* Application Configuration */
  const baseUrl = '<?= rtrim(site_url(), '/') ?>';
  const routes = {
    list: baseUrl + '/tasks/list_json',    // API endpoint to fetch tasks
    move: baseUrl + '/tasks/kanban_move'   // API endpoint to update task status/order
  };

  /* Notification System */
  const toastArea = document.getElementById('toastArea');
  function notify(msg, type = 'success') {
    const id = 't' + Date.now();
    toastArea.insertAdjacentHTML('beforeend',
      `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2">
         <div class="d-flex">
           <div class="toast-body">${msg}</div>
           <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
         </div>
       </div>`);
    new bootstrap.Toast(document.getElementById(id), { delay: 1800 }).show();
  }

  /* HTML Escaping Utility */
  function escapeHtml(s){
    return (s||'').replace(/[&<>"'`=\/]/g,(c)=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'}[c]));
  }

  /* Date Formatting Utility */
  function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  /* Get User Initials for Avatar Fallback */
  function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
  }

  /* API Fetch Wrapper with Error Handling */
  async function fetchJSON(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['X-Requested-With'] = 'XMLHttpRequest';
    if (!options.headers['Accept']) options.headers['Accept'] = 'application/json';

    let res, text = '';
    try {
      res = await fetch(url, options);
    } catch (err) {
      throw new Error('Network error: ' + err.message);
    }

    const ct = (res.headers.get('content-type') || '').toLowerCase();

    if (ct.includes('application/json')) {
      let j;
      try { j = await res.json(); } catch (e) { throw new Error('Malformed JSON from server'); }
      if (!res.ok || j.success === false) {
        throw new Error(j && j.message ? j.message : `HTTP ${res.status}`);
      }
      return j;
    }

    try { text = await res.text(); } catch (_) { /* ignore */ }
    const hint = text ? text.slice(0, 500) : '';
    const brief = hint.replace(/\s+/g, ' ').trim();

    if (!res.ok) {
      throw new Error(`HTTP ${res.status} (non-JSON). ${brief || 'Server returned HTML.'}`);
    }

    throw new Error(`Non-JSON response. ${brief || 'Server returned HTML.'}`);
  }

  // ==================== APPLICATION STATE ====================
  
  /* DOM Elements & State Variables */
  const columns = Array.from(document.querySelectorAll('.kb-col-body'));
  let tasks = [];                    // All loaded tasks
  let isDragging = false;           // Drag state flag
  let dragTaskId = null;            // Currently dragged task ID
  
  /* Task Status Configuration */
  const statusKeys = ['not_started', 'in_progress', 'in_review', 'on_hold', 'cancelled', 'completed'];

  // ==================== RENDERING SYSTEM ====================
  
  /* Generate Status Badge HTML */
  function badgeStatus(s){
    const map = {
      not_started: 'secondary',
      in_progress: 'info', 
      in_review: 'warning',
      on_hold: 'warning',      
      completed: 'success',
      cancelled: 'danger'
    };
    return `<span class="small badge kb-card-badge bg-${map[s]||'secondary'} text-uppercase">${(s||'').replace('_',' ')}</span>`;
  }

  /* Main Render Function - Updates the entire Kanban board */
  function render() {
    // Reset all columns
    const byStatus = Object.fromEntries(statusKeys.map(k=>[k,[]]));
    columns.forEach(col => { 
      col.innerHTML = '';
      // Add empty state placeholder
      const emptyDiv = document.createElement('div');
      emptyDiv.className = 'kb-col-empty';
      emptyDiv.innerHTML = 'No tasks';
      emptyDiv.style.display = 'none';
      col.appendChild(emptyDiv);
    });

    // Group tasks by status
    tasks.forEach(t => {
      const key = statusKeys.includes(t.status) ? t.status : 'not_started';
      byStatus[key].push(t);
    });

    // Render each column
    for (const [status, list] of Object.entries(byStatus)) {
      const col = document.getElementById('col-' + status);
      const countEl = document.getElementById('count-' + status);
      const emptyDiv = col.querySelector('.kb-col-empty');
      
      // Update column count
      if (countEl) countEl.textContent = list.length;

      // Show/hide empty state
      if (emptyDiv) {
        emptyDiv.style.display = list.length === 0 ? 'flex' : 'none';
      }

      // Sort tasks by kanban order, then by ID
      list.sort((a,b)=> (a.kanban_order||0)-(b.kanban_order||0) || a.id-b.id);
      
      // Create and append task cards
      for (const task of list) {
        const el = document.createElement('div');
        el.className = 'kb-card';
        el.draggable = true;
        el.dataset.id = task.id;
        el.dataset.status = task.status;
        
        // Build assignee HTML with avatar and name
        let assigneeHtml = '<div class="assignee-avatar">?</div><span class="assignee-name text-muted">Unassigned</span>';
        if (task.assignee) {
          const defaultImage = baseUrl + '/assets/images/default.png';
          const avatarSrc = task.assignee.profile_image ? task.assignee.profile_image : defaultImage;
          assigneeHtml = `<img src="${avatarSrc}" class="assignee-avatar" alt="${escapeHtml(task.assignee.full_name)}" onerror="this.src='${defaultImage}'">
                          <span class="assignee-name">${escapeHtml(task.assignee.full_name)}</span>`;
        }

        // Build followers HTML with avatar stack
        let followersHtml = '';
        try {
          if (task.followers) {
            let followerList = [];
            
            // Handle different follower data formats
            if (Array.isArray(task.followers)) {
              followerList = task.followers;
            } else if (typeof task.followers === 'string') {
              try {
                const parsed = JSON.parse(task.followers);
                if (Array.isArray(parsed)) {
                  followerList = parsed;
                }
              } catch (e) {
                // Fallback: comma-separated IDs
                const parts = task.followers.split(',');
                followerList = parts.map(part => {
                  const id = parseInt(part.trim());
                  return !isNaN(id) ? { id: id } : null;
                }).filter(Boolean);
              }
            }
            
            // Process valid followers
            const validFollowers = followerList
              .filter(f => f && (f.id || f.user_id))
              .map(follower => ({
                id: follower.id || follower.user_id,
                full_name: follower.full_name || follower.name || `User #${follower.id || follower.user_id}`,
                profile_image: follower.profile_image || follower.avatar || null
              }));
            
            if (validFollowers.length > 0) {
              const visibleFollowers = validFollowers.slice(0, 3);
              const extraCount = validFollowers.length - 3;
              
              followersHtml = visibleFollowers.map(follower => {
                const fullName = escapeHtml(follower.full_name);
                const initials = getInitials(follower.full_name);
                
                const defaultImage = baseUrl + '/assets/images/default.png';
                let imageUrl = defaultImage;
                
                if (follower.profile_image) {
                  imageUrl = follower.profile_image.startsWith('http') 
                    ? follower.profile_image 
                    : `${baseUrl}/uploads/users/profile/${follower.profile_image}`;
                }
                
                return `<img src="${imageUrl}" class="follower-avatar" alt="${fullName}" title="${fullName}" onerror="this.src='${defaultImage}'">`;

              }).join('');
              
              // Show +count for extra followers
              if (extraCount > 0) {
                followersHtml += `<div class="more-followers" title="${extraCount} more followers">+${extraCount}</div>`;
              }
            }
          }
        } catch (error) {
          console.warn('Error processing followers:', error);
          // Fail silently - don't break the card if followers processing fails
        }

        // Build priority indicator class
        const priorityClass = task.priority ? `priority pr-${task.priority}` : 'priority-medium';

        const priorityKey = String(task.priority || 'normal').toLowerCase();
        const priorityLabels = {
          low:    'Low',
          normal: 'Normal',
          high:   'High',
          urgent: 'Urgent',
          no:     'N/A'
        };
        const priorityLabel = priorityLabels[priorityKey] || 'Normal';

        // Construct task card HTML
        el.innerHTML = `
          <div class="kb-card-priority"></div>
          <div class="kb-card-actions">
            <button class="kb-action-btn" data-action="preview" title="Preview">
              <i class="ti ti-eye"></i>
            </button>
          </div>
          <div class="kb-card-header">
            <div class="kb-card-title text-primary">${escapeHtml(task.name||'Untitled')}</div>
          </div>
          <div class="kb-card-description">${escapeHtml((task.description||'').replace(/\s+/g,' ').substring(0,100))}</div>
          
          <div class="kb-card-meta">
            <div class="kb-card-dates">
              <div class="kb-date-item">
                <i class="ti ti-calendar kb-date-icon"></i>
                <span class="small">${formatDate(task.start_date)}</span>
                <i class="ti ti-dots-vertical"></i>
                <i class="ti ti-clock kb-date-icon"></i>
                  <span class="small">${formatDate(task.due_date)}</span>
              </div>
            </div>
                <div class="kb-date-item">
                  <span class="priority pr-${priorityClass}">
                    <i class="ti ti-flag-3-filled me-1"></i> 
                    ${escapeHtml(priorityLabel)}
                  </span>
                </div>            
            
            <div class="kb-card-assignee">
              ${assigneeHtml}
            </div>
            
            ${followersHtml ? `<div class="kb-card-followers">${followersHtml}</div>` : ''}
          </div>
          
          <div class="kb-card-footer">
            <span class="kb-card-id">${badgeStatus(task.status)}</span>
            <div class="kb-card-attachments">
              <i class="ti ti-paperclip attachment-icon"></i>
              <span>${parseInt(task.attachment_count, 10) || 0}</span>
            </div>
            <a class="kb-card-link" href="${baseUrl}/tasks/view/${task.id}">
              <i class="ti ti-external-link"></i> Open
            </a>
          </div>`;

        // Add drag event handlers
        el.addEventListener('dragstart', handleDragStart);
        el.addEventListener('dragend', handleDragEnd);
        
        // Add preview button handler
        const previewBtn = el.querySelector('[data-action="preview"]');
        if (previewBtn) {
          previewBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            showTaskPreview(task);
          });
        }

        col.appendChild(el);
      }
    }
  }

  /* Task Preview Modal */
  function showTaskPreview(task) {
    document.getElementById('previewTaskTitle').textContent = task.name || 'Untitled';
    document.getElementById('previewTaskStatus').textContent = task.status ? task.status.replace('_', ' ') : 'Not Started';
    document.getElementById('previewTaskPriority').textContent = task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium';
    document.getElementById('previewTaskStartDate').textContent = formatDate(task.start_date);
    document.getElementById('previewTaskDueDate').textContent = formatDate(task.due_date);
    document.getElementById('previewTaskDescription').textContent = task.description || 'No description available.';
    document.getElementById('previewTaskLink').href = `${baseUrl}/tasks/view/${task.id}`;
    
    const modal = new bootstrap.Modal(document.getElementById('taskPreviewModal'));
    modal.show();
  }

  // ==================== MOBILE RESPONSIVENESS ====================
  
  /* Mobile Status Filter */
  function initializeMobileFilter() {
    const mobileFilter = document.getElementById('mobileStatusFilter');
    if (!mobileFilter) return;

    mobileFilter.addEventListener('change', function() {
      const selectedStatus = this.value;
      const allColumns = document.querySelectorAll('.kb-col');
      
      // Show/hide columns based on mobile filter selection
      allColumns.forEach(col => {
        if (selectedStatus === 'all' || col.dataset.status === selectedStatus) {
          col.classList.remove('mobile-hidden');
        } else {
          col.classList.add('mobile-hidden');
        }
      });
    });
  }

  // ==================== DRAG & DROP SYSTEM ====================
  
  /* Drag Start Handler */
  function handleDragStart(e) {
    isDragging = true;
    dragTaskId = parseInt(e.target.dataset.id, 10);
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(dragTaskId));
    
    // Touch support for mobile devices
    if (e.type === 'touchstart') {
      e.preventDefault();
    }
  }

  /* Drag End Handler */
  function handleDragEnd(e) {
    isDragging = false;
    dragTaskId = null;
    // Clean up drag states
    document.querySelectorAll('.kb-card.dragging').forEach(el => {
      el.classList.remove('dragging');
    });
    document.querySelectorAll('.kb-drop-over').forEach(el => {
      el.classList.remove('kb-drop-over');
    });
  }

  /* Drag Over Handler */
  function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    // Visual feedback for drop zones
    const dropZone = e.currentTarget;
    document.querySelectorAll('.kb-drop-over').forEach(el => {
      if (el !== dropZone) el.classList.remove('kb-drop-over');
    });
    dropZone.classList.add('kb-drop-over');
  }

  /* Drag Leave Handler */
  function handleDragLeave(e) {
    // Only remove class if not dragging over a child element
    if (!e.currentTarget.contains(e.relatedTarget)) {
      e.currentTarget.classList.remove('kb-drop-over');
    }
  }

  /* Drop Handler */
  async function handleDrop(e) {
    e.preventDefault();
    const dropZone = e.currentTarget;
    dropZone.classList.remove('kb-drop-over');

    const taskId = parseInt(e.dataTransfer.getData('text/plain'), 10);
    const newStatus = dropZone.dataset.status;
    
    if (!taskId || !newStatus) return;

    const draggedEl = document.querySelector(`.kb-card[data-id="${taskId}"]`);
    if (!draggedEl) return;

    const oldStatus = draggedEl.dataset.status;
    
    // Handle reorder within same column
    if (oldStatus === newStatus) {
      handleReorder(dropZone, draggedEl, e.clientY);
    } else {
      // Handle move to different column
      await handleStatusChange(dropZone, draggedEl, taskId, newStatus, e.clientY);
    }
  }

  /* Reorder Tasks Within Same Column */
  function handleReorder(dropZone, draggedEl, clientY) {
    const afterEl = getDragAfterElement(dropZone, clientY);
    
    // Insert at correct position
    if (afterEl) {
      dropZone.insertBefore(draggedEl, afterEl);
    } else {
      dropZone.appendChild(draggedEl);
    }
    
    // Persist new order to server (no primaryId -> no status-change notification)
    persistColumnOrder(dropZone.dataset.status, null);
  }

  /* Move Task to Different Column */
  async function handleStatusChange(dropZone, draggedEl, taskId, newStatus, clientY) {
    // Remove from old column
    draggedEl.remove();
    
    // Add to new column at correct position
    const afterEl = getDragAfterElement(dropZone, clientY);
    if (afterEl) {
      dropZone.insertBefore(draggedEl, afterEl);
    } else {
      dropZone.appendChild(draggedEl);
    }
    
    // Update visual status badge
    const badge = draggedEl.querySelector('.badge');
    if (badge) {
      badge.className = 'badge kb-card-badge ' + getBadgeClass(newStatus);
      badge.textContent = newStatus.replace('_', ' ');
    }
    
    draggedEl.dataset.status = newStatus;

    try {
      // NEW: pass taskId so backend knows which card triggered the move
      await persistColumnOrder(newStatus, taskId);
      notify('Task moved to ' + newStatus.replace('_', ' '));
    } catch (err) {
      // Revert on error
      render();
      notify(err.message || 'Move failed', 'danger');
    }

  }

  /* Get CSS Class for Status Badge */
  function getBadgeClass(status) {
    const map = {
      not_started: 'bg-secondary',
      in_progress: 'bg-info',
      in_review: 'bg-warning',
      on_hold: 'bg-warning',
      completed: 'bg-success', 
      cancelled: 'bg-danger'
    };
    return map[status] || 'bg-secondary';
  }

  /* Calculate Drop Position for Insertion */
  function getDragAfterElement(container, y) {
    const cards = [...container.querySelectorAll('.kb-card:not(.dragging)')];
    
    return cards.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      
      if (offset < 0 && offset > closest.offset) {
        return { offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
  }

  /* Persist Column Order to Server */
async function persistColumnOrder(status, primaryId = null) {
  const col = document.getElementById(`col-${status}`);
  const payload = [];
  
  // Build payload with current order
  col.querySelectorAll('.kb-card').forEach((card, idx) => {
    payload.push({
      id: parseInt(card.dataset.id, 10),
      order: idx + 1
    });
  });

  if (payload.length === 0) return;

  // Update in-memory state
  for (const item of payload) {
    const task = tasks.find(t => t.id === item.id);
    if (task) {
      task.status = status;
      task.kanban_order = item.order;
    }
  }

  // Send update to server
  const fd = new FormData();
  fd.append('status', status);

  // NEW: tell backend which task was actually dragged
  if (primaryId) {
    fd.append('primary_id', primaryId);
  }

  payload.forEach((item, index) => {
    fd.append(`orders[${index}][id]`, item.id);
    fd.append(`orders[${index}][order]`, item.order);
  });

  await fetchJSON(routes.move, {
    method: 'POST',
    body: fd
  });
}


  /* Initialize Drag & Drop Event Listeners */
  function initializeDragAndDrop() {
    columns.forEach(col => {
      col.addEventListener('dragover', handleDragOver);
      col.addEventListener('dragleave', handleDragLeave);
      col.addEventListener('drop', handleDrop);
      
      // Touch support for mobile
      col.addEventListener('touchmove', handleDragOver, { passive: false });
      col.addEventListener('touchend', handleDrop);
    });
  }

  // ==================== DATA MANAGEMENT ====================
  
  /* Load Tasks from Server */
  async function load() {
    try {
      const fd = new FormData();
      fd.append('limit', '1000');
      const json = await fetchJSON(routes.list, { method: 'POST', body: fd });
      tasks = json.data?.rows || [];
      
      // Process and enhance task data
      tasks = tasks.map(task => {
        // Build assignee object
        const assignee = task.assignee_id ? {
          id: task.assignee_id,
          full_name: task.assignee_name || task.assignee_fullname || 'Unknown User',
          profile_image: task.assignee_profile_image || task.assignee_avatar || null
        } : null;

        // Build followers array
        let followers = [];
        if (task.followers) {
          // Handle different follower data formats
          if (Array.isArray(task.followers)) {
            followers = task.followers.map(follower => ({
              id: follower.id || follower.user_id,
              full_name: follower.full_name || follower.name || 'Unknown User',
              profile_image: follower.profile_image || follower.avatar || null
            }));
          } else if (typeof task.followers === 'string') {
            try {
              const parsed = JSON.parse(task.followers);
              if (Array.isArray(parsed)) {
                followers = parsed.map(follower => ({
                  id: follower.id || follower.user_id,
                  full_name: follower.full_name || follower.name || 'Unknown User',
                  profile_image: follower.profile_image || follower.avatar || null
                }));
              }
            } catch (e) {
              // Fallback for comma-separated IDs
              const followerIds = task.followers.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
              followers = followerIds.map(id => ({
                id: id,
                full_name: 'User #' + id,
                profile_image: null
              }));
            }
          }
        }

        // Build proper image URLs
        if (assignee && assignee.profile_image && !assignee.profile_image.startsWith('http')) {
          assignee.profile_image = baseUrl + '/uploads/users/profile/' + assignee.profile_image;
        }

        followers = followers.map(follower => {
          if (follower.profile_image && !follower.profile_image.startsWith('http')) {
            follower.profile_image = baseUrl + '/uploads/users/profile/' + follower.profile_image;
          }
          return follower;
        });

        // Comprehensive date field mapping
        const findDateField = (possibleNames) => {
          for (const name of possibleNames) {
            if (task[name] && task[name] !== '0000-00-00' && task[name] !== '0000-00-00 00:00:00') {
              return task[name];
            }
          }
          return null;
        };

        const startDate = findDateField([
          'start_date', 'startdate', 'dateadded', 'date_added', 
          'created_at', 'created', 'task_start_date', 'start'
        ]);

        const dueDate = findDateField([
          'due_date', 'duedate', 'deadline', 'end_date', 
          'date_end', 'task_due_date', 'due', 'datefinished'
        ]);

// NEW: trust backend field attachment_count
const attachmentCount = parseInt(task.attachment_count, 10) || 0;


// Return enhanced task object
return {
  ...task,
  assignee: assignee,
  followers: followers,
attachment_count: attachmentCount,
  priority: task.priority || 'medium',
  start_date: startDate,
  due_date: dueDate
};


      });
      
      // Initialize the board
      render();
      initializeDragAndDrop();
      initializeMobileFilter();
    } catch (e) {
      console.error('Load error:', e);
      notify(e.message, 'danger');
    }
  }

  // ==================== APPLICATION INITIALIZATION ====================
  
  /* Initialize application when DOM is ready */
  document.addEventListener('DOMContentLoaded', load);
  
  /* Handle window resize for responsive adjustments */
  window.addEventListener('resize', function() {
    // Add any responsive adjustments here if needed
  });
})();
</script>
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
:root {
  --gantt-primary: #3b82f6;
  --gantt-success: #10b981;
  --gantt-warning: #f59e0b;
  --gantt-danger: #ef4444;
  --gantt-info: #0ea5e9;
  --gantt-gray: #6b7280;
  
  --status-not-started: #6b7280;
  --status-in-progress: #0ea5e9;
  --status-review: #f59e0b;
  --status-completed: #10b981;
  --status-cancelled: #ef4444;
  --status-on_hold: #9ca3af;
}

.gantt-container {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gantt-header {
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.gantt-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.gantt-zoom-controls {
  display: flex;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  overflow: hidden;
}

.gantt-zoom-btn {
  padding: 0.5rem 1rem;
  border: none;
  background: #fff;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.675rem;
  font-weight: 500;
}

.gantt-zoom-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.gantt-zoom-btn.active {
  background: #3b82f6;
  color: #fff;
}

.gantt-date-navigation {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.gantt-current-range {
  font-size: 0.675rem;
  font-weight: 600;
  color: #374151;
  min-width: 200px;
  text-align: center;
}

.gantt-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  height: 600px;
  overflow: auto;
}

.gantt-sidebar {
  background: #f8fafc;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
}

.gantt-chart-area {
  position: relative;
  background: 
    linear-gradient(90deg, #f9fafb 1px, transparent 1px),
    linear-gradient(0deg, #f3f4f6 1px, transparent 1px);
  background-size: 40px 40px;
  overflow: auto;
}

.gantt-sidebar-header,
.gantt-chart-header {
  position: sticky;
  top: 0;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  z-index: 10;
  padding: 0.75rem 1rem;
  font-weight: 600;
  font-size: 0.75rem;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.gantt-sidebar-header {
  display: grid;
  grid-template-columns: 1fr 80px; /* ID column removed */
  gap: 1rem;
  align-items: center;
}


.gantt-chart-header {
  display: flex;
  height: 45px;
}

.gantt-time-slots {
  display: flex;
  height: 100%;
}

.gantt-time-slot {
  width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  color: #6b7280;
  border-right: 1px solid #e5e7eb;
  font-weight: 500;
}

.gantt-time-slot.weekend {
  background: #fef2f2;
}

.gantt-time-slot.today {
  background: #dbeafe;
  color: #1e40af;
  font-weight: 600;
}

.gantt-rows {
  position: relative;
}

.gantt-row {
  display: grid;
  grid-template-columns: 300px 1fr;
  min-height: 50px;
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.2s ease;
}

.gantt-row:hover {
  background: #f8fafc;
}

.gantt-row-content {
  display: grid;
  grid-template-columns: 1fr 80px; /* ID column removed */
  gap: 1rem;
  align-items: center;
}

.gantt-task-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.gantt-task-avatar {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.6rem;
  font-weight: 600;
  color: #374151;
  flex-shrink: 0;
}

.gantt-task-text {
  min-width: 0;
}

.gantt-task-name {
  font-size: 0.675rem;
  font-weight: 500;
  color: #374151;
  margin: 0;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.gantt-task-meta {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 2px;
}

.gantt-task-dates {
  font-size: 0.75rem;
  color: #6b7280;
  text-align: right;
}

.gantt-task-bar-area {
  position: relative;
  height: 100%;
}

.gantt-task-bar {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  height: 24px;
  border-radius: 6px;
  background: var(--gantt-primary);
  border: 1px solid rgba(255,255,255,0.2);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  padding: 0 8px;
  min-width: 4px;
}

.gantt-task-bar:hover {
  transform: translateY(-50%) scale(1.02);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.gantt-task-bar.progress {
  background: linear-gradient(90deg, var(--gantt-info) 0%, var(--gantt-info) var(--progress, 50%), #e5e7eb var(--progress, 50%), #e5e7eb 100%);
}

.gantt-task-bar-text {
  color: white;
  font-size: 0.7rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-shadow: 0 1px 1px rgba(0,0,0,0.3);
}

/* Status colors */
.gantt-task-bar.status-not_started { background: var(--status-not-started); }
.gantt-task-bar.status-in_progress { background: var(--status-in-progress); }
.gantt-task-bar.status-review { background: var(--status-review); }
.gantt-task-bar.status-completed { background: var(--status-completed); }
.gantt-task-bar.status-cancelled { background: var(--status-cancelled); }
.gantt-task-bar.status-on_hold { background: var(--status-on_hold); }

/* Progress indicator */
.gantt-progress {
  width: 60px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.gantt-progress-bar {
  flex: 1;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.gantt-progress-fill {
  height: 100%;
  background: var(--gantt-success);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.gantt-progress-text {
  font-size: 0.7rem;
  color: #6b7280;
  font-weight: 500;
  min-width: 30px;
}

/* Milestone marker */
.gantt-milestone {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-left: 8px solid var(--gantt-warning);
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  z-index: 5;
}

/* Today line */
.gantt-today-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--gantt-danger);
  z-index: 4;
}

.gantt-today-line::after {
  content: '';
  position: absolute;
  top: 0;
  left: -3px;
  width: 8px;
  height: 8px;
  background: var(--gantt-danger);
  border-radius: 50%;
}

/* Loading state */
.gantt-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #6b7280;
  font-size: 0.675rem;
}

/* Empty state */
.gantt-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #6b7280;
  text-align: center;
}

.gantt-empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

/* Responsive */
@media (max-width: 1024px) {
  .gantt-grid {
    grid-template-columns: 250px 1fr;
  }
  
  .gantt-row {
    grid-template-columns: 250px 1fr;
  }
  
  .gantt-sidebar-header,
  .gantt-row-content {
    grid-template-columns: 1fr 70px; /* no ID column on smaller screens too */
    gap: 0.75rem;
  }
}


@media (max-width: 768px) {
  .gantt-controls {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .gantt-date-navigation {
    order: -1;
    width: 100%;
    justify-content: center;
  }
}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Tasks Gantt') ?> Gantt Chart</h1>
    </div>

    <?php
      $canExport     = (function_exists('staff_can') && staff_can('export', 'general'));
      $canPrint      = (function_exists('staff_can') && staff_can('print', 'general'));
    ?>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#taskCreateModal">
        <i class="ti ti-plus"></i> New Task
      </button>
      
      <div class="btn-divider"></div>

      <div class="btn-group" role="group" aria-label="Views">
        <a href="<?= site_url('tasks?view=list') ?>" class="btn btn-outline-primary btn-header">
          <i class="ti ti-list-details me-1"></i> List
        </a>
        <a href="<?= site_url('tasks?view=kanban') ?>" class="btn btn-outline-primary btn-header">
          <i class="ti ti-layout-kanban me-1"></i> Kanban
        </a>
        <a href="<?= site_url('tasks?view=gantt') ?>" class="btn btn-primary btn-header">
          <i class="ti ti-chart-bar me-1"></i> Gantt
        </a>
      </div>

      <?php if ($canExport): ?>
        <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-export-table" title="Export to Excel">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
        <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-print-table" title="Print">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="gantt-container">
    <div class="gantt-header">
      <div class="gantt-controls">
        <div class="gantt-zoom-controls">
          <button class="gantt-zoom-btn active" data-zoom="days">Days</button>
          <button class="gantt-zoom-btn" data-zoom="weeks">Weeks</button>
          <button class="gantt-zoom-btn" data-zoom="months">Months</button>
        </div>
      </div>
      
      <div class="gantt-date-navigation">
        <button class="btn btn-light-primary btn-header" id="ganttPrev" title="Previous period">
          <i class="ti ti-chevron-left"></i>
        </button>
        <div class="gantt-current-range" id="ganttDateRange">Loading...</div>
        <button class="btn btn-light-primary btn-header" id="ganttNext" title="Next period">
          <i class="ti ti-chevron-right"></i>
        </button>
        <button class="btn btn-light-primary btn-header" id="ganttToday" title="Go to today">
          <i class="ti ti-calendar"></i> Today
        </button>
      </div>
    </div>

    <div class="gantt-grid">
      <div class="gantt-sidebar">
        <div class="gantt-sidebar-header">
          <div>Task Name</div>
          <div>Progress</div>
        </div>
        <div id="ganttSidebar" class="gantt-sidebar-content">
          <div class="gantt-loading">
            <i class="ti ti-loader-2 spinner"></i> Loading tasks...
          </div>
        </div>
      </div>
      
      <div class="gantt-chart-area">
        <div id="ganttChartHeader" class="gantt-chart-header">
          <div class="gantt-time-slots" id="ganttTimeSlots"></div>
        </div>
        <div id="ganttChartBody" class="gantt-chart-body">
          <div class="gantt-loading">Loading chart...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$CI = &get_instance();
$CI->load->view('tasks/modals/create_modal', compact('assignees','followers'));
?>

<div id="toastArea" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;"></div>

<script>
(function(){
  'use strict';
  
  // ==================== CONFIGURATION ====================
  const baseUrl = '<?= rtrim(site_url(), '/') ?>';
  const routes = { list: baseUrl + '/tasks/list_json' };
  
  const dayMs = 24 * 60 * 60 * 1000;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // ==================== STATE MANAGEMENT ====================
  let tasks = [];
  let currentZoom = 'days';
  let currentDate = new Date(today);
  let dateRange = { start: null, end: null };
  
  // ==================== DOM ELEMENTS ====================
  const elements = {
    sidebar: document.getElementById('ganttSidebar'),
    chartHeader: document.getElementById('ganttChartHeader'),
    chartBody: document.getElementById('ganttChartBody'),
    timeSlots: document.getElementById('ganttTimeSlots'),
    dateRange: document.getElementById('ganttDateRange'),
    prevBtn: document.getElementById('ganttPrev'),
    nextBtn: document.getElementById('ganttNext'),
    todayBtn: document.getElementById('ganttToday'),
    zoomBtns: document.querySelectorAll('.gantt-zoom-btn')
  };
  
  // ==================== UTILITY FUNCTIONS ====================
  function formatDate(date) {
    return date.toISOString().slice(0, 10);
  }
  
  function parseDate(dateString) {
    if (!dateString) return null;
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? null : date;
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0] || '').join('').toUpperCase().substring(0, 2);
  }
  
  function getStatusColor(status) {
    const colors = {
      'not_started': 'var(--status-not-started)',
      'in_progress': 'var(--status-in-progress)', 
      'review': 'var(--status-review)',
      'completed': 'var(--status-completed)',
      'cancelled': 'var(--status-cancelled)',
      'on_hold': 'var(--status-on_hold)'
    };
    return colors[status] || 'var(--gantt-gray)';
  }
  
  // ==================== DATE CALCULATIONS ====================
  function calculateDateRange() {
    const start = new Date(currentDate);
    const end = new Date(currentDate);
    
    switch(currentZoom) {
      case 'days':
        start.setDate(start.getDate() - 7);
        end.setDate(end.getDate() + 21);
        break;
      case 'weeks':
        start.setDate(start.getDate() - 14);
        end.setDate(end.getDate() + 56);
        break;
      case 'months':
        start.setMonth(start.getMonth() - 2);
        end.setMonth(end.getMonth() + 4);
        break;
    }
    
    dateRange = { start, end };
    updateDateRangeDisplay();
  }
  
  function updateDateRangeDisplay() {
    const options = { 
      month: 'short', 
      year: 'numeric',
      day: currentZoom === 'days' ? 'numeric' : undefined
    };
    
    const startStr = dateRange.start.toLocaleDateString('en-US', options);
    const endStr = dateRange.end.toLocaleDateString('en-US', options);
    elements.dateRange.textContent = `${startStr} - ${endStr}`;
  }
  
  // ==================== RENDERING FUNCTIONS ====================
  function renderTimeSlots() {
    elements.timeSlots.innerHTML = '';
    const current = new Date(dateRange.start);
    
    while (current <= dateRange.end) {
      const isWeekend = current.getDay() === 0 || current.getDay() === 6;
      const isToday = current.toDateString() === today.toDateString();
      
      let slotClass = 'gantt-time-slot';
      if (isWeekend) slotClass += ' weekend';
      if (isToday) slotClass += ' today';
      
      const dayNum = current.getDate();
      elements.timeSlots.innerHTML += `<div class="${slotClass}">${dayNum}</div>`;
      
      current.setDate(current.getDate() + 1);
    }
  }
  
function renderSidebar() {
  if (tasks.length === 0) {
    elements.sidebar.innerHTML = `
      <div class="gantt-empty">
        <i class="ti ti-chart-bar gantt-empty-icon"></i>
        <div>No tasks found</div>
        <small class="text-muted">Create your first task to see it here</small>
      </div>`;
    return;
  }
  
  let sidebarHTML = '';
  
  tasks.forEach(task => {
    const total   = task.checklist_stats?.total || 0;
    const done    = task.checklist_stats?.done  || 0;
    const percent = total > 0 ? (done / total) * 100 : 0;

    const assignee = task.assignee;
    const assigneeName = assignee ? assignee.full_name : '';
    const initials = getInitials(assigneeName);

    // avatar: image if available, otherwise colored initials
    let avatarHtml = `
      <div class="gantt-task-avatar" style="background: ${getStatusColor(task.status)};">
        ${initials}
      </div>`;
    
    if (assignee && assignee.profile_image) {
      avatarHtml = `
        <img src="${assignee.profile_image}"
             class="gantt-task-avatar"
             alt="${escapeHtml(assigneeName)}">`;
    }

    sidebarHTML += `
      <div class="gantt-row" data-task-id="${task.id}">
        <div class="gantt-row-content">
          <div class="gantt-task-info">
            ${avatarHtml}
            <div class="gantt-task-text">
              <div class="gantt-task-name" title="${escapeHtml(task.name || 'Untitled')}">
                ${escapeHtml(task.name || 'Untitled')}
              </div>
              <div class="gantt-task-meta">
                ${assignee ? escapeHtml(assigneeName) : 'Unassigned'}
              </div>
            </div>
          </div>
          <div class="gantt-progress">
            <div class="gantt-progress-bar">
              <div class="gantt-progress-fill" style="width: ${percent}%;"></div>
            </div>
            <div class="gantt-progress-text">
              ${done}/${total}
            </div>
          </div>
        </div>
      </div>`;
  });
  
  elements.sidebar.innerHTML = sidebarHTML;
}

  
  function renderChart() {
    if (tasks.length === 0) {
      elements.chartBody.innerHTML = `
        <div class="gantt-empty">
          <i class="ti ti-chart-bar gantt-empty-icon"></i>
          <div>No tasks to display</div>
        </div>`;
      return;
    }
    
    let chartHTML = '';
    const totalDays = Math.ceil((dateRange.end - dateRange.start) / dayMs);
    const todayPosition = Math.ceil((today - dateRange.start) / dayMs) * 40;
    
    // Add today line
    if (today >= dateRange.start && today <= dateRange.end) {
      chartHTML += `<div class="gantt-today-line" style="left: ${todayPosition}px;"></div>`;
    }
    
tasks.forEach(task => {
  const startDate = parseDate(task.start_date) || parseDate(task.due_date) || new Date();
  const dueDate   = parseDate(task.due_date)   || startDate;
  
  const startPosition = Math.max(0, Math.ceil((startDate - dateRange.start) / dayMs)) * 40;
  const duration      = Math.max(1, Math.ceil((dueDate - startDate) / dayMs) + 1);
  const width         = duration * 40;
  
  // Only render if task is within visible range
  if (startPosition + width > 0 && startPosition < totalDays * 40) {
    const total   = task.checklist_stats?.total || 0;
    const done    = task.checklist_stats?.done  || 0;
    const percent = total > 0 ? (done / total) * 100 : 0;

    const barClass = `gantt-task-bar status-${task.status} ${percent > 0 ? 'progress' : ''}`;
    
    chartHTML += `
      <div class="gantt-row" data-task-id="${task.id}">
        <div class="gantt-task-bar-area">
          <div class="${barClass}" 
               style="left: ${startPosition}px; width: ${width}px; --progress: ${percent}%;"
               title="${escapeHtml(task.name || 'Untitled')} - ${done}/${total} checklist items">
            <div class="gantt-task-bar-text">
              ${escapeHtml(task.name || 'Untitled')}
            </div>
          </div>
        </div>
      </div>`;
  }
});

    
    elements.chartBody.innerHTML = chartHTML;
  }
  
  function render() {
    calculateDateRange();
    renderTimeSlots();
    renderSidebar();
    renderChart();
  }
  
  // ==================== DATA LOADING ====================
  async function fetchJSON(url, options = {}) {
    try {
      options.headers = options.headers || {};
      options.headers['X-Requested-With'] = 'XMLHttpRequest';
      
      const response = await fetch(url, options);
      const contentType = response.headers.get('content-type') || '';
      
      if (contentType.includes('application/json')) {
        const data = await response.json();
        if (!response.ok || data.success === false) {
          throw new Error(data.message || `HTTP ${response.status}`);
        }
        return data;
      }
      throw new Error('Non-JSON response from server');
    } catch (error) {
      console.error('Fetch error:', error);
      throw error;
    }
  }
  
  async function loadTasks() {
    try {
      elements.sidebar.innerHTML = '<div class="gantt-loading"><i class="ti ti-loader-2 spinner"></i> Loading tasks...</div>';
      
      const formData = new FormData();
      formData.append('limit', '1000');
      
      const response = await fetchJSON(routes.list, {
        method: 'POST',
        body: formData
      });
      
tasks = (response.data?.rows || []).map(task => {
  // Process assignee data
  const assignee = task.assignee_id ? {
    id: task.assignee_id,
    full_name: task.assignee_name || task.assignee_fullname || 'Unknown User',
    profile_image: task.assignee_profile_image || null
  } : null;

  // Normalize profile image URL if needed
  if (assignee && assignee.profile_image && !assignee.profile_image.startsWith('http')) {
    assignee.profile_image = `${baseUrl}/uploads/users/profile/${assignee.profile_image}`;
  }
  
  // Process followers data
  let followers = [];
  if (task.followers && Array.isArray(task.followers)) {
    followers = task.followers.map(follower => ({
      id: follower.id || follower.user_id,
      full_name: follower.full_name || follower.name || 'Unknown User',
      profile_image: follower.profile_image || null
    }));
  }

  // Process checklist stats
  const checklist_stats = task.checklist_stats || {
    total: task.checklist_total || 0,
    done: task.checklist_done || 0,
    percent: task.checklist_percent || 0
  };
  
  return {
    ...task,
    assignee,
    followers,
    checklist_stats,
    start_date: task.start_date || task.startdate || task.dateadded,
    due_date: task.due_date || task.duedate || task.deadline
  };
});

      
      render();
      
    } catch (error) {
      console.error('Failed to load tasks:', error);
      elements.sidebar.innerHTML = `
        <div class="gantt-empty">
          <i class="ti ti-alert-triangle gantt-empty-icon"></i>
          <div>Failed to load tasks</div>
          <small class="text-muted">${error.message}</small>
        </div>`;
    }
  }
  
  // ==================== EVENT HANDLERS ====================
  function setupEventListeners() {
    // Zoom controls
    elements.zoomBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        elements.zoomBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentZoom = btn.dataset.zoom;
        render();
      });
    });
    
    // Date navigation
    elements.prevBtn.addEventListener('click', () => {
      switch(currentZoom) {
        case 'days': currentDate.setDate(currentDate.getDate() - 7); break;
        case 'weeks': currentDate.setDate(currentDate.getDate() - 14); break;
        case 'months': currentDate.setMonth(currentDate.getMonth() - 1); break;
      }
      render();
    });
    
    elements.nextBtn.addEventListener('click', () => {
      switch(currentZoom) {
        case 'days': currentDate.setDate(currentDate.getDate() + 7); break;
        case 'weeks': currentDate.setDate(currentDate.getDate() + 14); break;
        case 'months': currentDate.setMonth(currentDate.getMonth() + 1); break;
      }
      render();
    });
    
    elements.todayBtn.addEventListener('click', () => {
      currentDate = new Date(today);
      render();
    });
    
    // Task click handlers
    elements.chartBody.addEventListener('click', (e) => {
      const taskBar = e.target.closest('.gantt-task-bar');
      if (taskBar) {
        const taskId = taskBar.closest('.gantt-row').dataset.taskId;
        window.open(`${baseUrl}/tasks/view/${taskId}`, '_blank');
      }
    });
  }
  
  // ==================== INITIALIZATION ====================
  function init() {
    setupEventListeners();
    loadTasks();
  }
  
  document.addEventListener('DOMContentLoaded', init);
})();
</script>
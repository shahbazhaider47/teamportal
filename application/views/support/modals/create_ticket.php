<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php

  // Settings we must honor in the form UI
  $openAllDepts     = function_exists('get_setting') ? (get_setting('support_staff_can_open_all_departments', 'yes') ?: 'yes') : 'yes';
  $whoCanAddWatchers= function_exists('get_setting') ? (get_setting('support_user_added_watchers', 'both') ?: 'both') : 'both';
  $maxFilesSetting  = (int)((function_exists('get_setting') ? get_setting('support_max_attachments') : 5) ?? 5);
  $allowedCsv       = (string)((function_exists('get_setting') ? get_setting('support_allowed_mime_types') : 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip') ?? 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip');
  
  $canAssign    = function_exists('staff_can') ? staff_can('view_global','support') : false;
  
  // Build accept attribute from CSV
  $allowedExtArr = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
  $acceptAttr    = '';
  if (!empty($allowedExtArr)) {
    $acceptList = array_map(static function($x) { $x = ltrim(strtolower($x), '.'); return '.' . $x; }, $allowedExtArr);
    $acceptAttr = implode(',', $acceptList);
  }

  // From controller (never call $this->session in a view)
  $departments      = $departments      ?? [];
  $users_minimal    = $users_minimal    ?? [];
  $currentUserId    = isset($current_user_id) ? (int)$current_user_id : 0;
  $recent_tickets   = $recent_tickets   ?? [];

  // Only users with this capability can file "on behalf of"
  $canCreateForOthers = function_exists('staff_can') ? staff_can('view_global', 'support') : false;

  // Small helper to format a display name
  if (!function_exists('view_fullname')) {
    function view_fullname(array $u) {
      $name = trim(($u['fullname'] ?? '') ?: trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? '')));
      return $name !== '' ? $name : ('User #'.((int)($u['id'] ?? 0)));
    }
  }

  // Build a map of user avatars (assuming uploads/users/profile/{filename})
  $avatar_url = function($profile_image) {
    $img = trim((string)($profile_image ?? ''));
    return $img !== '' ? base_url('uploads/users/profile/'.$img) : base_url('uploads/users/profile/default.png');
  };


?>

<div class="container-fluid support-module">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Support Tickets') ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">

      <div class="btn-divider"></div>
      <a href="<?= base_url('support'); ?>" class="btn btn-outline-primary btn-header">
        <i class="ti ti-arrow-left"></i> Go Back
      </a>
      
    </div>
  </div>
  
  <div class="row justify-content-center">
    <!-- Left: Form -->
    <div class="col-12 col-xl-8">
      <div class="card">
        <div class="card-body">
          <form method="post" action="<?= base_url('support/create'); ?>" enctype="multipart/form-data" class="row g-3 app-form" id="createTicketForm" data-open-all-depts="<?= html_escape($openAllDepts) ?>" data-watcher-who="<?= html_escape($whoCanAddWatchers) ?>" data-max-files="<?= (int)$maxFilesSetting ?>" data-accept="<?= html_escape($acceptAttr) ?>">
            <input type="hidden" name="body" id="bodyField">
            <!-- SUBJECT -->
            <div class="col-12">
              <label class="form-label">Ticket Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" class="form-control" required minlength="3" placeholder="e.g., Request for new headphone, payslip">
            </div>

            <!-- DEPARTMENT -->
            <div class="col-12 col-md-5">
              <label class="form-label">Department <span class="text-danger">*</span></label>
              <select name="emp_department" id="departmentSelect" class="form-select" required <?= $openAllDepts === 'no' ? 'data-locked="1"' : '' ?>>
                <option value="">Select department</option>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= (int)$d['id'] ?>"><?= html_escape($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($openAllDepts === 'no'): ?>
                <div class="form-text">Department is restricted by policy and will match the requester’s department.</div>
              <?php endif; ?>
            </div>

            <!-- PRIORITY -->
            <div class="col-12 col-md-3">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-select">
                <option value="normal" selected>Normal</option>
                <option value="low">Low</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <!-- TAGS -->
            <div class="col-12 col-md-4">
              <label class="form-label">Tags <small>(comma separated)</small></label>
              <input type="text" name="tags" class="form-control" placeholder="hr, payroll, admin">
            </div>

            <!-- REQUESTER -->
            <?php if ($canCreateForOthers && !empty($users_minimal)): ?>
              <div class="col-12 col-md-6">
                <label class="form-label">Requester</label>
                <select name="requester_id" id="requesterSelect" class="form-select">
                 <option value="">Select user/requester</option>  
                    <?php foreach ($users_minimal as $u):
                    if (isset($u['is_active']) && (int)$u['is_active'] !== 1) continue;
                    $uid   = (int)($u['id'] ?? 0);
                    $label = view_fullname($u);
                    $dept  = (int)($u['emp_department'] ?? 0);
                  ?>
                    <option
                      value="<?= $uid ?>"
                      data-dept="<?= $dept ?>"
                      data-avatar="<?= html_escape($avatar_url($u['profile_image'] ?? '')) ?>"
                      <?= $uid === $currentUserId ? 'selected' : '' ?>
                    >
                      <?= html_escape($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Pick another requester if you're filing on someone's behalf.</div>
              </div>
            <?php else: ?>
              <?php
                // When filing for self, still expose a hidden input with requester dept (to sync department if locked)
                $selfDept = 0;
                foreach ($users_minimal as $u) {
                  if ((int)($u['id'] ?? 0) === $currentUserId) { $selfDept = (int)($u['emp_department'] ?? 0); break; }
                }
              ?>
              <input type="hidden" name="requester_id" id="requesterSelect" value="<?= (int)$currentUserId ?>" data-dept="<?= (int)$selfDept ?>">
            <?php endif; ?>

            <!-- WATCHERS (filtered by Department) -->
            <?php if ((in_array($whoCanAddWatchers, ['requester','both'], true)) || $canAssign): ?>
            <div class="col-12 col-md-7" id="watchersBlock">
              <label class="form-label mb-0 mt-0 d-flex align-items-center justify-content-between">
                <span>Add Watchers <small class="text-muted">(Optional — Add from same department only)</small></span>
              </label>
              <small class="text-muted">Please Note: Ticket watchers can only view your case or issue and will be infromed of each activity. You can add your teamlead, manager or a person who should be notified.</small>
              <select name="watchers[]" id="watchersSelect" class="form-select" multiple size="6">
                <?php foreach ($users_minimal as $u):
                  if (isset($u['is_active']) && (int)$u['is_active'] !== 1) continue;
                  $uid   = (int)($u['id'] ?? 0);
                  if ($uid === $currentUserId) continue;
                  $label = view_fullname($u);
                  $dept  = (int)($u['emp_department'] ?? 0);
                ?>
                  <option
                    value="<?= $uid ?>"
                    data-dept="<?= $dept ?>"
                    data-avatar="<?= html_escape($avatar_url($u['profile_image'] ?? '')) ?>"
                    data-name="<?= html_escape($label) ?>"
                  >
                    <?= html_escape($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-5" id="watchersChipsWrap">
              <!-- Selected Watchers Chips -->
              <div id="watchersChips" class="d-flex flex-wrap gap-2 mt-2"></div>
            </div>
            <?php else: ?>
              <!-- Watchers hidden because policy allows only assignee to add watchers -->
              <input type="hidden" id="watchersSelect" value="">
            <?php endif; ?>

            <!-- DESCRIPTION (rich editor) -->
            <div class="col-12">
              <label class="form-label">Message / Description</label>

              <!-- Modern Rich Text Editor -->
              <div class="rich-text-editor border rounded">
                <!-- Toolbar -->
                <div class="editor-toolbar d-flex flex-wrap align-items-center gap-1 p-2 border-bottom bg-light-primary small">
                  <!-- Font Family -->
                  <select class="form-select form-select-sm small" style="width: 180px;" id="fontFamily">
                    <option value="">Font</option>
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="'Helvetica Neue', Helvetica, sans-serif">Helvetica</option>
                    <option value="'Times New Roman', Times, serif">Times New Roman</option>
                    <option value="'Courier New', Courier, monospace">Courier New</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="Verdana, sans-serif">Verdana</option>
                  </select>

                  <!-- Font Size -->
                  <select class="form-select form-select-sm" style="width: 100px;" id="fontSize">
                    <option value="">Size</option>
                    <option value="1">Small</option>
                    <option value="3" selected>Normal</option>
                    <option value="5">Large</option>
                    <option value="7">X-Large</option>
                  </select>
                  <!-- Text Formatting -->
                  <div class="btn-group" role="group">
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="bold" title="Bold">
                      <i class="ti ti-bold"></i>
                    </button>
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="italic" title="Italic">
                      <i class="ti ti-italic"></i>
                    </button>
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="underline" title="Underline">
                      <i class="ti ti-underline"></i>
                    </button>
                  </div>

                  <!-- Text Alignment -->
                  <div class="btn-group ms-1" role="group">
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyLeft" title="Align Left">
                      <i class="ti ti-align-left"></i>
                    </button>
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyCenter" title="Align Center">
                      <i class="ti ti-align-center"></i>
                    </button>
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyRight" title="Align Right">
                      <i class="ti ti-align-right"></i>
                    </button>
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyFull" title="Justify">
                      <i class="ti ti-align-justified"></i>
                    </button>
                  </div>

                  <!-- Lists -->
                  <div class="btn-group ms-1" role="group">
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="insertUnorderedList" title="Bulleted list">
                      <i class="ti ti-list"></i>
                    </button>
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="insertOrderedList" title="Numbered list">
                      <i class="ti ti-list-numbers"></i>
                    </button>
                  </div>

                  <!-- Colors -->
                  <div class="btn-group ms-1" role="group">
                    <div class="dropdown">
                      <button class="btn btn-ssm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Text Color">
                        <i class="ti ti-palette"></i>
                      </button>
                      <div class="dropdown-menu p-2">
                        <div class="d-flex flex-wrap gap-1" style="width: 150px;">
                          <?php 
                          $colors = [
                            '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3', '#ffffff',
                            '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff', '#ff00ff',
                            '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#cfe2f3', '#d9d2e9', '#ead1dc'
                          ];
                          foreach($colors as $color): ?>
                            <button 
                              type="button" 
                              class="color-btn border rounded" 
                              style="width: 20px; height: 20px; background-color: <?= $color ?>;"
                              data-color="<?= $color ?>"
                              data-cmd="foreColor"
                              title="<?= $color ?>"
                            ></button>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Links -->
                  <div class="btn-group ms-1" role="group">
                    <button class="btn btn-ssm btn-outline-secondary" type="button" id="linkBtn" title="Insert link">
                      <i class="ti ti-link"></i>
                    </button>
                  </div>

                  <!-- Clear Formatting -->
                  <div class="btn-group ms-1" role="group">
                    <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="removeFormat" title="Clear formatting">
                      <i class="ti ti-eraser"></i>
                    </button>
                  </div>
                </div>

                <!-- Editable area -->
                <div 
                  id="editor" 
                  class="editor-content form-control border-0" 
                  style="min-height: 220px; max-height: 400px; overflow-y: auto; padding: 12px;"
                  contenteditable="true" 
                  placeholder="Provide details about your issue or request..."
                ></div>
              </div>

            </div>

            <!-- ATTACHMENTS -->
            <div class="col-12">
              <label class="form-label">Attachments</label>
              <div class="file-upload-area border rounded p-3 text-center">
                <input type="file" name="attachments[]" class="form-control d-none" id="attachments" multiple <?= $acceptAttr ? 'accept="'.html_escape($acceptAttr).'"' : '' ?>>
                <div class="mb-2">
                  <i class="ti ti-cloud-upload display-4 text-muted"></i>
                </div>
                <p class="mb-2">Drag & drop files here or click to browse</p>
                <button type="button" class="btn btn-sm btn-outline-primary" id="browseFilesBtn">Browse Files</button>
                <div class="form-text mt-2">
                  Max files: <?= (int)$maxFilesSetting ?>.
                  Allowed: <?= html_escape($allowedCsv) ?>
                </div>
              </div>
              <div id="filePreview" class="mt-2"></div>
            </div>

            <!-- SUBMIT -->
            <div class="col-12 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-primary btn-sm" id="saveDraftBtn">
                <i class="ti ti-device-floppy"></i> Save Draft
              </button>
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="ti ti-send"></i> Submit Ticket
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Tips -->
      <div class="alert alert-info mt-3">
        <i class="ti ti-info-circle"></i>
        Tickets may be auto-assigned to a department handler and auto-closed after
        <?= (int)(get_setting('support_auto_close_days') ?? 5) ?> days of inactivity.
      </div>
    </div>

    <!-- Right: Recent tickets -->
    <div class="col-12 col-xl-4">
      <div class="card shadow-sm">
        <div class="card-header d-flex bg-light-primary justify-content-between align-items-center">
          <strong><i class="ti ti-history me-1"></i> Your Recent Tickets</strong>
          <a href="<?= base_url('support'); ?>" class="btn btn-light-primary btn-header">View all</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($recent_tickets)): ?>
            <div class="p-3 small text-muted">No recent tickets.</div>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach (array_slice($recent_tickets, 0, 6) as $t):
                $sid = (int)($t['id'] ?? 0);
                $sub = (string)($t['subject'] ?? '');
                $st  = (string)($t['status'] ?? 'open');
                $la  = (string)($t['last_activity_at'] ?? '');
                $pr  = (string)($t['priority'] ?? 'normal');
              ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div class="me-2">
                    <a class="text-decoration-none" href="<?= base_url('support/view/'.$sid); ?>">
                      <?= html_escape($sub) ?>
                    </a>
                    <div class="small text-muted">
                      #<?= $sid ?> · <?= html_escape($pr) ?> · <?= html_escape(str_replace('_',' ',$st)) ?> · <?= html_escape($la) ?>
                    </div>
                  </div>
                  <span class="badge text-bg-secondary text-capitalize"><?= html_escape(str_replace('_',' ',$st)) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  // ---------- Rich text editor ----------
  const editor = document.getElementById('editor');
  const bodyField = document.getElementById('bodyField');
  const form = document.getElementById('createTicketForm');

  // Initialize editor with default content
  if (editor.innerHTML.trim() === '') {
    editor.innerHTML = '<p><br></p>';
  }

  // Toolbar button handlers
  document.querySelectorAll('[data-cmd]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const cmd = btn.getAttribute('data-cmd');
      const value = btn.getAttribute('data-value') || btn.getAttribute('data-color') || null;

      if (cmd === 'foreColor' && value) {
        document.execCommand(cmd, false, value);
      } else {
        document.execCommand(cmd, false, null);
      }

      editor.focus();
      e.preventDefault();
    });
  });

  // Font family selector
  document.getElementById('fontFamily').addEventListener('change', function() {
    if (this.value) {
      document.execCommand('fontName', false, this.value);
    }
    editor.focus();
  });

  // Font size selector
  document.getElementById('fontSize').addEventListener('change', function() {
    if (this.value) {
      document.execCommand('fontSize', false, this.value);
    }
    editor.focus();
  });

  // Link button
  document.getElementById('linkBtn').addEventListener('click', () => {
    const url = prompt('Enter URL (https://...)', 'https://');
    if (url) {
      document.execCommand('createLink', false, url);
    }
    editor.focus();
  });

  // Form submission
  form.addEventListener('submit', () => {
    bodyField.value = editor.innerHTML.trim();
  });

  // Save draft functionality
  const saveDraftBtn = document.getElementById('saveDraftBtn');
  if (saveDraftBtn) {
    saveDraftBtn.addEventListener('click', () => {
      // In a real implementation, you would save to localStorage or send to server
      const draftData = {
        subject: form.querySelector('input[name="subject"]').value,
        body: editor.innerHTML.trim(),
        department: form.querySelector('select[name="emp_department"]').value,
        timestamp: new Date().toISOString()
      };

      localStorage.setItem('ticketDraft', JSON.stringify(draftData));

      // Show feedback
      const originalText = saveDraftBtn.innerHTML;
      saveDraftBtn.innerHTML = '<i class="ti ti-check"></i> Draft Saved';
      saveDraftBtn.disabled = true;

      setTimeout(() => {
        saveDraftBtn.innerHTML = originalText;
        saveDraftBtn.disabled = false;
      }, 2000);
    });
  }

  // Load draft if exists
  const savedDraft = localStorage.getItem('ticketDraft');
  if (savedDraft) {
    try {
      const draft = JSON.parse(savedDraft);
      if (confirm('A saved draft was found. Would you like to restore it?')) {
        form.querySelector('input[name="subject"]').value = draft.subject || '';
        editor.innerHTML = draft.body || '<p><br></p>';
        if (draft.department) {
          form.querySelector('select[name="emp_department"]').value = draft.department;
        }
      }
    } catch(e) {
      console.error('Error loading draft:', e);
    }
  }

  // ---------- File upload improvements (enforce settings) ----------
  const fileInput = document.getElementById('attachments');
  const filePreview = document.getElementById('filePreview');
  const browseFilesBtn = document.getElementById('browseFilesBtn');
  const fileUploadArea = document.querySelector('.file-upload-area');

  // Accept & max files from settings
  const maxFiles = parseInt(form.getAttribute('data-max-files') || '0', 10); // 0 = unlimited
  const acceptList = (form.getAttribute('data-accept') || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);

  if (browseFilesBtn && fileInput) {
    browseFilesBtn.addEventListener('click', () => {
      fileInput.click();
    });
  }

  // Drag and drop functionality
  if (fileUploadArea && fileInput) {
    fileUploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      fileUploadArea.classList.add('bg-primary-subtle');
    });

    fileUploadArea.addEventListener('dragleave', () => {
      fileUploadArea.classList.remove('bg-primary-subtle');
    });

    fileUploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      fileUploadArea.classList.remove('bg-primary-subtle');

      if (e.dataTransfer.files.length) {
        // Merge with existing but enforce max files
        const incoming = Array.from(e.dataTransfer.files);
        const current  = fileInput.files ? Array.from(fileInput.files) : [];
        const merged   = current.concat(incoming);

        const limited = (maxFiles > 0) ? merged.slice(0, maxFiles) : merged;

        const dt = new DataTransfer();
        limited.forEach(f => {
          if (isAllowedExt(f, acceptList)) dt.items.add(f);
        });
        fileInput.files = dt.files;
        updateFilePreview();
      }
    });
  }

  function isAllowedExt(file, accept) {
    if (!accept || !accept.length) return true;
    const name = (file && file.name ? file.name : '').toLowerCase();
    const ext  = '.' + (name.split('.').pop() || '');
    return accept.includes(ext);
  }

  function enforceMaxAndAccept() {
    if (!fileInput.files || !fileInput.files.length) return;
    let files = Array.from(fileInput.files);

    // Filter by accept list
    if (acceptList.length) {
      files = files.filter(f => isAllowedExt(f, acceptList));
    }

    // Enforce max files
    if (maxFiles > 0) {
      files = files.slice(0, maxFiles);
    }

    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
  }

  function updateFilePreview() {
    filePreview.innerHTML = '';
    if (!fileInput.files || !fileInput.files.length) return;

    enforceMaxAndAccept();

    const wrap = document.createElement('div');
    wrap.className = 'd-flex flex-column gap-2';

    Array.from(fileInput.files).forEach((f, index) => {
      const row = document.createElement('div');
      row.className = 'd-flex align-items-center justify-content-between border rounded p-2';

      const fileInfo = document.createElement('div');
      fileInfo.className = 'd-flex align-items-center gap-2';

      // File icon based on type
      const icon = document.createElement('i');
      icon.className = getFileIconClass(f.type || f.name);

      const fileDetails = document.createElement('div');
      const fileName = document.createElement('div');
      fileName.className = 'small fw-medium';
      fileName.textContent = f.name;

      const fileSize = document.createElement('div');
      fileSize.className = 'text-muted small';
      fileSize.textContent = formatFileSize(f.size);

      fileDetails.appendChild(fileName);
      fileDetails.appendChild(fileSize);

      fileInfo.appendChild(icon);
      fileInfo.appendChild(fileDetails);

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn btn-sm btn-outline-danger';
      removeBtn.innerHTML = '<i class="ti ti-x"></i>';
      removeBtn.addEventListener('click', () => {
        // Create a new FileList without this file
        const dt = new DataTransfer();
        Array.from(fileInput.files).forEach((file, i) => {
          if (i !== index) dt.items.add(file);
        });
        fileInput.files = dt.files;
        updateFilePreview();
      });

      row.appendChild(fileInfo);
      row.appendChild(removeBtn);
      wrap.appendChild(row);
    });

    filePreview.appendChild(wrap);
  }

  function getFileIconClass(fileTypeOrName) {
    const type = (fileTypeOrName || '').toLowerCase();
    if (type.includes('image') || type.endsWith('.jpg') || type.endsWith('.jpeg') || type.endsWith('.png') || type.endsWith('.gif') || type.endsWith('.webp')) return 'ti ti-photo text-primary';
    if (type.includes('pdf') || type.endsWith('.pdf')) return 'ti ti-file-text text-danger';
    if (type.includes('word') || type.includes('doc') || type.endsWith('.doc') || type.endsWith('.docx')) return 'ti ti-file-word text-primary';
    if (type.includes('excel') || type.includes('xls') || type.endsWith('.xls') || type.endsWith('.xlsx') || type.endsWith('.csv')) return 'ti ti-file-spreadsheet text-success';
    if (type.includes('zip') || type.includes('rar') || type.includes('tar') || type.endsWith('.zip') || type.endsWith('.rar') || type.endsWith('.tar')) return 'ti ti-file-zip text-warning';
    return 'ti ti-file text-secondary';
  }

  function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
  }

  if (fileInput) {
    fileInput.addEventListener('change', updateFilePreview);
  }

  // ---------- Watchers: filter + chips ----------
  const deptSel = document.getElementById('departmentSelect');
  const watchersSel = document.getElementById('watchersSelect');
  const chipsWrap = document.getElementById('watchersChips');
  const requesterSel = document.getElementById('requesterSelect'); // may be hidden input (id still present)

  function currentRequesterDept() {
    if (!requesterSel) return 0;
    if (requesterSel.tagName === 'SELECT') {
      const opt = requesterSel.options[requesterSel.selectedIndex];
      return parseInt((opt && (opt.dataset.dept || '0')) || '0', 10);
    }
    return parseInt(requesterSel.getAttribute('data-dept') || '0', 10);
  }

  // If policy says staff cannot open to all departments, lock department to requester’s dept
  function syncDeptLockByPolicy() {
    if (!deptSel) return;
    const openAll = (form.getAttribute('data-open-all-depts') || 'yes') === 'yes';
    if (openAll) return;

    const rDept = currentRequesterDept();
    if (rDept > 0) {
      deptSel.value = String(rDept);
    }
    // Disable changing
    deptSel.setAttribute('disabled', 'disabled');
  }

  function filterWatchers() {
    if (!watchersSel) return;
    const deptId = parseInt(deptSel.value || '0', 10);
    const requesterId = parseInt((requesterSel && requesterSel.tagName === 'SELECT' ? requesterSel.value : requesterSel.value) || '0', 10);
    Array.from(watchersSel.options).forEach(opt => {
      const oDept = parseInt(opt.dataset.dept || '0', 10);
      const oId = parseInt(opt.value || '0', 10);
      // show only same department and not the requester
      opt.hidden = !(deptId && oDept === deptId) || (oId === requesterId);
      if (opt.hidden && opt.selected) opt.selected = false;
    });
    renderWatcherChips();
  }

  function renderWatcherChips() {
    if (!chipsWrap || !watchersSel) return;
    chipsWrap.innerHTML = '';
    const selected = Array.from(watchersSel.selectedOptions);
    selected.forEach(opt => {
      const chip = document.createElement('div');
      chip.className = 'd-inline-flex align-items-center gap-2 border rounded-pill px-2 py-1';
      const img = document.createElement('img');
      img.src = opt.dataset.avatar || '';
      img.alt = 'avatar';
      img.width = 20; img.height = 20; img.style.objectFit='cover'; img.className='rounded-circle';
      const name = document.createElement('span'); name.className='small'; name.textContent = opt.dataset.name || opt.textContent;
      const close = document.createElement('button'); close.type='button'; close.className='btn btn-sm btn-link p-0 ms-1 text-danger'; close.innerHTML='&times;';
      close.addEventListener('click', () => { opt.selected = false; renderWatcherChips(); });
      chip.appendChild(img); chip.appendChild(name); chip.appendChild(close);
      chipsWrap.appendChild(chip);
    });
  }

  if (deptSel) deptSel.addEventListener('change', filterWatchers);
  if (requesterSel && requesterSel.tagName === 'SELECT') requesterSel.addEventListener('change', () => {
    syncDeptLockByPolicy(); // keep dept in sync with requester when policy locks it
    filterWatchers();
  });
  if (watchersSel) watchersSel.addEventListener('change', renderWatcherChips);

  // Initialize on load
  syncDeptLockByPolicy();
  if (watchersSel) {
    // If policy disallows requester to add watchers, the block is hidden by PHP already
    filterWatchers();
  }
})();
</script>

<style>
.rich-text-editor {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}

.editor-toolbar {
  border-bottom: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.editor-content:focus {
  outline: none;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.color-btn:hover {
  transform: scale(1.1);
  transition: transform 0.1s;
}

.file-upload-area {
  border: 2px dashed #dee2e6;
  transition: all 0.3s ease;
}

.file-upload-area:hover {
  border-color: #0d6efd;
  background-color: #f8f9fa !important;
}

.bg-primary-subtle {
  background-color: #e7f1ff !important;
  border-color: #0d6efd !important;
}
</style>

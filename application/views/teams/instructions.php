<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
  <style>
    /* ===== Team Guides (scoped) ===== */
    .tg-wrap { position: relative; }
    .tg-rail {
      position: absolute; left: 18px; top: 0; bottom: 0;
      width: 2px; background: var(--bs-border-color, #e9ecef);
    }
    .tg-item {
      position: relative; padding-left: 56px; padding-right: 8px;
    }
    .tg-dot {
      position: absolute; left: 12px; top: 18px;
      width: 14px; height: 14px; border-radius: 50%;
      background: #fff; border: 2px solid var(--bs-primary,#0d6efd);
      box-shadow: 0 0 0 4px #fff; /* halo against the rail */
    }
.tg-card {
  border: 1px solid var(--bs-border-color,#e9ecef);
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(16,24,40,0.06);
  padding: 14px 16px;
  background: #fff;
  overflow: visible;
  position: relative;
}
    .tg-card + .tg-item { margin-top: 14px; }
.tg-head {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  overflow: visible;
  position: relative;
}
    .tg-title { margin: 0; font-size: 14px; font-weight: 600; }
    .tg-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .tg-badge {
      border: 1px solid var(--bs-border-color,#e9ecef);
      background: #f8f9fa; color: #6c757d;
      padding: 2px 8px; border-radius: 999px; font-size: 11px;
    }
    .tg-pin {
      color: #b36d00; background: #fff7e6; border-color: #ffe8bf;
    }
    .tg-author { color:#6c757d; font-size:12px; }
    .tg-body { margin-top: 8px; font-size: 13px; color: #3f4254; line-height: 1.55; }
    .tg-actions .dropdown-menu { min-width: 160px; }
    .tg-avatar {
      width: 28px; height: 28px; border-radius: 50%; object-fit: cover;
      border: 1px solid var(--bs-border-color,#e9ecef);
    }
    .tg-empty {
      text-align:center; color:#6c757d; padding: 56px 12px;
    }
    .tg-empty .icon { font-size:28px; opacity:.6; }
    .tg-readmore { border:0; background:transparent; color:#0d6efd; padding:0; font-size:12px; }
    .tg-body[data-collapsed="true"] { display:-webkit-box; -webkit-line-clamp: 6; -webkit-box-orient: vertical; overflow:hidden; }
    @media (max-width: 576px) {
      .tg-item { padding-left: 48px; }
      .tg-rail { left: 16px; }
      .tg-dot { left: 10px; }
    }
  </style>
  
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title">
        <?= html_escape($page_title) ?>
        <small><?= isset($teamName) && $teamName ? ' - ' . html_escape($teamName) : '' ?></small>
      </h1>
      <div class="d-flex small align-items-center gap-1">
        <span class="badge bg-light-primary border">Members: <strong><?= count($teamUsers ?? []) ?></strong></span>
        <span class="badge bg-primary border">Team Lead: <strong><?= $teamLeadName ? html_escape($teamLeadName) : '—' ?></strong></span>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canAdd = staff_can('guide', 'teams');
        $canExport = staff_can('export', 'general');
        $canPrint  = staff_can('print', 'general');
      ?>

      <button
        type="button"
        class="btn <?= $canAdd ? 'btn-primary' : 'btn-disabled' ?> btn-header"
        <?= $canAdd ? 'data-bs-toggle="modal" data-bs-target="#addGuideModal"' : 'disabled' ?>
        title="Add Instruction"
      >
        <i class="fas fa-plus me-1"></i> Add Instruction
      </button>

      <div class="btn-divider"></div>

      <a href="<?= site_url('teams/my_team') ?>"
         id="btn-manage-users"
         class="btn btn-light-primary btn-header">
          <i class="fas fa-arrow-left me-1"></i> Go Back
      </a>

      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="team-instructions-<?= (int)($teamId ?? 0) ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                title="Print">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Timeline Card -->
<!-- Timeline Card (refined) -->
<div class="card">

  <div class="card-body tg-wrap">
    <?php if (empty($guides)): ?>
      <div class="tg-empty">
        <div class="icon mb-2"><i class="ti ti-notes"></i></div>
        <h6 class="fw-normal mb-1">No instructions yet</h6>
        <div class="small">When instructions are added, they’ll appear here—newest on top and pinned entries first.</div>
      </div>
    <?php else: ?>

      <?php foreach ($guides as $g):
        $author     = trim(($g['firstname'] ?? '').' '.($g['lastname'] ?? ''));
        $avatarFile = $g['profile_image'] ?? '';
        $avatar     = $avatarFile ? base_url('uploads/users/profile/' . $avatarFile)
                                  : base_url('assets/images/default-avatar.png');
        $isPinned   = (int)$g['is_pinned'] === 1;
        $created    = date('M d, Y h:i A', strtotime($g['created_at']));
      ?>

        <div class="tg-card mb-4">
          <div class="tg-head">
            <div class="d-flex align-items-center gap-2">
              <img src="<?= html_escape($avatar) ?>" alt="<?= html_escape($author ?: 'User') ?>"
                   class="tg-avatar"
                   onerror="this.onerror=null;this.src='<?= base_url('assets/images/default-avatar.png') ?>';">
              <div>
                <h6 class="tg-title mb-2">
                  <?= html_escape($g['title']) ?>
                  <?= $isPinned ? '<span class="tg-badge tg-pin me-1"><i class="ti ti-pinned-filled me-1"></i>Pinned</span>' : '' ?>
                </h6>
              </div>
            </div>

            <?php if (!empty($canAdd) && $canAdd): ?>
            <div class="tg-actions dropdown">
              <button class="btn btn-ssm" type="button"
                      data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                <i class="ti ti-dots-vertical"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" style="min-width:170px;">
            
              <!-- Edit -->
              <button type="button"
                      class="dropdown-item btn-edit-guide d-flex align-items-center small gap-2"
                      data-id="<?= (int)$g['id'] ?>"
                      data-title="<?= html_escape($g['title']) ?>"
                      data-body="<?= html_escape($g['body']) ?>"
                      data-pinned="<?= (int)$g['is_pinned'] ?>"
                      data-files="<?= html_escape(json_encode(json_decode($g['files'] ?? '[]', true) ?: [])) ?>">
                <i class="ti ti-edit text-primary" style="font-size:14px;width:16px;"></i>
                <span>Edit</span>
              </button>
            
              <!-- Pin / Unpin -->
              <form action="<?= site_url('teams/guide_pin/' . (int)$g['id']) ?>"
                    method="post" class="app-form">
                <button type="submit"
                        class="dropdown-item d-flex align-items-center small gap-2">
                  <i class="ti <?= $isPinned ? 'ti-pin' : 'ti-pinned-filled' ?> text-warning"
                     style="font-size:14px;width:16px;"></i>
                  <span><?= $isPinned ? 'Unpin' : 'Pin to Top' ?></span>
                  <input type="hidden" name="is_pinned" value="<?= $isPinned ? 0 : 1 ?>">
                </button>
              </form>
            
              <div class="dropdown-divider my-1"></div>
            
              <!-- Delete -->
              <form action="<?= site_url('teams/guide_delete/' . (int)$g['id']) ?>"
                    method="post"
                    onsubmit="return confirm('Delete this instruction?');"
                    class="app-form">
                <button type="submit"
                        class="dropdown-item d-flex align-items-center small gap-2 text-danger">
                  <i class="ti ti-trash" style="font-size:14px;width:16px;"></i>
                  <span>Delete</span>
                </button>
              </form>
            
              </div>
            </div>
            <?php endif; ?>
            
          </div>
            <div class="tg-meta mt-2">
            <?php if ($author): ?>
            <span class="tg-author text-primary">By <?= html_escape($author) ?></span>
            <i class="ti ti-dots-vertical"></i>
            <?php endif; ?>
            <span class="tg-badge"><?= html_escape($created) ?></span>
            </div> 
          <div class="tg-body small" data-collapsed="true" id="tg-body-<?= (int)$g['id'] ?>">
            <?= nl2br(html_escape($g['body'])) ?>
          </div>

            <?php
            // Decode and display attached files
            $guideFiles = [];
            if (!empty($g['files'])) {
                $guideFiles = json_decode($g['files'], true) ?: [];
            }
            if (!empty($guideFiles)):
            ?>
            <div class="mt-2 d-flex flex-wrap gap-1">
              <?php foreach ($guideFiles as $gf): ?>
                <?php
                $ext     = strtolower($gf['ext'] ?? '');
                $iconMap = [
                    'pdf'  => 'ti-file-type-pdf text-danger',
                    'doc'  => 'ti-file-type-doc text-primary',
                    'docx' => 'ti-file-type-doc text-primary',
                    'xls'  => 'ti-file-type-xls text-success',
                    'xlsx' => 'ti-file-type-xls text-success',
                    'txt'  => 'ti-file-type-txt text-secondary',
                    'zip'  => 'ti-file-zip text-warning',
                    'jpg'  => 'ti-photo text-info',
                    'jpeg' => 'ti-photo text-info',
                    'png'  => 'ti-photo text-info',
                    'gif'  => 'ti-photo text-info',
                ];
                $icon = $iconMap[$ext] ?? 'ti-file text-secondary';
                $name = html_escape($gf['original_name'] ?? 'file');
                $url  = html_escape(base_url($gf['path'] ?? ''));
                ?>
                <a href="<?= $url ?>"
                   target="_blank"
                   class="badge bg-light-secondary text-dark border small text-decoration-none"
                   style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                   title="<?= $name ?>">
                  <i class="ti <?= $icon ?> me-1"></i><?= $name ?>
                </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

          <div class="mt-2">
            <button class="tg-readmore" type="button"
              data-target="#tg-body-<?= (int)$g['id'] ?>" aria-expanded="false">
              Read more
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</div>

<!-- ── Edit Guide Modal ─────────────────────────────────────── -->
<div class="modal fade" id="editGuideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" id="editGuideForm"
            action="" 
            class="app-form" enctype="multipart/form-data">

        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white">
            <i class="ti ti-edit me-1"></i> Edit Instruction
          </h5>
          <button type="button" class="btn-close btn-close-white"
                  data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="editGuideTitle"
                   class="form-control" maxlength="180" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Instruction <span class="text-danger">*</span></label>
            <textarea name="body" id="editGuideBody"
                      rows="6" class="form-control" required></textarea>
          </div>

          <!-- Existing files -->
          <div class="mb-3" id="editExistingFilesWrap">
            <label class="form-label small fw-semibold">Current Attachments</label>
            <div id="editExistingFileList" class="d-flex flex-wrap gap-1 mb-2"></div>
            <small class="text-muted">Files marked for removal will be deleted on save.</small>
            <!-- Hidden field carries the remaining files JSON after removals -->
            <input type="hidden" name="existing_files" id="editExistingFilesJson" value="[]">
          </div>

          <!-- New files -->
          <div class="mb-3">
            <label class="form-label">
              Add New Attachments
              <small class="text-muted fw-normal">(optional — max 5MB each)</small>
            </label>
            <input type="file"
                   name="guide_files[]"
                   id="editGuideFiles"
                   class="form-control"
                   multiple
                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
            <div class="form-text">
              Allowed: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP
            </div>
            <div id="editNewFileList" class="mt-2 d-flex flex-wrap gap-1"></div>
          </div>

          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox"
                   id="editPinSwitch" name="is_pinned" value="1">
            <label class="form-check-label" for="editPinSwitch">Pin to top</label>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light-primary btn-sm"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-1"></i> Save Changes
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<?php if (!empty($canAdd) && $canAdd): ?>
<!-- Add Instruction Modal -->
<div class="modal fade" id="addGuideModal" tabindex="-1" aria-labelledby="addGuideModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
        <form method="post" action="<?= site_url('teams/instructions/' . (int)($teamId ?? 0)) ?>"
              class="app-form" enctype="multipart/form-data">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="addGuideModalLabel"><i class="ti ti-notes"></i> Add Team Instruction</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <?php
            // Controller passes $canGlobal (boolean) and $allTeamsBrief (array of ['id','name'])
            $canGlobal     = isset($canGlobal) ? (bool)$canGlobal : false;
            $allTeamsBrief = isset($allTeamsBrief) && is_array($allTeamsBrief) ? $allTeamsBrief : [];
            $currentTeamId = (int)($teamId ?? 0);
          ?>

          <!-- Team Selector (UX rule: enable if view_global, else disabled + hidden input) -->
          <div class="mb-3">
            <label class="form-label">Team <span class="text-danger">*</span></label>

            <?php if ($canGlobal): ?>
              <select name="team_id" class="form-select" required>
                <?php foreach ($allTeamsBrief as $t): ?>
                  <option value="<?= (int)$t['id'] ?>" <?= (int)$t['id'] === $currentTeamId ? 'selected' : '' ?>>
                    <?= html_escape($t['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="hidden" name="team_id" value="<?= $currentTeamId ?>">
              <select class="form-select" disabled>
                <option selected><?= html_escape($teamName ?? 'My Team') ?></option>
              </select>
              <small class="text-muted d-block mt-1">You can add instructions only for your current team.</small>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" maxlength="180" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Instruction <span class="text-danger">*</span></label>
            <textarea name="body" rows="6" class="form-control" placeholder="Write the instruction..." required></textarea>
          </div>

            <div class="mb-3">
              <label class="form-label">
                Attachments
                <small class="text-muted fw-normal">(optional — max 5MB each)</small>
              </label>
              <input type="file"
                     name="guide_files[]"
                     id="guideFiles"
                     class="form-control"
                     multiple
                     accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
              <div class="form-text">
                Allowed: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP
              </div>
              <!-- Live file name preview -->
              <div id="guideFileList" class="mt-2 d-flex flex-wrap gap-1"></div>
            </div>
            
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="pinSwitch" name="is_pinned" value="1">
            <label class="form-check-label" for="pinSwitch">Pin to top</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check"></i> Save Instruction</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
      <script>
        (function () {
          // Toggle "Read more / less"
          document.querySelectorAll('.tg-readmore').forEach(function(btn){
            btn.addEventListener('click', function(){
              var sel = btn.getAttribute('data-target');
              var body = document.querySelector(sel);
              if (!body) return;

              var collapsed = body.getAttribute('data-collapsed') === 'true';
              body.setAttribute('data-collapsed', collapsed ? 'false' : 'true');
              btn.textContent = collapsed ? 'Read less' : 'Read more';
              btn.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
            });
          });
        })();

// File preview badges
var fileInput = document.getElementById('guideFiles');
if (fileInput) {
    fileInput.addEventListener('change', function () {
        var list = document.getElementById('guideFileList');
        list.innerHTML = '';
        Array.from(this.files).forEach(function (f) {
            var badge = document.createElement('span');
            badge.className = 'badge bg-light-primary text-primary border small';
            badge.style.maxWidth = '180px';
            badge.style.overflow = 'hidden';
            badge.style.textOverflow = 'ellipsis';
            badge.style.whiteSpace = 'nowrap';
            badge.title = f.name;
            badge.textContent = f.name;
            list.appendChild(badge);
        });
    });
}        

// ── Edit Guide modal ────────────────────────────────────────
var _editFiles = []; // tracks remaining existing files

// Edit guide — delegated click on data-attribute buttons
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-edit-guide');
    if (!btn) return;

    var id      = btn.getAttribute('data-id');
    var title   = btn.getAttribute('data-title');
    var body    = btn.getAttribute('data-body');
    var pinned  = parseInt(btn.getAttribute('data-pinned'), 10);
    var files   = [];

    try {
        files = JSON.parse(btn.getAttribute('data-files') || '[]');
    } catch (err) {
        files = [];
    }

    _editFiles = Array.isArray(files) ? files.slice() : [];

    document.getElementById('editGuideForm').action =
        '<?= site_url('teams/guide_edit') ?>/' + id;

    document.getElementById('editGuideTitle').value  = title || '';
    document.getElementById('editGuideBody').value   = body  || '';
    document.getElementById('editPinSwitch').checked = pinned === 1;

    renderExistingFiles();

    document.getElementById('editGuideFiles').value      = '';
    document.getElementById('editNewFileList').innerHTML = '';

    // Close any open dropdown first then open modal
    var openDropdown = document.querySelector('.dropdown-menu.show');
    if (openDropdown) {
        var dropdownToggle = openDropdown.previousElementSibling;
        if (dropdownToggle) {
            var bsDrop = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (bsDrop) bsDrop.hide();
        }
    }

    setTimeout(function () {
        var modal = new bootstrap.Modal(document.getElementById('editGuideModal'));
        modal.show();
    }, 150);
});

function renderExistingFiles() {
    var container = document.getElementById('editExistingFileList');
    var wrap      = document.getElementById('editExistingFilesWrap');
    container.innerHTML = '';

    if (_editFiles.length === 0) {
        wrap.style.display = 'none';
        document.getElementById('editExistingFilesJson').value = '[]';
        return;
    }

    wrap.style.display = '';

    _editFiles.forEach(function (f, idx) {
        var span = document.createElement('span');
        span.className = 'badge bg-light-secondary text-dark border small d-inline-flex align-items-center gap-1';
        span.style.maxWidth = '200px';

        var nameSpan = document.createElement('span');
        nameSpan.style.overflow     = 'hidden';
        nameSpan.style.textOverflow = 'ellipsis';
        nameSpan.style.whiteSpace   = 'nowrap';
        nameSpan.title       = f.original_name || 'file';
        nameSpan.textContent = f.original_name || 'file';

        var removeBtn = document.createElement('button');
        removeBtn.type      = 'button';
        removeBtn.className = 'btn-close btn-close ms-1';
        removeBtn.style.fontSize = '8px';
        removeBtn.title = 'Remove this file';
        removeBtn.setAttribute('aria-label', 'Remove');
        removeBtn.addEventListener('click', function () {
            _editFiles.splice(idx, 1);
            renderExistingFiles();
        });

        span.appendChild(nameSpan);
        span.appendChild(removeBtn);
        container.appendChild(span);
    });

    document.getElementById('editExistingFilesJson').value =
        JSON.stringify(_editFiles);
}

// New file preview in edit modal
var editFileInput = document.getElementById('editGuideFiles');
if (editFileInput) {
    editFileInput.addEventListener('change', function () {
        var list = document.getElementById('editNewFileList');
        list.innerHTML = '';
        Array.from(this.files).forEach(function (f) {
            var badge = document.createElement('span');
            badge.className = 'badge bg-light-primary text-primary border small';
            badge.style.maxWidth    = '180px';
            badge.style.overflow    = 'hidden';
            badge.style.textOverflow = 'ellipsis';
            badge.style.whiteSpace  = 'nowrap';
            badge.title       = f.name;
            badge.textContent = f.name;
            list.appendChild(badge);
        });
    });
}
      </script>
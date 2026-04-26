<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
if (!function_exists('get_priority_class')) {
    function get_priority_class($priority) {
        switch ($priority) {
            case 'low': return 'info';
            case 'high': return 'warning';
            case 'critical': return 'danger';
            default: return 'primary';
        }
    }
}

if (!function_exists('get_recipient_display')) {
    function get_recipient_display($sent_to) {
        switch ($sent_to) {
            case 'all': return 'All Users';
            case 'employee': return 'All Employees';
            case 'teamlead': return 'All Team Leads';
            case 'manager': return 'All Managers';
            default: return ucfirst($sent_to);
        }
    }
}
?>

<div class="container-fluid">
<script>window.NO_GLOBAL_ANNOUNCEMENT_POPUP = true;</script>
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canAddAnnouncement  = staff_can('create', 'announcements');
        $canExport           = staff_can('export', 'general');
        $canPrint            = staff_can('print', 'general');
        $canDelete           = staff_can('delete', 'announcements');
      ?>
      <div class="btn-divider"></div>

      <!-- Add -->
      <button type="button"
              class="btn btn-header <?= $canAddAnnouncement ? 'btn-primary' : 'btn-disable' ?>"
              <?= $canAddAnnouncement ? 'data-bs-toggle="modal" data-bs-target="#addAnnouncementModal"' : 'disabled' ?>
              title="Add New Announcement">
        <i class="fas fa-plus me-1"></i> Add New
      </button>

      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search"
               data-table-target="<?= $table_id ?? 'announcementTable' ?>">
        <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
      </div>

      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="<?= html_escape($page_title ?? 'export') ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                title="Print Table">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body">
      <div class="list-table-header app-scroll">
        <table class="table table-bottom-border small align-middle mb-2" id="announcementTable">
          <thead class="bg-light-primary">
            <tr>
              <th class="bg-light-primary">Announcement Title</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Sent To</th>
              <th>Status</th>
              <th>Announcement Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($announcements as $a): ?>
            <tr>
              <td>
                <?= html_escape($a['title']) ?>
                <?php if (!empty($a['attachment'])): ?>
                  <i class="fas fa-paperclip text-muted ms-1"></i>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($a['category_name'])): ?>
                  <span class="badge" style="background-color: <?= html_escape($a['category_color']) ?>;">
                    <?= html_escape($a['category_name']) ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge text-light-<?= get_priority_class($a['priority']) ?>">
                  <?= ucfirst(html_escape($a['priority'])) ?>
                </span>
              </td>
              <td><?= html_escape(get_recipient_display($a['sent_to'])) ?></td>
              <td>
                <?php if (!empty($a['is_published'])): ?>
                  <span class="badge text-light-success">Published</span>
                <?php else: ?>
                  <span class="badge text-light-dark">Draft</span>
                <?php endif; ?>
              </td>
                <?php
                  $dateFmt  = get_system_setting('date_format') ?: 'Y-m-d';
                  $timePref = get_system_setting('time_format') ?: '24';
                  $timeFmt  = ($timePref === '24') ? 'H:i' : 'h:i A';
                ?>
              <td><?= date("$dateFmt $timeFmt", strtotime($a['created_at'])); //remove $timeFmt if want to show only date ?></td>
              <td>
                <div class="btn-group btn-group-sm">
                  <!-- View (AJAX loads into modal) -->
                <button type="button"
                        class="btn btn-outline-secondary btn-view-announcement"
                        data-id="<?= (int)$a['id'] ?>"
                        title="View">
                  <i class="fas fa-eye"></i>
                </button>
                  <?php if (staff_can('edit', 'announcements')): ?>
                    <button type="button"
                            class="btn btn-outline-secondary btn-edit-announcement"
                            data-id="<?= (int)$a['id'] ?>"
                            data-title="<?= html_escape($a['title']) ?>"
                            data-message="<?= html_escape($a['message']) ?>"
                            data-category_id="<?= (int)($a['category_id'] ?? 0) ?>"
                            data-priority="<?= html_escape($a['priority']) ?>"
                            data-sent_to="<?= html_escape($a['sent_to']) ?>"
                            data-published="<?= (int)$a['is_published'] ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editAnnouncementModal">
                      <i class="fas fa-edit"></i>
                    </button>
                  <?php endif; ?>

                    <!-- Delete Button -->
                    <?php if ($canDelete): ?>
                        <?= delete_link([
                        'url' => 'announcements/delete/' . $a['id'],
                        'label' => '',
                        'class' => 'btn btn-outline-secondary',
                        'message' => '',                                             
                        ]) ?>
                    <?php endif; ?>

                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Single, shared View Modal (AJAX-driven, VIEW-ONLY; no dismiss logging here) -->
<div class="modal fade" id="viewAnnouncementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">Announcement Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="announcementViewBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Announcement Modal (no dates) -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editAnnouncementForm" method="post" class="modal-content app-form" enctype="multipart/form-data">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="editAnnouncementModalLabel">Edit Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="editAnnouncementId" />

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Announcement Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" id="editTitle" required />
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" id="editCategory">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= html_escape($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Announcement Message <span class="text-danger">*</span></label>
          <textarea name="message" class="form-control" rows="5" id="editMessage" required></textarea>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-select" id="editPriority">
                <option value="medium">Medium</option>
                <option value="low">Low</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Attachment (optional)</label>
              <input type="file" name="attachment" class="form-control" />
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Send To</label>
              <select name="sent_to" class="form-select" id="editSentTo">
                <option value="all">All Users</option>
                <option value="employee">Employees</option>
                <option value="teamlead">Team Leads</option>
                <option value="manager">Managers</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="is_published" class="form-select" id="editStatus">
                <option value="1">Published</option>
                <option value="0">Draft</option>
              </select>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Update Announcement</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Announcement Modal (no dates) -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form action="<?= site_url('announcements/create') ?>" method="post" class="modal-content app-form small" enctype="multipart/form-data">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="addAnnouncementModalLabel">New Announcement</h5>
        <button type="button" class="btn-close btn-close-white m-0 fs-5" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required />
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select select-basic" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= html_escape($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Announcement Message <span class="text-danger">*</span></label>
          <textarea name="message" class="form-control" rows="5" required></textarea>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Priority <span class="text-danger">*</span></label>
              <select name="priority" class="form-select" required>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Attachment</label>
              <input type="file" name="attachment" class="form-control" />
              <small class="text-muted">Max size: 5MB. Allowed types: PDF, DOC, XLS, JPG, PNG</small>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Send To <span class="text-danger">*</span></label>
                <select name="sent_to" class="form-select capital" id="sentToSelect" required>
                  <?= roles_dropdown_options(); ?>
                  <option value="all">All Staff Roles</option>                  
                </select>
            </div>
          </div>
    
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="is_published" class="form-select">
                <option value="1">Published</option>
                <option value="0">Draft</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="ti ti-send me-1"></i>Send Announcement
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
  // VIEW-ONLY modal loader
  function openAnnouncement(id) {
    const modalEl = document.getElementById('viewAnnouncementModal');
    const modalBody = document.getElementById('announcementViewBody');
    const bsModal = new bootstrap.Modal(modalEl);

    modalBody.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    `;

    fetch("<?= site_url('announcements/view_ajax/') ?>" + encodeURIComponent(id))
      .then(r => r.text())
      .then(html => {
        modalBody.innerHTML = html;
        bsModal.show();
      })
      .catch(() => {
        modalBody.innerHTML = `<div class="alert alert-danger">Failed to load announcement.</div>`;
        bsModal.show();
      });
  }

const viewEl = document.getElementById('viewAnnouncementModal');
if (viewEl) {
  viewEl.addEventListener('hidden.bs.modal', () => {
    // If no other modal is open, clean up any leftover backdrop/body flags
    if (!document.querySelector('.modal.show')) {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
    }
  });
}

  // Table "View" buttons
  document.querySelectorAll('.btn-view-announcement').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      if (id) openAnnouncement(id);
    });
  });

  // Edit modal prefill (no dates)
  document.querySelectorAll('.btn-edit-announcement').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('editAnnouncementForm').action = "<?= site_url('announcements/edit') ?>/" + this.dataset.id;

      document.getElementById('editAnnouncementId').value = this.dataset.id || '';
      document.getElementById('editTitle').value        = this.dataset.title || '';
      document.getElementById('editMessage').value      = this.dataset.message || '';
      document.getElementById('editCategory').value     = this.dataset.category_id || '';
      document.getElementById('editPriority').value     = this.dataset.priority || 'medium';
      document.getElementById('editSentTo').value       = this.dataset.sent_to || 'all';
      document.getElementById('editStatus').value       = this.dataset.published || '1';
    });
  });
})();
</script>

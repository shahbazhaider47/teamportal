<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
<div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title">Manage Departments</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
    
            <!-- Internal Buttons -->
            <?php $canCreate = staff_can('create', 'departments'); ?>
            <button type="button"
                    id="btn-add-user"
                    class="btn <?= $canCreate ? 'btn-primary' : 'btn-light-danger' ?> btn-header"
                    <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="clearDepartmentForm()"' : 'disabled' ?>>
              <i class="fas fa-plus"></i> Add New
            </button>
        
    <div class="btn-divider"></div> 

            <!-- Dynamic Section -->
            <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
                <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                       placeholder="Search..." 
                       aria-label="Search"
                       data-table-target="<?= $table_id ?? 'departmentsTable' ?>">
                <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
            </div>
            <?php if (staff_can('export', 'general')) : ?>
              <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-export-table" 
                      title="Export to Excel" data-export-filename="<?= $page_title ?? 'export' ?>">
                <i class="ti ti-download"></i>
              </button>
            <?php endif; ?>
            
            <?php if (staff_can('print', 'general')) : ?>
              <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-print-table" 
                      title="Print Table">
                <i class="ti ti-printer"></i>
              </button>
            <?php endif; ?>
    </div>
</div>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (empty($departments)): ?>
        <div class="alert alert-info">No departments found. Add your first department.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm small align-middle table-bottom-border" id="departmentsTable">
            <thead class="table bg-light-primary">
              <tr>
                <th>ID #</th>
                <th>Department Name</th>
                <th>Description</th>
                <th>Head of Department</th>
                <th class="text-center">Total Staff</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($departments as $d): ?>
              <tr>
                <td><?= html_escape($d['id']) ?></td>
                <td>
                  <div><?= html_escape($d['name']) ?></div>
                  <div class="dept-actions">
                    <!-- View Button (triggers the right modal below) -->
                    <button
                      type="button"
                      class="btn p-0 text-success"
                      data-bs-toggle="modal"
                      data-bs-target="#viewDepartmentModal-<?= (int)$d['id'] ?>">
                      View
                    </button>
                    <!-- Edit Button -->
                    <button
                      type="button"
                      class="btn p-0 text-info"
                      data-id="<?= (int)$d['id'] ?>"
                      data-dept='<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>'
                      onclick="editDepartmentFromButton(this)">
                      Edit
                    </button>
                    
                    <!-- Delete Button -->
                    <?php $canDelete    = staff_can('delete', 'departments'); ?>
                    <?php if ($canDelete && $d['staff_count'] == 0): ?>
                        <?= delete_link([
                            'url'   => 'departments/delete/' . $d['id'],
                            'label' => 'Delete',
                            'class' => 'btn p-0 text-danger',
                            'message' => '',
                        ]) ?>
                    <?php else: ?>
                        <button class="btn p-0 text-muted"
                                disabled
                                title="Cannot delete department with staff members">
                            Delete
                        </button>
                    <?php endif; ?>
                    
                  </div>
                </td>
                <td><div><?= html_escape($d['description']) ?></div></td>
                <td>
                  <?php if (!empty($d['hod_user'])): ?>
                    <?php
                      $hod      = $d['hod_user'];
                      $first    = $hod['firstname'] ?? '';
                      $last     = $hod['lastname'] ?? '';
                      $full     = trim($first . ' ' . $last);
                      $filename = $hod['profile_image'] ?? '';
                
                      // Same path logic you’re already using elsewhere
                      $absPath  = FCPATH . 'uploads/users/profile/' . $filename;
                      $imgPath  = (!empty($filename) && file_exists($absPath))
                                    ? base_url('uploads/users/profile/' . $filename)
                                    : base_url('assets/images/default-avatar.png');
                    ?>
                    <div class="d-flex align-items-center">
                      <img src="<?= $imgPath ?>"
                           alt="<?= html_escape($full ?: 'Profile Image') ?>"
                           class="rounded-circle me-2"
                           width="25" height="25"
                           loading="lazy"
                           onerror="this.onerror=null;this.src='<?= base_url('assets/images/default-avatar.png') ?>';">
                      <span><?= html_escape($full ?: 'Unnamed') ?></span>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">N/A</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-light-primary">
                    <b><?= (int)$d['staff_count'] ?></b> Members
                  </span>
                </td>
              </tr>
            <!-- Per-row View Modal (hidden by default, shows on button click) -->
              <div class="modal fade" id="viewDepartmentModal-<?= (int)$d['id'] ?>" tabindex="-1" aria-labelledby="viewDepartmentLabel-<?= (int)$d['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header bg-primary">
                      <h5 class="modal-title text-white small" id="viewDepartmentLabel-<?= (int)$d['id'] ?>">Department Details | <?= html_escape($d['name']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <div class="mb-2 d-flex align-items-center">
                      <?php if (!empty($d['hod_user'])): ?>
                        <?php
                          $hod = $d['hod_user'];
                          $img_path = !empty($hod['profile_image']) && file_exists(FCPATH . 'uploads/users/profile/' . $hod['profile_image'])
                            ? base_url('uploads/users/profile/' . $hod['profile_image'])
                            : base_url('assets/images/default-avatar.png'); // fallback avatar
                        ?>
                        <img src="<?= $img_path ?>" alt="Profile Image" class="rounded-circle me-2" width="40" height="40">
                        <div class="d-flex flex-column">
                          <span><?= html_escape($hod['firstname'] . ' ' . $hod['lastname']) ?></span>
                          <small class="text-muted small text-primary">Department Head</small>
                        </div>
                      <?php else: ?>
                        <span class="text-muted">Not Assigned</span>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-12">
                      <p class="text-muted">
                        <?= !empty($d['description']) ? html_escape($d['description']) : '<span class="text-muted small">No description</span>' ?>
                      </p>
                    
                      <div class="app-divider-v mt-3 mb-3 secondary justify-content-center">
                        <span class="badge text-bg-primary">Total Members (<?= isset($d['users']) ? count($d['users']) : 0 ?>)</span>
                      </div>
                    
                      <?php if (!empty($d['users'])): ?>
                        <?php
                          // Sort users alphabetically by full name
                          usort($d['users'], function($a, $b) {
                            return strcmp($a['firstname'] . $a['lastname'], $b['firstname'] . $b['lastname']);
                          });
                    
                          // Chunk users into 2 columns
                          $chunks = array_chunk($d['users'], ceil(count($d['users']) / 2));
                        ?>
                    
                        <div class="row">
                          <?php foreach ($chunks as $chunk): ?>
                            <div class="col-md-6">
                              <ul class="list-group mb-3">
                                <?php foreach ($chunk as $user): ?>
                                  <li class="list-group-item d-flex align-items-center">
                                    <?php if (!empty($user['profile_image'])): ?>
                                      <img src="<?= base_url('uploads/users/profile/' . $user['profile_image']) ?>" class="rounded-circle me-2" width="25" height="25" alt="">
                                    <?php else: ?>
                                      <span class="bg-light-primary h-25 w-25 d-flex-center b-r-50 small me-2">
                                        <?= strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)) ?>
                                      </span>
                                    <?php endif; ?>
                                    <span class="small"><?= html_escape($user['firstname'] . ' ' . $user['lastname']) ?></span>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            </div>
                          <?php endforeach; ?>
                        </div>
                    
                      <?php else: ?>
                        <div class="alert alert-light-primary mb-0">No staff assigned to this department.</div>
                      <?php endif; ?>
                    </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
            </tbody>  
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Department Modal (Add/Edit) -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('departments') ?>" id="departmentForm" class="app-form" autocomplete="off">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="departmentModalLabel">Add New Department</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="department_id">
          <div class="mb-3">
            <label for="department_name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" id="department_name" required>
            <div class="invalid-feedback">Please provide a department name.</div>
          </div>
            <div class="mb-3">
              <label for="department_hod" class="form-label">Head of Department</label>
              <select name="hod" class="form-control" id="department_hod">
                <option value="">-- Not Assigned --</option>
                <?php foreach ($users as $user): ?>
                  <option value="<?= (int)$user['id'] ?>">
                    <?= html_escape($user['firstname'] . ' ' . $user['lastname']) ?>
                    <?php if (!empty($user['user_role'])): ?>
                        (<?= html_escape(ucwords($user['user_role'])) ?>)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
                
          <div class="mb-3">
            <label for="department_description" class="form-label">Description</label>
            <textarea name="description" class="form-control" id="department_description" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_department" value="1" class="btn btn-primary btn-sm" id="modalSubmitBtn">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Clear form for Add
function clearDepartmentForm() {
  document.getElementById('departmentModalLabel').textContent = 'Add New Department';
  document.getElementById('department_id').value = '';
  document.getElementById('department_name').value = '';
  document.getElementById('department_hod').value = '';
  document.getElementById('department_description').value = '';
  document.getElementById('modalSubmitBtn').name = 'add_department';
  document.getElementById('modalSubmitBtn').value = '1';
  document.getElementById('modalSubmitBtn').textContent = 'Save';
}

// For Edit (use handler with JSON-encoded data)
function editDepartmentFromButton(btn) {
  try {
    const id = btn.getAttribute('data-id');
    const data = JSON.parse(btn.getAttribute('data-dept'));
    document.getElementById('departmentModalLabel').textContent = 'Edit Department';
    document.getElementById('department_id').value = id;
    document.getElementById('department_name').value = data.name;
    document.getElementById('department_hod').value = data.hod || '';
    document.getElementById('department_description').value = data.description || '';
    document.getElementById('modalSubmitBtn').name = 'update_department';
    document.getElementById('modalSubmitBtn').value = '1';
    document.getElementById('modalSubmitBtn').textContent = 'Update';
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('departmentModal'));
    modal.show();
  } catch (err) {
    console.error(err);
  }
}

// Show assign staff form
function showAssignStaffForm() {
  document.getElementById('assignStaffForm').style.display = 'block';
  document.getElementById('assignStaffBtn').style.display = 'none';
}

// Form validation
document.getElementById('departmentForm').addEventListener('submit', function(e) {
  const form = e.target;
  if (!form.checkValidity()) {
    e.preventDefault();
    e.stopPropagation();
  }
  form.classList.add('was-validated');
});

</script>
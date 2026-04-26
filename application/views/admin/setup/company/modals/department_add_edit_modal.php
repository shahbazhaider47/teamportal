<!-- Department Modal (Add/Edit) -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('admin/setup/company') ?>" id="departmentForm" class="app-form" autocomplete="off">
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
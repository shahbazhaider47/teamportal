<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('admin/setup/company/edit_role') ?>" class="modal-content app-form">
      <div class="modal-header">
        <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="original_name" name="original_name" value="">
        <div class="mb-3">
          <label for="edit_role_name" class="form-label">Role Name</label>
          <input type="text"
                 id="edit_role_name"
                 name="role_name"
                 class="form-control capital"
                 placeholder="Only letters and underscores"
                 pattern="^[A-Za-z_]+$"
                 title="Only letters (A–Z, a–z) and underscores (_) are allowed."
                 required
                 oninput="this.value = this.value.replace(/[^A-Za-z_]/g, '')">
        </div>

        <div class="mb-0">
          <label for="edit_role_description" class="form-label d-flex justify-content-between">
            <span>Description</span>
            <small class="text-muted"><span id="editDescCount">0</span>/100</small>
          </label>
          <textarea id="edit_role_description"
                    name="description"
                    class="form-control"
                    rows="2"
                    maxlength="100"
                    placeholder="Optional: short description (max 100 characters)"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>
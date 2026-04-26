<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('admin/setup/company/create_role') ?>" class="modal-content app-form">
      <div class="modal-header">
        <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label for="role_name" class="form-label">Role Name</label>
          <input type="text"
                 id="role_name"
                 name="role_name"
                 class="form-control"
                 placeholder="Only letters and underscores"
                 pattern="^[A-Za-z_]+$"
                 title="Only letters (A–Z, a–z) and underscores (_) are allowed."
                 required
                 oninput="this.value = this.value.replace(/[^A-Za-z_]/g, '')">
        </div>

        <div class="mb-0">
          <label for="role_description" class="form-label d-flex justify-content-between">
            <span>Description</span>
            <small class="text-muted"><span id="descCount">0</span>/100</small>
          </label>
          <textarea id="role_description"
                    name="description"
                    class="form-control"
                    rows="2"
                    maxlength="100"
                    placeholder="Optional: short description (max 100 characters)"
                    oninput="document.getElementById('descCount').textContent = this.value.length;"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary btn-sm">Add Role</button>
      </div>
    </form>
  </div>
</div>
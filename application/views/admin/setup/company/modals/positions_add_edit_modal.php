<!-- Position Modal -->
<div class="modal fade" id="positionModal" tabindex="-1" aria-labelledby="positionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="positionForm" method="post" action="<?= site_url('admin/setup/company/save_position') ?>" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="positionModalLabel">Add New Position / Designation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="pos_id">

          <div class="mb-3">
            <label class="form-label">Position Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="pos_title" class="form-control" required>
          </div>

          <div class="row g-3">
            <div class="col-md-6 mb-3">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" id="pos_code" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Department</label>
              <select name="department_id" id="pos_department" class="form-select">
                <option value="">Select Department</option>
                <?php if (!empty($departments)): ?>
                  <?php foreach($departments as $dept): ?>
                    <option value="<?= (int)$dept['id'] ?>"><?= html_escape($dept['name']) ?></option>
                  <?php endforeach ?>
                <?php endif; ?>
              </select>
            </div>
          </div>
            <?php $sym = html_escape(get_base_currency_symbol()); ?>
            <?php $pos = (get_system_setting('currency_symbol_position') ?? 'before'); // 'before' | 'after' ?>
            <div class="row g-3">
              <div class="col-md-6 mb-3">
                <label class="form-label">Minimum Salary</label>
                <div class="currency-wrap" data-pos="<?= $pos ?>">
                  <span class="currency-symbol"><?= $sym ?></span>
                  <input type="number"
                         name="min_salary"
                         id="pos_min_salary"
                         class="form-control currency-input"
                         min="0"
                         inputmode="decimal">
                </div>
              </div>
            
              <div class="col-md-6 mb-3">
                <label class="form-label">Maximum Salary</label>
                <div class="currency-wrap" data-pos="<?= $pos ?>">
                  <span class="currency-symbol"><?= $sym ?></span>
                  <input type="number"
                         name="max_salary"
                         id="pos_max_salary"
                         class="form-control currency-input"
                         min="0"
                         inputmode="decimal">
                </div>
              </div>
            </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="pos_description" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label">Status</label>
            <select name="status" id="pos_status" class="form-select">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Position</button>
        </div>
      </div>
    </form>
  </div>
</div>

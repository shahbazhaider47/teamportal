<!-- Header -->
<div class="card-header bg-light-primary mb-2">
  <div class="d-flex align-items-center justify-content-between">
    <h6 class="card-title text-primary mb-0">
      <i class="ti ti-building me-2" style="font-size:18px;"></i>
      All Departments
    </h6>

    <?php if (staff_can('manage', 'company')): ?>
      <button type="button"
              class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#departmentModal"
              onclick="clearDepartmentForm()">
        <i class="ti ti-plus"></i> Add Department
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-bottom-border table-sm small table-hover align-middle">
    <thead class="bg-light-primary">
      <tr>
        <th style="width:1%">#</th>
        <th style="width:18%">Department Name</th>
        <th style="width:30%">Description</th>
        <th>Email</th>
        <th>Head of Department</th>
        <th>Members</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>

    <tbody>
    <?php if (!empty($departments)): ?>
      <?php foreach ($departments as $i => $d): ?>
        <tr>

          <td><?= $i + 1 ?></td>

          <td class="fw-semibold">
            <?= html_escape($d['name']) ?>
          </td>

          <td class="text-muted">
            <?= !empty($d['description']) ? html_escape($d['description']) : '—' ?>
          </td>

          <td>
            <?= !empty($d['email']) ? html_escape($d['email']) : '—' ?>
          </td>

          <td>
            <?php if (!empty($d['hod_user'])): ?>
              <?= user_profile_small($d['hod_user']['id']) ?>
            <?php else: ?>
              <span class="text-muted small">Not Assigned</span>
            <?php endif; ?>
          </td>

          <td>
            <span class="badge bg-light-primary">
              <?= (int)($d['staff_count'] ?? 0) ?>
            </span>
          </td>

          <td>
            <span class="badge bg-light-<?= !empty($d['status']) ? 'primary' : 'secondary' ?>">
              <?= !empty($d['status']) ? 'Active' : 'Inactive' ?>
            </span>
          </td>

          <td class="text-end">
            <div class="btn-group btn-group-sm">

              <!-- View -->
              <button type="button"
                      class="btn btn-outline-secondary"
                      data-bs-toggle="modal"
                      data-bs-target="#viewDepartmentModal-<?= (int)$d['id'] ?>">
                <i class="ti ti-eye"></i>
              </button>

              <!-- Edit -->
              <button type="button"
                      class="btn btn-outline-secondary"
                      data-id="<?= (int)$d['id'] ?>"
                      data-dept='<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>'
                      onclick="editDepartmentFromButton(this)">
                <i class="ti ti-edit"></i>
              </button>

              <!-- Delete -->
              <?= delete_link([
                  'url'   => 'admin/setup/company/delete_department/' . (int)$d['id'],
                  'label' => '',
                  'class' => 'btn btn-outline-secondary',
              ]) ?>

            </div>
          </td>

        </tr>

        <!-- View Modal (unchanged, per-row) -->
        <?php $CI =& get_instance(); ?>
        <?= $CI->load->view(
            'admin/setup/company/modals/department_view_modal',
            ['d' => $d],
            true
        ); ?>

      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="8" class="text-center py-4 text-muted">
          No departments found.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php $CI =& get_instance(); ?>
<?= $CI->load->view('admin/setup/company/modals/department_add_edit_modal', true); ?>

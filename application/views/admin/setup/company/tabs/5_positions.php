    <!-- Header -->
    <div class="card-header bg-light-primary mb-2">
      <div class="d-flex align-items-center justify-content-between">
        <h6 class="card-title text-primary mb-0">
          <i class="ti ti-building me-2" style="font-size:18px;"></i>
          All Positions / Designations
        </h6>
    
        <?php if (staff_can('manage', 'company')): ?>
          <button type="button"
                  class="btn btn-primary btn-header"
                  data-bs-toggle="modal"
                  data-bs-target="#positionModal"
                  onclick="clearPositionForm()">
            <i class="ti ti-plus"></i> Add Position
          </button>
        <?php endif; ?>
      </div>
    </div>

      <div class="table-responsive">
        <table class="table table-bottom-border table-sm small table-hover align-middle">
          <thead class="bg-light-primary">
            <tr>
              <th style="width: 1%">#</th>
              <th style="width: 18%">Position Title</th>
              <th style="width: 30%">Description</th>
              <th>Code</th>
              <th>Department</th>
              <th>Min Salary</th>
              <th>Max Salary</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($positions)): ?>
            <?php foreach ($positions as $i => $pos): ?>
              <?php
                $min = isset($pos['min_salary']) && $pos['min_salary'] !== null ? number_format((float)$pos['min_salary']) : '—';
                $max = isset($pos['max_salary']) && $pos['max_salary'] !== null ? number_format((float)$pos['max_salary']) : '—';
                $deptName = $pos['department_name'] ?? null; // from get_all_with_stats()
              ?>
              <tr>
                <td><?= (int)$i + 1 ?></td>
                <td><?= html_escape($pos['title']) ?></td>
                <td><?= html_escape($pos['description']) ?></td>
                <td><?= html_escape($pos['code']) ?></td>
                <td><?= html_escape($pos['department_name'] ?? '-') ?></td>
                <td><?= $min ?></td>
                <td><?= $max ?></td>
                <td>
                  <span class="badge bg-light-<?= !empty($pos['status']) ? 'primary' : 'danger' ?>">
                    <?= !empty($pos['status']) ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="editPosition(<?= (int)$pos['id'] ?>)">
                      <i class="ti ti-edit"></i>
                    </button>
                    
                      <!-- Delete -->
                      <?= delete_link([
                          'url'   => 'admin/setup/company/delete_position/' . (int)$pos['id'],
                          'label' => '',
                          'class' => 'btn btn-outline-secondary',
                      ]) ?>
                      
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-4">No positions found.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>


<?php $CI =& get_instance(); ?>
<?= $CI->load->view('admin/setup/company/modals/positions_add_edit_modal', true); ?>

<script>
function clearPositionForm() {
  const f = document.getElementById('positionForm');
  if (!f) return;
  f.reset();
  document.getElementById('pos_id').value = '';
  document.getElementById('positionModalLabel').textContent = 'Add New Position / Designation';
}

async function editPosition(id) {
  try {
    const resp = await fetch('<?= site_url('admin/setup/company/get_position') ?>/' + encodeURIComponent(id), {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store'
    });
    if (!resp.ok) throw new Error('Network error: ' + resp.status);
    const res = await resp.json();
    if (!res || typeof res !== 'object') throw new Error('Invalid response');

    // Fill form safely (handles null/undefined)
    document.getElementById('pos_id').value           = res.id ?? '';
    document.getElementById('pos_title').value        = res.title ?? '';
    document.getElementById('pos_code').value         = res.code ?? '';
    document.getElementById('pos_department').value   = res.department_id ?? '';
    document.getElementById('pos_min_salary').value   = (res.min_salary ?? '') === null ? '' : res.min_salary;
    document.getElementById('pos_max_salary').value   = (res.max_salary ?? '') === null ? '' : res.max_salary;
    document.getElementById('pos_description').value  = res.description ?? '';
    document.getElementById('pos_status').value       = (res.status ?? 0);

    document.getElementById('positionModalLabel').textContent = 'Edit Position';

    // Bootstrap 5 modal API (works even without jQuery)
    const modalEl = document.getElementById('positionModal');
    const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

  } catch (e) {
    console.error(e);
    alert('Failed to load position!');
  }
}
</script>
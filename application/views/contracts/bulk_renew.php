<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$filters = $filters ?? [];
$rows    = $rows    ?? [];
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-2 mb-3 rounded-3 shadow-sm">
        <div>
            <h1 class="h6 mb-0">Bulk Contract Renewal</h1>
        </div>
        <div>
            <a href="<?= site_url('contracts'); ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-left me-1"></i> Back to Contracts
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form action="<?= site_url('contracts/bulk_renew'); ?>" method="post" enctype="multipart/form-data" class="row g-3 app-form">
                <!-- Global contract settings -->
                <div class="col-12">
                    <h6 class="text-muted mb-2">Contract Settings</h6>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Contract Type <span class="text-danger">*</span></label>
                    <select name="contract_type" class="form-select" required>
                        <option value="">Select Contract Type</option>
                        <?php foreach ($contract_types as $ct): ?>
                            <option value="<?= e($ct); ?>" <?= ($filters['contract_type'] ?? '') === $ct ? 'selected' : ''; ?>>
                                <?= e($ct); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($contract_types)): ?>
                        <div class="form-text text-muted">No Contract Types configured in System Options.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date"
                           name="start_date"
                           class="form-control"
                           required
                           value="<?= html_escape($filters['start_date'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date"
                           name="end_date"
                           class="form-control"
                           required
                           value="<?= html_escape($filters['end_date'] ?? ''); ?>">
                </div>

                <!-- Renew by dimension -->

                <div class="col-md-3">
                    <label class="form-label">Renew By <span class="text-danger">*</span></label>
                    <select name="renew_by" id="renew_by" class="form-select" required>
                        <option value="">Select Option</option>
                        <option value="team"       <?= ($filters['renew_by'] ?? '') === 'team' ? 'selected' : ''; ?>>Team</option>
                        <option value="department" <?= ($filters['renew_by'] ?? '') === 'department' ? 'selected' : ''; ?>>Department</option>
                        <option value="position"   <?= ($filters['renew_by'] ?? '') === 'position' ? 'selected' : ''; ?>>Position</option>
                        <option value="employees"  <?= ($filters['renew_by'] ?? '') === 'employees' ? 'selected' : ''; ?>>Specific Employees</option>
                    </select>
                </div>
                
                <div class="col-12 mt-2">
                    <hr>
                </div>

                <div class="col-md-3 renew-by renew-by-team d-none">
                    <label class="form-label">Teams</label>
                    <select name="team_ids[]" class="form-select" multiple>
                        <?php foreach ($teams as $id => $name): ?>
                            <option value="<?= (int)$id; ?>"
                                <?= in_array((string)$id, array_map('strval', $filters['team_ids'] ?? []), true) ? 'selected' : ''; ?>>
                                <?= html_escape($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 renew-by renew-by-department d-none">
                    <label class="form-label">Departments</label>
                    <select name="department_ids[]" class="form-select" multiple>
                        <?php foreach ($departments as $id => $name): ?>
                            <option value="<?= (int)$id; ?>"
                                <?= in_array((string)$id, array_map('strval', $filters['department_ids'] ?? []), true) ? 'selected' : ''; ?>>
                                <?= html_escape($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 renew-by renew-by-position d-none">
                    <label class="form-label">Positions</label>
                    <select name="position_ids[]" class="form-select" multiple>
                        <?php foreach ($positions as $id => $name): ?>
                            <option value="<?= (int)$id; ?>"
                                <?= in_array((string)$id, array_map('strval', $filters['position_ids'] ?? []), true) ? 'selected' : ''; ?>>
                                <?= html_escape($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 renew-by renew-by-employees d-none">
                    <label class="form-label">Specific Employees</label>
                    <select name="employee_ids[]" class="form-select" multiple>
                        <?php foreach ($staff_list as $u): ?>
                            <?php
                                $uid  = (int)$u['id'];
                                $name = !empty($u['fullname'])
                                    ? $u['fullname']
                                    : trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
                                $empId = !empty($u['emp_id']) ? emp_id_display($u['emp_id']) : null;
                            ?>
                            <option value="<?= $uid; ?>"
                                <?= in_array((string)$uid, array_map('strval', $filters['employee_ids'] ?? []), true) ? 'selected' : ''; ?>>
                                <?= html_escape($name); ?><?= $empId ? ' ('.html_escape($empId).')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" name="action" value="load" class="btn btn-primary btn-sm">
                        <i class="ti ti-users-group me-1"></i> Load Employees
                    </button>
                </div>

                <?php if (!empty($rows)): ?>
                    <div class="col-12 mt-4">
                        <h6 class="text-muted mb-2">Employees & Contracts to Renew <span class="text-muted small">(Showing staff with active or signed contracts only)</span></h6>
                        <div class="table-responsive">
                            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover">
                                <thead class="bg-light-primary">
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" id="bulk_select_all">
                                        </th>
                                        <th>Emp ID</th>
                                        <th>Employee Name</th>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th>Team</th>
                                        <th>Notice Period (days)</th>
                                        <th>Previous Contract</th>
                                        <th>New Contract File</th>                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $idx => $r): ?>
                                        <?php
                                            $empId      = !empty($r['emp_id']) ? emp_id_display($r['emp_id']) : '—';
                                            $fullName   = $r['fullname']
                                                ?? trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''));
                                            $titleText  = !empty($r['emp_title']) ? resolve_emp_title($r['emp_title']) : ($r['position_title'] ?? '—');
                                            $deptText   = !empty($r['department_name']) ? $r['department_name'] : '—';
                                            $teamText   = !empty($r['emp_team']) ? $r['emp_team'] : '—';
                                            $noticeDays = (int)($r['notice_period_days'] ?? 30);
                                            $fileName   = !empty($r['contract_file']) ? $r['contract_file'] : null;
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                       class="bulk-row-checkbox"
                                                       name="rows[<?= $idx; ?>][renew]"
                                                       value="1">
                                                <input type="hidden" name="rows[<?= $idx; ?>][row_index]" value="<?= $idx; ?>">
                                                <input type="hidden" name="rows[<?= $idx; ?>][user_id]" value="<?= (int)$r['user_id']; ?>">
                                                <input type="hidden" name="rows[<?= $idx; ?>][contract_id]" value="<?= (int)$r['contract_id']; ?>">
                                                <input type="hidden" name="rows[<?= $idx; ?>][existing_contract_file]" value="<?= html_escape($fileName); ?>">
                                            </td>
                                            <td><?= html_escape($empId); ?></td>
                                            <td><?= html_escape($fullName); ?></td>
                                            <td><?= html_escape($titleText); ?></td>
                                            <td><?= html_escape($deptText); ?></td>
                                            <td><?= html_escape($teamText); ?></td>
                                            <td>
                                                <input type="number"
                                                       name="rows[<?= $idx; ?>][notice_period_days]"
                                                       class="form-control form-control-sm"
                                                       value="<?= $noticeDays; ?>"
                                                       min="0">
                                            </td>
                                            <td>
                                                <?php if ($fileName): ?>
                                                    <div class="small">
                                                        <div class="text-truncate" title="<?= html_escape($fileName); ?>">
                                                            <?= html_escape($fileName); ?>
                                                        </div>
                                                        <a href="<?= base_url('uploads/users/contracts/' . $fileName); ?>"
                                                           target="_blank"
                                                           class="small text-primary">
                                                            <i class="ti ti-eye me-1"></i>View File
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">No file</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div class="mt-1">
                                                    <input type="file"
                                                           name="contract_file_<?= $idx; ?>"
                                                           class="form-control form-control-sm">
                                                </div>
                                            </td>
                                            
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button type="submit" name="action" value="renew" class="btn btn-success btn-sm">
                                <i class="ti ti-refresh me-1"></i> Renew Selected Contracts
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const renewBySelect = document.getElementById('renew_by');
    const sections      = document.querySelectorAll('.renew-by');

    function toggleRenewBy() {
        const val = renewBySelect ? renewBySelect.value : '';
        sections.forEach(function(sec) { sec.classList.add('d-none'); });

        if (!val) return;
        const targetClass = '.renew-by-' + val;
        document.querySelectorAll(targetClass).forEach(function(sec) {
            sec.classList.remove('d-none');
        });
    }

    if (renewBySelect) {
        renewBySelect.addEventListener('change', toggleRenewBy);
        toggleRenewBy(); // initial
    }

    // Bulk select rows
    const bulkAll = document.getElementById('bulk_select_all');
    const rowCbs  = document.querySelectorAll('.bulk-row-checkbox');
    if (bulkAll) {
        bulkAll.addEventListener('change', function() {
            rowCbs.forEach(cb => cb.checked = bulkAll.checked);
        });
    }
})();
</script>

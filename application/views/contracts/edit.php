<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// -------------------------------------------------------------------------
// Helper to read JSON arrays from options (same as in create view)
// -------------------------------------------------------------------------
if (!function_exists('read_option_array')) {
    function read_option_array($key) {
        $json = function_exists('get_setting') ? (string) get_setting($key, '[]') : '[]';
        $arr  = json_decode($json, true);
        if (!is_array($arr)) return [];
        $arr = array_map(function($v){ return trim((string)$v); }, $arr);
        return array_values(array_unique(array_filter($arr, 'strlen')));
    }
}

// Contract types from options
$contract_types = read_option_array('contract_types');

// -------------------------------------------------------------------------
// Pre-format datetime-local fields (HTML expects Y-m-d\TH:i)
// -------------------------------------------------------------------------
$sent_at_value  = set_value('sent_at');
if ($sent_at_value === '' && !empty($contract['sent_at']) && $contract['sent_at'] !== '0000-00-00 00:00:00') {
    $ts            = strtotime($contract['sent_at']);
    $sent_at_value = $ts ? date('Y-m-d\TH:i', $ts) : '';
}

$signed_at_value = set_value('signed_at');
if ($signed_at_value === '' && !empty($contract['signed_at']) && $contract['signed_at'] !== '0000-00-00 00:00:00') {
    $ts              = strtotime($contract['signed_at']);
    $signed_at_value = $ts ? date('Y-m-d\TH:i', $ts) : '';
}

$renew_at_value = set_value('renew_at');
if ($renew_at_value === '' && !empty($contract['renew_at']) && $contract['renew_at'] !== '0000-00-00 00:00:00') {
    $ts             = strtotime($contract['renew_at']);
    $renew_at_value = $ts ? date('Y-m-d\TH:i', $ts) : '';
}

// For header EMP ID display
$empIdDisplay = !empty($contract['emp_id'])
    ? emp_id_display($contract['emp_id'])
    : '';
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-2 mb-3 rounded-3 shadow-sm">
    <div>
      <h1 class="h6 header-title">
        Edit Contract #<?= (int)$contract['id']; ?>
        <i class="ti ti-chevron-right"></i>
        <span class="text-muted small">
          <?= user_profile_image($contract['fullname'] ?? 'N/A'); ?>
        </span>
        <i class="ti ti-dots-vertical"></i>
        <span class="text-muted small">
          <?php if ($empIdDisplay): ?>
            <?= $empIdDisplay; ?>
          <?php endif; ?>
        </span>
      </h1>
    </div>
    <div>
      <a href="<?= site_url('contracts/view/' . (int)$contract['id']); ?>" class="btn btn-light-primary btn-header">
        <i class="ti ti-arrow-left me-1"></i> Back to contract
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="<?= site_url('contracts/update/' . (int)$contract['id']); ?>"
            method="post"
            enctype="multipart/form-data"
            class="row g-3 app-form">

        <!-- LEFT: staff selection + user details (same layout as NEW) -->
        <div class="col-12 col-lg-4">
          <div class="mb-3">
            <label class="form-label">Staff Member <span class="text-danger">*</span></label>
            <select name="user_id_display"
                    id="contract_user_id"
                    class="form-select"
                    disabled>
              <?php
              $uid   = (int)($contract['user_id'] ?? 0);
              $name  = !empty($contract['fullname'])
                  ? $contract['fullname']
                  : trim(($contract['firstname'] ?? '') . ' ' . ($contract['lastname'] ?? ''));
              ?>
              <option value="<?= $uid; ?>">
                <?= html_escape($name ?: 'N/A'); ?>
                <?php if (!empty($contract['emp_id'])): ?>
                  (<?= emp_id_display($contract['emp_id']); ?>)
                <?php endif; ?>
              </option>
            </select>
            <!-- Hidden field to keep user_id if you ever need it later -->
            <input type="hidden" name="user_id" value="<?= $uid; ?>">
          </div>

          <!-- Staff detail card populated by JS (identical to NEW) -->
          <div id="staff-profile-card" class="border rounded-3 p-3 bg-light-subtle d-none">
            <div class="d-flex align-items-center mb-2">
              <div id="spc-avatar" class="me-2"></div>
              <div>
                <div class="fw-semibold text-primary" id="spc-fullname"></div>
                <div class="small text-muted">
                  <span id="spc-firstname"></span>
                  <span id="spc-initials"></span>
                  <span id="spc-lastname"></span>
                </div>
              </div>
            </div>

            <div class="app-divider-v dotted mb-2"></div>

            <dl class="row small mb-0">
              <dt class="col-5">User Role</dt>
              <dd class="col-7 capital" id="spc-user-role"></dd>

              <dt class="col-5">Emp ID</dt>
              <dd class="col-7" id="spc-emp-id"></dd>

              <dt class="col-5">Title</dt>
              <dd class="col-7" id="spc-emp-title"></dd>

              <dt class="col-5">Department</dt>
              <dd class="col-7" id="spc-emp-department"></dd>

              <dt class="col-5">Team</dt>
              <dd class="col-7" id="spc-emp-team"></dd>

              <dt class="col-5">Joining Date</dt>
              <dd class="col-7" id="spc-emp-joining"></dd>

              <dt class="col-5">Employment Type</dt>
              <dd class="col-7" id="spc-employment-type"></dd>

              <dt class="col-5">Joining Salary</dt>
              <dd class="col-7" id="spc-joining-salary"></dd>

              <dt class="col-5">Current Salary</dt>
              <dd class="col-7" id="spc-current-salary"></dd>
            </dl>
          </div>
        </div>

        <!-- RIGHT: Contract fields (same as earlier edit, plus your extra fields) -->
        <div class="col-12 col-lg-8">
          <div class="row g-3">
            <!-- Contract type (dropdown from options, same UX as NEW) -->
            <div class="col-md-4">
              <label for="contract_type" class="form-label">
                Contract Type <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="contract_type" name="contract_type" required>
                <option value="">Select Contract Type</option>
                <?php
                $currentType = set_value('contract_type', $contract['contract_type'] ?? '');
                foreach ($contract_types as $opt):
                    $isSelected = ($currentType === $opt);
                ?>
                  <option value="<?= e($opt); ?>" <?= $isSelected ? 'selected' : ''; ?>>
                    <?= e($opt); ?>
                  </option>
                <?php endforeach; ?>
                <?php if ($currentType && !in_array($currentType, $contract_types, true)): ?>
                  <!-- Ensure current saved type is selectable even if option was removed -->
                  <option value="<?= e($currentType); ?>" selected>
                    <?= e($currentType); ?> (current)
                  </option>
                <?php endif; ?>
              </select>
              <?php if (empty($contract_types)): ?>
                <div class="form-text text-muted">No Contract Types configured in System Options.</div>
              <?php endif; ?>
            </div>

            <!-- Start / End dates -->
            <div class="col-md-4">
              <label class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date"
                     name="start_date"
                     class="form-control"
                     required
                     value="<?= set_value('start_date', $contract['start_date']); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">End Date</label>
              <input type="date"
                     name="end_date"
                     class="form-control"
                     value="<?= set_value('end_date', $contract['end_date']); ?>">
            </div>

            <!-- Notice period / renewable -->
            <div class="col-md-4">
              <label class="form-label">Notice Period (days)</label>
              <input type="number"
                     name="notice_period_days"
                     class="form-control"
                     min="0"
                     value="<?= set_value('notice_period_days', $contract['notice_period_days']); ?>">
            </div>

            <div class="col-md-4 d-flex align-items-center">
              <div class="form-check mt-4">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_renewable"
                       id="is_renewable"
                       value="1"
                       <?= set_checkbox('is_renewable', '1', !empty($contract['is_renewable'])); ?>>
                <label class="form-check-label" for="is_renewable">
                  Is Renewable?
                </label>
              </div>
            </div>

            <!-- Renew at -->
            <div class="col-md-4">
              <label class="form-label">Renew At</label>
              <input type="datetime-local"
                     name="renew_at"
                     class="form-control"
                     value="<?= html_escape($renew_at_value); ?>">
            </div>

            <!-- Status -->
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php
                $statuses = ['draft','sent','signed','expired','cancelled','renewed'];
                $curSt    = set_value('status', $contract['status']);
                foreach ($statuses as $st): ?>
                  <option value="<?= $st; ?>" <?= $curSt === $st ? 'selected' : ''; ?>>
                    <?= ucfirst($st); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Sent / Signed -->
            <div class="col-md-4">
              <label class="form-label">Sent At</label>
              <input type="datetime-local"
                     name="sent_at"
                     class="form-control"
                     value="<?= html_escape($sent_at_value); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Signed At</label>
              <input type="datetime-local"
                     name="signed_at"
                     class="form-control"
                     value="<?= html_escape($signed_at_value); ?>">
            </div>

            <!-- Sign method / hash -->
            <div class="col-md-4">
              <label class="form-label">Sign Method</label>
              <select name="sign_method" class="form-select">
                <?php
                $methods = ['manual','digital','system'];
                $curM    = set_value('sign_method', $contract['sign_method']);
                foreach ($methods as $m): ?>
                  <option value="<?= $m; ?>" <?= $curM === $m ? 'selected' : ''; ?>>
                    <?= ucfirst($m); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">Signature Hash</label>
              <input type="text"
                     name="signature_hash"
                     class="form-control"
                     value="<?= set_value('signature_hash', $contract['signature_hash']); ?>">
            </div>

            <!-- Contract file (replace) -->
            <div class="col-md-6">
              <label class="form-label">Contract File (replace)</label>
              <input type="file"
                     name="contract_file"
                     id="contract_file"
                     class="form-control">
              <?php if (!empty($contract['contract_file'])): ?>
                <div class="small mt-1">
                  Current File:
                  <a href="<?= base_url('uploads/users/contracts/' . $contract['contract_file']); ?>"
                     target="_blank">
                    <span class="text-primary fw-semibold">View <i class="ti ti-external-link me-1"></i></span>
                  </a>
                </div>
              <?php endif; ?>
              <div id="contract_file_info" class="form-text text-muted mt-1"></div>
            </div>

            <!-- Internal notes -->
            <div class="col-12">
              <label class="form-label">Internal Notes</label>
              <textarea name="internal_notes"
                        rows="4"
                        class="form-control"><?= set_value('internal_notes', $contract['internal_notes']); ?></textarea>
            </div>
          </div>
        </div>

        <div class="col-12">
          <hr>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i> Save Changes
          </button>
          <a href="<?= site_url('contracts/view/' . (int)$contract['id']); ?>" class="btn btn-light">
            Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
// -------------------------------------------------------------------------
// Build ui_staff_details from the current contract ONLY,
// so the JS + card behave exactly like "New Contract".
// -------------------------------------------------------------------------
$ui_staff_details = [];
$uid = (int)($contract['user_id'] ?? 0);

if ($uid > 0) {
    $fullname = $contract['fullname']
        ?? trim(($contract['firstname'] ?? '') . ' ' . ($contract['lastname'] ?? ''));

    $ui_staff_details[$uid] = [
        'fullname'        => $fullname,
        'firstname'       => $contract['firstname'] ?? '',
        'initials'        => $contract['initials'] ?? '',
        'lastname'        => $contract['lastname'] ?? '',
        'user_role'       => $contract['user_role'] ?? '',
        'emp_id'          => !empty($contract['emp_id']) ? emp_id_display($contract['emp_id']) : '—',
        'emp_title'       => $contract['position_title'] ?? ($contract['emp_title'] ?? ''),
        'emp_department'  => $contract['department_name'] ?? ($contract['emp_department'] ?? ''),
        'emp_team'        => $contract['team_name'] ?? ($contract['emp_team'] ?? ''),
        'emp_joining'     => !empty($contract['emp_joining']) ? format_date($contract['emp_joining']) : '—',
        'employment_type' => $contract['employment_type'] ?? '',
        'joining_salary'  => (isset($contract['joining_salary']) && $contract['joining_salary'] !== '')
                                ? c_format((float)$contract['joining_salary'])
                                : '—',
        'current_salary'  => (isset($contract['current_salary']) && $contract['current_salary'] !== '')
                                ? c_format((float)$contract['current_salary'])
                                : '—',
        'avatar_html'     => !empty($fullname) ? user_profile($fullname) : '',
    ];
}
?>

<script>
  (function() {
    // Staff details mapping from PHP (same pattern as NEW, but single record)
    const staffDetails = <?= json_encode($ui_staff_details); ?>;
    const selectEl     = document.getElementById('contract_user_id');
    const card         = document.getElementById('staff-profile-card');

    if (selectEl && card) {
      const avatarEl  = document.getElementById('spc-avatar');
      const fn        = document.getElementById('spc-fullname');
      const fNameEl   = document.getElementById('spc-firstname');
      const initEl    = document.getElementById('spc-initials');
      const lNameEl   = document.getElementById('spc-lastname');

      const roleEl    = document.getElementById('spc-user-role');
      const empIdEl   = document.getElementById('spc-emp-id');
      const titleEl   = document.getElementById('spc-emp-title');
      const deptEl    = document.getElementById('spc-emp-department');
      const teamEl    = document.getElementById('spc-emp-team');
      const joiningEl = document.getElementById('spc-emp-joining');
      const empTypeEl = document.getElementById('spc-employment-type');
      const joinSalEl = document.getElementById('spc-joining-salary');
      const curSalEl  = document.getElementById('spc-current-salary');

      function updateCard() {
        const id = selectEl.value;
        if (!id || !staffDetails[id]) {
          card.classList.add('d-none');
          return;
        }

        const s = staffDetails[id];

        avatarEl.innerHTML   = s.avatar_html || '';
        fn.textContent       = s.fullname || '';
        fNameEl.textContent  = s.firstname ? 'First: ' + s.firstname : '';
        initEl.textContent   = s.initials ? ' (' + s.initials + ')' : '';
        lNameEl.textContent  = s.lastname ? ' Last: ' + s.lastname : '';

        roleEl.textContent    = s.user_role || '—';
        empIdEl.textContent   = s.emp_id || '—';
        titleEl.textContent   = s.emp_title || '—';
        deptEl.textContent    = s.emp_department || '—';
        teamEl.textContent    = s.emp_team || '—';
        joiningEl.textContent = s.emp_joining || '—';
        empTypeEl.textContent = s.employment_type || '—';
        joinSalEl.textContent = s.joining_salary || '—';
        curSalEl.textContent  = s.current_salary || '—';

        card.classList.remove('d-none');
      }

      // Select is disabled (read-only), but we still call once on load
      updateCard();
    }

    // File details below Contract File field (same UX as NEW)
    var fileInput = document.getElementById('contract_file');
    var fileInfo  = document.getElementById('contract_file_info');

    if (fileInput && fileInfo) {
      fileInput.addEventListener('change', function () {
        if (!fileInput.files || !fileInput.files.length) {
          fileInfo.textContent = '';
          return;
        }
        var f    = fileInput.files[0];
        var size = (f.size / 1024 / 1024).toFixed(2); // MB
        fileInfo.textContent = 'Selected: ' + f.name + ' (' + size + ' MB, ' + (f.type || 'unknown type') + ')';
      });
    }
  })();
</script>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-2 mb-3 rounded-3 shadow-sm">
    <div>
      <h1 class="h6 mb-0">New Staff Contract</h1>
    </div>
    <div>
      <a href="<?= site_url('contracts'); ?>" class="btn btn-light-primary btn-header">
        <i class="ti ti-arrow-left me-1"></i> Back to list
      </a>
    </div>
  </div>

<div class="card">
    <div class="card-body">
      <form action="<?= site_url('contracts/store'); ?>" method="post" enctype="multipart/form-data" class="row g-3 app-form">

        <!-- LEFT: staff selection + user details -->
        <div class="col-12 col-lg-4">
          <div class="mb-3">
            <label class="form-label">Staff Member <span class="text-danger">*</span></label>
            <select name="user_id" id="contract_user_id" class="form-select" required>
              <option value="">Select staff...</option>
              <?php foreach ($staff_list as $u): ?>
                <?php
                $id   = (int)$u['id'];
                $name = !empty($u['fullname'])
                  ? $u['fullname']
                  : trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
                ?>
                <option value="<?= $id; ?>" <?= set_select('user_id', $id); ?>>
                  <?= html_escape($name); ?>
                  <?php if (!empty($u['emp_id'])): ?>
                    (<?= emp_id_display($u['emp_id']); ?>)
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Staff detail card populated by JS -->
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


<?php 

    // helper to read JSON arrays from options (trim + dedupe)
    if (!function_exists('read_option_array')) {
        function read_option_array($key) {
            $json = function_exists('get_setting') ? (string) get_setting($key, '[]') : '[]';
            $arr  = json_decode($json, true);
            if (!is_array($arr)) return [];
            $arr = array_map(function($v){ return trim((string)$v); }, $arr);
            return array_values(array_unique(array_filter($arr, 'strlen')));
        }
    }
    
        $contract_types      = read_option_array('contract_types');
    
    ?>
    
        <!-- RIGHT: contract fields -->
        <div class="col-12 col-lg-8">
          <div class="row g-3">
            <div class="col-md-4">
                <label for="contract_type" class="form-label">Contract Type <span class="text-danger">*</span></label>
                <select class="form-select" id="contract_type" name="contract_type" required>
                    <option value="">Select Contract Type</option>
            <?php foreach ($contract_types as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= (($user['contract_type'] ?? '') === $opt) ? 'selected' : '' ?>>
                    <?= e($opt) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                    <?php if (empty($contract_types)): ?>
                <div class="form-text text-muted">No Contract Types configured in System Options.</div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date"
                     name="start_date"
                     class="form-control"
                     required
                     value="<?= set_value('start_date'); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date"
                     name="end_date"
                     class="form-control"
                     required
                     value="<?= set_value('end_date'); ?>">
            </div>
            
            <div class="col-md-4">
              <label class="form-label">Notice Period (days)</label>
              <input type="number"
                     name="notice_period_days"
                     class="form-control"
                     value="<?= set_value('notice_period_days', 30); ?>"
                     min="0">
            </div>

            <div class="col-md-4 d-flex align-items-center">
              <div class="form-check mt-4">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_renewable"
                       id="is_renewable"
                       value="1"
                       <?= set_checkbox('is_renewable', '1', true); ?>>
                <label class="form-check-label" for="is_renewable">
                  Is Renewable?
                </label>
              </div>
            </div>

            <div class="col-md-12">
              <label class="form-label">Contract File (PDF / DOC / Image) <span class="text-danger">*</span></label>
              <input type="file" name="contract_file" id="contract_file" class="form-control" required>
              <div id="contract_file_info" class="form-text text-muted mt-1"></div>
            </div>
            
            <div class="col-12">
              <label class="form-label">Internal Notes</label>
              <textarea name="internal_notes"
                        rows="6"
                        class="form-control"
                        placeholder="Any HR notes, probation remarks, clauses, etc."><?= set_value('internal_notes'); ?></textarea>
            </div>

          </div>
        </div>

        <div class="col-12">
          <hr>
          <a href="<?= site_url('contracts'); ?>" class="btn btn-light-primary btn-sm">Cancel</a>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-check me-1"></i> Save Contract
          </button>
          
        </div>
      </form>
    </div>
  </div>
</div>

<?php
// Build formatted staff details for the UI (using helpers)
$ui_staff_details = [];
if (!empty($staff_details) && is_array($staff_details)) {
    foreach ($staff_details as $id => $s) {
        $id       = (int) $id;
        $fullname = $s['fullname'] ?? '';

        $ui_staff_details[$id] = [
            'fullname'        => $fullname,
            'firstname'       => $s['firstname'] ?? '',
            'initials'        => $s['initials'] ?? '',
            'lastname'        => $s['lastname'] ?? '',
            'user_role'       => $s['user_role'] ?? '',
            // Emp ID with prefix
            'emp_id'          => !empty($s['emp_id']) ? emp_id_display($s['emp_id']) : '—',
            'emp_title'       => $s['emp_title'] ?? '',
            'emp_department'  => $s['emp_department'] ?? '',
            // IMPORTANT: this should already be the team name, not ID (see note below)
            'emp_team'        => $s['emp_team'] ?? '',
            // Joining date formatted
            'emp_joining'     => !empty($s['emp_joining']) ? format_date($s['emp_joining']) : '—',
            'employment_type' => $s['employment_type'] ?? '',
            // Salaries formatted as currency
            'joining_salary'  => (isset($s['joining_salary']) && $s['joining_salary'] !== '')
                                    ? c_format((float) $s['joining_salary'])
                                    : '—',
            'current_salary'  => (isset($s['current_salary']) && $s['current_salary'] !== '')
                                    ? c_format((float) $s['current_salary'])
                                    : '—',
            // Rendered avatar HTML (profile image + name)
            'avatar_html'     => !empty($fullname) ? user_profile($fullname) : '',
        ];
    }
}
?>

<script>
  (function() {
    // Staff details mapping from controller
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
        teamEl.textContent    = s.emp_team || '—';          // already team NAME
        joiningEl.textContent = s.emp_joining || '—';        // formatted date
        empTypeEl.textContent = s.employment_type || '—';
        joinSalEl.textContent = s.joining_salary || '—';     // formatted currency
        curSalEl.textContent  = s.current_salary || '—';     // formatted currency

        card.classList.remove('d-none');

      }

      selectEl.addEventListener('change', updateCard);
      updateCard(); // initial if value persisted
    }

    // File details below Contract File field
    const fileInput = document.getElementById('contract_file');
    const fileInfo  = document.getElementById('contract_file_info');

    if (fileInput && fileInfo) {
      fileInput.addEventListener('change', function () {
        if (!fileInput.files || !fileInput.files.length) {
          fileInfo.textContent = '';
          return;
        }
        const f    = fileInput.files[0];
        const size = (f.size / 1024 / 1024).toFixed(2); // MB
        fileInfo.textContent = `Selected: ${f.name} (${size} MB, ${f.type || 'unknown type'})`;
      });
    }
  })();
</script>

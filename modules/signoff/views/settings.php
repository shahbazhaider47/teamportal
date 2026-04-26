<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="card-body">
<?php
// Normalize settings array
$S = [];
if (isset($existing) && is_array($existing)) {
  $S = $existing;
} elseif (isset($existing_data) && is_array($existing_data)) {
  $S = $existing_data;
}

// ---- Current values with sane defaults
$allowSignoff      = $S['enable_signoff_submissions']   ?? 'no';      // yes|no
$defaultPeriod     = $S['signoff_default_period']       ?? '';        // daily|weekly|monthly
$perfIndicators    = $S['signoff_perf_indicators']      ?? '';        // points|targets|none
$allowBackdated    = $S['signoff_allow_backdated']      ?? '';        // yes|no
$autoApprove       = $S['signoff_auto_approve']         ?? '';        // yes|no

$lockAfterSubmit   = $S['signoff_lock_after_submit']    ?? '';
$retentionYears    = $S['signoff_retention_years']      ?? '';
$currentTz         = $S['signoff_default_timezone']     ?? '';   // PHP timezone identifier

// ---- Exclude positions: robustly normalize to STRING id array ----
$excludePositionIdsRaw = $S['signoff_exclude_position_ids'] ?? [];

/**
 * Convert a mixed input (array | csv string | JSON string | serialized string)
 * into an array of string ids, deduped and trimmed.
 */
$toStringIdArray = static function ($raw) {
  $arr = [];

  if (is_array($raw)) {
    $arr = $raw;

  } elseif (is_string($raw)) {
    $s = trim($raw);

    // JSON array?
    if ($s !== '' && ($s[0] === '[' || $s[0] === '{')) {
      $jd = json_decode($s, true);
      if (is_array($jd)) {
        $arr = $jd;
      } else {
        // fallback to CSV
        $arr = array_map('trim', explode(',', $s));
      }

    // Serialized PHP?
    } elseif (preg_match('/^a:\d+:\{.*\}$/s', $s)) {
      $ud = @unserialize($s);
      if (is_array($ud)) {
        $arr = $ud;
      } else {
        $arr = array_map('trim', explode(',', $s));
      }

    } else {
      // CSV fallback
      $arr = array_map('trim', explode(',', $s));
    }
  }

  // Normalize to strings and dedupe + drop empties
  $arr = array_values(array_unique(array_filter(array_map(static function($v){
    return (string)$v;
  }, $arr), static function($v){ return $v !== ''; })));

  return $arr;
};

$excludePositionIds = $toStringIdArray($excludePositionIdsRaw);

// --- Load POSITIONS (works with HMVC modules or app models) ---
$positions = $positions ?? ($positionsList ?? ($all_positions ?? []));
if (empty($positions)) {
    $CI = isset($CI) ? $CI : get_instance();

    if (!isset($CI->Hrm_positions_model)) {
        // try module model then app model
        $CI->load->model('Hrm_positions_model');
        if (!isset($CI->Hrm_positions_model)) {
            $CI->load->model('Hrm_positions_model');
        }
    }

    if (isset($CI->Hrm_positions_model)) {
        if (method_exists($CI->Hrm_positions_model, 'get_all_positions')) {
            $positions = (array) $CI->Hrm_positions_model->get_all_positions();
        } elseif (method_exists($CI->Hrm_positions_model, 'get_all')) {
            $positions = (array) $CI->Hrm_positions_model->get_all();
        }
    }

    if (empty($positions)) {
$positions = $CI->db->select('id, title')
                    ->from('hrm_positions')
                    ->order_by('title','ASC')
                    ->get()
                    ->result_array();

    }
}

// Build a simple [id => title] map for quick lookups & JS
$positionsMap = [];
foreach ($positions as $p) {
  $pid = (string)($p['id'] ?? $p->id ?? '');
  $ptt = (string)($p['title'] ?? $p->name ?? 'Position');
  if ($pid !== '') { $positionsMap[$pid] = $ptt; }
}

// === Load ACTIVE users ===
$CI = isset($CI) ? $CI : get_instance();
$usersActive = [];
if (file_exists(APPPATH.'models/User_model.php')) {
  $CI->load->model('User_model');
  if (isset($CI->User_model)) {
    if (method_exists($CI->User_model, 'get_active_users')) {
      $usersActive = $CI->User_model->get_active_users();
    } elseif (method_exists($CI->User_model, 'get_all_users')) {
      $allUsers = $CI->User_model->get_all_users(true);
      foreach ((array)$allUsers as $u) {
        $isActive = (int)($u['is_active'] ?? $u->is_active ?? 0);
        if ($isActive === 1) $usersActive[] = is_array($u) ? $u : (array)$u;
      }
    }
  }
}
if (empty($usersActive)) {
  $usersActive = $CI->db->select('id, firstname, lastname, email')
                        ->from('users')
                        ->where('is_active', 1)
                        ->order_by('firstname', 'ASC')
                        ->order_by('lastname', 'ASC')
                        ->get()->result_array();
}

// Helper to format user display
$fmtUser = function($u) {
  $first = (string)($u['firstname'] ?? $u->firstname ?? '');
  $last  = (string)($u['lastname']  ?? $u->lastname  ?? '');
  $name  = trim($first.' '.$last);
  $mail  = (string)($u['email']     ?? $u->email     ?? '');
  return trim($name) !== '' ? $name . ($mail ? ' • '.$mail : '') : ($mail ?: 'User');
};
?>

  <div class="settings-section">
    <div class="row app-form">

      <!-- Enable/Disable Signoff -->
      <div class="col-md-4 mb-3">
        <label class="form-label">Enable Signoff Submissions</label>
        <select name="settings[enable_signoff_submissions]" class="form-select" id="enableSignoff">
          <option value="yes" <?= $allowSignoff === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no"  <?= $allowSignoff === 'no'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <!-- Everything below depends on enable = yes -->
      <div class="col-12"></div>
      <div class="col-12 signoff-dependent <?= $allowSignoff === 'yes' ? '' : 'd-none' ?>">

        <div class="row">
          <!-- Default Period -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Default Period</label>
            <select name="settings[signoff_default_period]" class="form-select">
              <option value="daily"   <?= $defaultPeriod === 'daily'   ? 'selected' : '' ?>>Daily</option>
              <option value="weekly"  <?= $defaultPeriod === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
              <option value="monthly" <?= $defaultPeriod === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>
          </div>

          <!-- Performance Indicators -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Performance Indicators</label>
            <select name="settings[signoff_perf_indicators]" class="form-select">
              <option value="points"  <?= $perfIndicators === 'points'  ? 'selected' : '' ?>>Points</option>
              <option value="targets" <?= $perfIndicators === 'targets' ? 'selected' : '' ?>>Targets</option>
              <option value="none"    <?= $perfIndicators === 'none'    ? 'selected' : '' ?>>None</option>
            </select>
          </div>

          <!-- Allow Backdated -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Allow Backdated Signoff</label>
            <select name="settings[signoff_allow_backdated]" class="form-select">
              <option value="yes" <?= $allowBackdated === 'yes' ? 'selected' : '' ?>>Yes</option>
              <option value="no"  <?= $allowBackdated === 'no'  ? 'selected' : '' ?>>No</option>
            </select>
          </div>

          <!-- Auto Approve -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Auto Sign-Off Approval</label>
            <select name="settings[signoff_auto_approve]" class="form-select" id="autoApprove">
              <option value="yes" <?= $autoApprove === 'yes' ? 'selected' : '' ?>>Yes</option>
              <option value="no"  <?= $autoApprove === 'no'  ? 'selected' : '' ?>>No</option>
            </select>
          </div>

          <!-- Lock after Submission -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Lock after Submission</label>
            <select name="settings[signoff_lock_after_submit]" class="form-select">
              <option value="yes" <?= $lockAfterSubmit === 'yes' ? 'selected' : '' ?>>Yes</option>
              <option value="no"  <?= $lockAfterSubmit === 'no'  ? 'selected' : '' ?>>No</option>
            </select>
          </div>

          <!-- Retention Period -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Retention Period (Years)</label>
            <input type="number"
                   min="1"
                   step="1"
                   class="form-control"
                   name="settings[signoff_retention_years]"
                   value="<?= html_escape((string)$retentionYears) ?>"
                   placeholder="e.g., 3">
          </div>

          <!-- Signoff Timezone -->
          <div class="col-md-4 mb-3">
            <label class="form-label">Signoff Timezone</label>
            <select name="settings[signoff_default_timezone]" class="form-select">
              <option value="">Server Default (<?= date_default_timezone_get() ?>)</option>
              <?php
                // US timezones only
                $__usTz = [
                    'America/Chicago'   => 'Chicago (GMT-5 / CDT)',
                    'America/Denver'    => 'Denver (GMT-6 / MDT)',
                    'America/Phoenix'   => 'Phoenix (GMT-7, no DST)',
                    'America/Los_Angeles' => 'Los Angeles (GMT-7 / PDT)',
                    'America/Anchorage' => 'Anchorage (GMT-8 / AKDT)',
                    'Pacific/Honolulu'  => 'Honolulu (GMT-10, no DST)',
                ];
                foreach ($__usTz as $__tzId => $__tzLabel):
              ?>
                <option value="<?= html_escape($__tzId) ?>"
                        <?= $currentTz === $__tzId ? 'selected' : '' ?>>
                  <?= html_escape($__tzLabel) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted d-block mt-1">
              All signoff dates and timestamps use this timezone.
              Leave blank to use the server default (<?= date_default_timezone_get() ?>).
            </small>
          </div>

          <!-- Exclude Positions (multi) -->
          <div class="col-md-12">
            <div class="select_primary">
              <label class="form-label">Exclude Positions</label>
              <select
                name="settings[signoff_exclude_position_ids][]"
                class="form-select select-positions w-100"
                id="exclude_positions"
                multiple="multiple"
                data-placeholder="Select Positions">
                <?php foreach ($positions as $p):
                  $pid = (string)($p['id'] ?? $p->id ?? '');
                  $ptt = (string)($p['title'] ?? $p->name ?? 'Position'); ?>
                  <option value="<?= html_escape($pid) ?>" <?= in_array($pid, $excludePositionIds, true) ? 'selected' : '' ?>>
                    <?= html_escape($ptt) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted d-block mb-2">Selected positions will be excluded from signoff workflow.</small>

              <!-- Readable list of excluded positions -->
              <div id="excludedPositionsList" class="mt-1">
                <!-- badges injected by JS -->
              </div>
            </div>
          </div>

        </div>

      </div><!-- /.signoff-dependent -->

    </div><!-- /.row -->
  </div><!-- /.settings-section -->
</div>

<script>
(function() {
  const enableEl = document.getElementById('enableSignoff');
  const depBlock = document.querySelector('.signoff-dependent');
  const autoEl   = document.getElementById('autoApprove');

  if (enableEl) {
    enableEl.addEventListener('change', function() {
      if (this.value === 'yes') {
        depBlock && depBlock.classList.remove('d-none');
      } else {
        depBlock && depBlock.classList.add('d-none');
      }
    });
  }

  if (autoEl) {
    autoEl.addEventListener('change', function() {
      if (this.value === 'no') {
        revBlock && revBlock.classList.remove('d-none');
      } else {
        revBlock && revBlock.classList.add('d-none');
      }
    });
  }

  // --- Live "Currently excluded" badges ---
  const positionsMap = <?=
    json_encode($positionsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
  ?>;

  const selectEl = document.getElementById('exclude_positions');
  const listEl   = document.getElementById('excludedPositionsList');

  function renderExcluded() {
    if (!listEl || !selectEl) return;
    const vals = Array.from(selectEl.selectedOptions).map(o => o.value);
    listEl.innerHTML = '';

    if (!vals.length) {
      listEl.innerHTML = '<span class="text-muted">Currently excluded: None</span>';
      return;
    }
    const frag = document.createDocumentFragment();
    const label = document.createElement('div');
    label.className = 'mb-1 small text-muted';
    label.textContent = 'Currently excluded:';
    frag.appendChild(label);

    vals.forEach(function(id){
      const name = positionsMap[id] || ('#' + id);
      const span = document.createElement('span');
      span.className = 'badge bg-secondary me-1 mb-1';
      span.textContent = name;
      frag.appendChild(span);
    });
    listEl.appendChild(frag);
  }

  if (selectEl) {
    selectEl.addEventListener('change', renderExcluded);
    // Initial render
    renderExcluded();
  }
})();
</script>
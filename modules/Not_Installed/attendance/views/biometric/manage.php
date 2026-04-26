<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Compatibility shim: some layouts pass variables as $view_data['x'] instead of extracting them.
if (!isset($devices)) {
    if (isset($view_data) && is_array($view_data) && isset($view_data['devices'])) {
        $devices = $view_data['devices'];
    }
}
// Final hardening.
$devices = is_array($devices ?? null) ? $devices : [];

// Helper for site_url since we set form action dynamically in JS as well
$base_device_form = site_url('attendance/biometric/device_form');
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
        
      <a href="<?= site_url('attendance/biometric/settings') ?>" class="btn btn-outline-primary btn-header">Settings</a>
        
        <div class="btn-divider"></div>

      <button class="btn btn-primary btn-header" onclick="openDeviceModal('add')">Add Device</button>
      
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'bioTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
    
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
        
      </div>
    </div>
    
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle mb-0 table-sm table-border-bottom" id="bioTable">
        <thead class="bg-light-primary">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Device Port</th>
            <th>IP Address</th> 
            <th>Comm Key</th>            
            <th>Status</th>
            <th>Last Fetch</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($devices)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No devices configured.</td></tr>
          <?php else: foreach ($devices as $d): if (!is_array($d)) continue; ?>
            <tr data-device-id="<?= (int)($d['id'] ?? 0) ?>">
              <td><?= (int)($d['id'] ?? 0) ?></td>
              <td><?= html_escape($d['name'] ?? '') ?></td>
              <td><?= html_escape(($d['port'] ?? '')) ?></td>
              <td><?= html_escape(($d['ip_address'] ?? '')) ?></td>
              <td><?= html_escape(($d['comm_key'] ?? '')) ?></td>              
              <td>
                <?php if ((int)($d['is_active'] ?? 0) === 1): ?>
                  <span class="badge bg-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
              </td>
              <td><?= !empty($d['last_fetch_at']) ? html_escape($d['last_fetch_at']) : '-' ?></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group" aria-label="Device actions">
                    <a class="btn btn-outline-primary"
                       href="<?= site_url('attendance/biometric/logs/'.((int)($d['id'] ?? 0))) ?>"
                       data-bs-toggle="tooltip" data-bs-title="View Logs">
                      <i class="ti ti-notebook me-2"></i>Logs
                    </a>
                
                    <a class="btn btn-outline-info"
                       href="<?= site_url('attendance/biometric/map_users/'.((int)($d['id'] ?? 0))) ?>"
                       data-bs-toggle="tooltip" data-bs-title="Map Users">
                      <i class="ti ti-users me-2"></i>User Map
                    </a>

                    <button type="button"
                       class="btn btn-outline-success"
                       onclick="pingDevice(<?= (int)($d['id'] ?? 0) ?>, this)"
                       data-bs-toggle="tooltip" data-bs-title="Ping Device">
                      <i class="ti ti-broadcast me-2"></i>Ping
                    </button>
                    
                    <button type="button"
                      class="btn btn-outline-secondary"
                      data-bs-toggle="tooltip" data-bs-title="Edit Device"
                      onclick='openDeviceModal("edit", <?= json_encode([
                        'id'         => (int)($d['id'] ?? 0),
                        'name'       => (string)($d['name'] ?? ''),
                        'ip_address' => (string)($d['ip_address'] ?? ''),
                        'port'       => (int)($d['port'] ?? 4370),
                        'comm_key'   => (string)($d['comm_key'] ?? ''),
                        'device_sn'  => (string)($d['device_sn'] ?? ''),
                        'timezone'   => (string)($d['timezone'] ?? (get_setting('biometric_timezone','Asia/Karachi'))),
                        'is_active'  => (int)($d['is_active'] ?? 1),
                      ], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>
                      <i class="ti ti-edit me-2"></i>Edit
                    </button>
                    
                    <a class="btn btn-outline-danger"
                       href="<?= site_url('attendance/biometric/delete_device/'.((int)($d['id'] ?? 0))) ?>"
                       onclick="return confirm('Delete this device?')"
                       data-bs-toggle="tooltip" data-bs-title="Delete Device">
                      <i class="ti ti-trash me-2"></i>Delete
                    </a>
                
                  </div>
                </td>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Device Add/Edit Modal -->
<div class="modal fade" id="deviceModal" tabindex="-1" aria-labelledby="deviceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <?= form_open('', ['id' => 'deviceForm']) ?>
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="deviceModalLabel">Add Device</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body app-form">
          <input type="hidden" id="device_id_hidden">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" id="f_name" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">IP Address</label>
              <input class="form-control" name="ip_address" id="f_ip" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Port</label>
              <input class="form-control" name="port" id="f_port" type="number" value="4370">
            </div>
            <div class="col-md-3">
              <label class="form-label">Comm Key</label>
              <input class="form-control" name="comm_key" id="f_comm_key" placeholder="e.g., 0 or 12345">
            </div>
            <div class="col-md-4">
              <label class="form-label">Device SN</label>
              <input class="form-control" name="device_sn" id="f_sn">
            </div>
            <div class="col-md-4">
              <label class="form-label">Timezone</label>
              <input class="form-control" name="timezone" id="f_tz" value="<?= html_escape(get_setting('biometric_timezone','Asia/Karachi')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Active</label>
              <select class="form-select" name="is_active" id="f_active">
                <option value="1" selected>Yes</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="deviceSaveBtn">Save Device</button>
        </div>
      <?= form_close() ?>
    </div>
  </div>
</div>

<script>
const baseFormUrl = '<?= $base_device_form ?>'; // create: /device_form ; edit: /device_form/{id}
let deviceModal;

/** Open modal in add or edit mode */
function openDeviceModal(mode = 'add', data = null) {
  // Lazy init bootstrap modal
  if (!deviceModal) {
    const el = document.getElementById('deviceModal');
    deviceModal = new bootstrap.Modal(el);
  }

  // Reset fields
  document.getElementById('deviceForm').reset();
  document.getElementById('device_id_hidden').value = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_ip').value = '';
  document.getElementById('f_port').value = 4370;
  document.getElementById('f_comm_key').value = '';
  document.getElementById('f_sn').value = '';
  document.getElementById('f_tz').value = '<?= html_escape(get_setting('biometric_timezone','Asia/Karachi')) ?>';
  document.getElementById('f_active').value = '1';

  const form = document.getElementById('deviceForm');
  const title = document.getElementById('deviceModalLabel');

  if (mode === 'edit' && data && Number(data.id) > 0) {
    title.textContent = 'Edit Device';
    document.getElementById('device_id_hidden').value = data.id;
    document.getElementById('f_name').value     = data.name || '';
    document.getElementById('f_ip').value       = data.ip_address || '';
    document.getElementById('f_port').value     = data.port || 4370;
    document.getElementById('f_comm_key').value = data.comm_key || '';
    document.getElementById('f_sn').value       = data.device_sn || '';
    document.getElementById('f_tz').value       = data.timezone || '<?= html_escape(get_setting('biometric_timezone','Asia/Karachi')) ?>';
    document.getElementById('f_active').value   = String((data.is_active ?? 1));

    // Post to device_form/{id}
    form.setAttribute('action', baseFormUrl + '/' + data.id);
  } else {
    title.textContent = 'Add Device';
    // Post to device_form
    form.setAttribute('action', baseFormUrl);
  }

  deviceModal.show();
}

</script>
<script>
async function pingDevice(id, btn) {
  if (!id) { alert('Invalid device id.'); return; }
  const orig = btn.innerText;
  btn.disabled = true; btn.innerText = 'Pinging…';
  try {
    const res = await fetch('<?= site_url('attendance/biometric/ping/') ?>' + id, {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    });

    const ctype = res.headers.get('content-type') || '';
    let payload;
    if (ctype.includes('application/json')) {
      payload = await res.json();
    } else {
      const text = await res.text();
      throw new Error(`Unexpected response (not JSON): ${text.substring(0, 120)}…`);
    }

    if (!res.ok || !payload.ok) {
      throw new Error(payload.message || 'Ping failed');
    }

    alert('✅ ' + payload.message);
    location.reload();
  } catch (e) {
    alert('❌ ' + (e.message || 'Ping failed'));
  } finally {
    btn.disabled = false; btn.innerText = orig;
  }
}
</script>


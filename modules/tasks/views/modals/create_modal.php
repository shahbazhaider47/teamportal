<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();

// --------------------------------------------------
// Check if Projects module is active
// --------------------------------------------------
$projects_enabled = false;

$projMod = $CI->db
    ->select('active')
    ->from('tblmodules')
    ->where('module_name', 'projects')
    ->limit(1)
    ->get()
    ->row_array();

if ($projMod && (int)$projMod['active'] === 1) {
    $projects_enabled = true;
}


if (!isset($assignees) || !is_array($assignees) || !$assignees) {
  $assignees = $CI->db->select('id, TRIM(CONCAT(COALESCE(firstname,"")," ",COALESCE(lastname,""))) AS fullname', false)
    ->from('users')->where('is_active', 1)
    ->order_by('fullname','ASC')->get()->result_array();
}

$rel_types = [
  ''        => '— None —',
];

if ($projects_enabled) {
    $rel_types['project'] = 'Project';
}

$rel_types['signoff'] = 'Signoff Form';
$rel_types['support'] = 'Support Ticket';


$projects = [];

if ($projects_enabled) {
    if (!isset($projects) || !is_array($projects)) {
        $projects = $CI->db
            ->select('id, name')
            ->from('projects')
            ->order_by('name', 'ASC')
            ->limit(200)
            ->get()
            ->result_array();
    }
}


if (!isset($signoff_forms) || !is_array($signoff_forms)) {
  $signoff_forms = $CI->db->select('id, title')->from('signoff_forms')->order_by('title','ASC')->limit(200)->get()->result_array();
}

if (!isset($support_tickets) || !is_array($support_tickets)) {
  $support_tickets = $CI->db->select('id, subject')->from('support_tickets')->order_by('id','DESC')->limit(200)->get()->result_array();
}

$default_status   = 'not_started';
$default_priority = 'normal';
?>
<style>

.form-label{
    font-size: 12px !important
}
.form-border {
    padding: 15px;
    margin: 15px 0;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

  #taskCreateModal .modal-body { max-height: calc(100vh - 220px); overflow: auto; }
  #taskCreateModal .form-control-sm,
  #taskCreateModal .form-select-sm {
    padding-top: .375rem; padding-bottom: .375rem; /* normalize */
    height: calc(1.5em + .75rem + 2px);
  }

  #taskCreateModal .related-inline { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
  @media (max-width: 767.98px) {
    #taskCreateModal .related-inline { grid-template-columns: 1fr; }
  }

  #taskCreateModal .btn-icon-sm { padding: .15rem .35rem; line-height: 1; }
</style>

<div class="modal fade" id="taskCreateModal" tabindex="-1" aria-labelledby="taskCreateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form class="app-form" id="taskCreateForm" action="<?= site_url('tasks/create') ?>" method="post" enctype="multipart/form-data">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="taskCreateModalLabel">
            <i class="ti ti-plus me-2"></i>Create Task
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 form-border">

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="swVisibleTeam" name="visible_to_team" value="1" checked>
                <label class="form-check-label small" for="swVisibleTeam">Visible to team</label>
              </div>
              <small class="small text-muted">When enabled, this task will be visible to your team only with only view permissions.</small>
            </div>
            
            <div class="col-md-9">
              <label class="form-label small">Task Title <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control form-control-sm" required
                     placeholder="e.g., Prepare monthly performance dashboard">
            </div>
            <?php
            $uid = (int) $CI->session->userdata('user_id');
            $canAssign = false;
            if (isset($CI->policy)) {
                $canAssign = $CI->policy->is_super_admin($uid) || (function_exists('staff_can') ? staff_can('assign', 'tasks') : false);
            } elseif (function_exists('staff_can')) {
                $canAssign = staff_can('assign', 'tasks');
            }
            
            $current_user_name = 'You';
            foreach ((array)$assignees as $u) {
                if ((int)$u['id'] === $uid) {
                    $current_user_name = trim($u['fullname']) !== '' ? $u['fullname'] : ('User#'.$uid);
                    break;
                }
            }
            if ($current_user_name === 'You') {
                $me = $CI->db->select('TRIM(CONCAT(COALESCE(firstname,"")," ",COALESCE(lastname,""))) AS fullname', false)
                             ->from('users')->where('id', $uid)->get()->row_array();
                if ($me && trim((string)$me['fullname']) !== '') {
                    $current_user_name = $me['fullname'];
                } else {
                    $current_user_name = 'User#'.$uid;
                }
            }
            ?>
            
            <div class="col-md-3">
              <label class="form-label small">Assignee</label>
            
              <?php if ($canAssign): ?>
                <select name="assignee_id" class="form-select form-select-sm">
                  <option value="">— Unassigned —</option>
                  <?php foreach ($assignees as $u): ?>
                    <option value="<?= (int)$u['id'] ?>">
                      <?= html_escape($u['fullname'] ?: ('User#'.$u['id'])) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input type="text" class="form-control form-control-sm" value="<?= html_escape($current_user_name) ?>" readonly>
                <input type="hidden" name="assignee_id" value="<?= (int)$uid ?>">
              <?php endif; ?>
            </div>

            <div class="col-12">
              <label class="form-label small">Description</label>
              <textarea name="description" class="form-control form-control" rows="4"
                        placeholder="What needs to be done, acceptance criteria, links…"></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Priority</label>
              <select name="priority" class="form-select form-select-sm">
                <option value="normal" <?= $default_priority==='normal'?'selected':''; ?>>Normal</option>
                <option value="low">Low</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small">Status</label>
              <select name="status" class="form-select form-select-sm">
                <option value="not_started" <?= $default_status==='not_started'?'selected':''; ?>>Not Started</option>
                <option value="in_progress">In Progress</option>
                <option value="in_review">In Review</option>
                <option value="completed">Completed</option>
                <option value="on_hold">On Hold</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small">Start Date</label>
              <input type="date" name="startdate" class="form-control basic-date" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-4">
              <label class="form-label small">Due Date</label>
              <input type="date" name="duedate" class="form-control basic-date" placeholder="YYYY-MM-DD">
            </div>

            <div class="col-md-8">
              <div class="related-inline">
                <div>
                  <label class="form-label small">Related To</label>
                  <select name="rel_type" id="relType" class="form-select form-select-sm">
                    <?php foreach ($rel_types as $k => $v): ?>
                      <option value="<?= html_escape($k) ?>"><?= html_escape($v) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div id="relDynamic" style="display:none">
                    <?php if ($projects_enabled): ?>
                    <div data-rel="project" class="rel-field" style="display:none">
                      <label class="form-label small">Select Project</label>
                      <select name="rel_id_project" class="form-select form-select-sm">
                        <option value="">— Select project —</option>
                        <?php foreach ($projects as $p): ?>
                          <option value="<?= (int)$p['id'] ?>"><?= html_escape($p['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <?php endif; ?>

                  <div data-rel="signoff" class="rel-field" style="display:none">
                    <label class="form-label small">Select Form</label>
                    <select name="rel_id_signoff" class="form-select form-select-sm">
                      <option value="">— Select signoff form —</option>
                      <?php foreach ($signoff_forms as $sf): ?>
                        <option value="<?= (int)$sf['id'] ?>"><?= html_escape($sf['title']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div data-rel="support" class="rel-field" style="display:none">
                    <label class="form-label small">Select Ticket</label>
                    <select name="rel_id_support" class="form-select form-select-sm">
                      <option value="">— Select ticket —</option>
                      <?php foreach ($support_tickets as $st): ?>
                        <option value="<?= (int)$st['id'] ?>">#<?= (int)$st['id'] ?> — <?= html_escape($st['subject']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <input type="hidden" name="rel_id" id="relIdSink" value="">
                </div>
              </div>
            </div>

            <?php
            $allowRecurring = function_exists('get_setting')
              ? in_array(strtolower((string)get_setting('tasks_allow_recurring')), ['1','yes','true','on','enabled'], true)
              : true;
            
            $isRecurring = !empty($_POST)
              ? (isset($_POST['recurring']) && (string)$_POST['recurring'] === '1')
              : (!empty($task['recurring']));
            
            $recType   = !empty($_POST) ? ($_POST['recurring_type'] ?? 'day') : ($task['recurring_type'] ?? 'day');
            $repeatEv  = !empty($_POST) ? ($_POST['repeat_every'] ?? '')      : ($task['repeat_every'] ?? '');
            $cyclesVal = !empty($_POST) ? ($_POST['cycles'] ?? '0')           : ($task['cycles'] ?? '0');
            $lastDate  = !empty($_POST) ? ($_POST['last_recurring_date'] ?? '') : ($task['last_recurring_date'] ?? '');
            ?>

            <?php if ($allowRecurring): ?>
              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch"
                         id="swRecurring" name="recurring" value="1"
                         <?= $isRecurring ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="swRecurring">Make this task recurring</label>
                </div>
              </div>
            
              <div id="recurringFields" class="row g-3" style="display: <?= $isRecurring ? 'flex' : 'none' ?>;">
                <div class="col-md-3">
                  <label class="form-label small">Recurring Type</label>
                  <select name="recurring_type" id="recurringType" class="form-select form-select-sm">
                    <option value="day"   <?= $recType==='day'   ? 'selected' : '' ?>>Day(s)</option>
                    <option value="week"  <?= $recType==='week'  ? 'selected' : '' ?>>Week(s)</option>
                    <option value="month" <?= $recType==='month' ? 'selected' : '' ?>>Month(s)</option>
                    <option value="year"  <?= $recType==='year'  ? 'selected' : '' ?>>Year(s)</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Repeat Every</label>
                  <input type="number" name="repeat_every" class="form-control form-control-sm" min="1"
                         value="<?= htmlspecialchars((string)$repeatEv, ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., 2">
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Cycles (max)</label>
                  <input type="number" name="cycles" class="form-control form-control-sm" min="0"
                         value="<?= htmlspecialchars((string)$cyclesVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="0 = unlimited">
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Last Recurring Date</label>
                  <input type="date" name="last_recurring_date" class="form-control form-control-sm"
                         value="<?= htmlspecialchars((string)$lastDate, ENT_QUOTES, 'UTF-8') ?>" placeholder="YYYY-MM-DD">
                </div>
                <input type="hidden" name="is_recurring_from" value="<?= isset($task['is_recurring_from']) ? (int)$task['is_recurring_from'] : '' ?>">
                <input type="hidden" name="total_cycles" value="<?= isset($task['total_cycles']) ? (int)$task['total_cycles'] : '0' ?>">
              </div>
            <?php else: ?>
              <input type="hidden" name="recurring" value="0">
            <?php endif; ?>


            <div class="col-12"><hr class="my-2"></div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label small mb-2">Task Checklist Items</label>
              </div>

              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="checklistTable">
                  <thead class="table-light">
                    <tr class="small text-muted">
                      <th style="width:50px" class="text-center">#</th>
                      <th>Description</th>
                      <th style="width:110px" class="text-center">Assigned</th>
                      <th style="width:110px" class="text-center">Order</th>
                      <th style="width:45px" class="text-center">Remove</th>
                    </tr>
                  </thead>
                  <tbody id="checklistBody">
                    <?php for ($i=0; $i<5; $i++): ?>
                      <tr>
                        <td class="text-center"><span class="row-index text-muted"><?= $i+1 ?></span></td>
                        <td>
                          <input type="text" class="form-control form-control-sm" name="checklist[description][]" placeholder="Describe the step...">
                          <input type="hidden" name="checklist[finished][]" value="0">
                          <input type="hidden" name="checklist[list_order][]" value="<?= $i+1 ?>">
                        </td>
                        <td class="text-center">
                          <select name="checklist[assigned][]" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php foreach ($assignees as $u): ?>
                              <option value="<?= (int)$u['id'] ?>"><?= html_escape($u['fullname'] ?: ('User#'.$u['id'])) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </td>
                        <td class="text-center">
                          <input type="number" class="form-control form-control-sm text-center" name="checklist[list_order_override][]" min="0" value="<?= $i+1 ?>">
                        </td>
                        <td class="text-center">
                          <button type="button" class="btn btn-outline-danger btn-icon-sm btnRemoveRow" title="Remove">
                            <i class="ti ti-x"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endfor; ?>
                  </tbody>
                </table>
              <div class="d-flex align-items-center justify-content-between mt-3">
                <label class="form-text">You can re-order later on the task page. Empty rows won’t be saved.</label>
                  <button type="button" class="btn btn-light-primary btn-header me-3" id="btnAddChecklist">
                    <i class="ti ti-plus"></i>
                  </button>                 
              </div>
              
              </div>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <div class="col-12">
              <label class="form-label small">Attachments</label>
              <input type="file" name="attachments[]" class="form-control form-control-sm" multiple>
              <div class="form-text">You can upload multiple files. Max size per file per system settings.</div>
            </div>

            <input type="hidden" name="milestone" value="0">
            <input type="hidden" name="kanban_order" value="1">
            <input type="hidden" name="milestone_order" value="0">
            <input type="hidden" name="deadline_notified" value="0">
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="text-danger small" id="createTaskError" style="display:none"></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="ti ti-device-floppy"></i> Create Task
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  const modal = document.getElementById('taskCreateModal');
  if (!modal) return;

  const relType    = modal.querySelector('#relType');
  const relDynamic = modal.querySelector('#relDynamic');
  const relSink    = modal.querySelector('#relIdSink');
  const recurringSwitch  = modal.querySelector('#swRecurring');
  const recurringFields  = modal.querySelector('#recurringFields');
  const checklistBody = modal.querySelector('#checklistBody');
  const btnAdd        = modal.querySelector('#btnAddChecklist');
  function show(el) { el.style.display = ''; }
  function hide(el) { el.style.display = 'none'; }
  function qsa(sel, root = modal) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
  function updateRelatedUI() {
    const val = (relType.value || '').trim();
    if (!val) {
      hide(relDynamic);
      relSink.value = '';
      qsa('.rel-field', relDynamic).forEach(hide);
      return;
    }
    show(relDynamic);
    qsa('.rel-field', relDynamic).forEach(hide);
    const block = relDynamic.querySelector('.rel-field[data-rel="'+val+'"]');
    if (block) show(block);
    let selectedId = '';
    if (val === 'project') {
      selectedId = (block.querySelector('select[name="rel_id_project"]')?.value || '').trim();
    } else if (val === 'signoff') {
      selectedId = (block.querySelector('select[name="rel_id_signoff"]')?.value || '').trim();
    } else if (val === 'support') {
      selectedId = (block.querySelector('select[name="rel_id_support"]')?.value || '').trim();
    }
    relSink.value = selectedId;
  }

  relType?.addEventListener('change', updateRelatedUI);
  qsa('.rel-field select', relDynamic).forEach(function(sel) {
    sel.addEventListener('change', updateRelatedUI);
  });

  function updateRecurringUI() {
    if (!recurringSwitch) return;
    if (recurringSwitch.checked) {
      show(recurringFields);
    } else {
      hide(recurringFields);
      qsa('#recurringFields select, #recurringFields input').forEach(function(inp){
        if (inp.type === 'checkbox' || inp.type === 'radio') { inp.checked = false; }
        else { inp.value = ''; }
      });
    }
  }
  recurringSwitch?.addEventListener('change', updateRecurringUI);

  function renumberChecklist() {
    const rows = qsa('#checklistBody tr');
    rows.forEach(function(tr, i){
      tr.querySelector('.row-index').textContent = (i + 1);
      const orderHidden = tr.querySelector('input[name="checklist[list_order][]"]');
      const orderOverride = tr.querySelector('input[name="checklist[list_order_override][]"');
      if (orderHidden) orderHidden.value = (i + 1);
      if (orderOverride && !orderOverride.value) orderOverride.value = (i + 1);
    });
  }

  function addChecklistRow(desc = '') {
    const tr = document.createElement('tr');
    tr.innerHTML =
      '<td class="text-center"><span class="row-index text-muted"></span></td>' +
      '<td>' +
        '<input type="text" class="form-control form-control-sm" name="checklist[description][]" placeholder="Describe the step..." value="'+(desc || '')+'">' +
        '<input type="hidden" name="checklist[finished][]" value="0">' +
        '<input type="hidden" name="checklist[list_order][]" value="0">' +
      '</td>' +
      '<td class="text-center">' +
        '<select name="checklist[assigned][]" class="form-select form-select-sm">' +
          '<option value="">—</option>' +
          <?php ob_start(); foreach ($assignees as $u): ?>
          '<option value="<?= (int)$u['id'] ?>"><?= html_escape($u['fullname'] ?: ('User#'.$u['id'])) ?></option>' +
          <?php endforeach; $opts = ob_get_clean(); echo json_encode($opts); ?> +
        '</select>' +
      '</td>' +
      '<td class="text-center">' +
        '<input type="number" class="form-control form-control-sm text-center" name="checklist[list_order_override][]" min="0" value="">' +
      '</td>' +
      '<td class="text-center">' +
        '<button type="button" class="btn btn-outline-danger btn-icon-sm btnRemoveRow" title="Remove">' +
          '<i class="ti ti-x"></i>' +
        '</button>' +
      '</td>';
    checklistBody.appendChild(tr);
    renumberChecklist();
  }

  function clearEmptyChecklist() {
    qsa('#checklistBody tr').forEach(function(tr){
      const val = (tr.querySelector('input[name="checklist[description][]"]')?.value || '').trim();
      if (val === '') tr.remove();
    });
    if (!checklistBody.children.length) {
      for (let i=0;i<5;i++) addChecklistRow('');
    } else {
      renumberChecklist();
    }
  }

  checklistBody?.addEventListener('click', function(e){
    const btn = e.target.closest('.btnRemoveRow');
    if (!btn) return;
    const tr = btn.closest('tr');
    tr?.remove();
    renumberChecklist();
  });

  btnAdd?.addEventListener('click', function(){ addChecklistRow(''); });

  modal.addEventListener('shown.bs.modal', function () {
    updateRelatedUI();
    updateRecurringUI();
    renumberChecklist();
  });
})();
</script>
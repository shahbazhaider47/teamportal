<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
          $canDelete = staff_can('delete', 'users');
          $table_id     = $table_id ?? 'dataTable';          
        ?>

        <a href="<?= site_url('users') ?>"
           id="btn-inactive-users"
           class="btn btn-outline-primary btn-header"
           title="View In-Active Staff">
            <i class="fas fa-users me-1"></i> Staff List
        </a>
        
        <div class="btn-divider"></div>

            <!-- Filter & Export Buttons-->
            <?php render_export_buttons([
                'filename' => $page_title ?? 'export'
            ]); ?>
      </div>
    </div>

    <!-- Universal table filter (global search + per-column filters) -->
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => ['Last Working Day', 'Exit Date', 'Actions'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
  <div class="card">
    <div class="card-body table-responsive">
        <div class="table-responsive">    
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
                  <thead class="bg-light-primary">
                    <tr>
                      <th class="text-center" width="60">EMP ID</th>
                      <th>Employee Name</th>
                      <th>Designation</th>
                      <th>Status</th>
                      <th>Exit Date</th>
                      <th>Last Working Day</th>
                      <th>Exit Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                    <tbody>
                    <?php
                      $val = function(array $arr, string $k, $default = '') {
                          return array_key_exists($k, $arr) ? $arr[$k] : $default;
                      };
                    ?>
                    
                    <?php foreach ($users as $u): ?>
                    <?php
                      $uid      = (int)($val($u,'user_id', $val($u,'id', 0)));
                      $role     = strtolower(trim($val($u,'user_role','user')));
                      $first    = trim($val($u,'firstname',''));
                      $last     = trim($val($u,'lastname',''));
                      $fullName = trim(($first.' '.$last)) ?: '-';
                      $email    = $val($u,'email','-');
                      $gender   = strtolower(trim($val($u,'gender','')));
                      $profileImg = $val($u,'profile_image','');
                      $exitType   = $val($u,'exit_type','In-Active');
                      $exitDate   = $val($u,'exit_date','');
                      $lastWork   = $val($u,'last_working_date','');
                      $exitStatus = $val($u,'exit_status','-');
                    
                      $finalAmount = $val($u,'final_settlement_amount','');
                      $currency    = get_base_currency_symbol();
                      $exitInterviewDate = $val($u,'exit_interview_date','');
                      $exitInterviewBy   = $val($u,'exit_interview_conducted_by','');
                      $noticeServed      = $val($u,'notice_period_served','');
                      $reason            = $val($u,'reason','');
                      $remarks           = $val($u,'remarks','');
                      $checklist         = $val($u,'checklist_completed','');
                      $assetsReturned    = $val($u,'assets_returned','');
                      $finalDate         = $val($u,'final_settlement_date','');
                      $ndaSigned         = $val($u,'nda_signed','');
                      $createdAt         = $val($u,'created_at','');
                      $updatedAt         = $val($u,'updated_at','');
                    
                      $hasExit = $exitType || $exitStatus || $exitDate || $lastWork || $finalAmount !== '';
                    
                    $interviewName = '';
                    if (!empty($u['exit_interview_conducted_by'])) {
                        $CI =& get_instance();
                        $CI->db->select("CONCAT(firstname, ' ', lastname) AS name");
                        $CI->db->where('id', $u['exit_interview_conducted_by']);
                        $row = $CI->db->get('users')->row_array();
                        $interviewName = $row['name'] ?? '';
                    }
                      
                    ?>
                      <tr>
                          
                        <td class="text-center">
                        <span class="text-muted align-self-center small"><?= emp_id_display($u['emp_id'] ?? '-') ?></span>
                          <?php $role = $u['user_role']; ?>
                          <span class="badge badge-role-<?= html_escape($role) ?>">
                            <?= ucfirst(html_escape($role)) ?>
                          </span>
                        </td>
                
                        <td>
                            <div class="d-flex align-items-center">
                            <img
                              src="<?= user_avatar_url($u['profile_image'] ?? null) ?>"
                              class="rounded-circle me-2"
                              width="32" height="32"
                              alt="<?= html_escape(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')) ?>">
                              <div>
                                <strong class="text-muted">
                                  <?= html_escape($u['firstname'] . ' ' . $u['lastname']) ?>
                                  <?php if (!empty($u['gender'])): ?>
                                    <?php if (strtolower($u['gender']) === 'male'): ?>
                                      <i class="ti ti-gender-male text-primary ms-1" title="Male"></i>
                                    <?php elseif (strtolower($u['gender']) === 'female'): ?>
                                      <i class="ti ti-gender-female text-danger ms-1" title="Female"></i>
                                    <?php else: ?>
                                      <i class="ti ti-gender-bigender text-muted ms-1" title="Other"></i>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </strong>
                                <div class="text-muted small"><?= html_escape($u['email']) ?></div>
                              </div>
                            </div>
                        </td>
                    
                        <td><?= html_escape($val($u,'position_title','-')) ?></td>
                        <td><?= html_escape($exitType) ?></td>
                        <td><?= !empty($exitDate) ? date('M d, Y', strtotime($exitDate)) : '-' ?></td>
                        <td><?= !empty($lastWork) ? date('M d, Y', strtotime($lastWork)) : '-' ?></td>
                        <td><?= html_escape(ucfirst($exitStatus)) ?></td>
                    
                        <td>
                          <a href="<?= site_url('users/view/'.$uid) ?>"
                             class="btn btn-outline-primary btn-header" title="View Profile">
                            <i class="ti ti-eye"></i>
                          </a>
                          
                            <?php
                              $uid      = (int)($u['user_id'] ?? 0);
                              $empName  = trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
                              $currency = get_base_currency_symbol();
                              $hasExit  = !empty($u['exit_type']) || !empty($u['exit_status']) || !empty($u['exit_date']) || !empty($u['last_working_date']) || isset($u['final_settlement_amount']);
                            ?>
                            <button type="button"
                                    class="btn btn-outline-primary btn-header btn-view-exit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#exitDetailsModal"
                                    title="View Exit Details"
                            
                                    data-emp-name="<?= html_escape($empName) ?>"
                                    data-exit-type="<?= html_escape($u['exit_type'] ?? '') ?>"
                                    data-exit-status="<?= html_escape($u['exit_status'] ?? '') ?>"
                                    data-final-amount="<?= html_escape($u['final_settlement_amount'] ?? '') ?>"
                                    data-exit-date="<?= html_escape($u['exit_date'] ?? '') ?>"
                                    data-last-working="<?= html_escape($u['last_working_date'] ?? '') ?>"
                                    data-exit-interview-date="<?= html_escape($u['exit_interview_date'] ?? '') ?>"
                                    data-interviewed-by="<?= html_escape($interviewName) ?>"
                                    data-notice-served="<?= html_escape($u['notice_period_served'] ?? '') ?>"
                                    data-reason="<?= html_escape($u['reason'] ?? '') ?>"
                                    data-remarks="<?= html_escape($u['remarks'] ?? '') ?>"
                                    data-checklist="<?= html_escape($u['checklist_completed'] ?? '') ?>"
                                    data-assets-returned="<?= html_escape($u['assets_returned'] ?? '') ?>"
                                    data-final-date="<?= html_escape($u['final_settlement_date'] ?? '') ?>"
                                    data-nda="<?= html_escape($u['nda_signed'] ?? '') ?>"
                                    data-created-at="<?= html_escape($u['created_at'] ?? '') ?>"
                                    data-updated-at="<?= html_escape($u['updated_at'] ?? '') ?>"
                                    data-currency="<?= html_escape($currency) ?>"
                                    <?= $hasExit ? '' : 'disabled' ?>>
                              <i class="ti ti-door-exit"></i>
                            </button>

                            <button type="button"
                                    class="btn btn-outline-success btn-header btn-reactivate"
                                    data-bs-toggle="modal"
                                    data-bs-target="#statusModal"
                                    data-user-id="<?= (int)$uid ?>"
                                    data-emp-id="<?= html_escape($val($u,'emp_id','-')) ?>"
                                    data-emp-name="<?= html_escape($fullName) ?>"
                                    title="Reactivate / Re-Join">
                              <i class="ti ti-refresh"></i>
                            </button>
                             
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
          </div>
    </div>
</div>
<?php $CI->load->view('users/modals/view_exit_info_modal'); ?>
<?php $CI->load->view('users/modals/profile/status_modal'); ?>

<script>
(function () {
  "use strict";

  /* ---------------------------
   * Helpers
   * --------------------------- */
  function dash(v) {
    return (v === null || v === undefined || String(v).trim() === "") ? "—" : String(v);
  }

  function fmtDate(d) {
    if (!d) return "—";
    try {
      // Normalize YYYY-MM-DD into a safer parse
      const dt = new Date(String(d).replace(/-/g, "/"));
      if (isNaN(dt)) return dash(d);
      return dt.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
    } catch (e) {
      return dash(d);
    }
  }

  function fmtMoney(v, symbol) {
    if (v === null || v === "" || typeof v === "undefined") return "—";
    const num = Number(v);
    if (Number.isNaN(num)) return dash(v);
    return (symbol || "") + new Intl.NumberFormat(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(num);
  }

  function fmtBool(v) {
    const t = String(v).toLowerCase().trim();
    if (["1", "true", "yes", "y"].includes(t)) return "Yes";
    if (["0", "false", "no", "n"].includes(t)) return "No";
    return dash(v);
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  /* ---------------------------
   * Delegated click handlers
   * --------------------------- */
  document.addEventListener("click", function (e) {
    /* === Exit details modal population === */
    const exitBtn = e.target.closest(".btn-view-exit");
    if (exitBtn) {
      const ds = exitBtn.dataset;
      const currency = ds.currency || "";

      setText("exitEmpName", dash(ds.empName));
      setText("m_exit_type", dash(ds.exitType));
      setText("m_exit_status", dash(ds.exitStatus));
      setText("m_final_settlement", fmtMoney(ds.finalAmount, currency));
      setText("m_exit_date", fmtDate(ds.exitDate));
      setText("m_last_working", fmtDate(ds.lastWorking));
      setText("m_interview_date", fmtDate(ds.exitInterviewDate));
      setText("m_interview_by", dash(ds.interviewedBy));
      setText("m_notice_served", fmtBool(ds.noticeServed));
      setText("m_reason", dash(ds.reason));
      setText("m_remarks", dash(ds.remarks));
      setText("m_checklist", fmtBool(ds.checklist));
      setText("m_assets_returned", fmtBool(ds.assetsReturned));
      setText("m_final_date", fmtDate(ds.finalDate));
      setText("m_nda", fmtBool(ds.nda));
      setText("m_created_at", fmtDate(ds.createdAt));
      setText("m_updated_at", fmtDate(ds.updatedAt));

      return;
    }

    /* === Delete confirmation === */
    const delBtn = e.target.closest(".btn-delete-user");
    if (delBtn) {
      e.preventDefault();

      const form = delBtn.closest("form");
      if (!form) return;

      const name = (delBtn.getAttribute("data-user-name") || "").trim() || "this user";
      const msg = `Delete ${name}? This action is permanent and cannot be undone.`;

      if (confirm(msg)) form.submit();
      return;
    }

    /* === Reactivate (Re-Join) modal setup === */
    const reactBtn = e.target.closest(".btn-reactivate");
    if (reactBtn) {
      const userId = (reactBtn.dataset.userId || "").trim();
      if (!userId) return;

      const empId = (reactBtn.dataset.empId || "").trim();
      const empName = (reactBtn.dataset.empName || "").trim();

      // Identity block (ensure these IDs exist in the modal)
      setText("reactivate_emp_name", empName || "—");
      setText("reactivate_emp_id", empId || "—");

      // Hidden user_id
      const hidden = document.getElementById("reactivate_user_id");
      if (hidden) hidden.value = userId;

      // Form action from modal dataset
      const modalEl = document.getElementById("statusModal");
      const baseUrl = modalEl ? (modalEl.getAttribute("data-reactivate-url") || "").trim() : "";
      const form = document.getElementById("reactivateForm");
      if (form && baseUrl) form.action = baseUrl.replace(/\/$/, "") + "/" + userId;

      // Reset fields on open (optional but recommended)
      if (form) {
        const dateInput = form.querySelector('input[name="rejoin_date"]');
        const reasonSel = document.getElementById("rejoin_reson");
        const customWrap = document.getElementById("rejoin_custom_wrap");
        const customInput = form.querySelector('input[name="rejoin_reson_custom"]');

        if (dateInput) dateInput.value = "";
        if (reasonSel) reasonSel.value = "";
        if (customWrap) customWrap.classList.add("d-none");
        if (customInput) customInput.value = "";
      }

      return;
    }
  }, false);

  /* ---------------------------
   * Delegated change handler
   * --------------------------- */
  document.addEventListener("change", function (e) {
    if (e.target && e.target.id === "rejoin_reson") {
      const wrap = document.getElementById("rejoin_custom_wrap");
      if (!wrap) return;

      const v = (e.target.value || "").toLowerCase();
      if (v === "other") wrap.classList.remove("d-none");
      else wrap.classList.add("d-none");
    }
  }, false);

})();
</script>
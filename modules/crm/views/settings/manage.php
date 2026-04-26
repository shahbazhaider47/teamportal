<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
.crm-group {
    min-width: 250px;
    max-width: 250px;
    font-size: 12px !important
}
.input-group-text{
    font-size: 12px !important
}
</style>

<div class="container-fluid">
<?= form_open(site_url('crm/settings'), ['method' => 'post']) ?>
<div class="crm-page-header mb-3 d-flex align-items-center">

  <div class="crm-page-icon me-3">
    <i class="ti ti-settings"></i>
  </div>

  <div class="flex-grow-1">
    <div class="crm-page-title"><?= $page_title ?></div>
    <div class="crm-page-sub">Configure CRM defaults for leads, proposals, contracts, clients, and data quality.</div>
  </div>

  <div class="ms-auto">
    <button type="submit" class="btn btn-primary btn-header">
      <i class="ti ti-device-floppy me-1"></i> Save Settings
    </button>
  </div>

</div>

  <div class="row app-form">

    <!-- ══════════════════════════════════════════════════════
         COLUMN 1
    ══════════════════════════════════════════════════════ -->
    <div class="col-md-6">

      <!-- ── Leads ─────────────────────────────────────── -->
      <div class="card card-body border-bottom pb-3 mb-4">
        <div class="card-header bg-light-primary py-2 mb-3">
          <h6 class="mb-0 text-muted"><i class="ti ti-target-arrow me-2"></i>Lead Defaults</h6>
        </div>

        <!-- crm_leads.lead_status default -->
            <div class="mb-2">
                <div class="input-group">
                    <span class="input-group-text crm-group">Default CRM Currency</span>
                    <?php $cur = $existing_data['crm_default_currency'] ?? 'USD'; ?>
                    <select class="form-select" name="settings[crm_default_currency]" id="crmDealCurrency">
                        <?php
                        $currencies = function_exists('app_currency_dropdown')
                            ? app_currency_dropdown($cur)
                            : [$cur => $cur];

                        foreach ($currencies as $code => $label):
                        ?>
                            <option value="<?= html_escape($code) ?>" <?= $cur === $code ? 'selected' : '' ?>>
                                <?= html_escape($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Lead Status Done</span>
            <select class="form-select" name="settings[crm_default_lead_status]">
              <?php
              $dls = $existing_data['crm_default_lead_status'] ?? 'new';
              ?>
                <?php foreach (lead_status_dropdown() as $value => $label): ?>
            <option value="<?= html_escape($value) ?>"
                <?= (
                    set_value('lead_status') == $value
                    || (
                        empty(set_value('lead_status')) &&
                        (
                            (!empty($lead['lead_status']) && $lead['lead_status'] == $value)
                            || (empty($lead['lead_status']) && crm_setting('crm_default_lead_status', 'new') == $value)
                        )
                    )
                ) ? 'selected' : '' ?>>
                <?= html_escape($label) ?>
            </option>
                <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <!-- crm_leads.forecast_probability default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Forecast Probability</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_forecast_probability]"
                   min="0" max="100"
                   value="<?= e($existing_data['crm_default_forecast_probability'] ?? 25) ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>


        <!-- crm_leads.next_followup_date offset -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Follow-up (Days) Done</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_followup_days]"
                   min="1"
                   value="<?= e($existing_data['crm_default_followup_days'] ?? 3) ?>">
            <span class="input-group-text">days after creation</span>
          </div>
        </div>

        <!-- Stale threshold — used against crm_leads.last_contact_date -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text crm-group">Flag & Stale Lead Threshold Done</span>
            <input type="number" class="form-control"
                   name="settings[crm_flag_stale_lead_days]"
                   min="1"
                   value="<?= e($existing_data['crm_flag_stale_lead_days'] ?? 14) ?>">
            <span class="input-group-text">days without contact</span>
          </div>
        </div>

        <!-- Lead Automation toggles -->
        <div class="card card-body mb-0">
          <label class="form-label fw-bold mb-0 text-primary">Lead Automation</label>
          <hr class="mt-1">

          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="settings[crm_auto_assign_new_leads]" value="1"
                   <?= !empty($existing_data['crm_auto_assign_new_leads']) ? 'checked' : '' ?>>
            <label class="form-check-label">Auto-assign New Leads Done</label>
            <small class="text-muted d-block">Sets <code>crm_leads.assigned_to</code> automatically on creation.</small>
          </div>

          <div class="mb-2" id="wrap_default_assignee">
            <div class="input-group">
              <span class="input-group-text crm-group">Default Assignee</span>
                <select name="settings[crm_default_assignee_id]"
                        class="form-select js-assign-select">
                
                    <option value="">— None —</option>
                
                    <?php if (!empty($users)): foreach ($users as $u): ?>
                        <?php
                            $avatarSrc = user_avatar_url(
                                !empty($u['profile_image']) ? $u['profile_image'] : (int)$u['id']
                            );
                        ?>
                        <option value="<?= (int)$u['id'] ?>"
                                data-avatar="<?= html_escape($avatarSrc) ?>"
                            <?= ((int)($existing_data['crm_default_assignee_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>>
                            <?= html_escape($u['fullname'] ?? '') ?>
                        </option>
                    <?php endforeach; endif; ?>
                
                </select>
            </div>
            <small class="text-muted">Used when auto-assign is on</small>
          </div>

          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="settings[crm_require_loss_reason]" value="1"
                   <?= !empty($existing_data['crm_require_loss_reason']) ? 'checked' : '' ?>>
            <label class="form-check-label">Require Loss Reason</label>
            <small class="text-muted d-block">Forces entry of <code>crm_leads.loss_reason</code> when marking Lost or Disqualified.</small>
          </div>

        </div>
      </div>

      <!-- ── Proposals ──────────────────────────────────── -->
      <div class="card card-body border-bottom pb-3 mb-4">
        <div class="card-header bg-light-primary py-2 mb-3">
          <h6 class="mb-0 text-muted"><i class="ti ti-file-description me-2"></i>Proposal Defaults</h6>
        </div>

        <!-- crm_proposals.status default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Proposal Status</span>
            <select class="form-select" name="settings[crm_default_proposal_status]">
              <?php $dps = $existing_data['crm_default_proposal_status'] ?? 'draft'; ?>
              <option value="draft"          <?= $dps === 'draft'          ? 'selected' : '' ?>>Draft</option>
              <option value="pending_review" <?= $dps === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
              <option value="sent"           <?= $dps === 'sent'           ? 'selected' : '' ?>>Sent</option>
            </select>
          </div>
        </div>

        <!-- crm_proposals.validity_days default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Validity (Days)</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_proposal_validity]"
                   min="1"
                   value="<?= e($existing_data['crm_default_proposal_validity'] ?? 30) ?>">
            <span class="input-group-text">days</span>
          </div>
        </div>

        <!-- crm_proposals.billing_cycle default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Billing Cycle</span>
            <select class="form-select" name="settings[crm_default_billing_cycle]">
              <?php $dbc = $existing_data['crm_default_billing_cycle'] ?? 'monthly'; ?>
              <option value="monthly"   <?= $dbc === 'monthly'   ? 'selected' : '' ?>>Monthly</option>
              <option value="weekly"    <?= $dbc === 'weekly'    ? 'selected' : '' ?>>Weekly</option>
              <option value="bi-weekly" <?= $dbc === 'bi-weekly' ? 'selected' : '' ?>>Bi-Weekly</option>
              <option value="quarterly" <?= $dbc === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
              <option value="annual"    <?= $dbc === 'annual'    ? 'selected' : '' ?>>Annual</option>
              <option value="custom"    <?= $dbc === 'custom'    ? 'selected' : '' ?>>Custom</option>
            </select>
          </div>
        </div>

        <!-- crm_proposals.tax_rate default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Tax Rate</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_tax_rate]"
                   min="0" max="100" step="0.01"
                   value="<?= e($existing_data['crm_default_tax_rate'] ?? 0) ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>

        <!-- crm_proposals.payment_terms default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Payment Terms</span>
            <input type="text" class="form-control"
                   name="settings[crm_default_payment_terms]"
                   placeholder="e.g. Net 30"
                   value="<?= e($existing_data['crm_default_payment_terms'] ?? 'Net 30') ?>">
          </div>
        </div>

        <!-- Default terms & conditions text -->
        <div class="mb-0">
          <label class="form-label fw-semibold mb-1" style="font-size:13px;">Default Terms &amp; Conditions</label>
          <textarea class="form-control" rows="3"
                    name="settings[crm_default_terms_and_conditions]"
                    placeholder="Standard T&amp;C text pre-filled on every new proposal..."><?= html_escape($existing_data['crm_default_terms_and_conditions'] ?? '') ?></textarea>
        </div>

      </div>

      <!-- ── Contracts ──────────────────────────────────── -->
      <div class="card card-body border-bottom pb-3 mb-4">
        <div class="card-header bg-light-primary py-2 mb-3">
          <h6 class="mb-0 text-muted"><i class="ti ti-file-certificate me-2"></i>Contract Defaults</h6>
        </div>

        <!-- crm_contracts.status default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Contract Status</span>
            <select class="form-select" name="settings[crm_default_contract_status]">
              <?php $dcs = $existing_data['crm_default_contract_status'] ?? 'draft'; ?>
              <option value="draft"             <?= $dcs === 'draft'             ? 'selected' : '' ?>>Draft</option>
              <option value="sent"              <?= $dcs === 'sent'              ? 'selected' : '' ?>>Sent</option>
              <option value="pending_signature" <?= $dcs === 'pending_signature' ? 'selected' : '' ?>>Pending Signature</option>
            </select>
          </div>
        </div>

        <!-- crm_contracts.billing_model default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Billing Model</span>
            <select class="form-select" name="settings[crm_default_billing_model]">
              <?php $dbm = $existing_data['crm_default_billing_model'] ?? 'percentage'; ?>
              <option value="percentage" <?= $dbm === 'percentage' ? 'selected' : '' ?>>Percentage</option>
              <option value="flat_fee"   <?= $dbm === 'flat_fee'   ? 'selected' : '' ?>>Flat Fee</option>
              <option value="custom"     <?= $dbm === 'custom'     ? 'selected' : '' ?>>Custom</option>
            </select>
          </div>
        </div>

        <!-- crm_contracts.invoice_frequency default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Invoice Frequency</span>
            <select class="form-select" name="settings[crm_default_invoice_frequency]">
              <?php $dif = $existing_data['crm_default_invoice_frequency'] ?? 'monthly'; ?>
              <option value="monthly"   <?= $dif === 'monthly'   ? 'selected' : '' ?>>Monthly</option>
              <option value="weekly"    <?= $dif === 'weekly'    ? 'selected' : '' ?>>Weekly</option>
              <option value="bi-weekly" <?= $dif === 'bi-weekly' ? 'selected' : '' ?>>Bi-Weekly</option>
              <option value="quarterly" <?= $dif === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
              <option value="annual"    <?= $dif === 'annual'    ? 'selected' : '' ?>>Annual</option>
              <option value="custom"    <?= $dif === 'custom'    ? 'selected' : '' ?>>Custom</option>
            </select>
          </div>
        </div>

        <!-- crm_contracts.payment_terms_days default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Payment Terms</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_payment_terms_days]"
                   min="1"
                   value="<?= e($existing_data['crm_default_payment_terms_days'] ?? 30) ?>">
            <span class="input-group-text">days (Net-X)</span>
          </div>
        </div>

        <!-- crm_contracts.auto_renew default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Auto Renew</span>
            <select class="form-select" name="settings[crm_default_auto_renew]">
              <?php $dar = (string)($existing_data['crm_default_auto_renew'] ?? '0'); ?>
              <option value="0" <?= $dar === '0' ? 'selected' : '' ?>>No</option>
              <option value="1" <?= $dar === '1' ? 'selected' : '' ?>>Yes</option>
            </select>
          </div>
        </div>

        <!-- crm_contracts.renewal_period default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Renewal Period</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_renewal_period]"
                   min="1"
                   value="<?= e($existing_data['crm_default_renewal_period'] ?? 12) ?>">
            <span class="input-group-text">months</span>
          </div>
        </div>

        <!-- crm_contracts.notice_period_days default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Notice Period</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_notice_period_days]"
                   min="1"
                   value="<?= e($existing_data['crm_default_notice_period_days'] ?? 30) ?>">
            <span class="input-group-text">days</span>
          </div>
        </div>

        <!-- crm_contracts.termination_notice_days default -->
        <div class="mb-0">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Termination Notice</span>
            <input type="number" class="form-control"
                   name="settings[crm_default_termination_notice_days]"
                   min="1"
                   value="<?= e($existing_data['crm_default_termination_notice_days'] ?? 30) ?>">
            <span class="input-group-text">days</span>
          </div>
        </div>

      </div>

      <!-- ── Clients ────────────────────────────────────── -->
      <div class="card card-body border-bottom pb-3 mb-4">
        <div class="card-header bg-light-primary py-2 mb-3">
          <h6 class="mb-0 text-muted"><i class="ti ti-building me-2"></i>Client Defaults</h6>
        </div>

        <!-- crm_clients.billing_model default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Client Code Prefix</span>
            <input type="text" class="form-control"
                   name="settings[crm_default_client_code_prefix]"
                   min="1"
                   value="<?= e($existing_data['crm_default_client_code_prefix'] ?? 'PCR') ?>">
            <span class="input-group-text">00001</span>
          </div>
        </div>
        
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Billing Model</span>
            <select class="form-select" name="settings[crm_default_client_billing_model]">
              <?php $dcbm = $existing_data['crm_default_client_billing_model'] ?? 'percentage'; ?>
              <option value="percentage" <?= $dcbm === 'percentage' ? 'selected' : '' ?>>Percentage</option>
              <option value="flat"       <?= $dcbm === 'flat'       ? 'selected' : '' ?>>Flat Fee</option>
              <option value="custom"     <?= $dcbm === 'custom'     ? 'selected' : '' ?>>Custom</option>
            </select>
          </div>
        </div>

        <!-- crm_clients.invoice_frequency default -->
        <div class="mb-2">
          <div class="input-group">
            <span class="input-group-text crm-group">Default Invoice Frequency</span>
            <select class="form-select" name="settings[crm_default_client_invoice_freq]">
              <?php $dcif = $existing_data['crm_default_client_invoice_freq'] ?? 'monthly'; ?>
              <option value="monthly"   <?= $dcif === 'monthly'   ? 'selected' : '' ?>>Monthly</option>
              <option value="bi-weekly" <?= $dcif === 'bi-weekly' ? 'selected' : '' ?>>Bi-Weekly</option>
              <option value="weekly"    <?= $dcif === 'weekly'    ? 'selected' : '' ?>>Weekly</option>
              <option value="custom"    <?= $dcif === 'custom'    ? 'selected' : '' ?>>Custom</option>
            </select>
          </div>
        </div>


      </div>

      
    </div>

    <!-- ══════════════════════════════════════════════════════
         COLUMN 2
    ══════════════════════════════════════════════════════ -->
    <div class="col-md-6">



        <!-- ── Notifications ──────────────────────────────── -->
        <div class="card card-body border-bottom px-4 mb-4">
          <div class="card-header bg-light-primary py-2 mb-3">
            <h6 class="mb-0 text-muted"><i class="ti ti-bell me-2"></i>Notifications</h6>
          </div>
        
          <!-- Channel header row -->
            <div class="d-flex justify-content-end gap-4 pe-2 mb-2">
              <small class="text-muted fw-semibold" style="width:80px;text-align:center;white-space:nowrap;">
                <i class="ti ti-bell-ringing me-1"></i>In-App
              </small>
              <small class="text-muted fw-semibold" style="width:80px;text-align:center;white-space:nowrap;">
                <i class="ti ti-mail me-1"></i>Email
              </small>
            </div>
        
          <?php
          $notification_groups = [
        
            'Leads' => [
              'icon' => 'ti-user-plus',
              'items' => [

                [
                    'key'   => 'crm_notify_on_assign',
                    'desc'  => 'Get notified when a lead is assigned to you or reassigned.',
                    'icon'  => 'ti ti-user-check',
                ],
                [
                    'key'   => 'crm_notify_on_status_change',
                    'desc'  => 'Receive alerts whenever a lead status is updated.',
                    'icon'  => 'ti ti-refresh',
                ],
                [
                    'key'   => 'crm_notify_new_lead',
                    'desc'  => 'Stay informed when a new lead is created from any source.',
                    'icon'  => 'ti ti-user-plus',
                ],
                [
                    'key'   => 'crm_notify_overdue_followups',
                    'desc'  => 'Get notified when a scheduled follow-up is missed.',
                    'icon'  => 'ti ti-alert-circle',
                ],
                [
                    'key'   => 'crm_notify_followup_reminder',
                    'desc'  => 'Receive a reminder 24 hours before a follow-up is due.',
                    'icon'  => 'ti ti-clock',
                ],
                [
                    'key'   => 'crm_notify_task_assigned',
                    'desc'  => 'Get notified when a task is assigned to you.',
                    'icon'  => 'ti ti-checklist',
                ],
                [
                    'key'   => 'crm_notify_task_due_soon',
                    'desc'  => 'Receive alerts before a task deadline approaches.',
                    'icon'  => 'ti ti-hourglass',
                ],
                [
                    'key'   => 'crm_notify_task_overdue',
                    'desc'  => 'Stay informed when a task becomes overdue.',
                    'icon'  => 'ti ti-alert-triangle',
                ],
                [
                    'key'   => 'crm_notify_task_completed',
                    'desc'  => 'Get notified when a task is marked as completed.',
                    'icon'  => 'ti ti-circle-check',
                ],
                [
                    'key'   => 'crm_notify_on_proposal_status',
                    'desc'  => 'Be notified when a proposal status changes.',
                    'icon'  => 'ti ti-file-analytics',
                ],
                [
                    'key'   => 'crm_notify_proposal_viewed',
                    'desc'  => 'Get alerted when a proposal is viewed.',
                    'icon'  => 'ti ti-eye',
                ],
                [
                    'key'   => 'crm_notify_proposal_expiring',
                    'desc'  => 'Receive reminders when a proposal is nearing expiration.',
                    'icon'  => 'ti ti-calendar-time',
                ],
                [
                    'key'   => 'crm_notify_proposal_expired',
                    'desc'  => 'Stay informed when a proposal expires.',
                    'icon'  => 'ti ti-calendar',
                ],
                [
                    'key'   => 'crm_notify_proposal_approved',
                    'desc'  => 'Get notified when a proposal is approved.',
                    'icon'  => 'ti ti-thumb-up',
                ],
                [
                    'key'   => 'crm_notify_proposal_declined',
                    'desc'  => 'Be alerted when a proposal is declined.',
                    'icon'  => 'ti ti-thumb-down',
                ],
                [
                    'key'   => 'crm_notify_on_new_note',
                    'desc'  => 'Receive notifications when a new note is added.',
                    'icon'  => 'ti ti-notes',
                ],
                [
                    'key'   => 'crm_notify_note_mention',
                    'desc'  => 'Get alerted when you are mentioned in a note.',
                    'icon'  => 'ti ti-at',
                ],
                [
                    'key'   => 'crm_notify_new_activity',
                    'desc'  => 'Stay updated on activity related to your leads.',
                    'icon'  => 'ti ti-activity',
                ],
                [
                    'key'   => 'crm_notify_stage_change',
                    'desc'  => 'Be notified when a lead moves to a different stage.',
                    'icon'  => 'ti ti-git-branch',
                ],
                [
                    'key'   => 'crm_notify_forecast_change',
                    'desc'  => 'Get alerts when forecast details are updated.',
                    'icon'  => 'ti ti-trending-up',
                ],
                [
                    'key'   => 'crm_notify_high_value_lead',
                    'desc'  => 'Receive notifications for high-value leads.',
                    'icon'  => 'ti ti-currency-dollar',
                ],
                [
                    'key'   => 'crm_notify_import_complete',
                    'desc'  => 'Get notified when a data import is completed.',
                    'icon'  => 'ti ti-database-import',
                ],
                [
                    'key'   => 'crm_notify_export_ready',
                    'desc'  => 'Be alerted when your export file is ready.',
                    'icon'  => 'ti ti-database-export',
                ],
                [
                    'key'   => 'crm_notify_permission_change',
                    'desc'  => 'Stay informed when your access permissions are updated.',
                    'icon'  => 'ti ti-lock',
                ],
                [
                    'key'   => 'crm_notify_daily_digest',
                    'desc'  => 'Receive a daily summary of your leads, tasks, and follow-ups.',
                    'icon'  => 'ti ti-calendar-stats',
                    'email_only' => true,
                ],
                [
                    'key'   => 'crm_notify_weekly_report',
                    'desc'  => 'Get a weekly overview of pipeline performance and activity.',
                    'icon'  => 'ti ti-report-analytics',
                    'email_only' => true,
                ],

                
              ],
            ],
        
          ];
          ?>
        
            <?php foreach ($notification_groups as $group_label => $group): ?>
            <div class="mb-4">
            
              <?php foreach ($group['items'] as $i => $item):
                $is_last    = $i === array_key_last($group['items']);
                $email_only = !empty($item['email_only']);
                $inapp_key  = $item['key'];
                $email_key  = $item['key'] . '_email';
              ?>
              <div class="d-flex align-items-start justify-content-between gap-3 py-2 <?= !$is_last ? 'border-bottom' : '' ?>">
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    
                    <!-- Icon -->
                    <i class="<?= $item['icon'] ?> text-primary"></i>
                    
                    <!-- Description -->
                    <div style="flex:1;min-width:0;">
                        <small class="text-muted d-block"><?= $item['desc'] ?></small>
                    </div>
                
                </div>
            
                <div class="d-flex gap-4 flex-shrink-0 pe-1">
                  <!-- In-App toggle -->
                  <div style="width:80px;display:flex;justify-content:center;padding-top:2px;">
                    <?php if (!$email_only): ?>
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="settings[<?= $inapp_key ?>]" value="1"
                               <?= !empty($existing_data[$inapp_key]) ? 'checked' : '' ?>>
                      </div>
                    <?php else: ?>
                      <span class="text-muted" title="In-app not applicable" style="font-size:16px;">—</span>
                    <?php endif; ?>
                  </div>
            
                  <!-- Email toggle -->
                  <div style="width:80px;display:flex;justify-content:center;padding-top:2px;">
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input" type="checkbox" role="switch"
                             name="settings[<?= $email_key ?>]" value="1"
                             <?= !empty($existing_data[$email_key]) ? 'checked' : '' ?>>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        
        </div>


    </div>
    <!-- /col-md-6 -->

  </div>
</form>  
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* Show/hide default assignee row based on auto-assign toggle */
    const autoAssignToggle = document.querySelector('[name="settings[crm_auto_assign_new_leads]"]');
    const assigneeWrap     = document.getElementById('wrap_default_assignee');

    function syncAssigneeWrap() {
        if (!autoAssignToggle || !assigneeWrap) return;
        assigneeWrap.style.display = autoAssignToggle.checked ? '' : 'none';
    }

    if (autoAssignToggle) {
        autoAssignToggle.addEventListener('change', syncAssigneeWrap);
        syncAssigneeWrap();
    }

});

$('.js-assign-select').select2({
    templateResult: formatUser,
    templateSelection: formatUser
});

function formatUser(user) {
    if (!user.id) return user.text;

    var avatar = $(user.element).data('avatar');

    if (!avatar) return user.text;

    return $(
        '<span><img src="' + avatar + '" class="rounded-circle me-2" style="width:20px;height:20px;" />' 
        + user.text + '</span>'
    );
}
</script>
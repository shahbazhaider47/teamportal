<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cur     = html_escape($proposal['currency'] ?? 'USD');
$is_del  = !empty($proposal['deleted_at']);
$st_key = $proposal['status'] ?? 'draft';
?>

<div class="container-fluid">
    
    <div class="crm-page-header d-flex align-items-center justify-content-between gap-3 mb-3">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="crm-page-icon"><i class="ti ti-eye"></i></div>
            <div>
                <div class="crm-page-title mb-1"><?= html_escape($proposal['proposal_number'] ?? 'Proposal') ?>
                <i class="ti ti-dots-vertical text-light"></i>
                    <?= proposal_status_badge($proposal['status'] ?? 'draft') ?>
                    <?= forecast_badge($proposal['forecast_category'] ?? '') ?>
                </div>
                <div class="crm-page-sub"><?= html_escape($proposal['title'] ?? '') ?></div>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">

        <div class="header-actions">
            
            <div class="dropdown">
                <button class="btn btn-light-primary btn-header dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                </button>
        
                <div class="dropdown-menu dropdown-menu-end">
        
                    <?php if (!empty($can['edit'])): ?>
                        <a href="<?= site_url('crm/proposals/edit/' . (int)$proposal['id']) ?>"
                            class="dropdown-item"
                            title="Edit">
                            <i class="ti ti-pencil"></i>Edit Proposal
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="dropdown-item">
                        <i class="ti ti-download me-1"></i>Download PDF
                    </button>
                    
                    <button type="button" class="dropdown-item">
                        <i class="ti ti-send me-1"></i>Send to Client
                    </button>
        
                <div class="dropdown-divider"></div>

                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#">
                        <i class="ti ti-trash text-danger"></i> Delete Proposal
                    </button>
                </div>
            </div>
        </div>

            <div class="btn-divider"></div>
            
            <a href="<?= site_url('crm/proposals') ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-3">

        <!-- ══ LEFT COLUMN ════════════════════════════════════════════ -->
        <div class="col-lg-8">

            <!-- Summary -->
            <?php if (!empty($proposal['summary'])): ?>
                <div class="audit-section mb-3">
                    <div class="audit-section-header">
                        <span><i class="ti ti-align-left"></i>Summary</span>
                    </div>
                    <div class="internal-notes">
                        <?= nl2br(html_escape($proposal['summary'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Line Items -->
            <div class="audit-section">
                <div class="audit-section-header">
                    <span>
                        <i class="ti ti-list-details"></i>Line Items
                    </span>
                    <span class="badge badge-code">
                        <?= count($items ?? []) ?> item<?= count($items ?? []) !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="crm-table">
                    <table class="crm-table-light">
                        <thead>
                            <tr>
                                <th style="width:100px;">Type</th>
                                <th>Item &amp; Description</th>
                                <th style="width:75px;text-align:right;">Qty</th>
                                <th style="width:120px;text-align:right;">Unit Price</th>
                                <th style="width:110px;text-align:right;">Discount</th>
                                <th style="width:120px;text-align:right;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-type" style="text-transform:capitalize;">
                                                <?= html_escape(str_replace('_', ' ', $item['item_type'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size:13px;font-weight:600;color:#0f172a;">
                                                <?= html_escape($item['item_name'] ?? '—') ?>
                                            </div>
                                            <?php if (!empty($item['description'])): ?>
                                                <div style="font-size:11.5px;color:#94a3b8;margin-top:2px;">
                                                    <?= html_escape($item['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <span class="dc-value">
                                                <?= number_format((float)($item['quantity'] ?? 0), 2) ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <span class="dc-value">
                                                <?= $cur ?> <?= number_format((float)($item['unit_price'] ?? 0), 2) ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php $disc = (float)($item['discount_amount'] ?? 0); ?>
                                            <span class="<?= $disc > 0 ? 'audit-value-danger' : 'dc-value' ?>">
                                                <?= $disc > 0 ? '−' . $cur . ' ' . number_format($disc, 2) : '—' ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <span style="font-weight:700;color:#0f172a;font-size:13px;">
                                                <?= $cur ?> <?= number_format((float)($item['line_total'] ?? 0), 2) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="placeholder-content" style="padding:28px 20px;">
                                            <i class="ti ti-list-details"></i>
                                            <h4>No Line Items</h4>
                                            <p>No items have been added to this proposal.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                        <?php if (!empty($items)): ?>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align:right;padding:10px 14px;">
                                    <span class="kpi-label">Subtotal</span>
                                </td>
                                <td style="text-align:right;padding:10px 14px;font-weight:600;color:#334155;">
                                    <?= $cur ?> <?= number_format((float)($proposal['subtotal'] ?? 0), 2) ?>
                                </td>
                            </tr>
                            <?php if ((float)($proposal['discount_amount'] ?? 0) > 0): ?>
                            <tr>
                                <td colspan="5" style="text-align:right;padding:8px 14px;">
                                    <span class="kpi-label">
                                        Discount
                                        <?php if (!empty($proposal['discount_type']) && $proposal['discount_type'] !== 'none'): ?>
                                            <span class="prob-pill ms-1">
                                                <?= $proposal['discount_type'] === 'percent'
                                                    ? number_format((float)$proposal['discount_value'], 1) . '%'
                                                    : $cur . ' ' . number_format((float)$proposal['discount_value'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="text-align:right;padding:8px 14px;">
                                    <span class="audit-value-danger">
                                        −<?= $cur ?> <?= number_format((float)($proposal['discount_amount'] ?? 0), 2) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((float)($proposal['tax_amount'] ?? 0) > 0): ?>
                            <tr>
                                <td colspan="5" style="text-align:right;padding:8px 14px;">
                                    <span class="kpi-label">
                                        Tax
                                        <?php if (!empty($proposal['tax_rate'])): ?>
                                            <span class="prob-pill ms-1">
                                                <?= number_format((float)$proposal['tax_rate'], 1) ?>%
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="text-align:right;padding:8px 14px;font-weight:600;color:#334155;">
                                    <?= $cur ?> <?= number_format((float)($proposal['tax_amount'] ?? 0), 2) ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr style="border-top:2px solid #e2e8f0;">
                                <td colspan="5" style="text-align:right;padding:12px 14px;">
                                    <span class="kpi-label" style="color:#056464;font-size:11px;letter-spacing:0.7px;">
                                        TOTAL DUE
                                    </span>
                                </td>
                                <td style="text-align:right;padding:12px 14px;font-size:15px;font-weight:800;color:#056464;">
                                    <?= $cur ?> <?= number_format((float)($proposal['total_value'] ?? 0), 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>

                    </table>
                </div>
            </div>

            <!-- Client Notes -->
            <?php if (!empty($proposal['client_notes'])): ?>
                <div class="audit-section mt-3">
                    <div class="audit-section-header">
                        <span><i class="ti ti-message-circle"></i>Client Notes</span>
                    </div>
                    <div class="internal-notes">
                        <?= nl2br(html_escape($proposal['client_notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Terms & Conditions -->
            <?php if (!empty($proposal['terms_and_conditions'])): ?>
                <div class="audit-section mt-3">
                    <div class="audit-section-header">
                        <span><i class="ti ti-file-description"></i>Terms &amp; Conditions</span>
                    </div>
                    <div class="internal-notes">
                        <?= nl2br(html_escape($proposal['terms_and_conditions'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Internal Notes -->
            <?php if (!empty($proposal['internal_notes'])): ?>
                <div class="audit-section mt-3">
                    <div class="audit-section-header">
                        <span>
                            <i class="ti ti-lock" style="color:#d97706;"></i>Internal Notes
                        </span>
                        <span class="badge" style="background:#fffbeb;border-color:#fde68a;color:#92400e;font-size:10px;">
                            Staff Only
                        </span>
                    </div>
                    <div class="internal-notes" style="background:#fffbeb;border-left:3px solid #fde68a;">
                        <?= nl2br(html_escape($proposal['internal_notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- ══ RIGHT COLUMN ═══════════════════════════════════════════ -->
        <div class="col-lg-4">

            <!-- Actions Panel -->
            <?php if (!empty($can['edit'])): ?>
            <div class="audit-section app-form mb-3">
                <div class="audit-section-header">
                    <span><i class="ti ti-settings"></i>Change Status</span>
                </div>

                <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    <form method="post"
                          action="<?= site_url('crm/proposals/change_status/' . (int)($proposal['id'] ?? 0)) ?>">
                        <label class="form-label-sm mb-1">Change Status</label>
                        <select name="status" class="form-select form-select-sm mb-2">
                            <?php foreach (proposal_statuses() as $val => $cfg): ?>
                                <option value="<?= $val ?>"
                                    <?= ($st_key === $val) ? 'selected' : '' ?>>
                                    <?= $cfg['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div id="declineReasonWrap"
                             style="<?= $st_key !== 'declined' ? 'display:none;' : '' ?>margin-bottom:8px;">
                            <label class="form-label-sm mb-1">Decline Reason</label>
                            <textarea name="decline_reason"
                                      class="form-control form-control-sm"
                                      rows="2"
                                      placeholder="Reason for declining…"><?= html_escape($proposal['decline_reason'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-device-floppy me-1"></i>Update Status
                        </button>
                    </form>
                </div>

            </div>
            <?php endif; ?>
            
            <!-- Value Summary -->
            <div class="audit-section mb-3">
                <div class="audit-section-header">
                    <span><i class="ti ti-currency-dollar"></i>Value Summary</span>
                </div>

                <div class="kpi-strip" style="border:none;border-radius:0;margin-top:0;">
                    <div class="kpi">
                        <span class="kpi-label">Subtotal</span>
                        <span class="kpi-value"><?= $cur ?> <?= number_format((float)($proposal['subtotal'] ?? 0), 2) ?></span>
                    </div>
                    <div class="kpi">
                        <span class="kpi-label">Discount</span>
                        <span class="kpi-value <?= (float)($proposal['discount_amount'] ?? 0) > 0 ? 'kpi-value-warning' : '' ?>">
                            <?= (float)($proposal['discount_amount'] ?? 0) > 0
                                ? '−' . $cur . ' ' . number_format((float)$proposal['discount_amount'], 2)
                                : '—' ?>
                        </span>
                    </div>
                    <div class="kpi">
                        <span class="kpi-label">
                            Tax
                            <?php if (!empty($proposal['tax_rate'])): ?>
                                <span class="prob-pill ms-1">
                                    <?= number_format((float)$proposal['tax_rate'], 1) ?>%
                                </span>
                            <?php endif; ?>
                        </span>
                        <span class="kpi-value">
                            <?= (float)($proposal['tax_amount'] ?? 0) > 0
                                ? $cur . ' ' . number_format((float)$proposal['tax_amount'], 2)
                                : '—' ?>
                        </span>
                    </div>
                </div>

                <!-- Grand total row -->
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:12px 16px;background:linear-gradient(135deg,#056464,#0a8a8a);
                            color:#fff;margin-top:0;">
                    <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;
                                 letter-spacing:.8px;opacity:.82;">Total Value</span>
                    <span style="font-size:20px;font-weight:800;letter-spacing:-.5px;">
                        <?= $cur ?> <?= number_format((float)($proposal['total_value'] ?? 0), 2) ?>
                    </span>
                </div>
            </div>

            <!-- Lead Information -->
            <div class="audit-section mb-3">
                <div class="audit-section-header">
                    <span><i class="ti ti-building"></i>Lead Information</span>
                    <?php if (!empty($proposal['lead_id'])): ?>
                        <a href="<?= site_url('crm/leads/view/' . (int)$proposal['lead_id']) ?>"
                           class="btn btn-xs btn-outline-secondary">
                            <i class="ti ti-arrow-up-right me-1"></i>View Lead
                        </a>
                    <?php endif; ?>
                </div>
                <div class="detail-grid" style="grid-template-columns:1fr 1fr;border:none;border-radius:0;">
                    <div class="detail-cell">
                        <div class="dc-label">Practice</div>
                        <div class="dc-value"><?= html_escape($proposal['practice_name'] ?? '—') ?></div>
                    </div>
                    <div class="detail-cell">
                        <div class="dc-label">Contact Person</div>
                        <div class="dc-value"><?= html_escape($proposal['contact_person'] ?? '—') ?></div>
                    </div>
                    <div class="detail-cell">
                        <div class="dc-label">Email</div>
                        <div class="dc-value">
                            <?php if (!empty($proposal['contact_email'])): ?>
                                <a href="mailto:<?= html_escape($proposal['contact_email']) ?>"
                                   style="color:#056464;text-decoration:none;font-size:13px;">
                                    <?= html_escape($proposal['contact_email']) ?>
                                </a>
                            <?php else: ?>—<?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-cell">
                        <div class="dc-label">Phone</div>
                        <div class="dc-value"><?= html_escape($proposal['contact_phone'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <!-- Proposal Details -->
            <div class="audit-section mb-3">
                <div class="audit-section-header">
                    <span><i class="ti ti-info-circle"></i>Proposal Details</span>
                </div>

                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-refresh"></i></div>
                    <div class="audit-label">Billing Cycle</div>
                    <div class="audit-value"><?= html_escape($proposal['billing_cycle'] ?? '—') ?></div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-credit-card"></i></div>
                    <div class="audit-label">Payment Terms</div>
                    <div class="audit-value"><?= html_escape($proposal['payment_terms'] ?? '—') ?></div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-hourglass"></i></div>
                    <div class="audit-label">Validity</div>
                    <div class="audit-value">
                        <?= !empty($proposal['validity_days'])
                            ? html_escape($proposal['validity_days']) . ' days'
                            : '—' ?>
                    </div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-calendar-event"></i></div>
                    <div class="audit-label">Start Date</div>
                    <div class="audit-value"><?= crm_date($proposal['start_date'] ?? null) ?></div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-rocket"></i></div>
                    <div class="audit-label">Go-Live Date</div>
                    <div class="audit-value"><?= crm_date($proposal['go_live_date'] ?? null) ?></div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-clock-x"></i></div>
                    <div class="audit-label">Expires</div>
                    <div class="audit-value">
                        <?php
                        if (!empty($proposal['expires_at'])) {
                            $exp  = new DateTime($proposal['expires_at']);
                            $now  = new DateTime();
                            $diff = (int)$now->diff($exp)->days;
                            $past = $exp < $now;
                            if ($past) {
                                echo '<span class="audit-value-danger">' . crm_date($proposal['expires_at']) . ' (expired)</span>';
                            } elseif ($diff <= 7) {
                                echo '<span class="kpi-value-warning" style="font-size:12.5px;">' . crm_date($proposal['expires_at']) . ' (' . $diff . 'd)</span>';
                            } else {
                                echo '<span class="audit-value-success">' . crm_date($proposal['expires_at']) . '</span>';
                            }
                        } else { echo '—'; }
                        ?>
                    </div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-chart-bar"></i></div>
                    <div class="audit-label">Forecast</div>
                    <div class="audit-value">
                    <?= forecast_badge($proposal['forecast_category'] ?? '') ?>
                    </div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-eye"></i></div>
                    <div class="audit-label">View Count</div>
                    <div class="audit-value">
                        <?php $vc = (int)($proposal['view_count'] ?? 0); ?>
                        <?= $vc > 0
                            ? '<span class="audit-value-success">' . $vc . ' time' . ($vc !== 1 ? 's' : '') . '</span>'
                            : '—' ?>
                    </div>
                </div>
                <?php if (!empty($proposal['sent_at'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-send"></i></div>
                    <div class="audit-label">Sent</div>
                    <div class="audit-value"><?= crm_date($proposal['sent_at'], true) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($proposal['viewed_at'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-eye-check"></i></div>
                    <div class="audit-label">First Viewed</div>
                    <div class="audit-value"><?= crm_date($proposal['viewed_at'], true) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($proposal['approved_at'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-circle-check" style="color:#16a34a;"></i></div>
                    <div class="audit-label">Approved</div>
                    <div class="audit-value audit-value-success"><?= crm_date($proposal['approved_at'], true) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($proposal['approved_by_name'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-user-check"></i></div>
                    <div class="audit-label">Approved By</div>
                    <div class="audit-value"><?= html_escape($proposal['approved_by_name']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($proposal['decline_reason'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-message-x" style="color:#dc2626;"></i></div>
                    <div class="audit-label">Decline Reason</div>
                    <div class="audit-value audit-value-danger"><?= html_escape($proposal['decline_reason']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Audit Trail -->
            <div class="audit-section mb-3">
                <div class="audit-section-header">
                    <span><i class="ti ti-clock-record"></i>Audit Trail</span>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-user"></i></div>
                    <div class="audit-label">Created By</div>
                    <div class="audit-value"><?= html_escape($proposal['created_by_name'] ?? '—') ?></div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-calendar-plus"></i></div>
                    <div class="audit-label">Created At</div>
                    <div class="audit-value"><?= crm_date($proposal['created_at'] ?? null, true) ?></div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-edit"></i></div>
                    <div class="audit-label">Last Updated</div>
                    <div class="audit-value"><?= crm_date($proposal['updated_at'] ?? null, true) ?></div>
                </div>
                <?php if ($is_del): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-trash" style="color:#dc2626;"></i></div>
                    <div class="audit-label">Deleted At</div>
                    <div class="audit-value audit-value-danger"><?= crm_date($proposal['deleted_at'], true) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($proposal['sent_to_email'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="ti ti-mail"></i></div>
                    <div class="audit-label">Sent To</div>
                    <div class="audit-value"><?= html_escape($proposal['sent_to_email']) ?></div>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.col-lg-4 -->

    </div><!-- /.row -->

</div><!-- /.container-fluid -->

<script>
/* Decline reason toggle */
(function () {
    const sel  = document.querySelector('[name="status"]');
    const wrap = document.getElementById('declineReasonWrap');
    if (!sel || !wrap) return;
    sel.addEventListener('change', function () {
        wrap.style.display = this.value === 'declined' ? 'block' : 'none';
        const ta = wrap.querySelector('textarea');
        if (ta) ta.required = this.value === 'declined';
    });
})();
</script>
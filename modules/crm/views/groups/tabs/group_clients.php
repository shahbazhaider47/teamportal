<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (!$canViewClients): ?>
    <div class="placeholder-content">
        <i class="ti ti-lock"></i>
        <h4>Access Restricted</h4>
        <p>You don't have permission to view clients in this group.</p>
    </div>

<?php elseif (empty($clients)): ?>
    <div class="placeholder-content">
        <i class="ti ti-users-off"></i>
        <h4>No Clients Found</h4>
        <p>No clients are assigned to this group yet.</p>
    </div>

<?php else: ?>
    <div class="crm-card p-0">
        <div class="table-responsive crm-table">
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover">
                <thead class="bg-light-primary">
                    <tr>
                        <th>Client</th>
                        <th>Location</th>
                        <th>Billing</th>
                        <th>Contract</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                        <?php
                        $cId      = (int)($c['id'] ?? 0);
                        $cActive  = (int)($c['is_active'] ?? 0) === 1;
                        $legal    = trim((string)($c['practice_legal_name'] ?? ''));
                        $pname    = trim((string)($c['practice_name'] ?? ''));
                        $dispName = $legal !== '' ? $legal : ($pname !== '' ? $pname : '—');
                        $city     = trim((string)($c['city'] ?? ''));
                        $state    = trim((string)($c['state'] ?? ''));
                        $zip      = trim((string)($c['zip_code'] ?? ''));
                        $bModel   = trim((string)($c['billing_model'] ?? ''));
                        $rateP    = trim((string)($c['rate_percent'] ?? ''));
                        $rateF    = trim((string)($c['rate_flat'] ?? ''));
                        $start    = trim((string)($c['contract_start_date'] ?? ''));
                        $end      = trim((string)($c['contract_end_date'] ?? ''));
                        ?>
                        <tr>
                            <td class="small">
                                <div class="fw-semibold mb-1">
                                    <?php if ($canViewClients && $cId > 0): ?>
                                        <a href="<?= site_url('crm/client_view/' . $cId) ?>" class="text-primary" target="_blank" rel="noopener">
                                            <?= html_escape($dispName) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-dark"><?= html_escape($dispName) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="x-small text-muted">
                                    <?php if (!empty($c['client_code'])): ?>
                                        <span><i class="ti ti-hash text-muted me-1"></i><?= html_escape($c['client_code']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($c['specialty'])): ?>
                                        <span class="ms-2"><i class="ti ti-stethoscope text-info me-1"></i><?= html_escape($c['specialty']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="small">
                                <?php if ($city !== '' || $state !== ''): ?>
                                    <div><?= html_escape($city ?: '—') ?><?= $state ? ', ' . html_escape($state) : '' ?></div>
                                    <?php if ($zip): ?>
                                        <div class="x-small text-muted mt-1">
                                            <i class="ti ti-map-pin text-danger me-1"></i><?= html_escape($zip) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <div class="fw-semibold text-dark"><?= $bModel ? html_escape(ucfirst($bModel)) : '—' ?></div>
                                <div class="x-small text-muted mt-1">
                                    <?php if ($rateP !== ''): ?>
                                        <span><i class="ti ti-percentage me-1"></i><?= html_escape($rateP) ?>%</span>
                                    <?php endif; ?>
                                    <?php if ($rateF !== ''): ?>
                                        <span class="ms-2"><i class="ti ti-currency-dollar me-1"></i><?= html_escape($rateF) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="small">
                                <?php if ($start || $end): ?>
                                    <?php if ($start): ?>
                                        <div class="d-flex align-items-center gap-1 text-muted">
                                            <i class="ti ti-calendar-event text-success" style="font-size:13px;"></i>
                                            <span><?= html_escape($start) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($end): ?>
                                        <div class="d-flex align-items-center gap-1 text-muted mt-1">
                                            <i class="ti ti-calendar-off text-danger" style="font-size:13px;"></i>
                                            <span><?= html_escape($end) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cActive): ?>
                                    <span class="badge badge-active"><span class="badge-dot-green"></span> Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($canViewClients && $cId > 0): ?>
                                    <a href="<?= site_url('crm/client_view/' . $cId) ?>"
                                       class="btn btn-light-primary btn-header"
                                       target="_blank" rel="noopener">
                                        <i class="ti ti-eye"></i> View
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
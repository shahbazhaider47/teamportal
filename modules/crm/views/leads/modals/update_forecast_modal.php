<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $leadId = (int)($lead['id'] ?? 0); ?>
<div class="modal fade app-modal" id="updateForecastModal" tabindex="-1"
     aria-labelledby="updateForecastModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form class="app-form"
                  action="<?= site_url('crm/leads/update_forecast/' . $leadId) ?>"
                  method="post">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-teal">
                            <i class="ti ti-chart-line"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="updateForecastModalLabel">Update Forecast</div>
                            <div class="app-modal-subtitle">Adjust revenue estimates, probability, and close date</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body">

                    <div class="app-form-section">
                        <div class="row g-3">

                            <?php
                            $monthlyRevenue     = $lead['estimated_monthly_revenue'] ?? '';
                            $monthlyCollections = $lead['monthly_collections'] ?? '';
                            $showFetchBtn       = empty($monthlyRevenue) && !empty($monthlyCollections);
                            ?>
                            
                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="fc_monthly_rev">
                                        Monthly Revenue
                            
                                        <?php if ($showFetchBtn): ?>
                                            <span class="text-primary cursor-pointer x-small ms-1"
                                                  data-value="<?= html_escape($monthlyCollections) ?>"
                                                  data-target="fc_monthly_rev"
                                                  role="button"
                                                  tabindex="0"
                                                  title="Fetch monthly collections"
                                                  aria-label="Fetch monthly collections">
                                                <i class="ti ti-refresh" aria-hidden="true"></i> Fetch Collections
                                            </span>
                                        <?php endif; ?>
                                    </label>
                            
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" min="0"
                                               id="fc_monthly_rev"
                                               name="estimated_monthly_revenue"
                                               class="app-form-control"
                                               placeholder="0.00"
                                               value="<?= html_escape($monthlyRevenue) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="fc_setup_fee">Setup Fee</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" min="0"
                                               id="fc_setup_fee"
                                               name="estimated_setup_fee"
                                               class="app-form-control"
                                               placeholder="0.00"
                                               value="<?= html_escape($lead['estimated_setup_fee'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="fc_annual_val">Annual Value</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" min="0"
                                               id="fc_annual_val"
                                               name="estimated_annual_value"
                                               class="app-form-control"
                                               placeholder="0.00"
                                               value="<?= html_escape($lead['estimated_annual_value'] ?? '') ?>"
                                               id="fc_annual_val_input">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="fc_probability">Probability</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" min="0" max="100"
                                               id="fc_probability"
                                               name="forecast_probability"
                                               class="app-form-control"
                                               placeholder="0"
                                               value="<?= html_escape($lead['forecast_probability'] ?? '') ?>">
                                        <span class="app-form-input-suffix" style="font-weight:700;color:#475569;">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="fc_category">Forecast Category</label>
                                    <div class="app-form-select-wrap">
                                        <select id="fc_category"
                                                name="forecast_category"
                                                class="app-form-control">
                                            <option value="">Select category</option>
                                            <option value="commit"    <?= (($lead['forecast_category'] ?? '') === 'commit')    ? 'selected' : '' ?>>Commit</option>
                                            <option value="best_case" <?= (($lead['forecast_category'] ?? '') === 'best_case') ? 'selected' : '' ?>>Best Case</option>
                                            <option value="pipeline"  <?= (($lead['forecast_category'] ?? '') === 'pipeline')  ? 'selected' : '' ?>>Pipeline</option>
                                            <option value="omitted"   <?= (($lead['forecast_category'] ?? '') === 'omitted')   ? 'selected' : '' ?>>Omitted</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="fc_close_date">Expected Close Date</label>
                                    <input type="date"
                                           id="fc_close_date"
                                           name="expected_close_date"
                                           class="app-form-control"
                                           value="<?= html_escape($lead['expected_close_date'] ?? '') ?>">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-section-label">
                            <i class="ti ti-calculator" style="font-size:12px;color:#5ebfbf;"></i>
                            Weighted Value Preview
                        </div>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="app-form-label">Weighted Monthly</label>
                                <div class="app-form-computed" id="fc_weighted_monthly">—</div>
                                <div class="app-form-hint">Monthly × Probability</div>
                            </div>
                            <div class="col-md-4">
                                <label class="app-form-label">Weighted Annual</label>
                                <div class="app-form-computed" id="fc_weighted_annual">—</div>
                                <div class="app-form-hint">Annual × Probability</div>
                            </div>
                            <div class="col-md-4">
                                <label class="app-form-label">Total Deal Value</label>
                                <div class="app-form-computed" id="fc_total_deal" style="color:#0f172a;">—</div>
                                <div class="app-form-hint">Annual + Setup Fee</div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:14px;"></i>
                        Changes update the lead's pipeline forecast immediately.
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Save Forecast
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('updateForecastModal');
    if (!modal) return;

    const fmt = v => isNaN(v) || v === '' ? '—' : '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function recalc() {
        const monthly = parseFloat(document.getElementById('fc_monthly_rev')?.value) || 0;
        const annual  = parseFloat(document.getElementById('fc_annual_val')?.value)  || 0;
        const setup   = parseFloat(document.getElementById('fc_setup_fee')?.value)   || 0;
        const prob    = parseFloat(document.getElementById('fc_probability')?.value) / 100 || 0;

        const wm = monthly > 0 && prob > 0 ? monthly * prob : null;
        const wa = annual  > 0 && prob > 0 ? annual  * prob : null;
        const td = (annual + setup) > 0    ? annual + setup : null;

        document.getElementById('fc_weighted_monthly').textContent = wm !== null ? fmt(wm) : '—';
        document.getElementById('fc_weighted_annual').textContent  = wa !== null ? fmt(wa) : '—';
        document.getElementById('fc_total_deal').textContent       = td !== null ? fmt(td) : '—';
    }

    ['fc_monthly_rev', 'fc_setup_fee', 'fc_annual_val', 'fc_probability'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', recalc);
    });

    modal.addEventListener('shown.bs.modal', recalc);
})();
</script>

<script>
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-target][data-value]');
    if (!trigger) return;

    const input = document.getElementById(trigger.dataset.target);
    if (input) {
        input.value = trigger.dataset.value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
});
</script>
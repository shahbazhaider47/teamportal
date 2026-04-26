<div class="card border-0 shadow-sm">

    <!-- Header -->
    <div class="card-header bg-light-primary mb-3">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-settings me-2" style="font-size:18px;"></i>
            Company Settings
        </h6>
        <span class="text-muted small">
            These settings are centrally configured and applied across the entire application,
            with enforcement handled by backend rules and system logic.
        </span>
    </div>

    <!-- Body -->
    <div class="card-body">

        <form class="app-ajax-form app-form"
              method="post"
              action="<?= site_url('admin/setup/company/save_company_settings') ?>"
              data-refresh-target="<?= site_url('admin/setup/company/tab/8_companysettings') ?>">

            <div class="row">

                <!-- IS CNIC REQUIRED -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Is CNIC Required?</label>
                    <select name="cnic_required" class="form-select">
                        <option value="" disabled <?= !isset($settings['cnic_required']) ? 'selected' : '' ?>>
                            — Select Option —
                        </option>
                        <option value="1" <?= ($settings['cnic_required'] ?? null) === '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= ($settings['cnic_required'] ?? null) === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <!-- MINIMUM AGE TO HIRE -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Minimum Age to Hire</label>
                    <select name="min_hiring_age" class="form-select">
                        <option value="" disabled <?= !isset($settings['min_hiring_age']) ? 'selected' : '' ?>>
                            — Select Minimum Age —
                        </option>
                        <?php for ($i = 16; $i <= 30; $i++): ?>
                            <option value="<?= $i ?>"
                                <?= (string)($settings['min_hiring_age'] ?? '') === (string)$i ? 'selected' : '' ?>>
                                <?= $i ?> Years
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- DEFAULT EMPLOYMENT TYPE -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Default Employment Type</label>
                    <select name="default_employment_type" class="form-select">
                        <option value="" disabled <?= !isset($settings['default_employment_type']) ? 'selected' : '' ?>>
                            — Select Employment Type —
                        </option>
                        <?php foreach (['Full Time', 'Part Time', 'Probation', 'Remote'] as $type): ?>
                            <option value="<?= e($type) ?>"
                                <?= ($settings['default_employment_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= e($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PROBATION DURATION -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Probation Duration (Months)</label>
                    <input type="number"
                           name="probation_duration_months"
                           class="form-control"
                           min="1"
                           max="24"
                           value="<?= isset($settings['probation_duration_months']) ? e($settings['probation_duration_months']) : '' ?>">
                </div>

                <!-- NTN REQUIRED -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">NTN No Required?</label>
                    <select name="ntn_required" class="form-select">
                        <option value="" disabled <?= !isset($settings['ntn_required']) ? 'selected' : '' ?>>
                            — Select Option —
                        </option>
                        <option value="1" <?= ($settings['ntn_required'] ?? null) === '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= ($settings['ntn_required'] ?? null) === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <!-- TAX NUMBER REQUIRED -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tax Number Required?</label>
                    <select name="tax_number_required" class="form-select">
                        <option value="" disabled <?= !isset($settings['tax_number_required']) ? 'selected' : '' ?>>
                            — Select Option —
                        </option>
                        <option value="1" <?= ($settings['tax_number_required'] ?? null) === '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= ($settings['tax_number_required'] ?? null) === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <!-- DEFAULT EMPLOYEE GRADE -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Default Employee Grade</label>
                    <select name="default_employee_grade" class="form-select">
                        <option value="" disabled <?= !isset($settings['default_employee_grade']) ? 'selected' : '' ?>>
                            — Select Grade —
                        </option>
                        <?php foreach (['Grade A','Grade B','Grade C','Grade D'] as $grade): ?>
                            <option value="<?= e($grade) ?>"
                                <?= ($settings['default_employee_grade'] ?? '') === $grade ? 'selected' : '' ?>>
                                <?= e($grade) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- IBAN / ACCOUNT DIGITS -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Account / IBAN Digits (Min – Max)</label>
                    <div class="input-group">
                        <input type="number"
                               name="iban_min_digits"
                               class="form-control"
                               placeholder="Min"
                               min="10"
                               max="34"
                               value="<?= isset($settings['iban_min_digits']) ? e($settings['iban_min_digits']) : '' ?>">
                        <span class="input-group-text">to</span>
                        <input type="number"
                               name="iban_max_digits"
                               class="form-control"
                               placeholder="Max"
                               min="10"
                               max="34"
                               value="<?= isset($settings['iban_max_digits']) ? e($settings['iban_max_digits']) : '' ?>">
                    </div>
                </div>

                <!-- DEFAULT SALARY PAY METHOD -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Default Salary Pay Method</label>
                    <select name="default_salary_pay_method" class="form-select">
                        <option value="" disabled <?= !isset($settings['default_salary_pay_method']) ? 'selected' : '' ?>>
                            — Select Pay Method —
                        </option>
                        <option value="bank_transfer" <?= ($settings['default_salary_pay_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        <option value="cash" <?= ($settings['default_salary_pay_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="cheque" <?= ($settings['default_salary_pay_method'] ?? '') === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                        <option value="digital_wallet" <?= ($settings['default_salary_pay_method'] ?? '') === 'digital_wallet' ? 'selected' : '' ?>>Digital Wallet</option>
                    </select>
                </div>

                <!-- BLOOD GROUP REQUIRED -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Blood Group Required?</label>
                    <select name="blood_group_required" class="form-select">
                        <option value="" disabled <?= !isset($settings['blood_group_required']) ? 'selected' : '' ?>>
                            — Select Option —
                        </option>
                        <option value="1" <?= ($settings['blood_group_required'] ?? null) === '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= ($settings['blood_group_required'] ?? null) === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <!-- FATHER NAME REQUIRED -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Father's Name Required?</label>
                    <select name="father_name_required" class="form-select">
                        <option value="" disabled <?= !isset($settings['father_name_required']) ? 'selected' : '' ?>>
                            — Select Option —
                        </option>
                        <option value="1" <?= ($settings['father_name_required'] ?? null) === '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= ($settings['father_name_required'] ?? null) === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <!-- MOTHER NAME REQUIRED -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">Mother's Name Required?</label>
                    <select name="mother_name_required" class="form-select">
                        <option value="" disabled <?= !isset($settings['mother_name_required']) ? 'selected' : '' ?>>
                            — Select Option —
                        </option>
                        <option value="1" <?= ($settings['mother_name_required'] ?? null) === '1' ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= ($settings['mother_name_required'] ?? null) === '0' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

            </div>

            <!-- ACTIONS -->
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    Save Settings
                </button>
            </div>

        </form>

    </div>
</div>

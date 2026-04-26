<?php
$company  = $company  ?? [];
$offices  = $offices  ?? [];
?>

<!-- Company Info (Tab Content) -->
<div class="card border-0 shadow-sm">

    <!-- Header -->
    <div class="card-header bg-light-primary mb-3">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-building-skyscraper me-2" style="font-size:18px;"></i>
            Company Info
        </h6>
    </div>

    <!-- Body -->
    <div class="card-body">

        <form class="app-ajax-form app-form"
              method="post"
              enctype="multipart/form-data"
              action="<?= site_url('admin/setup/company/save_company') ?>"
              data-refresh-target="<?= site_url('admin/setup/company/tab/2_company_info') ?>">

            <!-- LOGOS -->
            <div class="row mb-4">

                <div class="col-md-4">
                    <label class="form-label">Light Logo</label>
                    <input type="file" name="light_logo" class="form-control" accept="image/*">
                
                    <?php if (!empty($company['light_logo'])): ?>
                        <div class="mt-2 d-inline-block">
                
                            <!-- Logo -->
                            <img src="<?= base_url('uploads/company/' . $company['light_logo']) ?>"
                                 style="max-height:40px"
                                 class="img-fluid border rounded p-2 d-block">
                
                            <!-- Remove action -->
                            <div class="text-end mt-1">
                                <a href="<?= site_url('admin/setup/company/remove_company_logo/light') ?>"
                                   class="text-danger small d-inline-flex align-items-center gap-1"
                                   title="Remove light logo"
                                   onclick="return confirm('Remove light logo?')">
                                    <i class="ti ti-trash"></i>
                                    Remove
                                </a>
                            </div>
                
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Dark Logo</label>
                    <input type="file" name="dark_logo" class="form-control" accept="image/*">
                
                    <?php if (!empty($company['dark_logo'])): ?>
                        <div class="mt-2 d-inline-block">
                
                            <!-- Logo -->
                            <img src="<?= base_url('uploads/company/' . $company['dark_logo']) ?>"
                                 style="max-height:40px"
                                 class="img-fluid bg-primary border rounded p-2 d-block">
                
                            <!-- Remove action -->
                            <div class="text-end mt-1">
                                <a href="<?= site_url('admin/setup/company/remove_company_logo/dark') ?>"
                                   class="text-danger small d-inline-flex align-items-center gap-1"
                                   title="Remove dark logo"
                                   onclick="return confirm('Remove dark logo?')">
                                    <i class="ti ti-trash"></i>
                                    Remove
                                </a>
                            </div>
                
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Favicon</label>
                    <input type="file"
                           name="favicon"
                           class="form-control"
                           accept="image/png,image/x-icon,image/svg+xml">
                
                    <?php if (!empty($company['favicon'])): ?>
                
                        <!-- Favicon preview ONLY -->
                        <div class="mt-2 d-inline-block bg-primary p-2 rounded">
                            <img src="<?= base_url('uploads/company/' . $company['favicon']) ?>"
                                 style="max-height:24px"
                                 class="d-block">
                        </div>
                
                            <a href="<?= site_url('admin/setup/company/remove_favicon') ?>"
                               class="text-danger small d-inline-flex align-items-center gap-1"
                               title="Remove favicon"
                               onclick="return confirm('Remove favicon?')">
                                <i class="ti ti-trash"></i>
                                Remove
                            </a>
                
                    <?php endif; ?>
                </div>
                
            </div>

            <hr>

            <!-- COMPANY DETAILS -->
            <div class="row">

                <div class="col-md-4 mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control"
                           value="<?= html_escape($company['company_name'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Company Type</label>
                    <select name="company_type" class="form-select">
                        <option value="">— Select Company Type —</option>
                
                        <option value="Private Limited"
                            <?= ($company['company_type'] ?? '') === 'Private Limited' ? 'selected' : '' ?>>
                            Private Limited
                        </option>
                
                        <option value="Public Limited"
                            <?= ($company['company_type'] ?? '') === 'Public Limited' ? 'selected' : '' ?>>
                            Public Limited
                        </option>
                
                        <option value="Limited Liability Company"
                            <?= ($company['company_type'] ?? '') === 'Limited Liability Company' ? 'selected' : '' ?>>
                            Limited Liability Company (LLC)
                        </option>
                
                        <option value="Sole Proprietorship"
                            <?= ($company['company_type'] ?? '') === 'Sole Proprietorship' ? 'selected' : '' ?>>
                            Sole Proprietorship
                        </option>
                
                        <option value="Partnership"
                            <?= ($company['company_type'] ?? '') === 'Partnership' ? 'selected' : '' ?>>
                            Partnership
                        </option>
                
                        <option value="Non-Profit Organization"
                            <?= ($company['company_type'] ?? '') === 'Non-Profit Organization' ? 'selected' : '' ?>>
                            Non-Profit Organization
                        </option>
                
                        <option value="Government Entity"
                            <?= ($company['company_type'] ?? '') === 'Government Entity' ? 'selected' : '' ?>>
                            Government Entity
                        </option>
                
                        <option value="Other"
                            <?= ($company['company_type'] ?? '') === 'Other' ? 'selected' : '' ?>>
                            Other
                        </option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">NTN / Tax Number</label>
                    <input type="text" name="ntn_no" class="form-control"
                           value="<?= html_escape($company['ntn_no'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Business Email</label>
                    <input type="email" name="business_email" class="form-control"
                           value="<?= html_escape($company['business_email'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Business Phone</label>
                    <input type="text" name="business_phone" class="form-control"
                           value="<?= html_escape($company['business_phone'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control"
                           placeholder="https://example.com"
                           value="<?= html_escape($company['website'] ?? '') ?>">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Registered Address</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= html_escape($company['address'] ?? '') ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= html_escape($company['city'] ?? '') ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control"
                           value="<?= html_escape($company['state'] ?? '') ?>">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Zip Code</label>
                    <input type="text" name="zip_code" class="form-control"
                           value="<?= html_escape($company['zip_code'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Main / Head Office</label>
                    <select name="office_id" class="form-select">
                        <option value="">— Select Office —</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?= (int)$office['id'] ?>"
                                <?= (string)$office['id'] === (string)($company['office_id'] ?? '') ? 'selected' : '' ?>>
                                <?= html_escape($office['office_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- ACTIONS -->
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    Save Changes
                </button>
            </div>

        </form>

    </div>
</div>

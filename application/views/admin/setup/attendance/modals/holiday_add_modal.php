<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-top">
        <div class="modal-content">

            <form method="post" action="<?= site_url('admin/setup/attendance/save_holiday') ?>" class="app-form">
                <!-- Header -->
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title text-white">
                        <i class="ti ti-calendar-plus me-2"></i>
                        Add Public Holiday
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Holiday Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Local">Local</option>
                                <option value="Federal">Federal</option>
                                <option value="Religion">Religion</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">From Date</label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">To Date</label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                        
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-0">
                                Applicable For:
                            </h6>
                            <small class="text-muted mb-2">Leave empty to apply for all</small>
                        
                            <hr class="mt-0 mb-3">
                        
                            <div class="row g-2 align-items-center">
                                
                                <!-- Locations -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-sitemap text-primary me-1"></i> Locations
                                </div>
                                <div class="col-md-8">
                                <div class="multi-select-wrapper">    
                                    <select name="locations" class="form-select" multiple style="display:none;">
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?= (int)$office['id'] ?>"
                                                <?= (string)$office['id'] === (string)($company['office_id'] ?? '') ? 'selected' : '' ?>>
                                                <?= html_escape($office['office_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                        <div class="form-control multi-select-display">None</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                        
                                </div>
                                </div>
                                
                                <!-- Departments -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-building text-primary me-1"></i> Departments
                                </div>
                                <div class="col-md-8">
                                <div class="multi-select-wrapper">                                    
                                    <select name="departments" class="form-select" multiple style="display:none;">
                                        <?php foreach ($departments ?? [] as $department): ?>
                                            <option value="<?= $department['id'] ?>">
                                                <?= html_escape($department['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                        <div class="form-control multi-select-display">None</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                        
                                </div>
                                </div>
                        
                                <!-- Positions -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-award text-primary me-1"></i> Positions
                                </div>
                                <div class="col-md-8">
                                <div class="multi-select-wrapper">    
                                    <select name="positions" class="form-select" multiple style="display:none;">
                                        <?php foreach ($positions ?? [] as $position): ?>
                                            <option value="<?= $position['id'] ?>">
                                                <?= html_escape($position['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                        <div class="form-control multi-select-display">None</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                        
                                </div>
                                </div>
                        
                                <!-- Employees -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-users text-primary me-1"></i> Employees
                                </div>
                                <div class="col-md-8">
                                <div class="multi-select-wrapper">
                                    <select name="employees" class="form-select" multiple style="display:none;">
                                        <?php foreach ($employees ?? [] as $employee): ?>
                                            <option value="<?= (int)$employee['id'] ?>">
                                                <?= html_escape($employee['full_name'] ?? ($employee['firstname'] . ' ' . $employee['lastname'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                </select>

                                        <div class="form-control multi-select-display">None</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                        
                                </div>
                                </div>
                        
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal"> Cancel </button>
                    <button type="submit" class="btn btn-primary btn-sm"> Save Holiday </button>
                </div>

            </form>

        </div>
    </div>
</div>
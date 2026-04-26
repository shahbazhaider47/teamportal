<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal fade"
     id="addLeaveTypeModal"
     tabindex="-1"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-lg modal-dialog-top">
        <div class="modal-content">

            <form method="post"
                  action="<?= site_url('admin/setup/attendance/save_leave_type') ?>"
                  class="app-form">

                <!-- HEADER -->
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title text-white">
                        <i class="ti ti-plus me-2"></i> Add Leave Type
                    </h6>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                </div>


                            <?php
                            $genderTypes = get_company_setting_array('gender_types');
                            $empTypes = get_company_setting_array('employment_types');
                            ?>
                            
                <!-- BODY -->
                <div class="modal-body">

                    <div class="row g-3">

                        <!-- Name -->
                        <div class="col-md-7">
                            <label class="form-label required">Leave Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <!-- Code -->
                        <div class="col-md-3">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control text-uppercase" required>
                        </div>

                        <!-- Color -->
                        <div class="col-md-2">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color">
                        </div>
                        
                        <!-- Type -->
                        <div class="col-md-3">
                            <label class="form-label required">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Compensatory">Compensatory</option>
                                <option value="Work from Home">Work from Home</option>
                            </select>
                        </div>
                    
                        <!-- Unit -->
                        <div class="col-md-3">
                            <label class="form-label required">Unit</label>
                            <select name="unit" class="form-select" required>
                                <option value="Days">Days</option>
                                <option value="Hours">Hours</option>
                            </select>
                        </div>

                        <!-- Limit -->
                        <div class="col-md-3">
                            <label class="form-label">Unit Limit</label>
                            <input type="number" step="0.5" name="limit" class="form-control" placeholder="1 for days & 0.4 for hours">
                        </div>

                        <!-- Based On -->
                        <div class="col-md-3">
                            <label class="form-label">Based On</label>
                            <select name="based_on" class="form-select">
                                <option value="Calendar Days">Calendar Days</option>
                                <option value="Joining Date">Joining Date</option>
                            </select>
                        </div>

                        <!-- Annual -->
                        <div class="col-md-3">
                            <label class="form-label">Allowed Annually</label>
                            <input type="number" step="0.5" name="allowed_annually" class="form-control">
                        </div>

                        <!-- Monthly -->
                        <div class="col-md-3">
                            <label class="form-label">Allowed Monthly</label>
                            <input type="number" step="0.5" name="allowed_monthly" class="form-control">
                        </div>

                        <!-- Attachment -->
                        <div class="col-md-3">
                            <label class="form-label required">Attachment Required</label>
                            <select name="attachment_required" class="form-select" required>
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-0">
                                Applicable For:
                            </h6>
                            <small class="text-muted mb-2">Leave empty to apply for all</small>
                        
                            <hr class="mt-0 mb-3">
                        
                            <div class="row g-2 align-items-center">
                            
                                <!-- Gender -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-gender-bigender text-primary me-1"></i> Genders
                                </div>
                                <div class="col-md-8">
                                    <div class="multi-select-wrapper">
                                        <select name="applies_to_genders[]" class="form-select" multiple style="display: none;">
                                            <option value="All">All Genders</option>
                                            <?php if (!empty($genderTypes)): ?>
                                                <?php foreach ($genderTypes as $gender): ?>
                                                    <option value="<?= html_escape($gender) ?>">
                                                        <?= html_escape($gender) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <!-- Fallback (only if admin hasn't configured yet) -->
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            <?php endif; ?>
                                        </select>
                                        
                                        <div class="form-control multi-select-display">All</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                        
                                        <div class="multi-select-dropdown">
                                            <!-- Checkboxes will be inserted here by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                        
                                <!-- Locations -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-sitemap text-primary me-1"></i> Locations
                                </div>
                                <div class="col-md-8">
                                    <div class="multi-select-wrapper">
                                        <select name="applies_to_locations[]" class="form-select" multiple style="display:none;">
                                            <?php foreach ($offices ?? [] as $office): ?>
                                                <option value="<?= (int)$office['id'] ?>">
                                                    <?= html_escape($office['office_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                
                                        <div class="form-control multi-select-display">All</div>
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
                                        <select name="applies_to_departments[]" class="form-select" multiple style="display:none;">
                                            <?php foreach ($departments ?? [] as $department): ?>
                                                <option value="<?= (int)$department['id'] ?>">
                                                    <?= html_escape($department['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                
                                        <div class="form-control multi-select-display">All</div>
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
                                        <select name="applies_to_positions[]" class="form-select" multiple style="display:none;">
                                            <?php foreach ($positions ?? [] as $position): ?>
                                                <option value="<?= (int)$position['id'] ?>">
                                                    <?= html_escape($position['title']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                
                                        <div class="form-control multi-select-display">All</div>
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
                                        <select name="applies_to_employees[]" class="form-select" multiple style="display:none;">
                                            <?php foreach ($employees ?? [] as $employee): ?>
                                                <option value="<?= (int)$employee['id'] ?>">
                                                    <?= html_escape(
                                                        $employee['full_name']
                                                        ?? trim(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? ''))
                                                    ) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                
                                        <div class="form-control multi-select-display">All</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                    </div>
                                </div>
                                
                                <!-- Roles -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-id text-primary me-1"></i> Roles
                                </div>
                                <div class="col-md-8">
                                    <div class="multi-select-wrapper">
                                        <select name="applies_to_roles[]" class="form-select" multiple style="display:none;">
                                            <?php foreach ($roles ?? [] as $role): ?>
                                                <option value="<?= html_escape($role['role_name']) ?>">
                                                    <?= ucfirst(html_escape($role['role_name'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                
                                        <div class="form-control multi-select-display">All</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                    </div>
                                </div>
                                
                                <!-- Employment Types -->
                                <div class="col-md-4 fw-semibold">
                                    <i class="ti ti-id-badge-2 text-primary me-1"></i> Employment Types
                                </div>
                                <div class="col-md-8">
                                    <div class="multi-select-wrapper">
                                        <select name="employment_types[]" class="form-select" multiple style="display:none;">
                                            <?php if (!empty($empTypes)): ?>
                                                <?php foreach ($empTypes as $empType): ?>
                                                    <option value="<?= html_escape($empType) ?>">
                                                        <?= html_escape($empType) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="Full Time">Full Time</option>
                                                <option value="Probation">Probation</option>
                                                <option value="Other">Other</option>
                                            <?php endif; ?>
                                        </select>
                                
                                        <div class="form-control multi-select-display">All</div>
                                        <button type="button" class="multi-select-clear" title="Clear selection">
                                            <i class="ti ti-x"></i>
                                        </button>
                                
                                        <div class="multi-select-dropdown"></div>
                                    </div>
                                </div>
                            
                            </div>
                        </div>
                      
                        <!-- Description -->
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="2"></textarea>
                        </div>
                        
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal"> Cancel </button>
                    <button type="submit" class="btn btn-primary btn-sm"> Save Leave Type </button>
                </div>

            </form>
        </div>
    </div>
</div>

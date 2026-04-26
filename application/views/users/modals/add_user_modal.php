<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form class="app-form" id="add-user-form" method="post" action="<?= site_url('user_new/add') ?>" enctype="multipart/form-data" autocomplete="off">
        
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="addUserModalLabel">Add New Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body modal-inner">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="emp_title" id="emp_title">

          <ul class="nav nav-tabs app-tabs-primary mb-3" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab"><i class="ti ti-user-circle me-2"></i> Personal</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment" type="button" role="tab"><i class="ti ti-badge me-2"></i> Employment</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button" role="tab"><i class="ti ti-report-money me-2"></i> Salary</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency" type="button" role="tab"><i class="ti ti-emergency-bed me-2"></i> Emergency</button>
            </li>
           
          </ul>
          
          <div class="tab-content" id="userTabContent">
            <!-- Personal Information Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-4 mb-3">
                  <div class="d-flex align-items-center gap-2">
                    <!-- Profile Image with Remove Button -->
                    <div class="profile-image-container">
                      <img id="profileImagePreview" 
                           src="<?= base_url('assets/images/default-avatar.png') ?>"
                           alt="Profile photo"
                           class="rounded-circle shadow-sm border border-light">
                      <button type="button"
                              id="removeProfileBtn"
                              class="btn btn-light border-0 shadow-sm remove-profile-btn p-1 rounded-circle"
                              title="Remove Photo"
                              aria-label="Remove profile photo"
                              onclick="removeProfilePhoto()"
                              style="display:none;">
                        <i class="ti ti-x fs-5 text-danger"></i>
                      </button>
                    </div>
                
                    <!-- Upload Field -->
                    <div class="flex-grow-1">
                      <div class="mb-2">
                        <label for="profile_image" class="btn btn-sm btn-outline-primary mb-0">
                          <i class="ti ti-upload me-1"></i>
                          Choose Photo
                        </label>
                        <input type="file" 
                               class="d-none" 
                               name="profile_image" 
                               accept="image/jpeg,image/png,image/webp" 
                               id="profile_image">
                      </div>
                      <div id="selectedFileName" class="text-muted small mt-1" style="display:none;"></div>
                      <div class="form-text small">JPG, PNG or WEBP. Max 2MB.</div>
                      <input type="hidden" name="remove_photo" id="remove_profile_photo" value="0">
                    </div>
                  </div>
                </div>

                <div class="col-md-2">
                  <label class="form-label">Username <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="username" name="username" maxlength="16" required placeholder="unique username">
                  <div class="invalid-feedback">Please enter a username</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">EMP ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="emp_id" name="emp_id" maxlength="4" required placeholder="unique employee ID">
                    <div class="invalid-feedback">Please enter an employee ID</div>
                </div>  
                
                <div class="col-md-2">
                  <label class="form-label">Staff Role <span class="text-danger">*</span></label>
                  <select name="user_role" class="form-select" id="user_role" required>
                    <option value="" selected disabled>Select Role</option>
                    <?php foreach ($roles as $role): ?>
                      <option value="<?= html_escape($role) ?>"><?= ucfirst(html_escape($role)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please select a role</div>
                </div>
                
                <div class="col-md-2">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="" selected disabled>Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="unknown">Unknown</option>
                    </select>    
                </div>
                
                <div class="app-divider-v dotted mb-3"></div> 
                
                <div class="col-md-3">
                  <label class="form-label">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="firstname" name="firstname" required>
                  <div class="invalid-feedback">Please enter first name</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Initial/Middle</label>
                  <input type="text" class="form-control" id="initials" name="initials">
                </div>
                        
                <div class="col-md-3">
                  <label class="form-label">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="lastname" name="lastname" required>
                  <div class="invalid-feedback">Please enter last name</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="fullname" name="fullname">
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                  <input type="date" class="form-control basic-date" name="emp_dob" id="emp_dob" required placeholder="YYYY-MM-DD">
                  <div class="invalid-feedback">Date of birth is required</div>
                </div>                
                
                <div class="col-md-3">
                  <label class="form-label">Email Address<span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="email" name="email" required placeholder="personla email address" maxlength="24">
                  <div class="invalid-feedback">Please enter a valid email</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Personal Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control phone-numeric-only" id="emp_phone" name="emp_phone" required placeholder="(312) 345-6789" maxlength="11">
                    <div class="invalid-feedback">Phone number is required</div>
                </div>

                <div class="col-md-3">
                    <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="marital_status" name="marital_status" required>
                        <option value="" selected disabled>Select Marital Status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                        <option value="other">Other</option>
                    </select>    
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                  <input class="form-control" id="address" name="address" required>
                  <div class="invalid-feedback">Permanent address is required</div>
                </div>

                <div class="col-md-2">
                  <label class="form-label">City <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="city" name="city" required>
                  <div class="invalid-feedback">City is required</div>
                </div>

                <div class="col-md-2">
                  <label class="form-label">State <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="state" name="state" required>
                  <div class="invalid-feedback">State is required</div>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Country <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="country" name="country" required>
                  <div class="invalid-feedback">Country is required</div>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Current Address Complete <span class="text-danger">*</span></label>
                  <input class="form-control" id="current_address" name="current_address" required>
                  <div class="invalid-feedback">Current address is required</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label">Nationality <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="nationality" name="nationality" required>
                  <div class="invalid-feedback">Nationality is required</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">National ID / CNIC <span class="text-danger">*</span></label>
                  <input type="text" class="form-control phone-numeric-only" id="national_id" name="national_id" required placeholder="Enter ID in standard format" maxlength="15">
                  <div class="invalid-feedback">National ID is required</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Passport No</label>
                  <input type="text" class="form-control" id="passport_no" name="passport_no">
                </div>
                
                <div class="col-md-3">
                  <label for="qualification" class="form-label">Qualification</label>
                  <select class="form-select" name="qualification" id="qualification" required>
                    <option value="">Select Qualification</option>
                    <?php foreach ($qualifications_list as $qua): ?>
                      <option value="<?= e($qua) ?>"><?= e($qua) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (empty($qualifications_list)): ?>
                    <div class="form-text text-muted">No qualifications configured in System Options.</div>
                  <?php endif; ?>
                </div>
            
                <div class="col-md-3">
                    <label for="religion" class="form-label">Religion <span class="text-danger">*</span></label>
                    <select class="form-select" id="religion" name="religion" required>
                        <option value="" selected disabled>Select Religion</option>
                        <option value="islam">Islam</option>
                        <option value="christianity">Christianity</option>
                        <option value="hinduism">Hinduism</option>
                        <option value="buddhism">Buddhism</option>
                        <option value="sikhism">Sikhism</option>
                        <option value="judaism">Judaism</option>
                        <option value="other">Other</option>                        
                    </select>
                    <div class="invalid-feedback">Religion is required</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="password" name="password" required>
                  <div class="invalid-feedback">Please enter a password</div>
                </div>
                
              </div>
        
              <div class="app-divider-v dotted mt-4 mb-3"></div>              
        
              <div class="tab-navigation">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#employment-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>
            
            <!-- Employment Information Tab -->
            <div class="tab-pane fade" id="employment" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Department <span class="text-danger">*</span></label>
                  <select class="form-select" name="emp_department" id="emp_department" required>
                    <option value="" selected disabled>Select Department</option>
                    <?php foreach($emp_department as $dept): ?>
                      <option value="<?= $dept['id'] ?>"><?= html_escape($dept['name']) ?></option>
                    <?php endforeach ?>
                  </select>
                </div>

                <!-- Team Name -->
                <div class="col-md-3">
                  <label class="form-label">Team Name</label>
                  <select class="form-select" id="emp_team" name="emp_team">
                    <option value="" selected disabled>Select Team</option>
                    <?php foreach ($teams as $t): ?>
                      <option value="<?= (int)$t['id']; ?>">
                        <?= html_escape($t['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
            
                <div class="col-md-3">
                  <label class="form-label">Position <span class="text-danger">*</span></label>
                  <select class="form-select" name="position_id" id="position_id" required>
                    <option value="" selected disabled>Select Position</option>
                    <?php foreach($positions as $pos): ?>
                        <option value="<?= (int)($pos['id'] ?? 0) ?>"
                                data-min-salary="<?= isset($pos['min_salary']) ? (float)$pos['min_salary'] : 0 ?>"
                                data-max-salary="<?= isset($pos['max_salary']) ? (float)$pos['max_salary'] : 0 ?>">
                          <?= e($pos['title'] ?? 'Untitled') ?>
                        </option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                    <div class="col-md-3">
                      <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                      <input type="date" class="form-control basic-date" name="emp_joining" id="emp_joining" placeholder="YYYY-MM-DD" required>
                    </div>
                    
                    <div class="col-md-3">
                      <label class="form-label">Probation End Date</label>
                      <input type="date" class="form-control basic-date" name="probation_end_date" id="probation_end_date" placeholder="YYYY-MM-DD">
                    </div>
                
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="employment_type" class="form-label">
                              Employment Type <span class="text-danger">*</span>
                            </label>
                        
                            <select class="form-select" id="employment_type" name="employment_type" required>
                              <option value="">Select Type</option>
                        
                              <?php foreach ($employment_types as $opt): ?>
                                <option
                                  value="<?= e($opt) ?>"
                                  <?= ($opt === 'Probation') ? 'selected' : '' ?>>
                                  <?= e($opt) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                        
                            <?php if (empty($employment_types)): ?>
                              <div class="form-text text-muted">
                                No Employment Types configured in System Options.
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>

                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="contract_type" class="form-label">Contract Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="contract_type" name="contract_type" required>
                              <option value="">Select Contract Type</option>
                              <?php foreach ($contract_types as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= (($user['contract_type'] ?? '') === $opt) ? 'selected' : '' ?>>
                                  <?= e($opt) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                            <?php if (empty($contract_types)): ?>
                              <div class="form-text text-muted">No Contract Types configured in System Options.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                    
                <div class="col-md-3">
                  <label for="shift" class="form-label">Working Shift <span class="text-danger">*</span></label>
                  <select class="form-select" name="shift" id="shift">
                    <option value="">Assign Work Shift</option>
                    <?php foreach (get_company_shifts(['format' => 'dropdown']) as $id => $name): ?>
                      <option value="<?= (int)$id ?>"><?= html_escape($name) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-3">
                    <label for="pay_period" class="form-label">Pay Period</label>
                    <select class="form-select" id="pay_period" name="pay_period">
                        <option value="" disabled>Select Pay Period</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                    </select>    
                </div>

                <div class="col-md-3">
                  <label for="work_location" class="form-label">Work Location</label>
                  <select class="form-select" name="work_location" id="work_location" required>
                    <option value="">Select Work Location</option>
                    <?php foreach ($work_location_types as $opt): ?>
                      <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (empty($work_location_types)): ?>
                    <div class="form-text text-muted">No Work Location types configured in System Options.</div>
                  <?php endif; ?>
                </div>

                <div class="col-md-3">
                  <label for="office_id" class="form-label">Office Location <span class="text-danger">*</span></label>
                  <select class="form-select" name="office_id" id="office_id">
                    <option value="">Select Office Location</option>
                    <?php foreach (get_company_offices(['format' => 'dropdown']) as $id => $name): ?>
                      <option value="<?= (int)$id ?>"><?= html_escape($name) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-12">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                </div>
              </div>
              
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#personal-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#salary-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>
            
            <!-- Salary Information Tab -->
            <div class="tab-pane fade" id="salary" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-6 salary-range-container d-none">
                  <label class="form-label">Salary Range <small class="text-muted">(Salary range for the selected position)</small></label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="min_salary_display" readonly>
                    <span class="input-group-text">to</span>
                    <input type="text" class="form-control" id="max_salary_display" readonly>
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Joining Salary <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                    <input type="number" class="form-control" name="joining_salary" id="joining_salary" step="0.01" min="0" required>
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Current Salary <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                    <input type="number" class="form-control" name="current_salary" id="current_salary" step="0.01" min="0" required>
                  </div>
                </div>
                
                <div class="col-md-12">
                  <div class="select_primary">
                    <label class="form-label">Employee Allowances <span class="text-danger">*</span></label>
                    <select class="form-select select-allowances w-100" name="allowance_ids[]" id="allowance_ids" multiple="multiple" data-placeholder="Select Allowances" required>
                      <?php foreach ($allowances as $a): ?>
                        <option value="<?= $a['id'] ?>">
                          <?= html_escape($a['title']) ?> 
                          (<?= $a['is_percentage'] ? $a['amount'] . '%' : html_escape(get_base_currency_symbol()) . number_format($a['amount'], 2) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="col-md-3">
                  <label class="form-label">Tax Number</label>
                  <input type="text" class="form-control" name="tax_number" id="tax_number">
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Insurance Policy No</label>
                  <input type="text" class="form-control" name="insurance_policy_no" id="insurance_policy_no">
                </div>

                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="emp_grade" class="form-label">Employee Grade</label>
                    <select class="form-select" id="emp_grade" name="emp_grade">
                      <option value="">Select Employee Grade</option>
                      <?php foreach ($employee_grades as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($user['emp_grade'] ?? '') === $opt) ? 'selected' : '' ?>>
                          <?= e(ucfirst($opt)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (empty($employee_grades)): ?>
                      <div class="form-text text-muted">No employee grade types configured in System Settings.</div>
                    <?php endif; ?>
                  </div>
                </div>
            
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="bank_name" class="form-label">Bank Name</label>
                    <select class="form-select" id="bank_name" name="bank_name">
                      <option value="">Select Ban Name</option>
                      <?php foreach ($bank_names as $bank): ?>
                        <option value="<?= e($bank) ?>" <?= (($user['bank_name'] ?? '') === $bank) ? 'selected' : '' ?>>
                          <?= e(ucfirst($bank)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (empty($bank_names)): ?>
                      <div class="form-text text-muted">No bank names configured in System Settings.</div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Account Number / IBAN <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="bank_account_number" id="bank_account_number" placeholder="enter complete IBAN number" required>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Branch Name</label>
                  <input type="text" class="form-control" name="bank_branch" id="bank_branch" placeholder="e.g., Blue Area, Civic Center">
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Bank Code</label>
                  <input type="text" class="form-control" name="bank_code" id="bank_code" placeholder="e.g., 56589">
                </div>
                
              </div>
              
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#employment-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#emergency-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>
            
            <!-- Emergency Contact Tab -->
            <div class="tab-pane fade" id="emergency" role="tabpanel">
              <div class="row g-3">

                <div class="col-md-3">    
                  <div class="mb-3">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <select class="form-select" id="blood_group" name="blood_group">
                      <option value="">Select Blood Group</option>
                      <?php foreach ($blood_group_types as $bgt): ?>
                        <option value="<?= e($bgt) ?>" <?= (($user['blood_group'] ?? '') === $bgt) ? 'selected' : '' ?>>
                          <?= e($bgt) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (empty($blood_group_types)): ?>
                      <div class="form-text text-muted">No Blood Group Types configured in System Options.</div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Father's Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="father_name" name="father_name" required>
                  <div class="invalid-feedback">Father's name is required</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Mother's Name</label>
                  <input type="text" class="form-control" id="mother_name" name="mother_name">
                </div>

                <div class="col-md-3">
                  <label class="form-label">Emergency Contact Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name" required>
                  <div class="invalid-feedback">Contact name is required</div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Emergency Contact Phone <span class="text-danger">*</span></label>
                  <input type="text" class="form-control phone-numeric-only" name="emergency_contact_phone" id="emergency_contact_phone" required placeholder="(312) 345-6789" maxlength="11">
                  <div class="invalid-feedback">Emergency phone is required</div>
                </div>
                
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="emergency_contact_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select class="form-select" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                      <option value="">Select Relationship</option>
                      <?php foreach ($relationship_types as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= (($user['emergency_contact_relationship'] ?? '') === $opt) ? 'selected' : '' ?>>
                          <?= e($opt) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Relationship is required</div>
                    <?php if (empty($relationship_types)): ?>
                      <div class="form-text text-muted">No Relationship Types configured in System Options.</div>
                    <?php endif; ?>
                  </div>
                </div>
                
              </div>
              
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#salary-tab">Previous</button>
                <button type="submit" class="btn btn-primary btn-sm" id="add-user-save">
                  <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                  <span class="btn-text">Save User</span>
                </button>
              </div>
            </div>
            
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(function () {
  // --- Select2 (optional)
  if ($.fn.select2) {
    $('.select-allowances').select2({
      width: '100%',
      placeholder: 'Select Allowances',
      allowClear: true,
      dropdownParent: $('#addUserModal')
    });
  }

  // --- Helpers
  const currencySymbol = "<?= html_escape(get_base_currency_symbol()) ?>";

  function formatCurrency(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '';
    return currencySymbol + ' ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
  }

  function showTabByButtonId(btnSelector) {
    const el = document.querySelector(btnSelector);
    if (!el) return;
    bootstrap.Tab.getOrCreateInstance(el).show();
  }

  function goToFieldTab($field) {
    const pane = $field.closest('.tab-pane');
    if (!pane.length) return;
    const paneId = pane.attr('id');
    const btn = $(`button[data-bs-target="#${paneId}"]`);
    if (btn.length) bootstrap.Tab.getOrCreateInstance(btn[0]).show();
  }

  // NEW: unified field flagger
  function flagField(selector, message) {
    const $f = $(selector);
    if (!$f.length) return;
    $f.addClass('is-invalid');
    goToFieldTab($f);
    try { $f[0].focus({ preventScroll: true }); } catch (_) { $f.focus(); }
    if (message && window.toastr) toastr.error(message);
  }

  // --- Validation
  function validatePane($pane) {
    let ok = true;
    $pane.find('[required]').each(function () {
      const v = ($(this).val() || '').toString().trim();
      if (!v) {
        $(this).addClass('is-invalid');
        if (ok) goToFieldTab($(this));
        ok = false;
      }
    });
    return ok;
  }

  function validateAll() {
    let ok = true;
    $('.tab-pane').each(function () {
      $(this).find('[required]').each(function () {
        const v = ($(this).val() || '').toString().trim();
        if (!v) {
          $(this).addClass('is-invalid');
          if (ok) goToFieldTab($(this));
          ok = false;
        }
      });
    });
    return ok;
  }

  $(document).on('input change', 'input, select, textarea', function () {
    $(this).removeClass('is-invalid');
  });

    // Enforce numeric-only for phone fields
    $(document).on('input', '.phone-numeric-only', function () {
        this.value = this.value.replace(/\D/g, ''); // strip everything except 0–9
    });

  // --- Position change
  $('#position_id').on('change', function () {
    const $opt = $(this).find(':selected');
    const minS = parseFloat($opt.data('min-salary')) || 0;
    const maxS = parseFloat($opt.data('max-salary')) || 0;

    if ($opt.val() && (minS > 0 || maxS > 0)) {
      $('.salary-range-container').removeClass('d-none');
      $('#min_salary_display').val(formatCurrency(minS));
      $('#max_salary_display').val(formatCurrency(maxS));
    } else {
      $('.salary-range-container').addClass('d-none');
      $('#min_salary_display, #max_salary_display').val('');
    }

    $('#emp_title').val($opt.val() || '');
    $('#header_emp_title').text(($opt.text() || '').trim());
  });

  // --- Profile image
  $('#profile_image').on('change', function (e) {
    const f = e.target.files && e.target.files[0];
    if (!f) { $('#selectedFileName').hide(); return; }
    const okTypes = ['image/jpeg','image/png','image/webp','image/gif'];
    if (okTypes.indexOf(f.type) === -1 || f.size > 2 * 1024 * 1024) {
      if (window.toastr) toastr.error('Only JPG/PNG/WEBP/GIF up to 2MB.');
      $(this).val('');
      return;
    }
    const url = URL.createObjectURL(f);
    $('#profileImagePreview').attr('src', url);
    $('#selectedFileName').text(f.name).show();
    $('#removeProfileBtn').show();
    $('#remove_profile_photo').val(0);
  });

  window.removeProfilePhoto = function () {
    $('#profileImagePreview').attr('src', '<?= base_url('assets/images/default-avatar.png') ?>');
    $('#profile_image').val('');
    $('#remove_profile_photo').val(1);
    $('#removeProfileBtn, #selectedFileName').hide();
  };

  // --- Auto Full Name
    $('#firstname, #initials, #lastname').on('input', function () {
        const parts = [
            $('#firstname').val(),
            $('#initials').val(),
            $('#lastname').val()
        ];
    
        const full = parts
            .map(v => (v || '').trim())
            .filter(v => v.length > 0)
            .join(' ');
    
        $('#fullname').val(full);
    });

  // --- Role visibility
  $('#user_role').on('change', function () {
    const role = $(this).val();
    $('.field-team-name').toggleClass('d-none', role === 'admin');
    $('.field-team-lead').toggleClass('d-none', role !== 'employee');
    $('.field-manager').toggleClass('d-none', role !== 'teamlead');
    $('.field-reporting').toggleClass('d-none', role !== 'manager');
  }).trigger('change');

  // --- Default joining date
  if (!$('#emp_joining').val()) {
    $('#emp_joining').val(new Date().toISOString().split('T')[0]);
  }

  // --- Tabs
  $('.next-tab').on('click', function () {
    const $pane = $(this).closest('.tab-pane');
    if (!validatePane($pane)) {
      if (window.toastr) toastr.error('Please fill required fields in this section.');
      return;
    }
    showTabByButtonId($(this).data('target'));
    $('html, body').animate({ scrollTop: $('#addUserModal').offset().top }, 100);
  });

  $('.prev-tab').on('click', function () {
    showTabByButtonId($(this).data('target'));
    $('html, body').animate({ scrollTop: $('#addUserModal').offset().top }, 100);
  });

  // --- Submit
  $('#add-user-form').on('submit', function (e) {
    e.preventDefault();

    $('input, select, textarea').removeClass('is-invalid');
    if (!validateAll()) {
      if (window.toastr) toastr.error('Please fix the highlighted fields.');
      return;
    }

    const $btn = $('#add-user-save');
    const $spinner = $btn.find('.spinner-border');
    const $text = $btn.find('.btn-text');

    const data = new FormData(this);

    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');
    $text.text('Saving...');

    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: data,
      processData: false,
      contentType: false,
      success: function (resp) {
        let res = resp;
        if (typeof resp === 'string') {
          try { res = JSON.parse(resp); } catch (e) {
            if (window.toastr) toastr.error('Unexpected server response.');
            return;
          }
        }

        if (res && res.success) {
          if (window.toastr) toastr.success(res.message || 'New user added successfully!');
          $('#addUserModal').modal('hide');
          window.location.reload();
          return;
        }

        // ---- Handle duplicates / server-side validation messages
        const msg = (res && res.message) ? res.message : 'Failed to save.';

        // 1) Structured error (if backend ever returns {field: "..."} )
        if (res && res.field) {
          if (res.field === 'username') return flagField('#username', msg);
          if (res.field === 'email')    return flagField('#email', msg);
          if (res.field === 'emp_id')   return flagField('#emp_id', msg);
        }

        // 2) Message-based detection (current controllers)
        if (/username/i.test(msg)) return flagField('#username', msg);
        if (/email/i.test(msg))    return flagField('#email', msg);
        if (/(^|\b)(employee\s*id|emp[\s_]?id)\b/i.test(msg)) return flagField('#emp_id', msg);

        // Fallback toast
        if (window.toastr) toastr.error(msg);
      },
      error: function (xhr) {
        let msg = 'Server error while saving user.';
        let res = null;
        try { res = JSON.parse(xhr.responseText || '{}'); } catch (_) {}

        if (res && res.message) msg = res.message;

        // Explicit 422 handling with field hints
        if (xhr.status === 422) {
          // Prefer structured field if provided
          if (res && res.field) {
            if (res.field === 'username') return flagField('#username', msg);
            if (res.field === 'email')    return flagField('#email', msg);
            if (res.field === 'emp_id')   return flagField('#emp_id', msg);
          }
          // Message-based fallback (includes emp_id now)
          if (/username/i.test(msg)) return flagField('#username', msg);
          if (/email/i.test(msg))    return flagField('#email', msg);
          if (/(^|\b)(employee\s*id|emp[\s_]?id)\b/i.test(msg)) return flagField('#emp_id', msg);
        }

        if (window.toastr) toastr.error(msg);
      },
      complete: function () {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none');
        $text.text('Save User');
      }
    });
  });
});
</script>
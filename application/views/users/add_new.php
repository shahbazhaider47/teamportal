<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/form_input_mask.css')?>">

<?php $probationMonths = (int) company_setting('probation_duration_months'); ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
       
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
    
        <a class="btn btn-header btn-light-primary" href="<?= base_url('users') ?>">
          <i class="ti ti-arrow-left"></i> Go Back
        </a>
            
      </div>
    </div>

  <div class="card">
    <div class="card-body">
      <form class="app-form" id="add-user-form" method="post" action="<?= site_url('user_new/add') ?>" enctype="multipart/form-data" autocomplete="off" data-probation-months="<?= $probationMonths ?>">
        <div class="form-body">
          <input type="hidden" name="emp_title" id="emp_title">

          <ul class="nav nav-tabs app-tabs-primary mb-3" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                <i class="ti ti-user-circle me-2"></i> Personal
              </button>
            </li>

            <li class="nav-item" role="presentation">
              <button class="nav-link" id="empaddress-tab" data-bs-toggle="tab" data-bs-target="#empaddress" type="button" role="tab" aria-controls="empaddress" aria-selected="false">
                <i class="ti ti-map me-2"></i> Address
              </button>
            </li>
            
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment" type="button" role="tab" aria-controls="employment" aria-selected="false">
                <i class="ti ti-badge me-2"></i> Employment
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button" role="tab" aria-controls="salary" aria-selected="false">
                <i class="ti ti-report-money me-2"></i> Salary
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency" type="button" role="tab" aria-controls="emergency" aria-selected="false">
                <i class="ti ti-emergency-bed me-2"></i> Emergency
              </button>
            </li>
          </ul>
          
          <div class="tab-content" id="userTabContent">
            <!-- Personal Information Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
              <div class="row g-3">
                <div class="col-md-4 mb-3">
                  <div class="d-flex align-items-center gap-2">
                    <!-- Profile Image with Remove Button -->
                    <div class="profile-image-container">
                      <img id="profileImagePreview" 
                           src="<?= base_url('assets/images/default.png') ?>"
                           alt="Profile photo"
                           class="rounded-circle shadow-sm border border-light">
                      <button type="button"
                              id="removeProfileBtn"
                              class="btn btn-light border-0 shadow-sm remove-profile-btn p-1 rounded-circle"
                              title="Remove Photo"
                              aria-label="Remove profile photo"
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
                               id="profile_image"
                               aria-describedby="profileHelp">
                      </div>
                      <div id="selectedFileName" class="text-muted small mt-1" style="display:none;"></div>
                      <div id="profileHelp" class="form-text small">JPG, PNG or WEBP. Max 2MB.</div>
                      <input type="hidden" name="remove_photo" id="remove_profile_photo" value="0">
                    </div>
                  </div>
                </div>

                <!-- Chrome autofill prevention (decoy fields) -->
                <input type="text" name="fakeusernameremembered" style="display:none">
                <input type="password" name="fakepasswordremembered" style="display:none">
                
                <div class="col-md-2">
                    <label for="emp_id" class="form-label">EMP ID <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <?php $empPrefix = trim((string) emp_id_prefix()); ?>
                        <span class="input-group-text">
                            <?php if ($empPrefix !== ''): ?>
                                <?= html_escape($empPrefix) ?>
                            <?php else: ?>
                            <i class="ti ti-ban text-danger me-1"></i> <small class="text-muted x-small">Prefix not set</small>
                            <?php endif; ?>
                        </span>
                        
                        <input type="text" class="form-control numeric-only" id="emp_id" name="emp_id" maxlength="4" minlength="4"
                               inputmode="numeric" pattern="[0-9]{4}" required placeholder="1___"
                               aria-describedby="empIdHelp">
                    </div>
                    <small id="empIdHelp" class="text-muted x-small"> Should be unique and 4 digits numeric only. </small>
                </div>
                
                <div class="col-md-2">
                  <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="username" name="username" maxlength="16" required placeholder="unique username" autocomplete="new-password">
                </div>
                
                <div class="col-md-2">
                  <label for="user_role" class="form-label">Staff Role <span class="text-danger">*</span></label>
                  <select name="user_role" class="form-select js-searchable-select" id="user_role" required>
                    <option value="" selected disabled>Select Role</option>
                    <?php foreach ($roles as $role): ?>
                      <option value="<?= html_escape($role) ?>"><?= ucfirst(html_escape($role)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-2">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select js-searchable-select" id="gender" name="gender" required>
                        <option value="" selected disabled>Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="unknown">Unknown</option>
                    </select>    
                </div>
                
                <div class="app-divider-v dotted mb-3"></div> 
                
                <div class="col-md-3">
                  <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="firstname" name="firstname" required>
                </div>
                
                <div class="col-md-3">
                  <label for="initials" class="form-label">Initial/Middle</label>
                  <input type="text" class="form-control" id="initials" name="initials">
                </div>
                        
                <div class="col-md-3">
                  <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="lastname" name="lastname" required>
                </div>
                
                <div class="col-md-3">
                  <label for="fullname" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="fullname" name="fullname">
                </div>

                <?php $minHiringAge = (int) company_setting('min_hiring_age', 0); ?>
                <div class="col-md-3">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label for="emp_dob" class="form-label mb-0">
                      Date of Birth <span class="text-danger">*</span>
                    </label>
                
                    <?php if ($minHiringAge > 0): ?>
                      <small class="text-muted x-small">
                        Age Limit:
                        <strong class="text-danger">
                          <?= (int) $minHiringAge ?> Years
                        </strong>
                      </small>
                    <?php endif; ?>
                  </div>
                
                  <input type="date"
                         class="form-control basic-date"
                         name="emp_dob"
                         id="emp_dob"
                         required
                         placeholder="YYYY-MM-DD">
                </div>
                
                <div class="col-md-3">
                  <label for="email" class="form-label">Email Address<span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="email" name="email" required placeholder="personal email address" maxlength="50">
                </div>
                
                <div class="col-md-3">
                  <label for="emp_phone" class="form-label">Personal Phone <span class="text-danger">*</span></label>
                
                  <div class="input-group">
                    <select class="form-select phone-country-code"
                            id="personal_phone_country"
                            data-phone-country="personal"
                            style="max-width:90px;">
                      <option value="" selected disabled>Code</option>
                      <option value="US">+1</option>
                      <option value="PK" selected>+92</option>
                    </select>
                
                    <input type="text"
                           class="form-control"
                           id="emp_phone"
                           name="emp_phone"
                           data-mask="phone"
                           data-phone-for="personal"
                           required
                           placeholder="Select country first">
                  </div>
                </div>
                
                <div class="col-md-3">
                    <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                    <select class="form-select js-searchable-select" id="marital_status" name="marital_status" required>
                        <option value="" selected disabled>Select Marital Status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                        <option value="other">Other</option>
                    </select>    
                </div>
                                
                <div class="col-md-3">
                    <label for="nationality" class="form-label">
                        Nationality <span class="text-danger">*</span>
                    </label>
                
                    <select name="nationality"
                            id="nationality"
                            class="form-select js-searchable-select"
                            required>
                
                        <option value="" disabled selected>
                            — Select Nationality —
                        </option>
                
                        <?php foreach (nationality_list() as $code => $row): ?>
                            <option value="<?= html_escape($row['name']) ?>">
                                <?= html_escape($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                
                    </select>
                </div>
                
                <?php $cnicRequired = company_setting('cnic_required', false); ?>
                <div class="col-md-3">
                    <label for="national_id" class="form-label">
                        National ID / CNIC
                        <?php if ($cnicRequired): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                
                    <input type="text"
                           class="form-control"
                           id="national_id"
                           name="national_id"
                           data-mask="cnic"
                           <?= $cnicRequired ? 'required' : '' ?>>
                </div>

                <div class="col-md-3">
                  <label for="nic_expiry" class="form-label">National ID Expiry</label>
                  <input type="date" class="form-control basic-date" name="nic_expiry" id="nic_expiry" placeholder="YYYY-MM-DD">
                </div> 
                
                <div class="col-md-3">
                  <label for="passport_no" class="form-label">Passport No</label>
                  <input type="text" class="form-control" id="passport_no" name="passport_no">
                </div>

                <div class="col-md-3">
                  <label for="qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" name="qualification" id="qualification" required>
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
                    <select class="form-select js-searchable-select" id="religion" name="religion" required>
                        <option value="" disabled>Select Religion</option>
                        <option value="islam" selected>Islam</option>
                        <option value="christianity">Christianity</option>
                        <option value="hinduism">Hinduism</option>
                        <option value="buddhism">Buddhism</option>
                        <option value="sikhism">Sikhism</option>
                        <option value="judaism">Judaism</option>
                        <option value="other">Other</option>                        
                    </select>
                </div>
                
                <div class="col-md-3">
                  <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                </div>
                
              </div>
        
              <div class="app-divider-v dotted mt-4 mb-3"></div>              
        
              <div class="tab-navigation">
                <a href="<?= site_url('users') ?>" class="btn btn-secondary btn-sm">Cancel</a>
                <!-- FIXED: Personal tab should go to Address tab, not Employment -->
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#empaddress-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>

            <!-- Address Information Tab -->
            <div class="tab-pane fade" id="empaddress" role="tabpanel" aria-labelledby="empaddress-tab">
              <div class="row g-3">
                
                <div class="col-md-6">
                  <label for="address" class="form-label">Permanent Address <span class="text-danger">*</span></label>
                  <input class="form-control" id="address" name="address" required>
                </div>

                <div class="col-md-2">
                  <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="city" name="city" required>
                </div>

                <div class="col-md-2">
                  <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="state" name="state" required>
                </div>
                
                <div class="col-md-2">
                    <label for="country" class="form-label">
                        Country <span class="text-danger">*</span>
                    </label>
                
                    <select name="country"
                            id="country"
                            class="form-select js-searchable-select"
                            required>
                
                        <option value="" disabled selected>
                            — Select Country —
                        </option>
                
                        <?php foreach (top_countries_list() as $code => $row): ?>
                            <option value="<?= html_escape($row['name']) ?>">
                                <?= html_escape($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                
                    </select>
                </div>
                
                <div class="col-md-6">
                  <label for="current_address" class="form-label">Current Address Complete <span class="text-danger">*</span></label>
                  <input class="form-control" id="current_address" name="current_address" required>
                </div>
                
              </div>
        
              <div class="app-divider-v dotted mt-4 mb-3"></div>              
        
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#personal-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#employment-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>
            
            <!-- Employment Information Tab -->
            <div class="tab-pane fade" id="employment" role="tabpanel" aria-labelledby="employment-tab">
              <div class="row g-3">
                <div class="col-md-3">
                  <label for="emp_department" class="form-label">Department <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" name="emp_department" id="emp_department" required>
                    <option value="" selected disabled>Select Department</option>
                    <?php foreach($emp_department as $dept): ?>
                      <option value="<?= $dept['id'] ?>"><?= html_escape($dept['name']) ?></option>
                    <?php endforeach ?>
                  </select>
                </div>

                <?php
                $user_role = strtolower($user['user_role'] ?? 'employee');

                $teamlead_list = $teamLeads ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'teamlead');
                $manager_list  = $managers  ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'manager');
                $director_list = $directors ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'director');
                ?>
                
                <!-- Team Name -->
                <div class="col-md-3">
                  <label for="emp_team" class="form-label">Team Name <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" id="emp_team" name="emp_team" required>
                    <option value="" selected disabled>Select Team</option>
                    <?php foreach ($teams as $t): ?>
                      <option value="<?= (int)$t['id']; ?>">
                        <?= html_escape($t['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- EMPLOYEE → TEAM LEAD -->
                <div class="col-md-3 field-teamlead d-none">
                  <label for="emp_teamlead" class="form-label">
                    Team Lead
                  </label>
                
                  <select class="form-select js-searchable-select"
                          name="emp_teamlead"
                          id="emp_teamlead"
                          data-scope="teamlead">
                    <option value="">Select Team Lead</option>
                    <?php foreach ($teamlead_list as $tl): ?>
                      <option value="<?= (int) $tl['id']; ?>">
                        <?= e($tl['fullname']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <!-- TEAM LEAD → MANAGER -->
                <div class="col-md-3 field-manager d-none">
                  <label for="emp_manager" class="form-label">
                    Manager
                  </label>
                
                  <select class="form-select js-searchable-select"
                          name="emp_manager"
                          id="emp_manager"
                          data-scope="manager">
                    <option value="">Select Manager</option>
                    <?php foreach ($manager_list as $mgr): ?>
                      <option value="<?= (int) $mgr['id']; ?>">
                        <?= e($mgr['fullname']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <!-- REPORTING PERSON -->
                <div class="col-md-3 field-reporting d-none">
                  <label for="emp_reporting" class="form-label">
                    Reporting Person
                  </label>
                
                  <select class="form-select js-searchable-select"
                          name="emp_reporting"
                          id="emp_reporting"
                          data-scope="reporting">
                    <option value="">Select Reporting Person</option>
                
                    <?php foreach ($directors as $rp): ?>
                      <option value="<?= (int) $rp['id']; ?>">
                        <?= e($rp['fullname']); ?>
                      </option>
                    <?php endforeach; ?>
                
                  </select>
                </div>
                
                <div class="col-md-3">
                  <label for="position_id" class="form-label">Position / Designation <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" name="position_id" id="position_id" required>
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
                  <label for="emp_joining" class="form-label">Joining Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control basic-date" name="emp_joining" id="emp_joining" placeholder="YYYY-MM-DD" required>
                </div>
                    
                <!-- PROBATION + CONFIRMATION -->
                <div class="col-md-3">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label for="probation_end_date" class="form-label mb-0">
                      Probation End Date <span class="text-danger">*</span>
                    </label>
                
                    <div class="form-check form-check-inline mb-0">
                      <input class="form-check-input"
                             type="checkbox"
                             id="is_confirmed_employee"
                             name="is_confirmed_employee"
                             value="1">
                      <label class="form-check-label small text-muted"
                             for="is_confirmed_employee">
                        Confirmed?
                      </label>
                    </div>
                  </div>
                
                  <input type="date"
                         class="form-control basic-date"
                         name="probation_end_date"
                         id="probation_end_date"
                         disabled 
                         required 
                         placeholder="YYYY-MM-DD">

                <?php $probationMonths = (int) company_setting('probation_duration_months', 0); ?>

                    <?php if ($probationMonths > 0): ?>
                      <small class="text-muted x-small">
                       Probation Duration:
                        <strong class="text-danger">
                          <?= (int) $probationMonths ?> Months
                        </strong>
                      </small>
                    <?php endif; ?>
                    
                </div>
                        
                <!-- CONFIRMATION DATE (ONLY WHEN CONFIRMED) -->
                <div class="col-md-3 d-none" id="confirmationDateWrapper">
                  <label for="confirmation_date" class="form-label">
                    Confirmation Date <span class="text-danger">*</span>
                  </label>
                  <input type="date"
                         class="form-control basic-date"
                         name="confirmation_date"
                         id="confirmation_date"
                         placeholder="YYYY-MM-DD">
                </div>
                        
                <?php $defaultEmploymentType = company_setting('default_employment_type'); ?>
                        
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="employment_type" class="form-label">
                      Employment Type <span class="text-danger">*</span>
                    </label>
                        
                    <select class="form-select js-searchable-select"
                            id="employment_type"
                            name="employment_type"
                            required>
                        
                      <option value="" disabled
                        <?= empty($defaultEmploymentType) ? 'selected' : '' ?>>
                        Select Type
                      </option>
                        
                      <?php foreach ($employment_types as $opt): ?>
                        <option value="<?= e($opt) ?>"
                          <?= ($defaultEmploymentType === $opt) ? 'selected' : '' ?>>
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
                    <select class="form-select js-searchable-select" id="contract_type" name="contract_type" required>
                      <option value="">Select Contract Type</option>
                      <?php foreach ($contract_types as $opt): ?>
                        <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (empty($contract_types)): ?>
                      <div class="form-text text-muted">No Contract Types configured in System Options.</div>
                    <?php endif; ?>
                  </div>
                </div>
                    
                <div class="col-md-3">
                  <label for="work_shift" class="form-label">Working Shift <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" name="work_shift" id="work_shift" required>
                    <option value="">Assign Work Shift</option>
                    <?php foreach (get_company_shifts(['format' => 'dropdown']) as $id => $name): ?>
                      <option value="<?= (int)$id ?>"><?= html_escape($name) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-3">
                  <label for="pay_period" class="form-label">Pay Period <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" id="pay_period" name="pay_period" required>
                    <option value="" disabled>Select Pay Period</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                  </select>    
                </div>

                <div class="col-md-3">
                  <label for="work_location" class="form-label">Work Location <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" name="work_location" id="work_location" required>
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
                  <select class="form-select js-searchable-select" name="office_id" id="office_id" required>
                    <option value="">Select Office Location</option>
                    <?php foreach (get_company_offices(['format' => 'dropdown']) as $id => $name): ?>
                      <option value="<?= (int)$id ?>"><?= html_escape($name) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-12">
                  <label for="notes" class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                </div>
              </div>
              
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#empaddress-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#salary-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>
            
            <!-- Salary Information Tab -->
            <div class="tab-pane fade" id="salary" role="tabpanel" aria-labelledby="salary-tab">
              <div class="row g-3">
                <div class="col-md-6 salary-range-container d-none">
                  <label class="form-label">Salary Range <small class="text-muted">(Salary range for the selected position)</small></label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="min_salary_display" readonly aria-label="Minimum salary">
                    <span class="input-group-text">to</span>
                    <input type="text" class="form-control" id="max_salary_display" readonly aria-label="Maximum salary">
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label for="joining_salary" class="form-label">Joining Salary <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                    <input type="number" class="form-control" name="joining_salary" id="joining_salary" step="0.01" min="0" required>
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label for="current_salary" class="form-label">Current Salary <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                    <input type="number" class="form-control" name="current_salary" id="current_salary" step="0.01" min="0" required>
                  </div>
                </div>
                
<div class="col-md-12">
  <div class="row g-2 align-items-start">

    <!-- Title -->
    <div class="col-md-3">
      <label class="form-label mb-1">
        Employee Allowances <span class="text-danger">*</span>
      </label>
      <small class="text-muted d-block">Select one or more</small>
    </div>

    <!-- Checkbox List -->
    <div class="col-md-9">
      <div class="allowance-checklist p-2 border rounded bg-white">

        <?php if (!empty($allowances)): ?>
          <?php foreach ($allowances as $a): ?>
            <?php
              $label = html_escape($a['title']) . ' (' .
                ($a['is_percentage']
                  ? ($a['amount'] . '%')
                  : (html_escape(get_base_currency_symbol()) . number_format($a['amount'], 2))
                ) . ')';
            ?>

            <label class="allowance-item d-flex align-items-center gap-2 mb-2">
              <input type="checkbox"
                     class="form-check-input allowance-checkbox"
                     name="allowance_ids[]"
                     value="<?= (int)$a['id'] ?>">
              <span class="small"><?= $label ?></span>
            </label>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted small mb-0">No allowances configured.</p>
        <?php endif; ?>

      </div>

      <!-- Hidden required field (ensures at least 1 checkbox selected) -->
      <input type="text"
             id="allowance_required"
             style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;"
             required>
    </div>

  </div>
</div>

                
                <?php $taxRequired = company_setting('tax_number_required', false); ?>
                <div class="col-md-3">
                  <label for="tax_number" class="form-label"> Tax ID / Number
                    <?php if ($taxRequired): ?>
                      <span class="text-danger">*</span>
                    <?php endif; ?>
                  </label>
                  <input type="text" class="form-control" id="tax_number" name="tax_number" <?= $taxRequired ? 'required' : '' ?>>
                </div>
                
                <div class="col-md-3">
                  <label for="insurance_policy_no" class="form-label">Insurance Policy No</label>
                  <input type="text" class="form-control" name="insurance_policy_no" id="insurance_policy_no">
                </div>

                <div class="col-md-3">
                  <label for="eobi_no" class="form-label">EOBI No</label>
                  <input type="text" class="form-control" name="eobi_no" id="eobi_no">
                </div>

                <?php $ntnRequired = company_setting('ntn_required', false); ?>
                
                <div class="col-md-3">
                  <label for="ntn_no" class="form-label">
                    NTN No
                    <?php if ($ntnRequired): ?>
                      <span class="text-danger">*</span>
                    <?php endif; ?>
                  </label>
                
                  <input type="text"
                         class="form-control"
                         id="ntn_no"
                         name="ntn_no"
                         <?= $ntnRequired ? 'required' : '' ?>>
                </div>
                
                <?php $defaultEmployeeGrade = company_setting('default_employee_grade'); ?>
                        
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="emp_grade" class="form-label">
                      Employee Grade <span class="text-danger">*</span>
                    </label>
                        
                    <select class="form-select js-searchable-select"
                            id="emp_grade"
                            name="emp_grade"
                            required>
                        
                      <option value="" disabled
                        <?= empty($defaultEmployeeGrade) ? 'selected' : '' ?>>
                        Select Type
                      </option>
                        
                      <?php foreach ($employee_grades as $opt): ?>
                        <option value="<?= e($opt) ?>"
                          <?= ($defaultEmployeeGrade === $opt) ? 'selected' : '' ?>>
                          <?= e($opt) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                        
                    <?php if (empty($employee_grades)): ?>
                      <div class="form-text text-muted">
                        No employee grade types configured in System Settings.
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <select class="form-select js-searchable-select" id="bank_name" name="bank_name" required>
                      <option value="">Select Bank Name</option>
                      <?php foreach ($bank_names as $bank): ?>
                        <option value="<?= e($bank) ?>"><?= e($bank) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (empty($bank_names)): ?>
                      <div class="form-text text-muted">No bank names configured in System Settings.</div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <?php
                $ibanMin = (int) company_setting('iban_min_digits');
                $ibanMax = (int) company_setting('iban_max_digits');
                ?>
                
                <div class="col-md-3">
                  <label for="bank_account_number" class="form-label">
                    Account Number / IBAN
                    <?php if ($ibanMin > 0): ?>
                      <span class="text-danger">*</span>
                    <?php endif; ?>
                  </label>
                
                  <input type="text"
                         class="form-control"
                         name="bank_account_number"
                         id="bank_account_number"
                         data-mask=""
                         data-min="<?= $ibanMin ?>"
                         data-max="<?= $ibanMax ?>"
                         minlength="<?= $ibanMin ?: '' ?>"
                         maxlength="<?= $ibanMax ?: '' ?>"
                         placeholder="Enter IBAN number"
                         <?= $ibanMin > 0 ? 'required' : '' ?>>
                </div>
                
                <div class="col-md-3">
                  <label for="bank_branch" class="form-label">Branch Name</label>
                  <input type="text" class="form-control" name="bank_branch" id="bank_branch" placeholder="e.g., Blue Area, Civic Center">
                </div>
                
                <div class="col-md-3">
                  <label for="bank_code" class="form-label">Bank Code</label>
                  <input type="text" class="form-control" name="bank_code" id="bank_code" placeholder="e.g., 56589">
                </div>

                <?php $defaultPayMethod = (string) company_setting('default_salary_pay_method'); ?>
                
                <div class="col-md-3">
                  <label for="pay_method" class="form-label">
                    Salary Pay Method <span class="text-danger">*</span>
                  </label>
                
                  <select class="form-select js-searchable-select"
                          name="pay_method"
                          id="pay_method"
                          required>
                
                    <option value="" disabled
                      <?= $defaultPayMethod === '' ? 'selected' : '' ?>>
                      Select Pay Method
                    </option>
                
                    <option value="bank_transfer"
                      <?= $defaultPayMethod === 'bank_transfer' ? 'selected' : '' ?>>
                      Bank Transfer
                    </option>
                
                    <option value="cash"
                      <?= $defaultPayMethod === 'cash' ? 'selected' : '' ?>>
                      Cash
                    </option>
                
                    <option value="cheque"
                      <?= $defaultPayMethod === 'cheque' ? 'selected' : '' ?>>
                      Cheque
                    </option>
                
                    <option value="digital_wallet"
                      <?= $defaultPayMethod === 'digital_wallet' ? 'selected' : '' ?>>
                      Digital Wallet
                    </option>
                
                    <option value="other"
                      <?= $defaultPayMethod === 'other' ? 'selected' : '' ?>>
                      Other
                    </option>
                
                  </select>
                </div>
                
                <div class="col-md-3">
                  <label for="allow_payroll" class="form-label">Add to Payroll <span class="text-danger">*</span></label>
                  <select class="form-select js-searchable-select" name="allow_payroll" id="allow_payroll" required>
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
                
              </div>
              
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#employment-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#emergency-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>
            
            <!-- Emergency Contact Tab -->
            <div class="tab-pane fade" id="emergency" role="tabpanel" aria-labelledby="emergency-tab">
              <div class="row g-3">

                <?php $bloodGroupRequired = (bool) company_setting('blood_group_required', false); ?>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="blood_group" class="form-label">
                      Blood Group
                      <?php if ($bloodGroupRequired): ?>
                        <span class="text-danger">*</span>
                      <?php endif; ?>
                    </label>
                
                    <select class="form-select js-searchable-select"
                            id="blood_group"
                            name="blood_group"
                            <?= $bloodGroupRequired ? 'required' : '' ?>>
                
                      <option value=""
                        <?= $bloodGroupRequired ? 'disabled selected' : 'selected' ?>>
                        Select Blood Group
                      </option>
                
                      <?php foreach ($blood_group_types as $bgt): ?>
                        <option value="<?= e($bgt) ?>">
                          <?= e($bgt) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                
                    <?php if (empty($blood_group_types)): ?>
                      <div class="form-text text-muted">
                        No Blood Group Types configured in System Options.
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <?php
                $fatherRequired = (bool) company_setting('father_name_required', false);
                $motherRequired = (bool) company_setting('mother_name_required', false);
                ?>
                <div class="col-md-3">
                  <label for="father_name" class="form-label">
                    Father's Name
                    <?php if ($fatherRequired): ?>
                      <span class="text-danger">*</span>
                    <?php endif; ?>
                  </label>
                
                  <input type="text"
                         class="form-control"
                         id="father_name"
                         name="father_name"
                         <?= $fatherRequired ? 'required' : '' ?>>
                </div>
                
                <div class="col-md-3">
                  <label for="mother_name" class="form-label">
                    Mother's Name
                    <?php if ($motherRequired): ?>
                      <span class="text-danger">*</span>
                    <?php endif; ?>
                  </label>
                
                  <input type="text"
                         class="form-control"
                         id="mother_name"
                         name="mother_name"
                         <?= $motherRequired ? 'required' : '' ?>>
                </div>

                <div class="col-md-3">
                  <label for="emergency_contact_name" class="form-label">Emergency Contact Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name" required>
                </div>
                
                <div class="col-md-3">
                  <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone <span class="text-danger">*</span></label>
                
                  <div class="input-group">
                    <select class="form-select phone-country-code"
                            id="emergency_phone_country"
                            data-phone-country="emergency"
                            style="max-width:90px;">
                      <option value="" selected disabled>Code</option>
                      <option value="US">+1</option>
                      <option value="PK" selected>+92</option>
                    </select>
                
                    <input type="text"
                           class="form-control"
                           id="emergency_contact_phone"
                           name="emergency_contact_phone"
                           data-mask="phone"
                           data-phone-for="emergency"
                           required
                           placeholder="Select country first">
                  </div>
                </div>
                
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="emergency_contact_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select class="form-select js-searchable-select" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                      <option value="">Select Relationship</option>
                      <?php foreach ($relationship_types as $opt): ?>
                        <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (empty($relationship_types)): ?>
                      <div class="form-text text-muted">No Relationship Types configured in System Options.</div>
                    <?php endif; ?>
                  </div>
                </div>
                
              </div>
              
              <div class="tab-navigation">
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#salary-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
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
<style>
.allowance-checklist {
  max-height: 240px;
  overflow-y: auto;
}

.allowance-item {
  cursor: pointer;
}

.allowance-item input {
  margin-top: 0;
}
    
</style>
<script>
    var base_url = '<?= base_url() ?>';
    var minHiringAge = <?= (int) $minHiringAge ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?= base_url('assets/js/form_input_mask.js'); ?>"></script>
<script src="<?= base_url('assets/js/add_user.js'); ?>"></script>


<script>
(function () {
  function validateAllowanceRequired() {
    const boxes = document.querySelectorAll('.allowance-checkbox');
    const requiredField = document.getElementById('allowance_required');
    if (!requiredField) return;

    const anyChecked = Array.from(boxes).some(b => b.checked);

    // If any selected, clear required. If none, force required.
    if (anyChecked) {
      requiredField.removeAttribute('required');
      requiredField.value = 'ok';
    } else {
      requiredField.setAttribute('required', 'required');
      requiredField.value = '';
    }
  }

  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('allowance-checkbox')) {
      validateAllowanceRequired();
    }
  });

  document.addEventListener('DOMContentLoaded', validateAllowanceRequired);
})();
    
</script>
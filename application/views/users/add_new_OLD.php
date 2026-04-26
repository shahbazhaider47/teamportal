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
              <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab"><i class="ti ti-user-circle me-2"></i> Personal</button>
            </li>

            <li class="nav-item" role="presentation">
              <button class="nav-link" id="empaddress-tab" data-bs-toggle="tab" data-bs-target="#empaddress" type="button" role="tab"><i class="ti ti-map me-2"></i> Address</button>
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
                           src="<?= base_url('assets/images/default.png') ?>"
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

                <!-- Chrome autofill prevention (decoy fields) -->
                <input type="text" name="fakeusernameremembered" style="display:none">
                <input type="password" name="fakepasswordremembered" style="display:none">
                
                <div class="col-md-2">
                    <label class="form-label">EMP ID <span class="text-danger">*</span></label>
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
                               inputmode="numeric" pattern="[0-9]{4}" required placeholder="1___">
                    </div>
                        <small class="text-muted x-small"> Should be unique and 4 digits numeric only. </small>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Username <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="username" name="username" maxlength="16" required placeholder="unique username" autocomplete="new-password">
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Staff Role <span class="text-danger">*</span></label>
                  <select name="user_role" class="form-select" id="user_role" required>
                    <option value="" selected disabled>Select Role</option>
                    <?php foreach ($roles as $role): ?>
                      <option value="<?= html_escape($role) ?>"><?= ucfirst(html_escape($role)) ?></option>
                    <?php endforeach; ?>
                  </select>
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
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Initial/Middle</label>
                  <input type="text" class="form-control" id="initials" name="initials">
                </div>
                        
                <div class="col-md-3">
                  <label class="form-label">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="lastname" name="lastname" required>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Full Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="fullname" name="fullname" required>
                </div>

                <?php $minHiringAge = (int) company_setting('min_hiring_age', 0); ?>
                <div class="col-md-3">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label mb-0">
                      Date of Birth <span class="text-danger">*</span>
                    </label>
                
                    <?php if ($minHiringAge > 0): ?>
                      <small class="text-muted x-small">
                        Age Limit:
                        <strong class="text-danger">
                          <?= (int) $minHiringAge ?> yrs
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
                  <label class="form-label">Email Address<span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="email" name="email" required placeholder="personla email address" maxlength="30">
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Personal Phone <span class="text-danger">*</span></label>
                
                  <div class="input-group">
                    <select class="form-select phone-country-code"
                            id="emp_phone_country"
                            data-phone-country
                            style="max-width:90px;">
                      <option value="" selected disabled>Code</option>
                      <option value="US">+1</option>
                      <option value="PK">+92</option>
                    </select>
                
                    <input type="text"
                           class="form-control"
                           id="emp_phone"
                           name="emp_phone"
                           data-mask="phone"
                           required
                           placeholder="Select country first">
                  </div>
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
                                
                <div class="col-md-3">
                    <label class="form-label">
                        Nationality <span class="text-danger">*</span>
                    </label>
                
                    <select name="nationality"
                            id="nationality"
                            class="form-select"
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
                    <label class="form-label">
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
                  <label class="form-label">National ID Expiry</label>
                  <input type="date" class="form-control basic-date" name="nic_expiry" id="nic_expiry" placeholder="YYYY-MM-DD">
                </div> 
                
                <div class="col-md-3">
                  <label class="form-label">Passport No</label>
                  <input type="text" class="form-control" id="passport_no" name="passport_no">
                </div>

                <div class="col-md-3">
                  <label for="qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
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
                  <label class="form-label">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                </div>
                
              </div>
        
              <div class="app-divider-v dotted mt-4 mb-3"></div>              
        
              <div class="tab-navigation">
                <a href="<?= site_url('users') ?>" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#employment-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
              </div>
            </div>

            <!-- Personal Information Tab -->
            <div class="tab-pane fade" id="empaddress" role="empaddress">
              <div class="row g-3">
                
                <div class="col-md-6">
                  <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                  <input class="form-control" id="address" name="address" required>
                </div>

                <div class="col-md-2">
                  <label class="form-label">City <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="city" name="city" required>
                </div>

                <div class="col-md-2">
                  <label class="form-label">State <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="state" name="state" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">
                        Country <span class="text-danger">*</span>
                    </label>
                
                    <select name="country"
                            id="country"
                            class="form-select"
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
                  <label class="form-label">Current Address Complete <span class="text-danger">*</span></label>
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

                <?php
                $user_role = strtolower($user['user_role'] ?? 'employee');

                $teamlead_list = $teamLeads ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'teamlead');
                $manager_list  = $managers  ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'manager');
                $director_list = $directors ?? array_filter($allUsers ?? [], fn($u) => strtolower($u['user_role'] ?? '') === 'director');
                ?>
                
                <!-- Team Name -->
                <div class="col-md-3">
                  <label class="form-label">Team Name <span class="text-danger">*</span></label>
                  <select class="form-select" id="emp_team" name="emp_team" required>
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
                  <label class="form-label">
                    Team Lead <span class="text-danger">*</span>
                  </label>
                
                  <select class="form-select"
                          name="emp_teamlead"
                          data-scope="teamlead"
                          required>
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
                  <label class="form-label">
                    Manager <span class="text-danger">*</span>
                  </label>
                
                  <select class="form-select"
                          name="emp_manager"
                          data-scope="manager"
                          required>
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
                  <label class="form-label">
                    Reporting Person <span class="text-danger">*</span>
                  </label>
                
                  <select class="form-select"
                          name="emp_reporting"
                          data-scope="reporting"
                          required>
                    <option value="">Select Reporting Person</option>
                
                    <?php foreach ($directors as $rp): ?>
                      <option value="<?= (int) $rp['id']; ?>">
                        <?= e($rp['fullname']); ?>
                      </option>
                    <?php endforeach; ?>
                
                  </select>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Position / Designation <span class="text-danger">*</span></label>
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
                    
                        <!-- PROBATION + CONFIRMATION -->
                        <div class="col-md-3">
                        
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label mb-0">
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
                                Confirmed Employee?
                              </label>
                            </div>
                          </div>
                        
                            <input type="date"
                                   class="form-control basic-date"
                                   name="probation_end_date"
                                   id="probation_end_date"
                                   disabled required placeholder="YYYY-MM-DD">
                        </div>
                        
                        <!-- CONFIRMATION DATE (ONLY WHEN CONFIRMED) -->
                        <div class="col-md-3 d-none" id="confirmationDateWrapper">
                          <label class="form-label">
                            Confirmation Date <span class="text-danger">*</span>
                          </label>
                          <input type="date"
                                 class="form-control basic-date"
                                 name="confirmation_date"
                                 id="confirmation_date"
                                 placeholder="YYYY-MM-DD">
                        </div>
                        
                        <?php
                        $defaultEmploymentType = company_setting('default_employment_type');
                        ?>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="employment_type" class="form-label">
                                    Employment Type <span class="text-danger">*</span>
                                </label>
                        
                                <select class="form-select"
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
                            <select class="form-select" id="contract_type" name="contract_type" required>
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
                  <label for="work_shift " class="form-label">Working Shift <span class="text-danger">*</span></label>
                  <select class="form-select" name="work_shift" id="work_shift" required>
                    <option value="">Assign Work Shift</option>
                    <?php foreach (get_company_shifts(['format' => 'dropdown']) as $id => $name): ?>
                      <option value="<?= (int)$id ?>"><?= html_escape($name) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-3">
                    <label for="pay_period" class="form-label">Pay Period <span class="text-danger">*</span></label>
                    <select class="form-select" id="pay_period" name="pay_period" required>
                        <option value="" disabled>Select Pay Period</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                    </select>    
                </div>

                <div class="col-md-3">
                  <label for="work_location" class="form-label">Work Location <span class="text-danger">*</span></label>
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
                  <select class="form-select" name="office_id" id="office_id" required>
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
                <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#empaddress-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
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
                
                <?php $taxRequired = company_setting('tax_number_required', false); ?>
                <div class="col-md-3">
                    <label class="form-label"> Tax ID / Number
                        <?php if ($taxRequired): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" class="form-control" id="tax_number" name="tax_number" <?= $taxRequired ? 'required' : '' ?>>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Insurance Policy No</label>
                  <input type="text" class="form-control" name="insurance_policy_no" id="insurance_policy_no">
                </div>

                <div class="col-md-3">
                  <label class="form-label">EOBI No</label>
                  <input type="text" class="form-control" name="eobi_no" id="eobi_no">
                </div>

                <?php $ntnRequired = company_setting('ntn_required', false); ?>
                
                <div class="col-md-3">
                    <label class="form-label">
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
                
                        <?php
                        $defaultEmployeeGrade = company_setting('default_employee_grade');
                        ?>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="emp_grade" class="form-label">
                                    Employee Grade <span class="text-danger">*</span>
                                </label>
                        
                                <select class="form-select"
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
                    <select class="form-select" id="bank_name" name="bank_name" required>
                      <option value="">Select Ban Name</option>
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
                    <label class="form-label">
                        Account Number / IBAN
                        <?php if ($ibanMin > 0): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                
                    <input type="text"
                           class="form-control numeric-only"
                           name="bank_account_number"
                           id="bank_account_number"
                           data-mask="iban"
                           data-min="<?= $ibanMin ?>"
                           data-max="<?= $ibanMax ?>"
                           minlength="<?= $ibanMin ?: '' ?>"
                           maxlength="<?= $ibanMax ?: '' ?>"
                           placeholder="Enter IBAN number"
                           <?= $ibanMin > 0 ? 'required' : '' ?>>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Branch Name</label>
                  <input type="text" class="form-control" name="bank_branch" id="bank_branch" placeholder="e.g., Blue Area, Civic Center">
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Bank Code</label>
                  <input type="text" class="form-control" name="bank_code" id="bank_code" placeholder="e.g., 56589">
                </div>

                <?php
                $defaultPayMethod = (string) company_setting('default_salary_pay_method');
                ?>
                
                <div class="col-md-3">
                  <label for="pay_method" class="form-label">
                    Salary Pay Method <span class="text-danger">*</span>
                  </label>
                
                  <select class="form-select"
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
                  <label for="allow_payroll" class="form-label">Exclude From Payroll <span class="text-danger">*</span></label>
                  <select class="form-select" name="allow_payroll" id="allow_payroll" required>
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                  </select>
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

                <?php
                $bloodGroupRequired = (bool) company_setting('blood_group_required', false);
                ?>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="blood_group" class="form-label">
                      Blood Group
                      <?php if ($bloodGroupRequired): ?>
                        <span class="text-danger">*</span>
                      <?php endif; ?>
                    </label>
                
                    <select class="form-select"
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
                  <label class="form-label">
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
                  <label class="form-label">
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
                  <label class="form-label">Emergency Contact Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name" required>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label">Emergency Contact Phone <span class="text-danger">*</span></label>
                
                  <div class="input-group">
                    <select class="form-select phone-country-code"
                            id="emp_phone_country"
                            data-phone-country
                            style="max-width:90px;">
                      <option value="" selected disabled>Code</option>
                      <option value="US">+1</option>
                      <option value="PK">+92</option>
                    </select>
                
                    <input type="text"
                           class="form-control"
                           id="emergency_contact_phone"
                           name="emergency_contact_phone"
                           data-mask="phone"
                           required
                           placeholder="Select country first">
                  </div>
                </div>
                
                <div class="col-md-3">
                  <div class="mb-3">
                    <label for="emergency_contact_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select class="form-select" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
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

<script src="<?= base_url('assets/js/form_input_mask.js'); ?>"></script>

<script>
$(function () {

  /* =========================================================
   * SELECT2 (OPTIONAL)
   * ======================================================= */
  if ($.fn.select2) {
    $('.select-allowances').select2({
      width: '100%',
      placeholder: 'Select Allowances',
      allowClear: true,
      dropdownParent: $(document.body)
    });
  }

  /* =========================================================
   * HELPERS
   * ======================================================= */
  const currencySymbol = "<?= html_escape(get_base_currency_symbol()) ?>";

  function formatCurrency(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '';
    return currencySymbol + ' ' +
      parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
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

  function flagField(selector, message) {
    const $f = $(selector);
    if (!$f.length) return;
    $f.addClass('is-invalid');
    goToFieldTab($f);
    try { $f[0].focus({ preventScroll: true }); } catch (_) { $f.focus(); }
    if (message && window.toastr) toastr.error(message);
  }

  /* =========================================================
   * VALIDATION
   * ======================================================= */
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

  $(document).on('input', '.phone-numeric-only', function () {
    this.value = this.value.replace(/\D/g, '');
  });

/* =========================================================
 * POSITION → SALARY RANGE + EMP TITLE (ID SAFE)
 * ======================================================= */
$('#position_id').on('change', function () {

  const $opt = $(this).find(':selected');

  const positionId = $opt.val() ? parseInt($opt.val(), 10) : null;
  const minS = parseFloat($opt.data('min-salary')) || 0;
  const maxS = parseFloat($opt.data('max-salary')) || 0;

  // Salary range UI
  if (positionId && (minS > 0 || maxS > 0)) {
    $('.salary-range-container').removeClass('d-none');
    $('#min_salary_display').val(formatCurrency(minS));
    $('#max_salary_display').val(formatCurrency(maxS));
  } else {
    $('.salary-range-container').addClass('d-none');
    $('#min_salary_display, #max_salary_display').val('');
  }

  $('#emp_title').val(positionId || '');

});

  /* =========================================================
   * PROFILE IMAGE
   * ======================================================= */
  $('#profile_image').on('change', function (e) {
    const f = e.target.files && e.target.files[0];
    if (!f) { $('#selectedFileName').hide(); return; }

    const okTypes = ['image/jpeg','image/png','image/webp','image/gif'];
    if (okTypes.indexOf(f.type) === -1 || f.size > 2 * 1024 * 1024) {
      if (window.toastr) toastr.error('Only JPG/PNG/WEBP/GIF up to 2MB.');
      $(this).val('');
      return;
    }

    $('#profileImagePreview').attr('src', URL.createObjectURL(f));
    $('#selectedFileName').text(f.name).show();
    $('#removeProfileBtn').show();
    $('#remove_profile_photo').val(0);
  });

  window.removeProfilePhoto = function () {
    $('#profileImagePreview').attr(
      'src',
      '<?= base_url('assets/images/default-avatar.png') ?>'
    );
    $('#profile_image').val('');
    $('#remove_profile_photo').val(1);
    $('#removeProfileBtn, #selectedFileName').hide();
  };

  /* =========================================================
   * AUTO FULL NAME
   * ======================================================= */
  $('#firstname, #initials, #lastname').on('input', function () {
    const full = [
      $('#firstname').val(),
      $('#initials').val(),
      $('#lastname').val()
    ].map(v => (v || '').trim()).filter(Boolean).join(' ');
    $('#fullname').val(full);
  });

  /* =========================================================
   * ROLE → REPORTING SCOPE (FINAL + CORRECT)
   * ======================================================= */
    const ROLE_SCOPE_MAP = {
      employee:   { show: ['teamlead'] },
      teamlead:   { show: ['manager'] },
    
      manager:    { show: ['reporting'] },
      admin:      { show: ['reporting'] },
      officeboy:  { show: ['reporting'] },
      sweeper:    { show: ['reporting'] },
      other:      { show: ['reporting'] },
    
      director:   { show: [] },
      superadmin: { show: [] }
    };

    function applyRoleScope(role) {
      role = (role || '').toLowerCase();
    
      $('.field-teamlead, .field-manager, .field-reporting')
        .addClass('d-none')
        .find('select').val('');
    
      if (!ROLE_SCOPE_MAP[role]) return;
    
      const show = ROLE_SCOPE_MAP[role].show || [];
    
      if (show.includes('teamlead'))   $('.field-teamlead').removeClass('d-none');
      if (show.includes('manager'))    $('.field-manager').removeClass('d-none');
      if (show.includes('reporting'))  $('.field-reporting').removeClass('d-none');
    }

  $('#user_role').on('change', function () {
    applyRoleScope($(this).val());
  });

  applyRoleScope($('#user_role').val());

  /* =========================================================
   * DEFAULT JOINING DATE
   * ======================================================= */
  if (!$('#emp_joining').val()) {
    $('#emp_joining').val(new Date().toISOString().split('T')[0]);
  }

  /* =========================================================
   * TAB NAVIGATION
   * ======================================================= */
  $('.next-tab').on('click', function () {
    const $pane = $(this).closest('.tab-pane');
    if (!validatePane($pane)) {
      if (window.toastr) toastr.error('Please fill required fields.');
      return;
    }
    showTabByButtonId($(this).data('target'));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  $('.prev-tab').on('click', function () {
    showTabByButtonId($(this).data('target'));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

});

</script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const form               = document.getElementById('add-user-form');
    const joiningInput        = document.getElementById('emp_joining');
    const probationInput      = document.getElementById('probation_end_date');
    const confirmedCheckbox   = document.getElementById('is_confirmed_employee');
    const confirmationWrapper = document.getElementById('confirmationDateWrapper');
    const confirmationInput   = document.getElementById('confirmation_date');

    if (!form || !joiningInput || !probationInput) return;

    const probationMonths = parseInt(
        form.dataset.probationMonths || '0',
        10
    );

    /* -----------------------------
     * Date helpers
     * ----------------------------- */
    function addMonths(dateStr, months) {
        const d = new Date(dateStr);
        if (isNaN(d)) return null;

        const day = d.getDate();
        d.setMonth(d.getMonth() + months);

        if (d.getDate() < day) {
            d.setDate(0);
        }

        return d;
    }

    function formatDate(d) {
        return d.toISOString().split('T')[0];
    }

    /* -----------------------------
     * Probation auto calculation
     * ----------------------------- */
    function calculateProbationEnd() {
        if (!joiningInput.value || probationMonths <= 0) {
            probationInput.value = '';
            return;
        }

        const endDate = addMonths(joiningInput.value, probationMonths);
        if (!endDate) return;

        probationInput.value = formatDate(endDate);
    }

    /* -----------------------------
     * Confirmed employee toggle
     * ----------------------------- */
    function toggleConfirmation() {
        if (confirmedCheckbox && confirmedCheckbox.checked) {
            confirmationWrapper.classList.remove('d-none');
            confirmationInput.required = true;
            probationInput.required = false;
        } else {
            confirmationWrapper.classList.add('d-none');
            confirmationInput.required = false;
            confirmationInput.value = '';
            probationInput.required = true;
        }
    }

    /* -----------------------------
     * EVENTS
     * ----------------------------- */
    joiningInput.addEventListener('change', calculateProbationEnd);
    joiningInput.addEventListener('input', calculateProbationEnd);

    if (confirmedCheckbox) {
        confirmedCheckbox.addEventListener('change', toggleConfirmation);
    }

    /* -----------------------------
     * INITIAL STATE
     * ----------------------------- */
    if (!joiningInput.value) {
        joiningInput.value = new Date().toISOString().split('T')[0];
    }

    calculateProbationEnd();
    toggleConfirmation();
});
</script>

<script>
$(function () {

  const $confirmedCheckbox = $('#is_confirmed_employee');
  const $wrapper           = $('#confirmationDateWrapper');
  const $confirmationInput = $('#confirmation_date');

  function toggleConfirmationDate() {
    if ($confirmedCheckbox.is(':checked')) {
      $wrapper.removeClass('d-none');
      $confirmationInput.prop('required', true);
    } else {
      $wrapper.addClass('d-none');
      $confirmationInput
        .prop('required', false)
        .val('');
    }
  }

  $confirmedCheckbox.on('change', toggleConfirmationDate);

  toggleConfirmationDate();

});


(function () {

  const dobInput = document.getElementById('emp_dob');
  const minAge   = <?= (int) $minHiringAge ?>;

  if (!dobInput || minAge <= 0) return;

  const today = new Date();
  const maxDate = new Date(
    today.getFullYear() - minAge,
    today.getMonth(),
    today.getDate()
  );

  dobInput.max = maxDate.toISOString().split('T')[0];

})();

</script>
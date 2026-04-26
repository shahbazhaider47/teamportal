<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
/* Form text sizing - applies to entire form */
.app-form {
  font-size: 12px;
}

.app-form .form-label,
.app-form .form-check-label,
.app-form .form-text,
.app-form .small,
.app-form small {
  font-size: 12px;
  font-weight: 500;
}

/* For placeholders specifically */
.app-form .form-control::placeholder,
.app-form .form-select:invalid {
  font-size: 12px;
  color: #6c757d;
  opacity: 1;
}

.modal-inner {
  padding: 25px;
}

/* Tab styling */
#userTabContent .tab-pane {
  background: #fff;
  border-radius: 0rem;
  padding: 1rem;
}

/* Salary range display */
.salary-range-container {
  margin-bottom: 1rem;
}

/* Profile image styling */
.profile-image-container {
  position: relative;
  width: 80px;
  height: 80px;
}

.profile-image-container img {
  width: 80%;
  height: 80%;
  object-fit: cover;
}

.remove-profile-btn {
  position: absolute;
  top: 0;
  right: 0;
  transform: translate(30%, -30%);
}

/* Navigation buttons */
.tab-navigation {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
}

.tab-navigation button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Error styling */
.is-invalid {
  border-color: #dc3545 !important;
}

.invalid-feedback {
  display: none;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}

.is-invalid ~ .invalid-feedback {
  display: block;
}

</style>


<!-- application/views/users/modals/edit_user_modal.php -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form class="app-form" id="edit-user-form" method="post" action="<?= site_url('users/edit/'); ?>" enctype="multipart/form-data" autocomplete="off">
        
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="editUserModalLabel">Edit Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body modal-inner">
          <!-- Loading spinner -->
          <div id="edit-user-loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          
          <div id="edit-user-fields" class="d-none">
            <input type="hidden" id="edit-user-id" name="id" />
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
                <button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab"><i class="ti ti-building-bank me-2"></i> Bank</button>
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
                    <input type="text" class="form-control" id="username" name="username" required placeholder="Enter unique username">
                    <div class="invalid-feedback">Please enter a username</div>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">EMP ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="emp_id" name="emp_id" required>
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
                    <label class="form-label">Gender</label>
                    <select class="form-select" name="gender" id="gender">
                      <option value="" selected disabled>Select</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                    </select>
                  </div>  
                  
                  <div class="app-divider-v dotted mb-3"></div> 
                  
                  <div class="col-md-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                    <div class="invalid-feedback">Please enter first name</div>
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
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control basic-date" name="emp_dob" id="emp_dob" placeholder="YYYY-MM-DD">
                  </div>                
                  
                  <div class="col-md-3">
                    <label class="form-label">Email Address<span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">Please enter a valid email</div>
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Personal Phone</label>
                    <input type="text" class="form-control" id="emp_phone" name="emp_phone">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Marital Status</label>
                    <select class="form-select" name="marital_status" id="marital_status">
                      <option value="" selected disabled>Select Status</option>
                      <option value="Single">Single</option>
                      <option value="Married">Married</option>
                      <option value="Divorced">Divorced</option>
                      <option value="Widowed">Widowed</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Nationality</label>
                    <input type="text" class="form-control" id="nationality" name="nationality">
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label">Permanent Address</label>
                    <textarea class="form-control" id="address" name="address" rows="1"></textarea>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country">
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state">
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city">
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label">Current Address</label>
                    <textarea class="form-control" id="current_address" name="current_address" rows="1"></textarea>
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">National ID / CNIC</label>
                    <input type="text" class="form-control" id="national_id" name="national_id">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Passport No</label>
                    <input type="text" class="form-control" id="passport_no" name="passport_no">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Qualification</label>
                    <input type="text" class="form-control" id="qualification" name="qualification" placeholder="e.g., BSc Computer Science">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Religion</label>
                    <input type="text" class="form-control" id="religion" name="religion">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current">
                    <div class="form-text">Leave blank to keep current password</div>
                  </div>
                  
                  <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                      <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1">
                      <label class="form-check-label" for="is_active">
                        Status <small>Active/InActive</small>
                      </label>
                    </div>
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
                    <label class="form-label">Department</label>
                    <select class="form-select" name="emp_department" id="emp_department">
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
                    <label class="form-label">Position</label>
                    <select class="form-select" name="position_id" id="position_id">
                      <option value="" selected disabled>Select Position</option>
                      <?php foreach($positions as $pos): ?>
                        <option value="<?= $pos['id'] ?>" 
                                data-min-salary="<?= $pos['min_salary'] ?>"
                                data-max-salary="<?= $pos['max_salary'] ?>">
                          <?= html_escape($pos['title']) ?>
                        </option>
                      <?php endforeach ?>
                    </select>
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Joining Date </label>
                    <input type="date" class="form-control basic-date" name="emp_joining" id="emp_joining" placeholder="YYYY-MM-DD">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Probation End Date</label>
                    <input type="date" class="form-control basic-date" name="probation_end_date" id="probation_end_date" placeholder="YYYY-MM-DD">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Confirmation Date</label>
                    <input type="date" class="form-control basic-date" name="confirmation_date" id="confirmation_date" placeholder="YYYY-MM-DD">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Last Increment Date</label>
                    <input type="date" class="form-control basic-date" name="last_increment_date" id="last_increment_date" placeholder="YYYY-MM-DD">
                  </div>
                  
<div class="col-md-3">
  <label class="form-label">Contract Type</label>
  <select class="form-select" name="contract_type" id="employee_contract_type">
    <option value="" selected disabled>Select Type</option>
    <option value="Permanent">Permanent</option>
    <option value="Fixed-Term">Fixed-Term</option>
    <option value="Temporary">Temporary</option>
    <option value="Internship">Internship</option>
    <option value="Consultant">Consultant</option>
    <option value="Volunteer">Volunteer</option>
  </select>
</div>


<div class="col-md-3">
  <label class="form-label">Employment Type</label>
  <select class="form-select" name="employment_type" id="employee_employment_type">
    <option value="" selected disabled>Select Type</option>
    <option value="Full-time">Full-time</option>
    <option value="Part-time">Part-time</option>
    <option value="On-Call">On-Call</option>
    <option value="Probation">Probation</option>
    <option value="Extended Probation">Extended Probation</option>    
    <option value="Trainee">Trainee</option>
    <option value="Remote">Remote</option>
  </select>
</div>

                  <div class="col-md-3">
                    <label class="form-label">Shift</label>
                    <select class="form-select" name="shift" id="employee_shift">
                      <option value="">Select Shift</option>
                      <option value="Morning">Morning</option>
                      <option value="After Noon">After Noon</option>
                      <option value="Evening">Evening</option>
                      <option value="Night">Night</option>
                      <option value="Rotational">Rotational</option>
                      <option value="Other">Other</option>                    
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Pay Period</label>
                    <select class="form-select" name="pay_period" id="employee_pay_period">
                      <option value="" selected disabled>Select Pay Period</option>  
                      <option value="Monthly">Monthly</option>
                      <option value="Semi-monthly">Semi-monthly</option>
                      <option value="Bi-weekly">Bi-weekly</option>
                      <option value="Weekly">Weekly</option>
                      <option value="Daily">Daily</option>
                      <option value="Project-based">Project-based</option>
                      <option value="Commission-based">Commission-based</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Work Location</label>
                    <select class="form-select" name="work_location" id="employee_work_location">
                      <option value="" selected disabled>Select Work Location</option>
                      <option value="On-site">On-site</option>
                      <option value="Remote">Remote</option>
                      <option value="Hybrid">Hybrid</option>
                      <option value="Field-based">Field-based</option>
                      <option value="Client Location">Client Location</option>
                      <option value="Multiple Locations">Multiple Locations</option>
                      <option value="Not Assigned">Not Assigned</option>
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
                    <label class="form-label">Joining Salary </label>
                    <div class="input-group">
                      <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                      <input type="number" class="form-control" name="joining_salary" id="joining_salary" step="0.01" min="0">
                    </div>
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Current Salary </label>
                    <div class="input-group">
                      <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
                      <input type="number" class="form-control" name="current_salary" id="current_salary" step="0.01" min="0">
                    </div>
                  </div>
                  
                  <div class="col-md-12">
                    <div class="select_primary">
                      <label class="form-label">Allowances</label>
                      <select class="form-select select-allowances w-100" name="allowance_ids[]" id="allowance_ids" multiple="multiple" data-placeholder="Select Allowances">
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
                    <label class="form-label">Employee Grade</label>
                    <input type="text" class="form-control" id="emp_grade" name="emp_grade" placeholder="e.g., Grade A">
                  </div>
                  
                </div>
                
                <div class="tab-navigation">
                  <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#employment-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                  <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#bank-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
                </div>
              </div>
              
              <!-- Bank Information Tab -->
              <div class="tab-pane fade" id="bank" role="tabpanel">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Bank Name</label>
                    <select class="form-select" name="bank_name" id="bank_name">
                      <option value="" selected disabled>Select Bank From List</option>
                      <option value="HBL">Habib Bank Limited (HBL)</option>
                      <option value="UBL">United Bank Limited (UBL)</option>
                      <option value="MCB">MCB Bank Limited</option>
                      <option value="NBP">National Bank of Pakistan (NBP)</option>
                      <option value="ABL">Allied Bank Limited (ABL)</option>
                      <option value="Meezan">Meezan Bank</option>
                      <option value="Alfalah">Bank Alfalah</option>
                      <option value="Faysal">Faysal Bank</option>
                      <option value="Standard Chartered">Standard Chartered Bank</option>
                      <option value="Summit">Summit Bank</option>
                      <option value="Sindh">Sindh Bank</option>
                      <option value="Askari">Askari Bank</option>
                      <option value="JS">JS Bank</option>
                      <option value="Samba">Samba Bank</option>
                      <option value="Silk">Silk Bank</option>
                      <option value="Soneri">Soneri Bank</option>
                      <option value="Bank Islami">Bank Islami</option>
                      <option value="Dubai Islamic">Dubai Islamic Bank</option>
                      <option value="HabibMetro">Habib Metropolitan Bank</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Account Number / IBAN</label>
                    <input type="text" class="form-control" name="bank_account_number" id="bank_account_number" placeholder="enter complete IBAN number">
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label">Bank Branch</label>
                    <input type="text" class="form-control" name="bank_branch" id="bank_branch" placeholder="e.g., Blue Area, Civic Center">
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label">Bank Code</label>
                    <input type="text" class="form-control" name="bank_code" id="bank_code" placeholder="e.g., 56589">
                  </div>
                </div>
                
                <div class="tab-navigation">
                  <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#salary-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                  <button type="button" class="btn btn-primary btn-sm next-tab" data-target="#emergency-tab">Next <i class="ti ti-chevron-right ms-1"></i></button>
                </div>
              </div>
              
              <!-- Emergency Contact Tab -->
              <div class="tab-pane fade" id="emergency" role="tabpanel">
                <div class="row g-3">

                  <div class="col-md-3">
                    <label class="form-label">Employee Blood Group</label>
                    <select class="form-select" id="blood_group" name="blood_group">
                      <option value="" selected disabled>Select</option>
                      <option value="A+">A+</option>
                      <option value="A-">A-</option>
                      <option value="B+">B+</option>
                      <option value="B-">B-</option>
                      <option value="AB+">AB+</option>
                      <option value="AB-">AB-</option>
                      <option value="O+">O+</option>
                      <option value="O-">O-</option>
                    </select>
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Father's Name</label>
                    <input type="text" class="form-control" id="father_name" name="father_name">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Mother's Name</label>
                    <input type="text" class="form-control" id="mother_name" name="mother_name">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" class="form-control" name="emergency_contact_phone" id="emergency_contact_phone">
                  </div>
                  
                  <div class="col-md-3">
                    <label class="form-label">Emergency Contact Relationship</label>
                    <select class="form-select" name="emergency_contact_relationship" id="emergency_contact_relationship">
                      <option value="" selected disabled>Select Relationship</option>
                      <option value="Spouse">Spouse</option>
                      <option value="Parent">Parent</option>
                      <option value="Father">Father</option>
                      <option value="Mother">Mother</option>
                      <option value="Sibling">Sibling</option>
                      <option value="Brother">Brother</option>
                      <option value="Sister">Sister</option>
                      <option value="Child">Child</option>
                      <option value="Relative">Relative</option>
                      <option value="Friend">Friend</option>
                      <option value="Guardian">Guardian</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  
                </div>
                
                <div class="tab-navigation">
                  <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-target="#bank-tab"><i class="ti ti-chevron-left me-1"></i> Previous</button>
                  <button type="submit" class="btn btn-primary btn-sm" id="edit-user-save">
                    Update User
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Initialize Select2 for allowances
  $('.select-allowances').select2({
    width: '100%',
    placeholder: 'Select Allowances',
    allowClear: true,
    dropdownParent: $('#editUserModal')
  });

  // Format currency with symbol
  function formatCurrency(amount) {
    if (!amount || isNaN(amount)) return '';
    const symbol = "<?= html_escape(get_base_currency_symbol()) ?>";
    return `${symbol} ${parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
  }

  // Position change handler
  $('#position_id').on('change', function() {
    const selected = $(this).find(':selected');
    const minSalary = parseFloat(selected.data('min-salary')) || 0;
    const maxSalary = parseFloat(selected.data('max-salary')) || 0;
    const salaryRangeContainer = $('.salary-range-container');
    
    // Update salary range display
    if (selected.val() && (minSalary > 0 || maxSalary > 0)) {
      salaryRangeContainer.removeClass('d-none');
      $('#min_salary_display').val(formatCurrency(minSalary));
      $('#max_salary_display').val(formatCurrency(maxSalary));
    } else {
      salaryRangeContainer.addClass('d-none');
      $('#min_salary_display').val('');
      $('#max_salary_display').val('');
    }
    
    // Update employee title
    const empTitleField = $('#emp_title');
    if (empTitleField.length) {
      empTitleField.val(selected.val());
      const headerEl = $('#header_emp_title');
      if (headerEl.length) {
        headerEl.text(selected.text().trim());
      }
    }
  });

  // Profile image change handler
  $('#profile_image').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const url = URL.createObjectURL(file);
      $('#profileImagePreview').attr('src', url);
      $('#selectedFileName').text(file.name).show();
      $('#removeProfileBtn').show();
      $('#remove_profile_photo').val(0);
    } else {
      $('#selectedFileName').hide();
    }
  });

  // Remove profile photo
  window.removeProfilePhoto = function() {
    $('#profileImagePreview').attr('src', '<?= base_url('assets/images/default-avatar.png') ?>');
    $('#profile_image').val('');
    $('#remove_profile_photo').val(1);
    $('#removeProfileBtn').hide();
    $('#selectedFileName').hide();
  };

  // Auto-generate full name
  $('#firstname, #lastname').on('input', function() {
    $('#fullname').val($('#firstname').val() + ' ' + $('#lastname').val()).trim();
  });

  // Form submission handling
  $('#edit-user-form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const formData = new FormData(this);
    const submitBtn = $('#edit-user-save');
    
    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        const res = typeof response === 'string' ? JSON.parse(response) : response;
        if (res.success) {
          toastr.success(res.message);
          $('#editUserModal').modal('hide');
          setTimeout(() => window.location.reload(), 1000);
        } else {
          toastr.error(res.message);
        }
      },
      error: function(xhr) {
        let errorMsg = 'An error occurred while updating user';
        try {
          const res = JSON.parse(xhr.responseText);
          errorMsg = res.message || errorMsg;
        } catch (e) {
          errorMsg = xhr.responseText || errorMsg;
        }
        toastr.error(errorMsg);
      }
    });
  });
  
  // Role-based field visibility
  $('#user_role').on('change', function() {
    const role = $(this).val();
    $('.field-team-name').toggleClass('d-none', role === 'admin');
    $('.field-team-lead').toggleClass('d-none', role !== 'employee');
    $('.field-manager').toggleClass('d-none', role !== 'teamlead');
    $('.field-reporting').toggleClass('d-none', role !== 'manager');
  }).trigger('change');

  // Tab navigation functionality
  $('.next-tab').on('click', function() {
    const targetTab = $(this).data('target');
    const currentTabPane = $(this).closest('.tab-pane');
    
    // Validate required fields in current tab
    let isValid = true;
    currentTabPane.find('[required]').each(function() {
      if (!$(this).val()) {
        $(this).addClass('is-invalid');
        isValid = false;
      } else {
        $(this).removeClass('is-invalid');
      }
    });
    
    if (isValid) {
      $(targetTab).tab('show');
      $('html, body').animate({
        scrollTop: $('#editUserModal').offset().top
      }, 100);
    } else {
      toastr.error('Please fill all required fields before proceeding');
    }
  });

  $('.prev-tab').on('click', function() {
    const targetTab = $(this).data('target');
    $(targetTab).tab('show');
    $('html, body').animate({
      scrollTop: $('#editUserModal').offset().top
    }, 100);
  });

  // Clear validation when user starts typing
  $('input, select').on('input change', function() {
    if ($(this).hasClass('is-invalid')) {
      $(this).removeClass('is-invalid');
    }
  });

  // Modal show event
  $('#editUserModal').on('show.bs.modal', function(e) {
    const modal = $(this);
    const form = modal.find('form');
    const userId = e.relatedTarget.getAttribute('data-id');
    const loadingIndicator = modal.find('#edit-user-loading');
    const fieldsWrapper = modal.find('#edit-user-fields');
    
    // Show loader and hide fields
    loadingIndicator.removeClass('d-none');
    fieldsWrapper.addClass('d-none');
    
    // Set form action
    form.attr('action', '<?= site_url('users/edit/') ?>' + userId);
    modal.find('#edit-user-id').val(userId);
    
    // Load user data
    $.getJSON('<?= site_url('users/get_user/') ?>' + userId, function(resp) {
      if (resp.error) {
        toastr.error(resp.error);
        bootstrap.Modal.getInstance(modal[0]).hide();
        return;
      }
      
      // Handle all fields including null values
      Object.entries(resp).forEach(([k, v]) => {
        const fld = modal.find('[name="' + k + '"]');
        if (!fld.length || k === 'profile_image' || k === 'password') return;
        
        if (fld.attr('type') === 'checkbox') {
          fld.prop('checked', (v == 1));
        } else if (fld.is('select')) {
          fld.val(v !== null ? v : '').trigger('change');
        } else {
          fld.val(v !== null ? v : '');
        }
      });
      
      // Handle profile image
      if (resp.profile_image) {
        $('#profileImagePreview').attr('src', '<?= base_url() ?>' + resp.profile_image);
        $('#removeProfileBtn').show();
      }
      
      // Handle allowances (multi-select)
      if (resp.allowance_ids) {
        const allowanceIds = Array.isArray(resp.allowance_ids) ? 
          resp.allowance_ids : resp.allowance_ids.split(',');
        $('#allowance_ids').val(allowanceIds).trigger('change');
      }
      
      // Hide loader and show fields
      loadingIndicator.addClass('d-none');
      fieldsWrapper.removeClass('d-none');
    }).fail(function() {
      toastr.error('Failed to load user data');
      bootstrap.Modal.getInstance(modal[0]).hide();
    });
  });

  // Modal hidden event
  $('#editUserModal').on('hidden.bs.modal', function() {
    $(this).find('#edit-user-fields').addClass('d-none');
    $(this).find('#edit-user-loading').removeClass('d-none');
    $(this).find('form')[0].reset();
    $('#profileImagePreview').attr('src', '<?= base_url('assets/images/default-avatar.png') ?>');
    $('#removeProfileBtn').hide();
    $('#remove_profile_photo').val(0);
    $('.select-allowances').val(null).trigger('change');
    $('.is-invalid').removeClass('is-invalid');
  });
});
</script>
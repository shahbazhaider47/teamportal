<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>


<style>
/* Quick inline for demonstration, move to your stylesheet */
.profile-bg {
    background: url('<?= base_url('assets/images/emp-bg-profile.svg') ?>'), linear-gradient(90deg,rgba(131, 58, 180, 1) 0%, rgba(253, 29, 29, 1) 50%, rgba(252, 176, 69, 1) 100%);
    background-size: cover;
    background-position: center center;
    border-bottom: 1px solid #ececec;
    border-radius: 1rem 1rem 0 0;
    min-height: 130px;
}
.profile-avatar {
    width: 80px; height: 80px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid;
    margin-top: -35px;
    margin-left: 20px;
    display: block;
}
.section-card {
    border: 1px solid #ececec;
    border-radius: 12px;
    margin-bottom: 24px;
    background: #fff;
    box-shadow: 0 1px 4px rgba(44,54,89,0.06);
}
.section-card .card-header {
    border-bottom: 1px solid #ececec;
    border-radius: 12px 12px 0 0;
}
.section-card .card-body {
    padding: 1rem;
}
.section-title {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    font-size: 13px;
    padding: 7px 7px;
    margin-bottom: 5px;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    background-color: var(--bs-primary); /* Bootstrap primary color */
    color: #fff;
    border-radius: 0.35rem 0.35rem 0 0;
    box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.05);
}


.info-value {
    font-size: 11px;
    color: #181818;
    word-break: break-all;
}
.tab-content-box {
    padding: 1.3rem 0;
}
.leave-box {
    border: 1px solid #ececec;
    border-radius: 8px;
    background: #faf9fe;
    text-align: center;
    padding: 16px 12px;
    margin-bottom: 12px;
}
.leave-box .leave-type {
    font-weight: 600;
    margin-bottom: 6px;
}
.leave-box .leave-remaining {
    font-size: 1.5rem;
    font-weight: bold;
}
.leave-box .leave-label {
    font-size: 0.88rem;
    color: #656565;
}
.edit-profile-btn {
    float: right;
    margin-top: 12px;
}

@media (max-width: 991px) {
    .profile-main-grid { flex-direction: column; }
    .profile-main-grid > div { width: 100%!important; }
}


.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 1rem;
}

.info-icon {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.95rem;
    min-width: 150px;
}

.info-label {
    margin-left: 1px;
    font-size: 12px;
    font-weight: 500;
    color: #6c757d;
}

.info-value {
    flex: 1;
    text-align: left;
    font-weight: 400;
    font-size: 12px;
    color: #212529;
    word-break: break-word;
}


.salary-mask {
  font-weight: 500;
  min-width: 60px;
  display: inline-block;
}
.toggle-salary {
  padding: 2px 6px;
  line-height: 1;
}

.capitalize {
    text-transform: capitalize;
}

</style>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <a href="<?= site_url('users') ?>" title="Go Back">
            <i class="ti ti-arrow-left fs-5"></i>
        </a>        
        <h1 class="h6 header-title">
            <?= $page_title ?>
            <div class="small text-muted badge bg-light-primary">
                <?= html_escape($user['position_title'] ?? '-') ?>
            </div>
        </h1>
            <?php if ($user['is_active']): ?>
                <span class="pill pill-success">Active</span>
            <?php else: ?>
                <?php if (!empty($exit['exit_type'])): ?>
                    <span class="pill pill-danger">
                        Inactive (Reason: <?= html_escape($exit['exit_type']) ?>)
                    </span>
                <?php else: ?>
                    <span class="pill pill-danger">In-Active (By System)</span>
                <?php endif; ?>
            <?php endif; ?>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">

        <?php
        $canEdit = staff_can('edit', 'users');
        ?>
        
        <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
            <button type="button"
                class="btn btn-header btn-outline-primary"
                onclick="exitEmployee(
                    <?= (int)$user['id'] ?>,
                    '<?= html_escape(addslashes($user['firstname'] . ' ' . $user['lastname'])) ?>',
                    '<?= html_escape(addslashes($user['emp_title'] ?? '')) ?>')"
                title="Exit Employee">
                <i class="ti ti-logout"></i> Exit Employee
            </button>
        <?php endif; ?>
 

        <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
            <button type="button"
                class="btn btn-header btn-outline-primary"
                onclick="promoteEmployee(
                    <?= (int)$user['id'] ?>,
                    '<?= html_escape(addslashes($user['firstname'] . ' ' . $user['lastname'])) ?>',
                    '<?= html_escape(addslashes($user['emp_title'] ?? '')) ?>')"
                title="Promote Employee">
                <i class="ti ti-trophy"></i> Promote
            </button>
            <?php endif; ?>
            
        <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
            <button type="button"
                class="btn btn-header btn-outline-primary"
                onclick="promoteEmployee(
                    <?= (int)$user['id'] ?>,
                    '<?= html_escape(addslashes($user['firstname'] . ' ' . $user['lastname'])) ?>',
                    '<?= html_escape(addslashes($user['emp_title'] ?? '')) ?>')"
                title="Transfer Employee">
                <i class="ti ti-trophy"></i> Transfer Employee
            </button>
            <?php endif; ?>
            
        
            <div class="btn-divider"></div>
            <div class="dropdown">
                <button class="btn btn-header btn-primary dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-edit"></i> Edit Profile
                </button>
                <ul class="dropdown-menu p-2">
                <h6 class="text-muted small mt-1 text-center">Select to Edit</h6>
                <div class="app-divider-v dashed"></div>
                    
                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editPersonalModal">
                            <i class="ti ti-user-check me-2 text-primary"></i></i>Personal Profile
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editAddressModal">
                            <i class="ti ti-map me-2 text-primary"></i></i>Change Address
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editOfficialModal">
                            <i class="ti ti-briefcase me-2 text-primary"></i>Official Information
                        </a>
                    </li>
                    <?php endif; ?>                    
                    
                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editSalaryModal">
                            <i class="ti ti-currency-dollar me-2 text-primary"></i>Salary Details
                        </a>
                    </li>
                    <?php endif; ?>                    
                    
                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editEmergencyModal">
                            <i class="ti ti-emergency-bed me-2 text-primary"></i>Emergency Info
                        </a>
                    </li>
                    <?php endif; ?>                    

                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editTeamModal">
                            <i class="ti ti-users me-2 text-primary"></i>Team & Reporting
                        </a>
                    </li>
                    <?php endif; ?>                    
                    
                    <div class="app-divider-v dashed"></div>
                
                    <?php if ($canEdit && !empty($user['is_active']) && (int)$user['is_active'] === 1): ?>
                    <li class="small">
                        <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#changePassModal">
                            <i class="ti ti-lock me-2 text-primary"></i>Change Password
                        </a>
                    </li>
                    <?php endif; ?>                    
                </ul>
            </div>
      </div>
    </div>
    <div class="row">
        <!-- Left: Profile Card -->
        <div class="col-md-5">
            <div class="card border-0">
                <div class="profile-bg position-relative">
                    <!-- background image or pattern -->
                    <div style="height: 60px"></div>
                    <img class="profile-avatar" src="<?= base_url('uploads/users/profile/' . $user['profile_image']) ?>">
                </div>
                <div class="card-body">
                <div class="mt-1">
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-lock-square-rounded me-2"></i></i><span class="info-label">Username</span></div>
                        <div class="info-value"><?= html_escape($user['username'] ?? '-') ?></div>
                    </div> 
    
                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-abc me-2"></i>
                        <span class="info-label">First Name</span>
                      </div>
                      <div class="info-value"><?= html_escape($user['firstname']) ?></div>
                    </div>
                    
                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-letter-case me-2"></i>
                        <span class="info-label">Initial/Middle</span>
                      </div>
                      <div class="info-value"><?= html_escape($user['initials']) ?></div>
                    </div>
                    
                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-sort-z-a me-2"></i>
                        <span class="info-label">Last Name</span>
                      </div>
                      <div class="info-value"><?= html_escape($user['lastname']) ?></div>
                    </div>
                    
                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-id-badge me-2"></i>
                        <span class="info-label">Full Name</span>
                      </div>
                      <div class="info-value">
                        <?= html_escape(trim((($user['firstname'] ?? '') . ' ' . $user['initials'] ?? '') . ' ' . ($user['lastname'] ?? ''))) ?>
                      </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-user-circle me-2"></i><span class="info-label">Staff Role</span></div>
                        <div class="info-value capitalize"><?= html_escape($user['user_role'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-id me-2"></i><span class="info-label">Employee ID</span></div>
                        <div class="info-value"><?= emp_id_display($user['emp_id'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-mail-forward me-2"></i><span class="info-label">Email</span></div>
                        <div class="info-value"><?= html_escape($user['email']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-device-mobile me-2"></i></i><span class="info-label">Personal Cell</span></div>
                        <div class="info-value"><?= html_escape($user['emp_phone']) ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-calendar me-2"></i><span class="info-label">DOB</span></div>
                        <div class="info-value"><?= !empty($user['emp_dob']) ? date('l, d F Y', strtotime($user['emp_dob'])) : '-' ?>
                        <i class="ti ti-dots-vertical me-2"></i> <span class="badge bg-light-primary"><?= get_emp_age($user['emp_dob'], true); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-gender-agender me-2"></i><span class="info-label">Gender</span></div>
                        <div class="info-value capital"><?= html_escape($user['gender']) ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-dna-2 me-2"></i><span class="info-label">Marital Status</span></div>
                        <div class="info-value capital"><?= html_escape($user['marital_status'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-map me-2"></i><span class="info-label">Current Address</span></div>
                        <div class="info-value"><?= html_escape($user['current_address'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-home-2 me-2"></i><span class="info-label">Permenent Address</span></div>
                        <div class="info-value"><?= html_escape($user['address'] ?? '-') ?>, <?= html_escape($user['city'] ?? '-') ?>, <?= html_escape($user['state'] ?? '-') ?>, <?= html_escape($user['country'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-id me-2"></i><span class="info-label">National ID No</span></div>
                        <div class="info-value"><?= html_escape($user['national_id'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-e-passport me-2"></i><span class="info-label">Passport No</span></div>
                        <div class="info-value"><?= html_escape($user['passport_no'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-flag me-2"></i><span class="info-label">Nationality</span></div>
                        <div class="info-value capital"><?= html_escape($user['nationality'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-notes me-2"></i><span class="info-label">Notes</span></div>
                        <div class="info-value text-muted"><?= html_escape($user['notes'] ?? '-') ?></div>
                    </div>                    

                </div>
                </div>
            </div>
            
            <!-- Official Details -->
            <div class="section-card">
                <div class="card-header section-title bg-primary">
                    <i class="ti ti-file-certificate"></i> Official
                </div>
                <div class="card-body pt-2 pb-2">
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-briefcase me-2"></i><span class="info-label">Employment</span></div>
                        <div class="info-value capital"><?= html_escape($user['employment_type'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-writing-sign me-2"></i><span class="info-label">Contract</span></div>
                        <div class="info-value"><?= html_escape($user['contract_type'] ?? '-') ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-clock-hour-4 me-2"></i><span class="info-label">Work Shift</span></div>
                        <div class="info-value"><?= html_escape($user['work_shift_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-calendar-plus me-2"></i><span class="info-label">Joining Date</span></div>
                        <div class="info-value"><?= !empty($user['emp_joining']) ? date('l, d F Y', strtotime($user['emp_joining'])) : '-' ?>

                        <?php if (!empty($user['is_rejoined'])): ?>
                        <i class="ti ti-dots-vertical mx-1"></i>
                        <span class="badge bg-light-primary">Re-Joined On</span>
                        <small class="text-muted small">
                        <?= date('l, d F Y', strtotime($user['rejoin_date'])) ?>
                        </small>
                        <?php endif; ?>

                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-user-check me-2"></i><span class="info-label">Confirmation</span></div>
                        <div class="info-value"><?= !empty($user['confirmation_date']) ? date('l, d F Y', strtotime($user['confirmation_date'])) : '-' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-calendar-off me-2"></i><span class="info-label">Probation Date</span></div>
                        <div class="info-value"><?= !empty($user['probation_end_date']) ? date('l, d F Y', strtotime($user['probation_end_date'])) : '-' ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-building me-2"></i><span class="info-label">Work Location</span></div>
                        <div class="info-value"><?= html_escape($user['work_location'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-building-skyscraper me-2"></i><span class="info-label">Office Location</span></div>
                        <div class="info-value"><?= get_company_office_name($user['office_id'] ?? '-') ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-award me-2"></i><span class="info-label">Designation</span></div>
                        <div class="info-value"><?= html_escape($user['position_title'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-building-bank me-2"></i><span class="info-label">Department</span></div>
                        <div class="info-value"><?= html_escape($user['department_name'] ?? '-') ?></div>
                    </div>
                    
                </div>
            </div>

            <!-- Salary -->
            <div class="section-card">
                <div class="card-header section-title bg-primary">
                    <i class="ti ti-currency-real"></i> Salary
                </div>
                <div class="card-body pt-2 pb-2">
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-calendar-stats me-2"></i><span class="info-label">Pay Period</span></div>
                        <div class="info-value capital"><?= html_escape($user['pay_period'] ?? '-') ?></div>
                    </div>
                    <!-- Joining Salary -->
                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-currency-dollar me-2"></i>
                        <span class="info-label">Joining Salary</span>
                      </div>
                      <div class="info-value d-flex align-items-center gap-2">
                        <span class="salary-mask" data-actual="<?= html_escape(c_format($user['joining_salary'] ?? 0)) ?>">****</span>
                        <button type="button" class="btn btn-sm btn-light-primary toggle-salary" title="Show Salary">
                          <i class="ti ti-eye"></i>
                        </button>
                      </div>
                    </div>
                    
                    <!-- Current Salary -->
                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-currency-dollar me-2"></i>
                        <span class="info-label">Current Salary</span>
                      </div>
                      <div class="info-value d-flex align-items-center gap-2">
                        <span class="salary-mask" data-actual="<?= html_escape(c_format($user['current_salary'] ?? 0)) ?>">****</span>
                        <button type="button" class="btn btn-sm btn-light-primary toggle-salary" title="Show Salary">
                          <i class="ti ti-eye"></i>
                        </button>
                      </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-code-plus me-2"></i><span class="info-label">Last Increment</span></div>
                        <div class="info-value"><?= !empty($user['last_increment_date']) ? date(' d F Y', strtotime($user['last_increment_date'])) : '-' ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon">
                            <i class="ti ti-moneybag me-2"></i>
                            <span class="info-label">Allowances</span>
                        </div>
                        <div class="info-value d-flex flex-wrap gap-1">
                            <?php if (!empty($user['allowance_names'])): ?>
                                <?php foreach ($user['allowance_names'] as $name): ?>
                                    <span class="badge bg-light-primary text-dark small"><?= html_escape($name) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-row">
                      <div class="info-icon">
                        <i class="ti ti-coin me-2"></i>
                        <span class="info-label">Total Allowances</span>
                      </div>
                    
                      <div class="info-value d-flex align-items-center gap-2">
                        <span class="salary-mask"
                              data-actual="<?= html_escape(c_format(get_user_total_allowances($user))) ?>">
                          ****
                        </span>
                        <button type="button"
                                class="btn btn-sm btn-light-primary toggle-salary"
                                title="Show Allowances">
                          <i class="ti ti-eye"></i>
                        </button>
                      </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-building-hospital me-2"></i><span class="info-label">Insurance No</span></div>
                        <div class="info-value"><?= html_escape($user['insurance_policy_no'] ?? '-') ?></div>
                    </div>                    
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-receipt-tax me-2"></i></i><span class="info-label">TAX Number</span></div>
                        <div class="info-value"><?= html_escape($user['tax_number'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-building-bank me-2"></i><span class="info-label">Bank Name</span></div>
                        <div class="info-value"><?= html_escape($user['bank_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-building me-2"></i><span class="info-label">Branch</span></div>
                        <div class="info-value"><?= html_escape($user['bank_branch'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-credit-card me-2"></i><span class="info-label">Account No</span></div>
                        <div class="info-value"><?= html_escape($user['bank_account_number'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-barcode me-2"></i><span class="info-label">Bank Code</span></div>
                        <div class="info-value"><?= html_escape($user['bank_code'] ?? '-') ?></div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="section-card">
                <div class="card-header section-title bg-primary">
                    <i class="ti ti-emergency-bed"></i> Emergency Contact
                </div>
                <div class="card-body pt-2 pb-2">

                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-user-exclamation me-2"></i><span class="info-label">Contact Name</span></div>
                        <div class="info-value"><?= html_escape($user['emergency_contact_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-device-landline-phone me-2"></i><span class="info-label">Contact Phone</span></div>
                        <div class="info-value"><?= html_escape($user['emergency_contact_phone'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-circles-relation me-2"></i><span class="info-label">Contact Relation</span></div>
                        <div class="info-value"><?= html_escape($user['emergency_contact_relationship'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-circles-relation me-2"></i><span class="info-label">Blood Group</span></div>
                        <div class="info-value"><?= html_escape($user['blood_group'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-circles-relation me-2"></i><span class="info-label">Father's Name</span></div>
                        <div class="info-value"><?= html_escape($user['father_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-circles-relation me-2"></i><span class="info-label">Mother's Name</span></div>
                        <div class="info-value"><?= html_escape($user['mother_name'] ?? '-') ?></div>
                    </div>                    

                </div>
            </div>
            
            <!-- Activity Details -->
            <div class="section-card">
                <div class="card-header section-title bg-primary">
                    <i class="ti ti-activity"></i> Activities
                </div>
                <div class="card-body pt-2 pb-2">
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-shield-check me-2"></i><span class="info-label">Status</span></div>
                        <div class="info-value">
                            <span class="pill <?= $user['is_active'] ? 'pill-success' : 'pill-danger' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                    </div>
                    <!-- Last Login -->
                    <div class="info-row">
                        <div class="info-icon">
                            <i class="ti ti-clock-2 me-2"></i>
                            <span class="info-label">Last Login</span>
                        </div>
                        <div class="info-value">
                            <?php if (!empty($user['last_login_at'])): ?>
                                <?= date('M d, Y h:i A', strtotime($user['last_login_at'])) ?> 
                                <span class="text-muted small ms-2">
                                    <i class="ti ti-dots-vertical mx-1"></i> <?= time_ago($user['last_login_at']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Never Logged In</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Created On -->
                    <div class="info-row">
                        <div class="info-icon">
                            <i class="ti ti-calendar-plus me-2"></i>
                            <span class="info-label">Created On</span>
                        </div>
                        <div class="info-value">
                            <?php if (!empty($user['created_at'])): ?>
                                <?= date('M d, Y h:i A', strtotime($user['created_at'])) ?>
                                <span class="text-muted small ms-2">
                                    <i class="ti ti-dots-vertical mx-1"></i> <?= time_ago($user['created_at']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Not Available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Last Updated -->
                    <div class="info-row">
                        <div class="info-icon">
                            <i class="ti ti-calendar-stats me-2"></i>
                            <span class="info-label">Last Updated</span>
                        </div>
                        <div class="info-value">
                            <?php if (!empty($user['updated_at'])): ?>
                                <?= date('M d, Y h:i A', strtotime($user['updated_at'])) ?>
                                <span class="text-muted small ms-2">
                                    <i class="ti ti-dots-vertical mx-1"></i> <?= time_ago($user['updated_at']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Never Updated</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-brand-samsungpass me-2"></i><span class="info-label">Password Token</span></div>
                        <div class="info-value"><?= html_escape($user['password_token'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ti ti-receipt-refund me-2"></i><span class="info-label">Token Expiry</span></div>
                        <div class="info-value"><?= html_escape($user['token_expires_at'] ?? '-') ?></div>
                    </div>                    
                    
                </div>
            </div>
            
            <?php
            // Helper shims (inline). Move to a helper if you prefer.
            if (!function_exists('yn_text')) {
              function yn_text($val) {
                $v = strtolower(trim((string)$val));
                if ($v === '' || $v === '0' || $v === 'no' || $v === 'false' || $v === 'n') {
                  return 'No';
                }
                return 'Yes';
              }
            }
            if (!function_exists('date_or_dash')) {
              function date_or_dash($d, $fmt = 'l, d F Y') {
                return (!empty($d) && $d !== '0000-00-00' && $d !== '0000-00-00 00:00:00')
                  ? date($fmt, strtotime($d))
                  : '—';
              }
            }
            if (!function_exists('exit_status_text')) {
              function exit_status_text($status) {
                return $status ? ucfirst(trim($status)) : '—';
              }
            }
            
            $hasExit = !empty($exit['exit_type'])
                    || !empty($exit['exit_status'])
                    || !empty($exit['exit_date'])
                    || !empty($exit['last_working_date'])
                    || isset($exit['final_settlement_amount'])
                    || !empty($exit['reason'])
                    || !empty($exit['checklist_completed'])
                    || !empty($exit['assets_returned'])
                    || !empty($exit['nda_signed'])
                    || !empty($exit['exit_interview_date'])
                    || !empty($exit['exit_interview_conducted_by'])
                    || !empty($exit['notice_period_served']);
            ?>
            
            <?php if (!empty($hasExit)): ?>
              <div class="section-card">
                <div class="card-header section-title <?= $user['is_active'] ? 'bg-warning text-dark' : 'bg-danger' ?>">
                  <i class="ti ti-logout"></i>
                  Exit Information <?= $user['is_active'] ? '(Pending)' : '' ?>
                </div>
                  <?php if ($user['is_active']): ?>
                    <div class="alert alert-warning d-flex align-items-center py-2 mb-3">
                      <i class="ti ti-alert-triangle me-2"></i>
                      <div class="small">
                        This employee has an exit record but the account is <strong>still active</strong>.
                        Consider deactivating this user's account on his last working day.
                      </div>
                    </div>
                  <?php endif; ?>
                <div class="card-body pt-2 pb-2">
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-door-exit me-2"></i><span class="info-label">Exit Type</span></div>
                    <div class="info-value"><?= html_escape($exit['exit_type'] ?? '—') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-message-2 me-2"></i><span class="info-label">Reason</span></div>
                    <div class="info-value"><?= html_escape($exit['reason'] ?? '—') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-hourglass-empty me-2"></i><span class="info-label">Notice Period</span></div>
                    <div class="info-value"><?= yn_text($exit['notice_period_served'] ?? '') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-clipboard-check me-2"></i><span class="info-label">Exit Checklist</span></div>
                    <div class="info-value"><?= yn_text($exit['checklist_completed'] ?? '') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-device-laptop me-2"></i><span class="info-label">Assets Returned</span></div>
                    <div class="info-value"><?= yn_text($exit['assets_returned'] ?? '') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-signature me-2"></i><span class="info-label">NDA Signed</span></div>
                    <div class="info-value"><?= yn_text($exit['nda_signed'] ?? '') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-user-search me-2"></i><span class="info-label">Exit Interview</span></div>
                    <div class="info-value">
                      <?= date_or_dash($exit['exit_interview_date'] ?? null, 'd M Y') ?>
                        <?php
                        if (!function_exists('user_full_name_by_id')) {
                          function user_full_name_by_id($uid) {
                            static $cache = [];
                            $uid = (int)$uid;
                            if ($uid <= 0) return '';
                            if (isset($cache[$uid])) return $cache[$uid];
                        
                            $CI =& get_instance();
                            $row = $CI->db->select("CONCAT(firstname,' ',lastname) AS name", false)
                                          ->where('id', $uid)
                                          ->get('users')->row_array();
                            $cache[$uid] = $row['name'] ?? '';
                            return $cache[$uid];
                          }
                        }
                        ?>
                        
                        <?php if (!empty($exit['exit_interview_conducted_by'])): ?>
                          <span class="text-muted small">
                            | By: <?= html_escape(user_full_name_by_id($exit['exit_interview_conducted_by'])) ?>
                          </span>
                        <?php endif; ?>
                    </div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-flag me-2"></i><span class="info-label">Exit Status</span></div>
                    <div class="info-value"><?= exit_status_text($exit['exit_status'] ?? '') ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-calendar-event me-2"></i><span class="info-label">Exit Date</span></div>
                    <div class="info-value"><?= date_or_dash($exit['exit_date'] ?? null) ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-calendar-off me-2"></i><span class="info-label">Last Working Day</span></div>
                    <div class="info-value"><?= date_or_dash($exit['last_working_date'] ?? null) ?></div>
                  </div>
            
                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-currency-dollar me-2"></i><span class="info-label">Final Settlement</span></div>
                    <div class="info-value">
                      <?php if (isset($exit['final_settlement_amount']) && $exit['final_settlement_amount'] !== ''): ?>
                        <?= html_escape(c_format($user['final_settlement_amount'] ?? 0)) ?>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="ti ti-calendar-stats me-2"></i><span class="info-label">Settlement Date</span></div>
                    <div class="info-value"><?= date_or_dash($exit['final_settlement_date'] ?? null) ?></div>
                  </div>
                  
                  <?php if (!empty($exit['remarks'])): ?>
                    <div class="info-row" style="align-items:flex-start">
                      <div class="info-icon"><i class="ti ti-note me-2"></i><span class="info-label">HR Remarks</span></div>
                      <div class="info-value"><?= nl2br(html_escape($exit['remarks'])) ?></div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
        </div>

        <!-- Right: Tabs Profile -->
        <div class="col-md-7">
            <!-- Tabbed Main Info -->
            <div class="card">
              <div class="card-body">
                <ul class="nav nav-tabs tab-primary bg-primary p-1 mb-2 small" id="profileTabs" role="tablist" style="--bs-nav-link-padding-y: 0.25rem; --bs-nav-link-padding-x: 0.5rem; font-size: 0.85rem;">

                  <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-2" id="teams-tab" data-bs-toggle="tab"
                     data-bs-target="#teams" type="button" role="tab" aria-controls="teams"
                     aria-selected="false"><i class="ti ti-users me-1"></i>Team</button>
                  </li>

                  <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2" id="attendance-tab" data-bs-toggle="tab"
                     data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance"
                     aria-selected="false"><i class="ti ti-clock me-1"></i>Attendance</button>
                  </li>
                  
                  <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2" id="documents-tab" data-bs-toggle="tab"
                     data-bs-target="#documents" type="button" role="tab" aria-controls="documents"
                     aria-selected="false"><i class="ti ti-file me-1"></i>Docs</button>
                  </li>

                  <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2" id="contract-tab" data-bs-toggle="tab"
                     data-bs-target="#contract" type="button" role="tab" aria-controls="contract"
                     aria-selected="false"><i class="ti ti-writing-sign me-1"></i>Contract</button>
                  </li>
                  
                  <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2" id="assets-tab" data-bs-toggle="tab"
                     data-bs-target="#assets" type="button" role="tab" aria-controls="assets"
                     aria-selected="false"><i class="ti ti-device-laptop me-1"></i>Assets</button>
                  </li>
                
                  <!-- Injected Tabs from Modules -->
                    <?php foreach (get_user_profile_tabs($user) as $tab): ?>
                      <li class="nav-item" role="presentation">
                        <?= $tab ?>
                      </li>
                    <?php endforeach; ?>
                
                  <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2" id="activity_log-tab" data-bs-toggle="tab"
                     data-bs-target="#activity_log" type="button" role="tab" aria-controls="activity_log"
                     aria-selected="false"><i class="ti ti-activity me-1"></i>Activity</button>
                  </li>
                  
                </ul>
                
                <div class="tab-content small" id="profileTabsContent">
                  
                  <!-- Employee Profile Tabs -->
                  <?php $CI =& get_instance(); ?>
                  <?php echo $CI->load->view('users/profile_tabs/reporting_team', [], true); ?>
                  <?php echo $CI->load->view('users/profile_tabs/attendance', [], true); ?>
                  <?php echo $CI->load->view('users/profile_tabs/documents', [], true); ?>
                  <?php echo $CI->load->view('users/profile_tabs/contract', [], true); ?>
                  <?php echo $CI->load->view('users/profile_tabs/assets', [], true); ?>
                  <?php echo $CI->load->view('users/profile_tabs/activity', [], true); ?>
            
                  
                <!-- 🔻 Module Tab Panes -->
                <?php foreach (get_user_profile_tab_contents($user) as $content): ?>
                  <?= $content ?>
                <?php endforeach; ?>
                </div>
              </div>
            </div>
        </div>
    </div>
</div>


<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('users/modals/exit_employeemodal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/edit_personal_modal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/edit_official_modal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/edit_salary_modal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/edit_emergency_modal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/edit_team_modal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/change_pass_modal', [], true); ?>
<?php echo $CI->load->view('users/modals/profile/edit_address_modal', [], true); ?>
<script>
/* ========= Salary mask toggle (delegated) ========= */
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.toggle-salary');
  if (!btn) return;

  const mask = btn.parentElement.querySelector('.salary-mask');
  if (!mask) return;

  const isMasked = (mask.textContent || '').indexOf('*') !== -1;
  if (isMasked) {
    mask.textContent = mask.getAttribute('data-actual') || '';
    const icon = btn.querySelector('i');
    if (icon) { icon.classList.remove('ti-eye'); icon.classList.add('ti-eye-off'); }
    btn.setAttribute('title', 'Hide Salary');
  } else {
    mask.textContent = '****';
    const icon = btn.querySelector('i');
    if (icon) { icon.classList.remove('ti-eye-off'); icon.classList.add('ti-eye'); }
    btn.setAttribute('title', 'Show Salary');
  }
});

/* ========= Exit Employee launcher (Bootstrap 5) ========= */
function exitEmployee(user_id, full_name, title) {
  // Reset all fields
  const idsToClear = [
    'exit_id','exit_type','exit_date','last_working_date','exit_reason','exit_remarks',
    'exit_interview_date','exit_interview_conducted_by','exit_interview_notes',
    'final_settlement_amount','final_settlement_date','severance_package_details',
    'reports_to','department_id'
  ];
  document.getElementById('exit_user_id')?.setAttribute('value', String(user_id));
  idsToClear.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

  const statusEl = document.getElementById('exit_status'); if (statusEl) statusEl.value = 'Pending';

  ['checklist_completed','assets_returned','nda_signed','notice_period_served'].forEach(id => {
    const cb = document.getElementById(id);
    if (cb) cb.checked = false;
  });

  // Safer text injection
  const labelEl = document.getElementById('exitEmployeeName');
  if (labelEl) {
    labelEl.textContent = full_name || '';
    // Optional: show title next to name if you want
    labelEl.textContent = [full_name || '', title || ''].filter(Boolean).join(' — ');
  }

  // Show modal with BS5 API
  const modalEl = document.getElementById('exitEmployeeModal');
  if (!modalEl) return;
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false });
  modal.show();

  // Optional: focus the first field
  setTimeout(() => { document.getElementById('exit_type')?.focus(); }, 150);
}

// Expose to inline onclick
window.exitEmployee = exitEmployee;
</script>

<script>
// Load dropdown data when modals open
document.addEventListener('DOMContentLoaded', function() {
    const modals = ['editOfficialModal', 'editSalaryModal', 'editTeamModal'];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                // You can add AJAX calls here if needed to refresh dropdown data
                console.log(`Loading data for ${modalId}`);
            });
        }
    });
});
</script>
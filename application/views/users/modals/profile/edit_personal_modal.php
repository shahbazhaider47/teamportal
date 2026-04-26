<link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/form_input_mask.css')?>">

<div class="modal fade" id="editPersonalModal" tabindex="-1" aria-labelledby="editPersonalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="editPersonalModalLabel">Edit Personal Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= site_url('profile_editor/edit_personal/' . $user['id']) ?>" method="POST" enctype="multipart/form-data" class="app-form">
                <div class="modal-body p-4">
                    <div class="row">

                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image <small class="text-muted">Current image: <?= html_escape($user['profile_image']) ?></small></label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        <?php if (!empty($user['profile_image'])): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                                <label class="form-check-label" for="remove_photo">Remove current profile image</label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="app-divider-v dotted mb-3"></div> 
                    
                        <div class="col-md-3">
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
                                
                                <input type="text" class="form-control btn-light-secondary numeric-only" id="emp_id" name="emp_id" value="<?= html_escape($user['emp_id'] ?? '') ?>" readonly maxlength="4" minlength="4"
                                       inputmode="numeric" pattern="[0-9]{4}" placeholder="1___"
                                       aria-describedby="empIdHelp">
                            </div>
                        </div>

                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="username" class="form-label">
                              Username <span class="text-danger">*</span>
                            </label>
                              <input
                                type="text"
                                class="form-control btn-light-secondary"
                                id="username"
                                name="username"
                                value="<?= html_escape($user['username'] ?? '') ?>"
                                readonly>
                          </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="user_role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="user_role" name="user_role" required>
                                    <option value="">Select Role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_name'] ?>" 
                                            <?= ($user['user_role'] ?? '') == $role['role_name'] ? 'selected' : '' ?>>
                                            <?= html_escape(ucfirst($role['role_name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>                        

                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                        
                            <select class="form-select capital" id="gender" name="gender" required>
                              <option value="">Select Gender</option>
                        
                              <?php
                                $genders = ['male', 'female', 'other', 'unknown'];
                                $current = $user['gender'] ?? '';
                              ?>
                        
                              <?php foreach ($genders as $gender): ?>
                                <option value="<?= e($gender); ?>" class="capital"
                                  <?= ($current === $gender) ? 'selected' : ''; ?>>
                                  <?= e($gender); ?>
                                </option>
                              <?php endforeach; ?>
                        
                            </select>
                          </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="firstname" name="firstname" 
                                       value="<?= html_escape($user['firstname'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="initials" class="form-label">Initials/Middle</label>
                                <input type="text" class="form-control" id="initials" name="initials" 
                                       value="<?= html_escape($user['initials'] ?? '') ?>">
                            </div>
                        </div>                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lastname" name="lastname" 
                                       value="<?= html_escape($user['lastname'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" 
                                   value="<?= html_escape($user['fullname'] ?? '') ?>" 
                                   placeholder="Enter full name here">
                        </div> 
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
                                  <?= (int) $minHiringAge ?> yrs
                                </strong>
                              </small>
                            <?php endif; ?>
                          </div>
                        
                          <input type="date"
                                 class="form-control basic-date"
                                 name="emp_dob"
                                 id="emp_dob"
                                 value="<?= html_escape($user['emp_dob'] ?? '') ?>"
                                 required
                                 placeholder="YYYY-MM-DD">
                        </div>
                
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= html_escape($user['email'] ?? '') ?>" required>
                            </div>
                        </div>
<div class="col-md-3">
  <label class="form-label">
    Personal Phone <span class="text-danger">*</span>
  </label>

  <div class="input-group">
    <select class="form-select phone-country-code"
            data-phone-country="personal"
            style="max-width:90px;">
      <option value="" disabled selected>Code</option>
      <option value="PK" <?= (($user['phone_country'] ?? '') === 'PK') ? 'selected' : '' ?>>+92</option>
      <option value="US" <?= (($user['phone_country'] ?? '') === 'US') ? 'selected' : '' ?>>+1</option>
    </select>

    <input type="text"
           class="form-control phone-input"
           name="emp_phone"
           data-phone-for="personal"
           required
           value="<?= html_escape($user['emp_phone'] ?? '') ?>"
           placeholder="Select country first">
  </div>
</div>


                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                        
                            <select class="form-select capital" id="marital_status" name="marital_status" required>
                              <option value="">Select Marital Status</option>
                        
                              <?php
                                $mStatuses = ['single', 'married', 'divorced', 'widowed', 'other'];
                                $current = $user['marital_status'] ?? '';
                              ?>
                        
                              <?php foreach ($mStatuses as $mStatus): ?>
                                <option value="<?= e($mStatus); ?>" class="capital"
                                  <?= ($current === $mStatus) ? 'selected' : ''; ?>>
                                  <?= e($mStatus); ?>
                                </option>
                              <?php endforeach; ?>
                        
                            </select>
                          </div>
                        </div>
                        
<div class="col-md-3">
  <div class="mb-3">
    <label class="form-label">
      Nationality <span class="text-danger">*</span>
    </label>

    <select name="nationality"
            class="form-select"
            required>

      <option value="" disabled>
        — Select Nationality —
      </option>

      <?php foreach (nationality_list() as $code => $row): ?>
        <option value="<?= html_escape($row['name']) ?>"
          <?= (($user['nationality'] ?? '') === $row['name']) ? 'selected' : '' ?>>
          <?= html_escape($row['name']) ?>
        </option>
      <?php endforeach; ?>

    </select>
  </div>
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
                                   value="<?= html_escape($user['national_id'] ?? '') ?>"
                                   data-mask="cnic"
                                   <?= $cnicRequired ? 'required' : '' ?>>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                              <label class="form-label">National ID Expiry</label>
                              <input type="date" class="form-control basic-date" name="nic_expiry" id="nic_expiry" 
                              value="<?= html_escape($user['nic_expiry'] ?? '') ?>"
                              placeholder="YYYY-MM-DD">
                            </div>
                        </div> 
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="passport_no" class="form-label">Passport No</label>
                                <input type="text" class="form-control" id="passport_no" name="passport_no" 
                                       value="<?= html_escape($user['passport_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="religion" class="form-label">Religion <span class="text-danger">*</span></label>
                        
                            <select class="form-select capital" id="religion" name="religion" required>
                              <option value="">Select Religion</option>
                        
                              <?php
                                $religions = ['islam', 'christianity', 'hinduism', 'buddhism', 'sikhism', 'judaism', 'other'];
                                $current = $user['religion'] ?? '';
                              ?>
                        
                              <?php foreach ($religions as $religion): ?>
                                <option value="<?= e($religion); ?>" class="capital"
                                  <?= ($current === $religion) ? 'selected' : ''; ?>>
                                  <?= e($religion); ?>
                                </option>
                              <?php endforeach; ?>
                        
                            </select>
                          </div>
                        </div>

                    <?php
                    $currentQualification = trim((string)($user['qualification'] ?? ''));
                    ?>
                    
                    <div class="col-md-3">
                        <label for="qualification" class="form-label">
                            Qualification <span class="text-danger">*</span>
                        </label>
                    
                        <select class="form-select"
                                name="qualification"
                                id="qualification"
                                required>
                    
                            <option value="" <?= $currentQualification === '' ? 'selected' : '' ?>>
                                Select Qualification
                            </option>
                    
                            <?php foreach ($qualifications_list as $qua): ?>
                                <?php
                                    $qua = trim((string)$qua);
                                    $selected = ($currentQualification === $qua) ? 'selected' : '';
                                ?>
                                <option value="<?= e($qua) ?>" <?= $selected ?>>
                                    <?= e($qua) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    
                        <?php if (empty($qualifications_list)): ?>
                            <div class="form-text text-muted">
                                No qualifications configured in System Options.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-12">
                      <label class="form-label">Notes</label>
                      <textarea class="form-control"
                                name="notes"
                                rows="3"><?= e($user['notes'] ?? '') ?></textarea>
                    </div>
                    
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    var base_url = '<?= base_url() ?>';
    var minHiringAge = <?= (int) $minHiringAge ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?= base_url('assets/js/form_input_mask.js'); ?>"></script>
<script src="<?= base_url('assets/js/add_user.js'); ?>"></script>
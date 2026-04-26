<div class="modal fade" id="editOfficialModal" tabindex="-1" aria-labelledby="editOfficialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="editOfficialModalLabel">Edit Official Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= site_url('profile_editor/edit_official/' . $user['id']) ?>" method="POST" class="app-form">
                <div class="modal-body p-4">
                    <div class="row">

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="emp_department" class="form-label">Department</label>
                                <select class="form-select" id="emp_department" name="emp_department">
                                    <option value="">Select Department</option>
                                    <?php foreach ($emp_department as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" 
                                            <?= ($user['emp_department'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                            <?= html_escape($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="emp_title" class="form-label">Designation</label>
                                <select class="form-select" id="emp_title" name="emp_title">
                                    <option value="">Select Designation</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?= $position['id'] ?>" 
                                            <?= ($user['emp_title'] ?? '') === $position['title'] ? 'selected' : '' ?>>
                                            <?= html_escape($position['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>                        
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="employment_type" class="form-label">Employment Type</label>
                            <select class="form-select" id="employment_type" name="employment_type">
                              <option value="">Select Type</option>
                              <?php foreach ($employment_types as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= (($user['employment_type'] ?? '') === $opt) ? 'selected' : '' ?>>
                                  <?= e($opt) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                            <?php if (empty($employment_types)): ?>
                              <div class="form-text text-muted">No Employment Types configured in System Options.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="contract_type" class="form-label">Contract Type</label>
                            <select class="form-select" id="contract_type" name="contract_type">
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
                          <div class="mb-3">
                            <label for="work_shift" class="form-label">Working Shift</label>
                        
                            <select class="form-select" name="work_shift" id="work_shift">
                              <option value="">Assign Work Shift</option>
                        
                              <?php foreach (get_company_shifts(['format' => 'dropdown']) as $id => $name): ?>
                                <option value="<?= (int)$id ?>"
                                  <?= ((int)($user['work_shift'] ?? 0) === (int)$id) ? 'selected' : '' ?>>
                                  <?= e($name) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="work_location" class="form-label">Work Location</label>
                            <select class="form-select" id="work_location" name="work_location">
                              <option value="">Select Work Location</option>
                              <?php foreach ($work_location_types as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= (($user['work_location'] ?? '') === $opt) ? 'selected' : '' ?>>
                                  <?= e($opt) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                            <?php if (empty($work_location_types)): ?>
                              <div class="form-text text-muted">No Work Location Types configured in System Options.</div>
                            <?php endif; ?>
                          </div>
                        </div>


                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="office_id" class="form-label">
                              Office Location <span class="text-danger">*</span>
                            </label>
                        
                            <select class="form-select" name="office_id" id="office_id" required>
                              <option value="">Select Office Location</option>
                        
                              <?php foreach (get_company_offices(['format' => 'dropdown']) as $id => $name): ?>
                                <option value="<?= (int)$id; ?>"
                                  <?= ((int)($user['office_id'] ?? 0) === (int)$id) ? 'selected' : ''; ?>>
                                  <?= e($name); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="emp_joining" class="form-label">Joining Date</label>
                                <input type="date" class="form-control" id="emp_joining" name="emp_joining" 
                                       value="<?= html_escape($user['emp_joining'] ?? '') ?>">
                            </div>
                        </div>                        

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="confirmation_date" class="form-label">Confirmation Date</label>
                                <input type="date" class="form-control" id="confirmation_date" name="confirmation_date" 
                                       value="<?= html_escape($user['confirmation_date'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="probation_end_date" class="form-label">Probation End Date</label>
                                <input type="date" class="form-control" id="probation_end_date" name="probation_end_date" 
                                       value="<?= html_escape($user['probation_end_date'] ?? '') ?>">
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="editStatusModalLabel">Edit Employee Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= site_url('profile_editor/change_status/' . $user['id']) ?>" method="POST" enctype="multipart/form-data" class="app-form">
                <div class="modal-body p-4">
                    <div class="row">


                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="address" class="form-label">Permanent Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?= html_escape($user['address'] ?? '') ?>" placeholder="Street address">
                            </div>
                        </div>
                    
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label class="form-label">
                              Country <span class="text-danger">*</span>
                            </label>
                        
                            <select name="country"
                                    class="form-select"
                                    required>
                        
                              <option value="" disabled>
                                — Select Country —
                              </option>
                        
                              <?php foreach (top_countries_list() as $code => $row): ?>
                                <option value="<?= html_escape($row['name']) ?>"
                                  <?= (($user['country'] ?? '') === $row['name']) ? 'selected' : '' ?>>
                                  <?= html_escape($row['name']) ?>
                                </option>
                              <?php endforeach; ?>
                        
                            </select>
                          </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?= html_escape($user['state'] ?? '') ?>" 
                                       placeholder="State or province">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?= html_escape($user['city'] ?? '') ?>" 
                                       placeholder="City name">
                            </div>
                        </div>
                        
                    </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="current_address" class="form-label">Current Address Complete</label>
                                <input type="text" class="form-control" id="current_address" name="current_address" 
                                       value="<?= html_escape($user['current_address'] ?? '') ?>" placeholder="Enter complete current addrrss: home, street, town">
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

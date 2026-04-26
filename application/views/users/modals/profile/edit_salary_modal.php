<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="editSalaryModal" tabindex="-1" aria-labelledby="editSalaryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Match other modals: primary header + white title -->
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="editSalaryModalLabel">Edit Salary Information</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="<?= site_url('profile_editor/edit_salary/' . $user['id']) ?>" method="POST" class="app-form">
        <div class="modal-body p-4">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="pay_period" class="form-label">Pay Period</label>
            
                <select class="form-select" id="pay_period" name="pay_period" required>
                  <option value="">Select Pay Period</option>
            
                  <?php
                    $periods = ['Daily', 'Weekly', 'Monthly'];
                    $current = $user['pay_period'] ?? '';
                  ?>
            
                  <?php foreach ($periods as $period): ?>
                    <option value="<?= e($period); ?>"
                      <?= ($current === $period) ? 'selected' : ''; ?>>
                      <?= e($period); ?>
                    </option>
                  <?php endforeach; ?>
            
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="last_increment_date" class="form-label">Last Increment Date</label>
                <input type="date" class="form-control" id="last_increment_date" name="last_increment_date"
                       value="<?= e($user['last_increment_date'] ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="joining_salary" class="form-label">
                  Joining Salary (<?= get_base_currency_symbol() ?>)
                </label>
                <input type="number" step="0.01" class="form-control" id="joining_salary" name="joining_salary"
                       value="<?= e($user['joining_salary'] ?? '') ?>" placeholder="0.00">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="current_salary" class="form-label">
                  Current Salary (<?= get_base_currency_symbol() ?>)
                </label>
                <input type="number" step="0.01" class="form-control" id="current_salary" name="current_salary"
                       value="<?= e($user['current_salary'] ?? '') ?>" placeholder="0.00">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Allowances</label>
            <div class="row">
              <?php 
                $user_allowances = json_decode($user['allowances'] ?? '[]', true) ?: [];
                if (!is_array($user_allowances)) $user_allowances = [];
              ?>
              <?php foreach ($allowances as $allowance): ?>
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="allowance_ids[]"
                           value="<?= e($allowance['id']) ?>"
                           id="allowance_<?= e($allowance['id']) ?>"
                           <?= in_array($allowance['id'], $user_allowances) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="allowance_<?= e($allowance['id']) ?>">
                      <?= e($allowance['title']) ?>
                      <?php if (!empty($allowance['amount'])): ?>
                        <small class="text-muted">
                          (<?= $allowance['is_percentage'] ? e($allowance['amount']).'%' : get_base_currency_symbol() . number_format((float)$allowance['amount'], 2) ?>)
                        </small>
                      <?php endif; ?>
                    </label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (empty($allowances)): ?>
              <div class="text-muted small">No allowances available.
                <a href="<?= site_url('users/allowances') ?>">Create allowances first</a>.
              </div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="tax_number" class="form-label">Tax Number</label>
                <input type="text" class="form-control" id="tax_number" name="tax_number"
                       value="<?= e($user['tax_number'] ?? '') ?>" placeholder="Tax identification number">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="insurance_policy_no" class="form-label">Insurance Policy No</label>
                <input type="text" class="form-control" id="insurance_policy_no" name="insurance_policy_no"
                       value="<?= e($user['insurance_policy_no'] ?? '') ?>" placeholder="Insurance policy number">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input type="text" class="form-control" id="bank_name" name="bank_name"
                       value="<?= e($user['bank_name'] ?? '') ?>" placeholder="Bank name">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="bank_branch" class="form-label">Bank Branch</label>
                <input type="text" class="form-control" id="bank_branch" name="bank_branch"
                       value="<?= e($user['bank_branch'] ?? '') ?>" placeholder="Branch name">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="bank_account_number" class="form-label">Account Number</label>
                <input type="text" class="form-control" id="bank_account_number" name="bank_account_number"
                       value="<?= e($user['bank_account_number'] ?? '') ?>" placeholder="Account number">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="bank_code" class="form-label">Bank Code</label>
                <input type="text" class="form-control" id="bank_code" name="bank_code"
                       value="<?= e($user['bank_code'] ?? '') ?>" placeholder="Bank code/Sort code">
              </div>
            </div>
          </div>
        </div>

        <!-- Match other modals: small buttons -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

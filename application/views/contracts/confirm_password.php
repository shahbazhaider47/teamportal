<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm mt-5">
                <div class="card-body">
                    <h5 class="mb-2">
                        <i class="ti ti-lock me-1"></i> Confirm Your Password
                    </h5>

                    <p class="small text-muted">
                        For security reasons, please re-enter your account password
                        to view this contract.
                    </p>

                    <form method="post"
                          action="<?= site_url('contracts/verify_contract_password'); ?>"
                          class="app-form">

                        <input type="hidden" name="contract_id" value="<?= (int)$contract_id; ?>">

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required
                                   autofocus>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= site_url('users/profile'); ?>"
                               class="btn btn-light-primary btn-sm">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="btn btn-primary btn-sm">
                                <i class="ti ti-check me-1"></i> Confirm
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

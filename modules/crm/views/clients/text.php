<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <i class="ti ti-building"></i>
      <h1 class="h6 header-title"><?= html_escape($client['practice_legal_name'] ?? '—'); ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= site_url('crm/clients'); ?>" class="btn btn-light-primary btn-header">
        <i class="ti ti-arrow-left"></i> Back to Clients
      </a>

      <div class="btn-divider"></div>
    </div>
  </div>

  <div class="row g-3">

    <div class="col-md-4">

      <div class="card">
        <div class="card-header bg-light-primary py-2">
          <h6 class="h6 header-title text-primary mb-0">
            <i class="ti ti-building me-2"></i>
            Client Details
          </h6>
        </div>

        <div class="card-body">
          <div>

            <h4 class="text-dark mb-2"><?= html_escape($client['practice_legal_name'] ?? '—'); ?></h4>
            <div class="app-divider-v dashed mb-2"></div>
            <div class="mb-2">
              <span class="fw-medium">Practice Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Practice Type</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_type'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Specialty</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['specialty'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Tax ID</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['tax_id'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">NPI Number</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['npi_number'] ?? '—'); ?></span>
            </div>

            <!-- Summary -->
            <div class="mb-2">
              <span class="fw-medium">Client Code</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['client_code'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Active</span>
              <span class="float-end badge <?= !empty($client['is_active']) ? 'bg-success' : 'bg-secondary'; ?>">
                <?= !empty($client['is_active']) ? 'Active' : 'Inactive'; ?>
              </span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Client Type</span>
              <span class="float-end badge <?= !empty($client['is_group']) ? 'bg-primary' : 'bg-light-primary'; ?>">
                <?= !empty($client['is_group']) ? 'Third Party' : 'Direct'; ?>
              </span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Client Status</span>
              <?php if (!empty($client['client_status'])): ?>
                <span class="float-end badge bg-info">
                  <?= html_escape(ucfirst($client['client_status'])); ?>
                </span>
              <?php else: ?>
                <span class="float-end f-s-13 text-secondary">—</span>
              <?php endif; ?>
            </div>
        
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header bg-light-primary py-2">
          <h6 class="h6 header-title text-primary mb-0">
            <i class="ti ti-user me-2"></i>
            Primary Contact
          </h6>
        </div>

        <div class="card-body">
          <div>

            <div class="mb-2">
              <span class="fw-medium">Contact Full Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_contact_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Contact Title</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_contact_title'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Contact Email</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_email'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Contact Phone</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_phone'] ?? '—'); ?></span>
            </div>
        
          </div>
        </div>
      </div>
      
    </div>

    <div class="col-md-4">

      <div class="card">
        <div class="card-header bg-light-primary py-2">
          <h6 class="h6 header-title text-primary mb-0">
            <i class="ti ti-building me-2"></i>
            Client Details
          </h6>
        </div>

        <div class="card-body">
          <div>

            <!-- Summary -->
            <div class="mb-2">
              <span class="fw-medium">Client Code</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['client_code'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Active</span>
              <span class="float-end badge <?= !empty($client['is_active']) ? 'bg-success' : 'bg-secondary'; ?>">
                <?= !empty($client['is_active']) ? 'Active' : 'Inactive'; ?>
              </span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Client Type</span>
              <span class="float-end badge <?= !empty($client['is_group']) ? 'bg-primary' : 'bg-light-primary'; ?>">
                <?= !empty($client['is_group']) ? 'Third Party' : 'Direct'; ?>
              </span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Client Status</span>
              <?php if (!empty($client['client_status'])): ?>
                <span class="float-end badge bg-info">
                  <?= html_escape(ucfirst($client['client_status'])); ?>
                </span>
              <?php else: ?>
                <span class="float-end f-s-13 text-secondary">—</span>
              <?php endif; ?>
            </div>

            <!-- Practice -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-building-community me-1"></i> Practice
            </div>

            <div class="mb-2">
              <span class="fw-medium">Practice Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Business Legal Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_legal_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Practice Type</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_type'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Specialty</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['specialty'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Tax ID</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['tax_id'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">NPI Number</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['npi_number'] ?? '—'); ?></span>
            </div>

            <!-- Address -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-map-pin me-1"></i> Address
            </div>

            <div class="mb-2">
              <span class="fw-medium">Address</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['address'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">City</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['city'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">State</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['state'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Zip Code</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['zip_code'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Country</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['country'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Time Zone</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['time_zone'] ?? '—'); ?></span>
            </div>

            <!-- Billing -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-receipt me-1"></i> Billing
            </div>

            <div class="mb-2">
              <span class="fw-medium">Billing Model</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['billing_model'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Rate Percent</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['rate_percent'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Rate Flat</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['rate_flat'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Rate Custom</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['rate_custom'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Invoice Frequency</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['invoice_frequency'] ?? '—'); ?></span>
            </div>

            <!-- Contract & Services -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-timeline-event-minus me-1"></i> Contract & Services
            </div>

            <div class="mb-2">
              <span class="fw-medium">Contract Start Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['contract_start_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Contract End Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['contract_end_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Services Included</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['services_included'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Avg Monthly Claims</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['avg_monthly_claims'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Expected Monthly Collections</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['expected_monthly_collections'] ?? '—'); ?></span>
            </div>

            <!-- Account & Dates -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-user-star me-1"></i> Account & Dates
            </div>

            <div class="mb-2">
              <span class="fw-medium">Account Manager</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['account_manager'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Onboarding Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['onboarding_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Offboarding Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['offboarding_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Termination Reason</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['termination_reason'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Internal Notes</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['internal_notes'] ?? '—'); ?></span>
            </div>

            <!-- System -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-settings me-1"></i> System
            </div>

            <div class="mb-2">
              <span class="fw-medium">ID</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['id'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Created By</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['created_by'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Updated By</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['updated_by'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Created At</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['created_at'] ?? '—'); ?></span>
            </div>

            <div>
              <span class="fw-medium">Updated At</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['updated_at'] ?? '—'); ?></span>
            </div>

          </div>
        </div>
      </div>

    </div>

    <div class="col-md-4">

      <div class="card">
        <div class="card-header bg-light-primary py-2">
          <h6 class="h6 header-title text-primary mb-0">
            <i class="ti ti-building me-2"></i>
            Client Details
          </h6>
        </div>

        <div class="card-body">
          <div>

            <!-- Summary -->
            <div class="mb-2">
              <span class="fw-medium">Client Code</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['client_code'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Active</span>
              <span class="float-end badge <?= !empty($client['is_active']) ? 'bg-success' : 'bg-secondary'; ?>">
                <?= !empty($client['is_active']) ? 'Active' : 'Inactive'; ?>
              </span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Client Type</span>
              <span class="float-end badge <?= !empty($client['is_group']) ? 'bg-primary' : 'bg-light-primary'; ?>">
                <?= !empty($client['is_group']) ? 'Third Party' : 'Direct'; ?>
              </span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Client Status</span>
              <?php if (!empty($client['client_status'])): ?>
                <span class="float-end badge bg-info">
                  <?= html_escape(ucfirst($client['client_status'])); ?>
                </span>
              <?php else: ?>
                <span class="float-end f-s-13 text-secondary">—</span>
              <?php endif; ?>
            </div>

            <!-- Practice -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-building-community me-1"></i> Practice
            </div>

            <div class="mb-2">
              <span class="fw-medium">Practice Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Business Legal Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_legal_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Practice Type</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['practice_type'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Specialty</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['specialty'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Tax ID</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['tax_id'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">NPI Number</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['npi_number'] ?? '—'); ?></span>
            </div>

            <!-- Primary Contact -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-user me-1"></i> Primary Contact
            </div>

            <div class="mb-2">
              <span class="fw-medium">Name</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_contact_name'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Title</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_contact_title'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Email</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_email'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Phone</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['primary_phone'] ?? '—'); ?></span>
            </div>

            <!-- Address -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-map-pin me-1"></i> Address
            </div>

            <div class="mb-2">
              <span class="fw-medium">Address</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['address'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">City</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['city'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">State</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['state'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Zip Code</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['zip_code'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Country</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['country'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Time Zone</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['time_zone'] ?? '—'); ?></span>
            </div>

            <!-- Billing -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-receipt me-1"></i> Billing
            </div>

            <div class="mb-2">
              <span class="fw-medium">Billing Model</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['billing_model'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Rate Percent</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['rate_percent'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Rate Flat</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['rate_flat'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Rate Custom</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['rate_custom'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Invoice Frequency</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['invoice_frequency'] ?? '—'); ?></span>
            </div>

            <!-- Contract & Services -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-timeline-event-minus me-1"></i> Contract & Services
            </div>

            <div class="mb-2">
              <span class="fw-medium">Contract Start Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['contract_start_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Contract End Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['contract_end_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Services Included</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['services_included'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Avg Monthly Claims</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['avg_monthly_claims'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Expected Monthly Collections</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['expected_monthly_collections'] ?? '—'); ?></span>
            </div>

            <!-- Account & Dates -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-user-star me-1"></i> Account & Dates
            </div>

            <div class="mb-2">
              <span class="fw-medium">Account Manager</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['account_manager'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Onboarding Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['onboarding_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Offboarding Date</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['offboarding_date'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Termination Reason</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['termination_reason'] ?? '—'); ?></span>
            </div>

            <div class="mb-3">
              <span class="fw-medium">Internal Notes</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['internal_notes'] ?? '—'); ?></span>
            </div>

            <!-- System -->
            <div class="pt-2 mb-2 text-muted fw-semibold">
              <i class="ti ti-settings me-1"></i> System
            </div>

            <div class="mb-2">
              <span class="fw-medium">ID</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['id'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Created By</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['created_by'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Updated By</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['updated_by'] ?? '—'); ?></span>
            </div>

            <div class="mb-2">
              <span class="fw-medium">Created At</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['created_at'] ?? '—'); ?></span>
            </div>

            <div>
              <span class="fw-medium">Updated At</span>
              <span class="float-end f-s-13 text-secondary"><?= html_escape($client['updated_at'] ?? '—'); ?></span>
            </div>

          </div>
        </div>
      </div>

    </div>
    
  </div>

</div>
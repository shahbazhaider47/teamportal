<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<style>
    
</style>
<div class="container-fluid">



  <!-- Page Header -->
  <div class="crm-page-header d-flex justify-content-between gap-3 flex-wrap">
    <div class="d-flex align-items-center gap-3">
      <div class="crm-page-icon">
        <i class="ti ti-building-hospital"></i>
      </div>
      <div>
        <div class="crm-page-title">CRM Clients</div>
        <div class="crm-page-sub">Manage practices, groups, contracts, and client lifecycle.</div>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button class="btn-add-new">
        <i class="ti ti-plus"></i>
        Add Client
      </button>

      <div class="dropdown">
        <button class="btn-add-new" type="button">
          <i class="ti ti-dots"></i>
          Actions
        </button>

        <div class="dropdown-menu is-open">
          <a href="javascript:void(0)" class="dropdown-item">
            <i class="ti ti-download"></i>
            Export Clients
          </a>
          <a href="javascript:void(0)" class="dropdown-item">
            <i class="ti ti-filter"></i>
            Advanced Filters
          </a>
          <div class="dropdown-divider"></div>
          <a href="javascript:void(0)" class="dropdown-item dropdown-item-danger">
            <i class="ti ti-trash"></i>
            Delete Selected
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Row -->
  <div class="row g-2 mb-3">
    <div class="col-md-6 col-lg-4 col-xl">
      <div class="crm-kpi-card">
        <div class="crm-kpi-icon" style="background:#dcfce7; color:#166534;">
          <i class="ti ti-user"></i>
        </div>
        <div>
          <div class="crm-kpi-value">12</div>
          <div class="crm-kpi-label">Direct Clients</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4 col-xl">
      <div class="crm-kpi-card">
        <div class="crm-kpi-icon" style="background:#dbeafe; color:#1d4ed8;">
          <i class="ti ti-users-group"></i>
        </div>
        <div>
          <div class="crm-kpi-value">24</div>
          <div class="crm-kpi-label">Group Clients</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4 col-xl">
      <div class="crm-kpi-card">
        <div class="crm-kpi-icon" style="background:#ecfeff; color:#0f766e;">
          <i class="ti ti-circle-check"></i>
        </div>
        <div>
          <div class="crm-kpi-value">30</div>
          <div class="crm-kpi-label">Total Active</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4 col-xl">
      <div class="crm-kpi-card">
        <div class="crm-kpi-icon" style="background:#fff7ed; color:#c2410c;">
          <i class="ti ti-user-off"></i>
        </div>
        <div>
          <div class="crm-kpi-value">6</div>
          <div class="crm-kpi-label">Total Inactive</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4 col-xl">
      <div class="crm-kpi-card">
        <div class="crm-kpi-icon" style="background:#fee2e2; color:#b91c1c;">
          <i class="ti ti-alert-triangle"></i>
        </div>
        <div>
          <div class="crm-kpi-value">2</div>
          <div class="crm-kpi-label">Terminated</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-4 col-xl">
      <div class="crm-kpi-card crm-kpi-warn">
        <div class="crm-kpi-icon" style="background:#fef3c7; color:#a16207;">
          <i class="ti ti-calendar-event"></i>
        </div>
        <div>
          <div class="crm-kpi-value">4</div>
          <div class="crm-kpi-label">Contract Expiring</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile / Tabs Section -->
  <div class="crm-profile-wrap mb-3">
    <ul class="crm-tab-nav">
      <li class="crm-tab-item">
        <button class="crm-tab-btn active" type="button" aria-selected="true">
          <i class="ti ti-list-details"></i>
          Overview
        </button>
      </li>
      <li class="crm-tab-item">
        <button class="crm-tab-btn" type="button">
          <i class="ti ti-file-text"></i>
          Contracts
          <span class="crm-tab-badge">3</span>
        </button>
      </li>
      <li class="crm-tab-item">
        <button class="crm-tab-btn" type="button">
          <i class="ti ti-receipt-2"></i>
          Billing
          <span class="crm-tab-badge">8</span>
        </button>
      </li>
      <li class="crm-tab-item">
        <button class="crm-tab-btn" type="button">
          <i class="ti ti-notes"></i>
          Notes
        </button>
      </li>
    </ul>

    <div class="crm-tab-content">

      <!-- Example Hero -->
      <div class="hero-inner mb-3">
        <div class="avatar">SM</div>

        <div class="hero-info">
          <h2 class="hero-name">
            Springfield Medical Group
            <span class="badge badge-active">
              <span class="badge-dot-green"></span>
              Active
            </span>
            <span class="badge badge-code">CL-10024</span>
            <span class="badge badge-type">Group</span>
          </h2>

          <div class="group-note">
            <i class="ti ti-users-group"></i>
            Parent Group of 6 linked practices
          </div>

          <div class="badge-row">
            <span class="badge badge-pill"><i class="ti ti-stethoscope"></i> Cardiology</span>
            <span class="badge badge-pill"><i class="ti ti-map-pin"></i> Houston, TX</span>
            <span class="badge badge-pill"><i class="ti ti-currency-dollar"></i> Percentage Model</span>
          </div>
        </div>
      </div>

      <!-- Kpi Strip -->
      <div class="kpi-strip mb-3">
        <div class="kpi">
          <div class="kpi-label">Expected Collections</div>
          <div class="kpi-value">$85,000</div>
          <div class="kpi-sub">Current monthly forecast</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Claims Volume</div>
          <div class="kpi-value">3,450</div>
          <div class="kpi-sub">Avg monthly claims</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Contract End</div>
          <div class="kpi-value kpi-value-warning">Sep 30, 2026</div>
          <div class="kpi-sub">Renews in 45 days</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Health Score</div>
          <div class="kpi-value kpi-value-success">88%</div>
          <div class="kpi-sub">Client retention strength</div>
        </div>
      </div>

      <!-- Table Example -->
      <div class="card-body crm-table bg-white p-0">
        <div class="table-responsive">
          <table class="crm-table-light small table-bottom-border" id="crmClientsTable">
            <thead class="crm-bg-light">
              <tr>
                <th>Client Code</th>
                <th>Client Type</th>
                <th>Practice Name</th>
                <th>Account Manager</th>
                <th>City / State</th>
                <th>Billing Model</th>
                <th>Contract Duration</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody class="crm-tb-small">
              <tr>
                <td>
                  <span class="badge badge-code">CL-10024</span>
                </td>

                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-light-primary text-primary">Group</span>
                    <div class="text-muted" style="font-size:11px;">
                      <i class="ti ti-users text-primary"></i> Springfield Parent Group
                    </div>
                  </div>
                </td>

                <td class="small">
                  <div class="fw-semibold">
                    <a href="#" class="text-primary text-decoration-none" target="_blank" rel="noopener">
                      Springfield Medical Group
                      <i class="ti ti-external-link" style="font-size:11px;"></i>
                    </a>
                  </div>
                  <div class="x-small text-muted">
                    <i class="ti ti-stethoscope text-primary" style="font-size:11px;"></i>
                    Cardiology
                  </div>
                </td>

                <td class="small">
                  <div class="d-flex align-items-center gap-2">
                    <div class="crm-user-avatar">
                      <i class="ti ti-user"></i>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark">John Smith</div>
                      <span class="x-small text-muted">Account Director</span>
                    </div>
                  </div>
                </td>

                <td>Houston, TX</td>
                <td>Percentage</td>
                <td>Jan 01, 2026 - Sep 30, 2026</td>
                <td><span class="badge badge-active">Active</span></td>
              </tr>

              <tr>
                <td>
                  <span class="badge badge-code">CL-10025</span>
                </td>

                <td>
                  <span class="badge bg-primary text-white">Direct Client</span>
                </td>

                <td class="small">
                  <div class="fw-semibold">
                    <a href="#" class="text-primary text-decoration-none" target="_blank" rel="noopener">
                      Alpha Family Practice
                      <i class="ti ti-external-link" style="font-size:11px;"></i>
                    </a>
                  </div>
                  <div class="x-small text-muted">
                    <i class="ti ti-stethoscope text-primary" style="font-size:11px;"></i>
                    Family Medicine
                  </div>
                </td>

                <td class="small">
                  <div class="d-flex align-items-center gap-2">
                    <div class="crm-user-avatar">
                      <i class="ti ti-user"></i>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark">Sarah Khan</div>
                      <span class="x-small text-muted">Client Success Manager</span>
                    </div>
                  </div>
                </td>

                <td>Dallas, TX</td>
                <td>Flat Fee</td>
                <td>Mar 01, 2026 - Mar 01, 2027</td>
                <td><span class="badge badge-inactive">Inactive</span></td>
              </tr>

              <tr>
                <td>
                  <span class="badge badge-code">CL-10026</span>
                </td>

                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-light-primary text-primary">Group</span>
                    <div class="text-muted" style="font-size:11px;">
                      <i class="ti ti-users text-primary"></i> Nova Health Network
                    </div>
                  </div>
                </td>

                <td class="small">
                  <div class="fw-semibold">
                    <a href="#" class="text-primary text-decoration-none" target="_blank" rel="noopener">
                      Nova Heart & Vascular
                      <i class="ti ti-external-link" style="font-size:11px;"></i>
                    </a>
                  </div>
                  <div class="x-small text-muted">
                    <i class="ti ti-stethoscope text-primary" style="font-size:11px;"></i>
                    Vascular Surgery
                  </div>
                </td>

                <td class="small">
                  <div class="d-flex align-items-center gap-2">
                    <div class="crm-user-avatar">
                      <i class="ti ti-user"></i>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark">Michael Lee</div>
                      <span class="x-small text-muted">Senior Account Manager</span>
                    </div>
                  </div>
                </td>

                <td>Austin, TX</td>
                <td>Custom</td>
                <td>Apr 15, 2026 - Oct 15, 2026</td>
                <td><span class="prp-badge contract-status-pending">Expiring Soon</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>


</div>
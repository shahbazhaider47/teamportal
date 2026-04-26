        <div class="dropdown">
          <button class="btn btn-header btn-primary dropdown-toggle" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-menu-2"></i>Menu
          </button>
        
          <ul class="dropdown-menu p-2">
        
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance') ?>">
                <i class="ti ti-clock me-2 text-primary"></i> Daily Attendance
              </a>
            </li>
        
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/logs') ?>">
                <i class="ti ti-activity me-2 text-primary"></i> Logs (All Staff)
              </a>
            </li>
            
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/user_logs') ?>">
                <i class="ti ti-users me-2 text-primary"></i> User Logs (Selector)
              </a>
            </li>
        
            <div class="app-divider-v dashed"></div>
        
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/manage_leaves') ?>">
                <i class="ti ti-clipboard-list me-2 text-primary"></i> Manage Leaves
              </a>
            </li>
            
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/all_leaves_calendar') ?>">
                <i class="ti ti-calendar-event me-2 text-primary"></i> Calendar
              </a>
            </li>

            <div class="app-divider-v dashed"></div>
        
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/biometric') ?>">
                <i class="ti ti-fingerprint me-2 text-primary"></i> Biometric
              </a>
            </li>
            
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/setup_biometric') ?>">
                <i class="ti ti-settings-automation me-2 text-primary"></i> Biometric Setup
              </a>
            </li>
            
          </ul>
        </div>
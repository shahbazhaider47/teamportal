        <div class="dropdown">
          <button class="btn btn-header btn-primary dropdown-toggle" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-menu-2"></i> Menu
          </button>
        
          <ul class="dropdown-menu p-2">
        
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance') ?>">
                <i class="ti ti-clock me-2 text-primary"></i> Daily Attendance
              </a>
            </li>
            
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/my_attendance') ?>">
                <i class="ti ti-clock me-2 text-primary"></i> My Attendance
              </a>
            </li>

            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/annual_attendance') ?>">
                <i class="ti ti-calendar me-2 text-primary"></i> Annual Attendance
              </a>
            </li>
            
            <div class="app-divider-v dashed"></div>
        
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/my_leaves') ?>">
                <i class="ti ti-clipboard-list me-2 text-primary"></i> My Leaves
              </a>
            </li>
            
            <li class="small">
              <a class="dropdown-item small" href="<?= site_url('attendance/calendar') ?>">
                <i class="ti ti-calendar-event me-2 text-primary"></i> Calendar
              </a>
            </li>
        
          </ul>
        </div>
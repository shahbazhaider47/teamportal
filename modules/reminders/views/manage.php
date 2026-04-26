<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex small align-items-center gap-1">
            <span class="badge bg-light-primary border">Total: <?= count($reminders) ?></span>
            <span class="badge bg-light-success border">Today: <?= $today_count ?></span>
            <span class="badge text-light-secondary">Upcoming: <?= $upcoming_count ?></span>
            <span class="badge text-light-danger">Past: <?= $past_count ?></span>
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canViewGlobal= staff_can('view_global', 'reminders');
          $canCreate    = staff_can('create', 'reminders');
          $canEdit      = staff_can('edit', 'reminders');
          $canDelete    = staff_can('delete', 'reminders');
          $canPrint     = staff_can('print', 'general');
          $canExport    = staff_can('export', 'general');
        ?>
        <div class="btn-divider"></div>

        <button type="button"
                id="btn-add-reminder"
                class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#add_reminder_modal"' : 'disabled' ?>
                title="Add New Reminder">
          <i class="fa fa-plus me-1"></i> Add New
        </button>
                        
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'remindersTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
     
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>

        <!-- Table Container -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive app-scroll">
                    <table class="table table-sm table-bottom-border table-hover align-middle" id="remindersTable">
                    <thead class="bg-light-primary">
                        <tr class="app-sort">
                            <th style="width: 25%;"><?= _l('list_reminder_title'); ?></th>
                            <th style="width: 12%;"><?= _l('is_recurring'); ?></th>                            
                            <th style="width: 12%;"><?= _l('recurring_frequency'); ?></th>                            
                            <th style="width: 25%;"><?= _l('list_reminder_date_time'); ?></th>
                            <th style="width: 10%;"><?= _l('list_reminder_status'); ?></th>
                            <th style="width: 18%;" class="text-center"><?= _l('list_reminder_actions'); ?></th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php if (!empty($reminders)): ?>
                                <?php foreach ($reminders as $r): 
                                    $status = '';
                                    $badge_class = '';
                                    $current_date = date('Y-m-d');
                                    $reminder_date = date('Y-m-d', strtotime($r['date']));
                                    
                                    if ($reminder_date == $current_date) {
                                        $status = 'Today';
                                        $badge_class = 'bg-success';
                                    } elseif ($reminder_date > $current_date) {
                                        $status = 'Upcoming';
                                        $badge_class = 'bg-primary';
                                    } else {
                                        $status = 'Past';
                                        $badge_class = 'bg-danger';
                                    }
                                ?>

                                    <tr data-status="<?= strtolower($status) ?>">
                                    <td>
                                      <?= e($r['title']); ?> <span class="badge bg-light-primary capital ms-1"><?= e($r['priority']); ?></span>
                                      <?php if ($canViewGlobal && !empty($r['created_by_name'])): ?>
                                        <br><small class="text-muted">By: <?= e($r['created_by_name']); ?></small>
                                      <?php endif; ?>
                                    </td>
                                      <td class="capital"><?= $r['is_recurring'] == 1 ? 'Recurring' : 'No Recurring'; ?></td>
                                      <td class="capital"><?= !empty($r['recurring_frequency']) ? e($r['recurring_frequency']) : 'N/A'; ?></td>
                                        <?php
                                        $dateFmt  = get_system_setting('date_format') ?: 'Y-m-d';
                                        $timePref = get_system_setting('time_format') ?: '24';
                                        $timeFmt  = ($timePref === '24') ? 'H:i' : 'h:i A';
                                        ?>
                                        <td><?= date("$dateFmt $timeFmt", strtotime($r['date'])); //remove $timeFmt if want to show only date ?></td>
                                      <td>
                                        <span class="badge <?= $badge_class ?>"><?= $status ?></span>
                                      </td>
                                      <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                    
                                          <!-- View Button -->
                                        <button 
                                          type="button" 
                                          class="btn btn-outline-secondary btn-view-reminder"
                                          data-id="<?= $r['id'] ?>"
                                          data-title="<?= e($r['title']) ?>"
                                          data-description="<?= e($r['description']) ?>"
                                          data-date="<?= $r['date'] ?>"
                                          data-priority="<?= $r['priority'] ?>"
                                          data-is_recurring="<?= $r['is_recurring'] ?>"
                                          data-recurring_frequency="<?= $r['recurring_frequency'] ?>"
                                          data-recurring_duration="<?= $r['recurring_duration'] ?>"
                                          data-created_by_name="<?= $canViewGlobal ? e($r['created_by_name'] ?? '') : '' ?>"
                                          data-is_completed="<?= (int)($r['is_completed'] ?? 0) ?>"
                                          data-completed_at="<?= $r['completed_at'] ?? '' ?>"
                                          data-created_at="<?= $r['created_at'] ?? '' ?>"
                                          data-updated_at="<?= $r['updated_at'] ?? '' ?>"
                                          title="View"
                                          data-bs-toggle="modal"
                                          data-bs-target="#viewReminderModal">
                                          <i class="fas fa-eye"></i>
                                        </button>
                                    
                                          <!-- Edit Button -->
                                          <button 
                                            type="button"
                                            class="btn <?= $canEdit ? 'btn-outline-secondary' : 'btn-outline-secondary disabled opacity-50' ?> edit-reminder"
                                            title="<?= $canEdit ? 'Edit' : 'No permission to edit' ?>"
                                            <?= $canEdit 
                                                  ? 'data-bs-toggle="modal" data-bs-target="#editReminderModal"' 
                                                  : 'disabled aria-disabled="true"' ?>
                                            data-id="<?= $r['id'] ?>"
                                            data-title="<?= e($r['title']) ?>"
                                            data-description="<?= e($r['description']) ?>"
                                            data-date="<?= $r['date'] ?>"
                                            data-is_recurring="<?= $r['is_recurring'] ?>"
                                            data-recurring_frequency="<?= $r['recurring_frequency'] ?>"
                                            data-recurring_duration="<?= $r['recurring_duration'] ?>"
                                            data-priority="<?= $r['priority'] ?>">
                                            <i class="fa fa-edit"></i>
                                          </button>
                                    
                                          <!-- Delete Button -->
                                          <?php if ($canDelete): ?>
                                            <?= delete_link([
                                              'url' => 'reminders/delete/' . $r['id'],
                                              'label' => '',
                                              'class' => 'btn btn-outline-secondary',
                                              'message' => '',                                             
                                            ]) ?>
                                            <?php endif; ?>
                                            
                                        </div>
                                      </td>
                                    </tr>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted"><?= _l('no_reminders_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('reminders/modals/add_reminder', [], true); ?>
<?php echo $CI->load->view('reminders/modals/edit_reminder', [], true); ?>
<?php echo $CI->load->view('reminders/modals/view', [], true); ?>

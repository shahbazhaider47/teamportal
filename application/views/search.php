<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container mt-4">
  <h3>Search Results for “<?php echo html_escape($term); ?>”</h3>
  <hr>

  <?php
  // “$result” is an array of groups, each with:
  //  - 'search_heading' => string
  //  - 'type'           => string (e.g. 'staff')
  //  - 'result'         => array of rows
  //
  // If no groups have results, show “No results.”
  $total_items = 0;
  foreach ($result as $group) {
      $total_items += count($group['result']);
  }

  if ($total_items === 0): ?>
    <div class="alert alert-warning">
      No results found for <strong><?php echo html_escape($term); ?></strong>.
    </div>
  <?php else: ?>
    <?php foreach ($result as $group): ?>
      <?php if (count($group['result']) > 0): ?>
        <h5 class="mt-4"><?php echo html_escape($group['search_heading']); ?></h5>
        <ul class="list-group mb-3">
          <?php foreach ($group['result'] as $_result): ?>
            <?php
              // Build each link exactly as your old dropdown did.
              // We’ll replicate the 'staff' case only; other types can be added here.
              $output = '';
              switch ($group['type']) {
                  case 'staff':
                      // $_result has: ['staffid' => int, 'firstname' => string, 'lastname' => string]
                      $staffid   = (int) $_result['staffid'];
                      $full_name = html_escape($_result['firstname'] . ' ' . $_result['lastname']);
                      $link = site_url("staff/member/{$staffid}");
                      $output = '<a href="' . $link . '">' . $full_name . '</a>';
                      break;

                  // You can add more cases here if you later include other types (clients, tickets, etc.)
                  default:
                      // Fallback: just print the raw array
                      $output = '<span class="text-muted">Unknown type</span>';
                      break;
              }
            ?>
            <li class="list-group-item"><?php echo $output; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

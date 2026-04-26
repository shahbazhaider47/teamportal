Hi <?= $recipient_name ?>,

Your support ticket has been created:

<?= $ticket_subject ?>

<?php if (!empty($ticket_code)): ?>Code: <?= $ticket_code . PHP_EOL ?><?php endif; ?>
Open ticket: <?= $ticket_url ?>


— <?= $brand ?> Support

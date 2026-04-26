Hi <?= $recipient_name ?>,

Your support ticket has been CLOSED:

<?= $ticket_subject ?>

<?php if (!empty($ticket_code)): ?>Code: <?= $ticket_code . PHP_EOL ?><?php endif; ?>
Review ticket: <?= $ticket_url ?>


— <?= $brand ?> Support

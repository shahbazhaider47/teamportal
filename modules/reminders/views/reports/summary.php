<div class="container">
    <h2><?= html_escape($title) ?></h2>
    <p>This is the reminders summary report.</p>
    <?php if (!empty($report_data)): ?>
        <ul>
            <?php foreach ($report_data as $row): ?>
                <li><?= html_escape($row['title']) ?>: <?= html_escape($row['count']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No data available.</p>
    <?php endif; ?>
</div>

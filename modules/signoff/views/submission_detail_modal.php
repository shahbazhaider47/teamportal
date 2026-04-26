<?php if (!$fields || !$data): ?>
    <div class="alert alert-warning">No submission data available.</div>
<?php else: ?>
    <div>
        <h6 class="mb-3">Submitted Fields</h6>
        <table class="table table-bordered mb-0">
            <tbody>
            <?php foreach ($fields as $f):
                $key = $f['name'];
                $label = $f['label'] ?? $key;
                $value = isset($data[$key]) ? $data[$key] : '<span class="text-muted">—</span>';
            ?>
                <tr>
                    <th style="width: 40%"><?= html_escape($label) ?></th>
                    <td><?= nl2br(html_escape($value)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-3 small text-muted">
        <b>Status:</b> <?= ucfirst($submission['status']) ?><br>
        <b>Date:</b> <?= date('l, d M Y', strtotime($submission['submission_date'])) ?>
    </div>
<?php endif; ?>

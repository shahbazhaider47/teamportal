<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222;">
  <p>Hi <?=htmlspecialchars($recipient_name ?? 'there', ENT_QUOTES,'UTF-8')?>,</p>
  <p>A new ticket has been created in your department:</p>
  <p><strong><?=htmlspecialchars($ticket_subject ?? '', ENT_QUOTES,'UTF-8')?></strong></p>
  <p><a href="<?=htmlspecialchars($ticket_url ?? '#', ENT_QUOTES,'UTF-8')?>">Open ticket</a></p>
  <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">
  <p style="color:#666"><?=htmlspecialchars($brand ?? 'Support', ENT_QUOTES,'UTF-8')?></p>
</div>

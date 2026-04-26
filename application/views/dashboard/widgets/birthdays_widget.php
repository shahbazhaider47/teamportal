<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>

<style>
.bd-card {
    background: #fff;
    border: 0.5px solid rgba(148,163,184,0.25);
    border-radius: 12px;
    overflow: hidden;
}
.bd-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
    border-bottom: 0.5px solid rgba(148,163,184,0.2);
}
.bd-header-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.bd-header-title {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}
.bd-count-pill {
    margin-left: auto;
    font-size: 11px;
    font-weight: 600;
    color: #d97706;
    background: #fef3c7;
    border-radius: 100px;
    padding: 2px 8px;
}
.bd-list { padding: 4px 0; }
.bd-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    border-bottom: 0.5px solid rgba(148,163,184,0.15);
    transition: background 0.12s;
}
.bd-row:last-child { border-bottom: none; }
.bd-row:hover { background: #f8fafc; }
.bd-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.bd-avatar-initials {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}
.bd-info { flex: 1; min-width: 0; }
.bd-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.bd-role {
    font-size: 11px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}
.bd-date-col { flex-shrink: 0; text-align: right; }
.bd-date { font-size: 12px; font-weight: 600; color: #475569; }
.bd-days { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.bd-days.is-today { color: #d97706; font-weight: 600; }
.bd-action { flex-shrink: 0; margin-left: 8px; }
.bd-wish-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    border: 0.5px solid rgba(148,163,184,0.35);
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: background 0.12s, border-color 0.12s, color 0.12s;
    white-space: nowrap;
    text-decoration: none;
}
.bd-wish-btn:hover {
    background: #fef3c7;
    border-color: #fcd34d;
    color: #92400e;
    text-decoration: none;
}
.bd-yours {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    border: none;
}
.bd-wished {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #94a3b8;
    padding: 4px 4px;
}
.bd-empty {
    padding: 28px 16px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}
</style>

<?php
// Count for the pill
$birthday_count = count($upcoming_birthdays ?? []);
$current_user_id = $CI->session->userdata('user_id');
?>

<div class="bd-card mb-4">

    <div class="bd-header">
        <div class="bd-header-icon">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M8 3c0-1.1.9-2 2-2" stroke="#d97706" stroke-width="1.4" stroke-linecap="round"/>
                <path d="M8 3c0-1.1-.9-2-2-2" stroke="#d97706" stroke-width="1.4" stroke-linecap="round"/>
                <rect x="2" y="5" width="12" height="9" rx="1.5" stroke="#d97706" stroke-width="1.4"/>
                <path d="M2 9h12M8 5v9" stroke="#d97706" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
        </div>
        <span class="bd-header-title">Upcoming birthdays</span>
        <?php if ($birthday_count > 0): ?>
            <span class="bd-count-pill">
                <?= $birthday_count ?> upcoming
            </span>
        <?php endif; ?>
    </div>

    <div class="bd-list">
        <?php if (!empty($upcoming_birthdays)): ?>
            <?php foreach ($upcoming_birthdays as $user): ?>
                <?php
                $wish_key      = 'birthday_wished_user_' . $user['id'];
                $already_wished = $CI->session->userdata($wish_key);
                $is_current_user = $current_user_id == $user['id'];

                // Avatar
                $profileImage = $user['profile_image'] ?? '';
                $profilePath  = FCPATH . 'uploads/users/profile/' . $profileImage;
                $profileUrl   = base_url('uploads/users/profile/' . $profileImage);
                $defaultUrl   = site_url('assets/images/default.png');
                $hasImage     = !empty($profileImage) && file_exists($profilePath);

                // Initials fallback
                $nameParts = explode(' ', trim($user['fullname'] ?? ''));
                $initials  = strtoupper(substr($nameParts[0] ?? '?', 0, 1) . substr(end($nameParts), 0, 1));

                // Avatar background colors cycling
                $avatarColors = [
                    ['bg' => '#dbeafe', 'color' => '#1e40af'],
                    ['bg' => '#d1fae5', 'color' => '#065f46'],
                    ['bg' => '#fce7f3', 'color' => '#9d174d'],
                    ['bg' => '#ede9fe', 'color' => '#5b21b6'],
                    ['bg' => '#fef3c7', 'color' => '#92400e'],
                    ['bg' => '#fee2e2', 'color' => '#991b1b'],
                ];
                $colorIdx    = crc32($user['id']) % count($avatarColors);
                $colorIdx    = abs($colorIdx);
                $avatarColor = $avatarColors[$colorIdx];

                // Days label
                $today   = new DateTime(date('Y-m-d'));
                $dobThisYear = new DateTime(date('Y') . '-' . date('m-d', strtotime($user['emp_dob'])));
                if ($dobThisYear < $today) {
                    $dobThisYear->modify('+1 year');
                }
                $diff     = (int)$today->diff($dobThisYear)->days;
                $daysText = $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : 'in ' . $diff . ' days');
                $isToday  = $diff === 0;
                ?>

                <div class="bd-row">
                    <?php if ($hasImage): ?>
                        <img src="<?= $profileUrl ?>"
                             alt="<?= html_escape($user['fullname']) ?>"
                             class="bd-avatar">
                    <?php else: ?>
                        <div class="bd-avatar-initials"
                             style="background:<?= $avatarColor['bg'] ?>;color:<?= $avatarColor['color'] ?>;">
                            <?= $initials ?>
                        </div>
                    <?php endif; ?>

                    <div class="bd-info">
                        <div class="bd-name"><?= html_escape($user['fullname']) ?></div>
                        <?php if (!empty($user['emp_title'])): ?>
                            <div class="bd-role"><?= html_escape(ucwords($user['emp_title'])) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="bd-date-col">
                        <div class="bd-date"><?= date('d M', strtotime($user['emp_dob'])) ?></div>
                        <div class="bd-days <?= $isToday ? 'is-today' : '' ?>"><?= $daysText ?></div>
                    </div>

                    <div class="bd-action">
                        <?php if ($is_current_user): ?>
                            <span class="bd-yours">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                    <path d="M6 1l1.2 2.5L10 4l-2 2 .5 2.8L6 7.5 3.5 8.8 4 6 2 4l2.8-.5L6 1z" fill="#1d4ed8"/>
                                </svg>
                                Your birthday
                            </span>
                        <?php elseif (!$already_wished): ?>
                            <a href="#"
                               class="bd-wish-btn wish-birthday-btn"
                               data-user-id="<?= (int)$user['id'] ?>"
                               data-fullname="<?= html_escape($user['fullname']) ?>"
                               data-bs-toggle="modal"
                               data-bs-target="#wishBirthdayModal">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                    <path d="M6 1.5c-.8-1.3-3-1-3 1.2 0 1.5 1.5 2.8 3 4.3 1.5-1.5 3-2.8 3-4.3 0-2.2-2.2-2.5-3-1.2z" stroke="currentColor" stroke-width="1.2"/>
                                    <path d="M2 10.5h8" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                                </svg>
                                Wish
                            </a>
                        <?php else: ?>
                            <span class="bd-wished">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                    <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Wished
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="bd-empty">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" style="display:block;margin:0 auto 8px;opacity:0.35;">
                    <path d="M14 5c0-1.7 1.3-3 3-3M14 5c0-1.7-1.3-3-3-3" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round"/>
                    <rect x="4" y="9" width="20" height="15" rx="2.5" stroke="#94a3b8" stroke-width="1.8"/>
                    <path d="M4 15h20M14 9v15" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                No upcoming birthdays this week
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Wish Birthday Modal -->
<div class="modal fade" id="wishBirthdayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="wishBirthdayForm" method="post" action="<?= site_url('users/send_birthday_wish') ?>">
            <div class="modal-content" style="border-radius:12px;border:0.5px solid rgba(148,163,184,0.25);">
                <div class="modal-header" style="border-bottom:0.5px solid rgba(148,163,184,0.2);padding:14px 20px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M8 2.5c-.7-1.1-2.5-.9-2.5 1 0 1.3 1.3 2.4 2.5 3.7 1.2-1.3 2.5-2.4 2.5-3.7 0-1.9-1.8-2.1-2.5-1z" stroke="#d97706" stroke-width="1.3"/>
                                <path d="M2 12.5h12" stroke="#d97706" stroke-width="1.3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h5 class="modal-title mb-0" style="font-size:14px;font-weight:600;">
                            Send wish to <span id="wishUserNameLabel" style="color:#2563eb;"></span>
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:16px 20px;">
                    <input type="hidden" name="user_id" id="wishUserId">
                    <label style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:6px;">
                        Your message
                    </label>
                    <textarea name="message"
                              id="wishMessage"
                              class="form-control"
                              rows="3"
                              placeholder="Happy birthday! Wishing you a wonderful day…"
                              style="font-size:13px;border-radius:8px;border:0.5px solid rgba(148,163,184,0.35);resize:none;"
                              required></textarea>
                </div>
                <div class="modal-footer" style="border-top:0.5px solid rgba(148,163,184,0.2);padding:12px 20px;gap:8px;">
                    <button type="button"
                            class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal"
                            style="font-size:12px;">
                        Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-primary btn-sm"
                            style="font-size:12px;">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" style="margin-right:4px;">
                            <path d="M1.5 6.5L11.5 2l-4.5 10-1-4-4.5-1.5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        </svg>
                        Send wish
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.wish-birthday-btn');
    if (!btn) return;
    document.getElementById('wishUserId').value       = btn.dataset.userId;
    document.getElementById('wishUserNameLabel').textContent = btn.dataset.fullname;
    document.getElementById('wishMessage').value      = '';
});
</script>
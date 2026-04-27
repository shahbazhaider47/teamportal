<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _members_modal.php
 * Variables: $user_id, $members (array)
 * Manage members of the active conversation.
 * Full member list is re-loaded via JS when modal opens.
 */
?>

<div class="modal fade" id="tcMembersModal" tabindex="-1"
     aria-labelledby="tcMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tcMembersModalLabel">
                    <i class="ti ti-users me-2"></i>
                    Members
                    <span class="badge bg-secondary ms-2" id="tcMembersCount">
                        <?php echo count($members ?? []); ?>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">

                <?php /* Add member section — shown only for admin/owner (JS toggles) */ ?>
                <div class="tc-members-add p-3 border-bottom d-none" id="tcMembersAddSection">
                    <div class="tc-user-search-wrap">
                        <i class="ti ti-search tc-search-icon"></i>
                        <input type="text"
                               id="tcAddMemberSearch"
                               class="form-control tc-user-search-input"
                               placeholder="Search users to add…"
                               autocomplete="off">
                    </div>
                    <div class="tc-user-results mt-2" id="tcAddMemberResults"></div>
                </div>

                <?php /* Members list */ ?>
                <div class="tc-members-list" id="tcMembersList">

                    <?php if (empty($members)): ?>
                    <div class="tc-members-empty p-4 text-center text-muted">
                        <i class="ti ti-users-off mb-2" style="font-size:2rem"></i>
                        <p>No members found.</p>
                    </div>
                    <?php else: ?>

                    <?php foreach ($members as $member):
                        $is_me        = ((int)$member['user_id'] === (int)$user_id);
                        $role         = $member['role'] ?? 'member';
                        $role_label   = ucfirst($role);
                        $role_badge   = match($role) {
                            'owner' => 'bg-primary',
                            'admin' => 'bg-info text-dark',
                            default => 'bg-secondary',
                        };
                        $online_class = !empty($member['is_online']) ? 'tc-status--online' : 'tc-status--offline';

                        $avatar = team_chat_user_avatar_url($member['profile_image'] ?? null)
                            ?: team_chat_initials_avatar($member['fullname'] ?? '?');
                    ?>
                    <div class="tc-member-row"
                         data-user-id="<?php echo (int)$member['user_id']; ?>"
                         data-role="<?php echo htmlspecialchars($role, ENT_QUOTES); ?>">

                        <div class="tc-member-row__avatar">
                            <img src="<?php echo $avatar; ?>"
                                 alt="<?php echo htmlspecialchars($member['fullname'] ?? '', ENT_QUOTES); ?>"
                                 class="tc-avatar tc-avatar--sm">
                            <span class="tc-avatar__status <?php echo $online_class; ?>"></span>
                        </div>

                        <div class="tc-member-row__info">
                            <div class="tc-member-row__name">
                                <?php echo htmlspecialchars($member['fullname'] ?? '', ENT_QUOTES); ?>
                                <?php if ($is_me): ?>
                                <span class="text-muted small">(you)</span>
                                <?php endif; ?>
                            </div>
                            <div class="tc-member-row__meta">
                                <?php echo htmlspecialchars($member['emp_id'] ?? '', ENT_QUOTES); ?>
                                <?php if (!empty($member['last_seen_at'])): ?>
                                · <span title="Last seen"><?php echo team_chat_time_ago($member['last_seen_at']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tc-member-row__role">
                            <span class="badge <?php echo $role_badge; ?>"><?php echo $role_label; ?></span>
                        </div>

                        <?php /* Member actions — only for admins/owners, shown via JS */ ?>
                        <div class="tc-member-row__actions tc-member-actions d-none"
                             data-user-id="<?php echo (int)$member['user_id']; ?>"
                             data-role="<?php echo htmlspecialchars($role, ENT_QUOTES); ?>">
                            <?php if (!$is_me): ?>

                            <?php /* Role selector */ ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                    Role
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item tc-role-change"
                                           href="#"
                                           data-uid="<?php echo (int)$member['user_id']; ?>"
                                           data-role="admin">
                                            Make Admin
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item tc-role-change"
                                           href="#"
                                           data-uid="<?php echo (int)$member['user_id']; ?>"
                                           data-role="member">
                                            Make Member
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <?php /* Remove */ ?>
                            <button class="btn btn-sm btn-outline-danger tc-remove-member"
                                    data-uid="<?php echo (int)$member['user_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($member['fullname'] ?? '', ENT_QUOTES); ?>"
                                    title="Remove from conversation">
                                <i class="ti ti-user-minus"></i>
                            </button>

                            <?php endif; ?>

                            <?php /* Self: leave */ ?>
                            <?php if ($is_me): ?>
                            <button class="btn btn-sm btn-outline-warning tc-leave-conv"
                                    data-uid="<?php echo (int)$member['user_id']; ?>"
                                    title="Leave conversation">
                                <i class="ti ti-door-exit me-1"></i> Leave
                            </button>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>

                    <?php endif; ?>
                </div>

            </div>

            <div class="modal-footer justify-content-between">
                <button type="button"
                        class="btn btn-outline-primary tc-add-member-toggle d-none"
                        id="tcToggleAddMember">
                    <i class="ti ti-user-plus me-1"></i>
                    Add Members
                </button>
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

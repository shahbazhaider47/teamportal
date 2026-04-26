<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Default user permissions (grants-only)
 * NOTE: Denies intentionally left empty; we no longer manage denies in Settings.
 * IMPORTANT: Keep values lowercase in "module:action" format.
 */
$config['default_user_grants'] = [
    "users:view_own",
    "reminders:view_own",
    "reminders:create",
    "reminders:edit",
    "reminders:delete",
    "teams:view_own",
    "announcements:view_own",
    "todos:view_own",
    "todos:create",
    "todos:edit",
    "todos:delete",
    "payroll:view_own",
    "attendance:view_own",
    "signoff:view_own",
    "signoff:create",
    "support:view_own",
    "support:create",
    ];

$config['default_user_denies'] = []; // keep for backward-compat; we won’t write to it

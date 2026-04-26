<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['hierarchy_loaded'] = true;

// ── Role weights (higher = more authority) ──────────────────────────────────
define('HIERARCHY_WEIGHTS', [
    'superadmin' => 100,
    'director'   => 80,
    'manager'    => 60,
    'teamlead'   => 40,
    'employee'   => 20,
    'officeboy'  => 0,
    'sweeper'    => 0,
    'other'      => 0,
]);

// ── Roles excluded from all hierarchy logic ─────────────────────────────────
define('HIERARCHY_EXCLUDED', ['officeboy', 'sweeper', 'other']);

// ── Scope each role operates within ─────────────────────────────────────────
// global = all teams/depts
// dept   = own department only (manager)
// team   = own team only (teamlead)
// self   = own data only (employee)
define('HIERARCHY_SCOPE', [
    'superadmin' => 'global',
    'director'   => 'global',
    'manager'    => 'dept',
    'teamlead'   => 'team',
    'employee'   => 'self',
]);

// ── Roles each role can VIEW ─────────────────────────────────────────────────
define('HIERARCHY_CAN_VIEW', [
    'superadmin' => ['director', 'manager', 'teamlead', 'employee'],
    'director'   => ['manager', 'teamlead', 'employee'],
    'manager'    => ['teamlead', 'employee'],
    'teamlead'   => ['employee'],
    'employee'   => [],
]);

// ── Roles each role can MANAGE (approve/evaluate/edit records of) ────────────
// teamlead: manages employees — but only own team (enforced in controller)
// manager:  manages teamleads + employees — across their department
define('HIERARCHY_CAN_MANAGE', [
    'superadmin' => ['director', 'manager', 'teamlead', 'employee'],
    'director'   => ['manager', 'teamlead', 'employee'],
    'manager'    => ['teamlead', 'employee'],
    'teamlead'   => ['employee'],
    'employee'   => [],
]);

// ── Roles eligible to be assigned as team lead ───────────────────────────────
define('HIERARCHY_LEAD_ELIGIBLE', ['teamlead', 'manager']);

// ── Backward-compat aliases (so existing code using TEAMS_* still works) ─────
if (!defined('TEAMS_ROLE_WEIGHTS'))     { define('TEAMS_ROLE_WEIGHTS',     HIERARCHY_WEIGHTS); }
if (!defined('TEAMS_ROLES_EXCLUDED'))   { define('TEAMS_ROLES_EXCLUDED',   HIERARCHY_EXCLUDED); }
if (!defined('TEAMS_LEAD_ELIGIBLE_ROLES')) { define('TEAMS_LEAD_ELIGIBLE_ROLES', HIERARCHY_LEAD_ELIGIBLE); }
if (!defined('TEAMS_GLOBAL_VIEW_ROLES')) {
    define('TEAMS_GLOBAL_VIEW_ROLES', ['superadmin', 'director', 'manager']);
}
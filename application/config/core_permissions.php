<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * --------------------------------------------------------------------------
 * Core System Permissions Map
 * --------------------------------------------------------------------------
 * Purpose:
 *   Central, human-readable registry of modules and their allowed actions.
 *
 * Usage:
 *   - Gate UI elements (buttons/links) and controller actions via helpers
 *     like staff_can('edit', 'users').
 *   - Keys MUST remain stable. Only labels/help text should evolve.
 *
 * Security Note:
 *   Controllers and policies MUST enforce permissions server-side.
 *   UI hiding is convenience only, not security.
 */

return [

/**
 * --------------------------------------------------------------------------
 * General (System-wide utilities)
 * --------------------------------------------------------------------------
 */
'general' => [

    'print' => [
        'label' => 'Print',
        'help'  => 'Allows printing of pages, reports, and records. Typically granted to operational staff and managers.',
    ],

    'import' => [
        'label' => 'Import',
        'help'  => 'Allows importing data into the system using CSV or Excel files. Recommended for admins or trusted power users.',
    ],

    'export' => [
        'label' => 'Export',
        'help'  => 'Allows exporting data from the system in CSV, Excel, or PDF formats. Commonly used for reporting and audits.',
    ],

    'download' => [
        'label' => 'Download',
        'help'  => 'Allows downloading files, documents, and attachments from the system.',
    ],

    'viewsystem' => [
        'label' => 'View Settings',
        'help'  => 'Provides read-only access to system configuration and settings pages. No changes can be made.',
    ],

    'editsystem' => [
        'label' => 'Edit Settings',
        'help'  => 'Allows modifying core system settings and configurations. High-risk permission; assign to administrators only.',
    ],

    'manage_requests' => [
        'label' => 'Manage Requests',
        'help'  => 'Allows reviewing, processing, and managing operational requests submitted by users.',
    ],

    'manage_permissions' => [
        'label' => 'Manage Permissions',
        'help'  => 'Allows user to manage all user permissions, not all permissions included.',
    ],
    
    'approve_requests' => [
        'label' => 'Approve Requests',
        'help'  => 'Allows approving or rejecting user requests. Typically granted to managers or department heads.',
    ],
    'feedback' => [
        'label' => 'Feedbacks',
        'help'  => 'Manage employee feedbacks (add, edit and delete)',
    ],    
],

/**
 * --------------------------------------------------------------------------
 * General (System-wide utilities)
 * --------------------------------------------------------------------------
 */
'company' => [

    'manage' => [
        'label' => 'Manage',
        'help'  => 'Manage company and modify any company public or confidential information',
    ],

    'view' => [
        'label' => 'View',
    ],

    'edit' => [
        'label' => 'Edit',
    ],

    'delete' => [
        'label' => 'Delete',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Users
 * --------------------------------------------------------------------------
 */
'users' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all user profiles across the entire organization. Usually assigned to HR and admins.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing own profile and profiles of direct reports only.',
    ],

    'manage' => [
        'label' => 'Manage Users',
        'help'  => 'Allows managing users including roles, teams, departments, and reporting structure. High-impact permission.',
    ],

    'view_progress' => [
        'label' => 'View Progress',
        'help'  => 'Allows viewing performance, onboarding, or progress tracking information for users.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new user accounts in the system.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing user details, assignments, and profile information.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deactivating or permanently deleting user accounts. Use with caution.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Departments
 * --------------------------------------------------------------------------
 */
'departments' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing the list of all departments in the organization.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new departments.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing department names, details, and configurations.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting departments. This may affect users assigned to them.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Contracts
 * --------------------------------------------------------------------------
 */
'contracts' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all staff contracts in the system.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing only the logged-in user’s own contract.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new staff contracts.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing existing contract details.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting or archiving staff contracts.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Teams
 * --------------------------------------------------------------------------
 */
'teams' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all teams across the organization.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing only teams the user belongs to.',
    ],

    'guide' => [
        'label' => 'Add Instructions',
        'help'  => 'Allows adding guidance or instructions for team members.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new teams.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing team details, members, and settings.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting teams. This action may be irreversible.',
    ],
],


/**
 * --------------------------------------------------------------------------
 * Attendance
 * --------------------------------------------------------------------------
 */
'attendance' => [

    'view_global' => [
        'label' => 'View Global',
    ],

    'view_own' => [
        'label' => 'View Own',
    ],

    'own_team' => [
        'label' => 'View Own Team',
    ],

    'approve' => [
        'label' => 'Approve',
    ],

    'apply' => [
        'label' => 'Apply Leave',
    ],
    
    'create' => [
        'label' => 'Create',
    ],

    'edit' => [
        'label' => 'Edit',
    ],

    'delete' => [
        'label' => 'Delete',
    ],
],


/**
 * --------------------------------------------------------------------------
 * Evaluations
 * --------------------------------------------------------------------------
 */

'evaluations' => [
    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all employee evaluations across the organization.',
    ],
    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing only evaluations belonging to the logged-in user.',
    ],

    'own_team' => [
        'label' => 'Own Team',
        'help'  => 'Allows to manage evaluations only belonging to own team.',
    ],
    
    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new employee evaluations.',
    ],
    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing existing evaluation records.',
    ],
    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting evaluation records.',
    ],
    'approve' => [
        'label' => 'Approve',
        'help'  => 'Allows approving or rejecting submitted evaluations.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Announcements
 * --------------------------------------------------------------------------
 */
'announcements' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all announcements across the organization.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing announcements targeted to the user or their team.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new announcements.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing existing announcements.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting announcements.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Calendar
 * --------------------------------------------------------------------------
 */
'calendar' => [

    'add' => [
        'label' => 'Add Events',
        'help'  => 'Allows creating calendar events.',
    ],

    'edit' => [
        'label' => 'Edit Events',
        'help'  => 'Allows modifying existing calendar events.',
    ],

    'delete' => [
        'label' => 'Delete Events',
        'help'  => 'Allows removing calendar events.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * To-Dos
 * --------------------------------------------------------------------------
 */
'todos' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all tasks across users and teams.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing tasks created by or assigned to the user.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new tasks.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing task details such as title, description, and dates.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting tasks.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Login Vault
 * --------------------------------------------------------------------------
 */
'vault' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all saved credentials in the login vault.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing only credentials owned by the user.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows adding new credentials to the vault.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing saved credentials.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting credentials from the vault.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Assets
 * --------------------------------------------------------------------------
 */
'assets' => [

    'view' => [
        'label' => 'View Asset',
        'help'  => 'Allows viewing asset inventory records.',
    ],

    'add' => [
        'label' => 'Add Asset',
        'help'  => 'Allows adding new assets to inventory.',
    ],

    'edit' => [
        'label' => 'Edit Asset',
        'help'  => 'Allows updating asset information and assignments.',
    ],

    'delete' => [
        'label' => 'Delete Asset',
        'help'  => 'Allows retiring or permanently deleting assets.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Subscriptions
 * --------------------------------------------------------------------------
 */
'subscriptions' => [

    'view' => [
        'label' => 'View',
        'help'  => 'Allows viewing subscription records and billing cycles.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new subscriptions.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing subscription details and renewal settings.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting subscriptions.',
    ],

    'export' => [
        'label' => 'Export',
        'help'  => 'Allows exporting subscription data for reporting purposes.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Support
 * --------------------------------------------------------------------------
 */
'support' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all support tickets in the system.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing only tickets created by or assigned to the user.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating new support tickets.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing ticket details and responses.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting support tickets.',
    ],

    'assign' => [
        'label' => 'Assign',
        'help'  => 'Allows assigning tickets to staff members.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Reports
 * --------------------------------------------------------------------------
 */
'reports' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows viewing all system reports.',
    ],

    'view_own' => [
        'label' => 'View Own',
        'help'  => 'Allows viewing reports scoped to the user or their team.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating custom reports.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing report definitions and parameters.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting report definitions.',
    ],
],

/**
 * --------------------------------------------------------------------------
 * Utilities
 * --------------------------------------------------------------------------
 */
'utilities' => [

    'view_global' => [
        'label' => 'View Global',
        'help'  => 'Allows accessing administrative and operational utility tools.',
    ],

    'create' => [
        'label' => 'Create',
        'help'  => 'Allows creating utility jobs or records.',
    ],

    'edit' => [
        'label' => 'Edit',
        'help'  => 'Allows editing utility configurations.',
    ],

    'delete' => [
        'label' => 'Delete',
        'help'  => 'Allows deleting utility records or configurations.',
    ],
],

];

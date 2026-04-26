<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| URI ROUTING (application/config/routes.php)
|--------------------------------------------------------------------------
| Here you can define how URI requests map to controllers and methods.
| This file has been enhanced for future scalability, adding RESTful-style
| user management routes, authentication, dashboard, and search. Adjust
| or extend as new modules/controllers are added.
|
| Pattern: example.com/class/method/id/
| 
| Reserved routes:
|   $route['default_controller'] = 'authentication/login';
|   $route['404_override']       = 'errors/page_missing';
|   $route['translate_uri_dashes'] = FALSE;
|
| For more details, see:
|   https://codeigniter.com/user_guide/general/routing.html
*/

/*
| -------------------------------------------------------------------------
| CORE ROUTES
| -------------------------------------------------------------------------
*/

// If no URI data is provided, redirect to the login page (or modify to 'dashboard' if user is already authenticated).
$route['default_controller'] = 'authentication/login';

// Fallback for invalid URIs
$route['404_override'] = 'errors/page_missing';

// Whether to translate dashes in controller/method names (FALSE = leave as-is)
$route['translate_uri_dashes'] = FALSE;

$route['__router_class'] = 'App_Router';
/*
| -------------------------------------------------------------------------
| AUTHENTICATION ROUTES
| -------------------------------------------------------------------------
| Handles user login, registration, logout, password resets, etc.
|
| future enhancements:
|   - password_reset
|   - account_activation
|   - two_factor_auth
*/

// Display login form / process login
$route['authentication/login'] = 'authentication/login';
$route['logout']          = 'authentication/logout';

// Display registration form / process registration
$route['register']        = 'authentication/register';



// ➕ ADD HERE:
$route['authentication/forgot_password'] = 'authentication/forgot_password';
$route['authentication/reset_password/(:any)'] = 'authentication/reset_password/$1';


// (Optional) Password reset flow endpoints
// $route['forgot-password']       = 'authentication/forgot_password';
// $route['reset-password/(:any)'] = 'authentication/reset_password/$1';

/*
| -------------------------------------------------------------------------
| DASHBOARD ROUTES
| -------------------------------------------------------------------------
| Central landing page for authenticated users, with drag & drop widgets, etc.
*/

// Main dashboard (shortcut to Dashboard::index)
$route['dashboard']       = 'dashboard/index';

// AJAX endpoints for dashboard widget configuration
$route['dashboard/save-order']      = 'dashboard/save_order';
$route['dashboard/save-visibility'] = 'dashboard/save_visibility';

/*
| -------------------------------------------------------------------------
| USER MANAGEMENT ROUTES
| -------------------------------------------------------------------------
| CRUD for users. Follows RESTful-ish patterns. Controllers should handle
| both GET (form/view) and POST (create/update/delete) as needed.
|
| Controller: Users.php
|   Methods: index(), add(), edit($id), delete($id), profile(), etc.
*/

// List all users
$route['users']                  = 'users/index';

// Display “Add New User” form / process submission
$route['users/add']              = 'users/add';
$route['users/create']           = 'users/add'; // alternative endpoint

// Display “Edit User” form or process update
$route['users/edit/(:num)']      = 'users/edit/$1';
$route['users/update/(:num)']    = 'users/edit/$1'; // if you prefer a separate method

// Delete (or deactivate) a user
$route['users/delete/(:num)']    = 'users/delete/$1';

// User profile (view or edit logged-in user info)
$route['users/profile']          = 'users/profile';
$route['users/profile/update']   = 'users/update_profile';




/**
 * @since 2.3.0
 * Routes for admin/modules URL because Modules.php class is used in application/third_party/MX
 */
$route['admin/modules']               = 'admin/mods';
$route['admin/modules/(:any)']        = 'admin/mods/$1';
$route['admin/modules/(:any)/(:any)'] = 'admin/mods/$1/$2';

/*
| -------------------------------------------------------------------------
| SEARCH ROUTES
| -------------------------------------------------------------------------
| Global application search
*/

// Execute search and display full results page
$route['search']                 = 'search/index';

// (Optional) AJAX dropdown search
// $route['search/ajax']         = 'search/ajax_dropdown';

/*
| -------------------------------------------------------------------------
| OTHER MODULES (Placeholders for future expansion)
| -------------------------------------------------------------------------
*/

$route['roles/permissions']        = 'roles/permissions';
$route['roles/permissions/(:num)'] = 'roles/permissions/$1';



/*
| -------------------------------------------------------------------------
| Routes for Settings
| -------------------------------------------------------------------------
*/
// Settings explicit methods (MUST be before catch-all)
$route['settings/ping']        = 'settings/ping';
$route['settings/system_info'] = 'settings/system_info';
$route['settings/manage_permissions'] = 'settings/manage_permissions';
$route['settings/system-info'] = 'settings/system_info';
$route['settings/test_smtp']   = 'settings/test_smtp';
$route['settings/save_cron']   = 'settings/save_cron';
$route['settings/cron/rotate-token'] = 'settings/cron_rotate_token';
$route['settings/cron/rotate_token'] = 'settings/cron_rotate_token';

$route['settings'] = 'settings/index';
$route['settings/(:any)'] = 'settings/index?group=$1';


// application/config/routes.php

// Ensure these lines exist (after the default routes):
$route['users/get_user/(:num)']  = 'users/get_user/$1';
$route['users/bulk_delete']      = 'users/bulk_delete';

// Map “/admin/users” exactly to Users::index()
$route['admin/users']            = 'users/index';
// Map any segment after “/admin/users/…” to the corresponding Users method
$route['admin/users/(:any)']     = 'users/$1';



$route['users/manage_roles'] = 'users/manage_roles';
$route['users/update_roles'] = 'users/update_roles';



$route['announcements/edit/(:num)']   = 'announcements/edit/$1';
$route['announcements/delete/(:num)'] = 'announcements/delete/$1';

// Clients
// $route['clients']            = 'clients/index';
// $route['clients/add']        = 'clients/add';
// $route['clients/edit/(:num)']= 'clients/edit/$1';
// $route['clients/delete/(:num)']= 'clients/delete/$1';

// Reports
$route['reports'] = 'reports/Reports/index';
$route['reports/(:any)/(:any)/report_view'] = 'reports/Reports/report_view/$1/$2';

// API Endpoints
// (If you later implement a RESTful API controller)
// $route['api/users']          = 'api/users/index';
// $route['api/users/(:num)']   = 'api/users/show/$1';


if (file_exists(APPPATH . 'config/modules_routes.php')) {
    include_once(APPPATH . 'config/modules_routes.php');
}

/*
| -------------------------------------------------------------------------
| WILDCARD / CATCH-ALL (optional)
| -------------------------------------------------------------------------
| If none of the above routes match, you can direct them to a custom 404
| controller or to a default landing page. Uncomment to override.
*/

// $route['(.+)'] = 'errors/page_missing';


// Route for Crone Job Run 

$route['cronjob/cleanup'] = 'cronjob_controller/cleanup_logs';


$route['cron/run']          = 'cron/run';
$route['cron/run/(:any)']   = 'cron/run/$1';
$route['cron/health']       = 'cron/health';
$route['cron/unlock']       = 'cron/unlock'; // if you added unlock()


$route['email_templates']              = 'email_templates/index';
$route['email_templates/create']       = 'email_templates/create';
$route['email_templates/edit/(:any)']  = 'email_templates/edit/$1';

$route['email_templates'] = 'email_templates/index';
$route['email_templates/preview/(:any)'] = 'email_templates/preview/$1';
$route['email_templates/edit/(:any)'] = 'email_templates/edit/$1';
$route['email_templates/create'] = 'email_templates/create';

// Tasks (HMVC) – map friendly URIs to module/controller
$route['tasks']                         = 'tasks/tasks/index';
$route['tasks/list_json']               = 'tasks/tasks/list_json';
$route['tasks/create']                  = 'tasks/tasks/create';
$route['tasks/view/(:num)']             = 'tasks/tasks/view/$1';
$route['tasks/delete/(:num)']           = 'tasks/tasks/delete/$1';
$route['tasks/(:num)/attachments/upload'] = 'tasks/tasks/upload_attachment/$1';
$route['tasks/(:num)/comments/add']     = 'tasks/tasks/add_comment/$1';


// Apps hub
$route['apps'] = 'Apps/index';


$route['contracts/my_contract'] = 'contracts/my_contract';

// -------------------------------------------------
// Requests hub
// -------------------------------------------------

$route['requests']                 = 'requests/index';
$route['requests/new']             = 'requests/new_request';
$route['requests/new_request']     = 'requests/new_request';
$route['requests/load_form/(:any)']     = 'requests/load_form/$1';
$route['requests/load_existing/(:any)'] = 'requests/load_existing/$1';
$route['requests/store']           = 'requests/store';
$route['requests/type/(:any)']     = 'requests/type/$1';
$route['requests/(:any)']          = 'requests/type/$1';
$route['requests/view_ajax/(:num)'] = 'requests/view_ajax/$1';
$route['requests/delete/(:num)']    = 'requests/delete/$1';

// -------------------------------------------------
// Attendance Maping attendance_leaves conttroller to merge the links
// All links from attendance_leaves will be routed form the controller Attendance
// -------------------------------------------------
$route['attendance/manage_leaves'] = 'attendance_leaves/manage_leaves';
$route['attendance/tracker']    = 'attendance_tracker/index';
$route['attendance/calendar']   = 'attendance_calendar/index';
$route['attendance/leaves/create'] = 'attendance_leaves/create';

// In config/routes.php — add if not already routing attendance_leaves
$route['attendance/my_leaves']              = 'attendance_leaves/my_leaves';
$route['attendance/ajax_my_leave_events']   = 'attendance_leaves/ajax_my_leave_events';
$route['attendance/ajax_cancel_leave']      = 'attendance_leaves/ajax_cancel_leave';
$route['attendance/ajax_user_leave_history'] = 'attendance_leaves/ajax_user_leave_history';

$route['lead-form']   = 'crm/public_leads/form';
$route['lead-submit'] = 'crm/public_leads/submit';


// =========================================================
// EMPLOYEE EVALUATIONS
// =========================================================
// -- Employee Self View (must be before evaluations/my) --
$route['evaluations/my/view/(:num)']               = 'evaluations/Evaluations/my_view/$1';
$route['evaluations/my']                           = 'evaluations/Evaluations/my';
// Index
$route['evaluations']                              = 'evaluations/Evaluations/index';
// -- Evaluations CRUD --
$route['evaluations/create']                       = 'evaluations/Evaluations/create';
$route['evaluations/fill/(:num)/(:num)']           = 'evaluations/Evaluations/fill/$1/$2';
$route['evaluations/edit/(:num)']                  = 'evaluations/Evaluations/edit/$1';
$route['evaluations/view/(:num)']                  = 'evaluations/Evaluations/view/$1';
$route['evaluations/delete/(:num)']                = 'evaluations/Evaluations/delete/$1';
// -- Evaluation workflow --
$route['evaluations/submit/(:num)']                = 'evaluations/Evaluations/submit/$1';
$route['evaluations/approve/(:num)']               = 'evaluations/Evaluations/approve/$1';
$route['evaluations/reject/(:num)']                = 'evaluations/Evaluations/reject/$1';
// -- AJAX: evaluation data --
$route['evaluations/get_eval_json/(:num)']         = 'evaluations/Evaluations/get_eval_json/$1';
$route['evaluations/employee_history/(:num)']      = 'evaluations/Evaluations/employee_history/$1';
// -- Templates --
$route['evaluations/templates']                    = 'evaluations/Evaluations/templates';
$route['evaluations/template_create']              = 'evaluations/Evaluations/template_create';
$route['evaluations/template_edit/(:num)']         = 'evaluations/Evaluations/template_edit/$1';
$route['evaluations/template_delete/(:num)']       = 'evaluations/Evaluations/template_delete/$1';
$route['evaluations/template_toggle/(:num)']       = 'evaluations/Evaluations/template_toggle/$1';
$route['evaluations/template_json/(:num)']         = 'evaluations/Evaluations/template_json/$1';
// -- Sections (AJAX) --
$route['evaluations/section_store']                = 'evaluations/Evaluations/section_store';
$route['evaluations/section_update/(:num)']        = 'evaluations/Evaluations/section_update/$1';
$route['evaluations/section_delete/(:num)']        = 'evaluations/Evaluations/section_delete/$1';
$route['evaluations/section_criteria_json/(:num)'] = 'evaluations/Evaluations/section_criteria_json/$1';
// -- Criteria (AJAX) --
$route['evaluations/criteria_store']               = 'evaluations/Evaluations/criteria_store';
$route['evaluations/criteria_update/(:num)']       = 'evaluations/Evaluations/criteria_update/$1';
$route['evaluations/criteria_delete/(:num)']       = 'evaluations/Evaluations/criteria_delete/$1';


// In config/routes.php
$route['team_chat/api/(:any)/(:any)/(:any)'] = 'team_chat/api/$1/$2/$3';
$route['team_chat/api/(:any)/(:any)']        = 'team_chat/api/$1/$2';
$route['team_chat/api/(:any)']               = 'team_chat/api/$1';

/*
| -------------------------------------------------------------------------
| NOTES FOR FUTURE ENHANCEMENTS
| -------------------------------------------------------------------------
| 1. Group module‐specific routes in separate comments/sections for clarity.
| 2. Use “translate_uri_dashes = TRUE” only if you adopt dashed-URLs for
|    controllers and methods (e.g. my-controller/my-method → My_controller::my_method).
| 3. Add role‐based or permission‐based routing in hooks or custom router class,
|    if you need to restrict certain controllers to specific user roles.
| 4. When implementing versioned API, prefix routes with ‘api/v1/’, etc.
| 5. If you switch to CodeIgniter 4 in the future, migrate to the new routing syntax accordingly.
*/


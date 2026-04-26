<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Controller → Method → Permission Action Map
|--------------------------------------------------------------------------
| Map each controller’s methods to the action keys you defined in
| your role_permissions JSON (view_global, view_own, view_team, create, edit, delete).
|
| Make sure the class names and method names exactly match your controllers.
*/

$config['permissions_map'] = [

    'Leaves' => [
        'index'  => 'view_own',    // list your own leaves
        'team'   => 'view_team',   // list team leaves (if you have a `team()` method)
        'all'    => 'view_global', // list all leaves (if you have an `all()` or similar)
        'create' => 'create',
        'edit'   => 'edit',
        'delete' => 'delete',
    ],

    'Attendance' => [
        'index'  => 'view_own',
        'team'   => 'view_team',
        'all'    => 'view_global',
        'create' => 'create',
        'edit'   => 'edit',
        'delete' => 'delete',
    ],

    'Signoff' => [
        'index'  => 'view_own',
        'team'   => 'view_team',
        'all'    => 'view_global',
        'create' => 'create',
        'edit'   => 'edit',
        'delete' => 'delete',
    ],

    'Goals' => [
        'index'  => 'view_own',
        'team'   => 'view_team',
        'all'    => 'view_global',
        'create' => 'create',
        'edit'   => 'edit',
        'delete' => 'delete',
    ],

    'Tasks' => [
        'index'  => 'view_own',
        'team'   => 'view_team',
        'all'    => 'view_global',
        'create' => 'create',
        'edit'   => 'edit',
        'delete' => 'delete',
    ],

    'Users' => [
        'index'  => 'view_global',
        'view'   => 'view_global', // if you have a detail `view($id)` method
        'create' => 'create',
        'edit'   => 'edit',
        'delete' => 'delete',
    ],

    'Permissions' => [
        'index'    => 'view_global', // show the permissions page
        'save'     => 'edit',        // your form-post handler (rename `save` to your actual method)
    ],

    'Settings' => [
        'index'       => 'view_global', // show settings
        'save_group'  => 'edit',        // your POST handler (e.g. saving each group)
    ],

];

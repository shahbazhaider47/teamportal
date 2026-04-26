<?php
defined('BASEPATH') OR exit('No direct script access allowed');


if (!function_exists('requests_registry')) {
    function requests_registry(): array
    {
        return [

            /* ----------------------------------------------------------
             | INVENTORY REQUEST
             |----------------------------------------------------------*/
            'inventory_request' => [
                'slug'         => 'inventory_request',
                'label'        => 'Inventory Request',
                'description'  => 'Request inventory or assets',
                'icon'         => 'ti ti-box',
                'form_view'    => 'requests/forms/inventory_request',
                'section_slug' => 'inventory',
            ],

            /* ----------------------------------------------------------
             | LEAVE REQUEST
             |----------------------------------------------------------*/
            'leave_request' => [
                'slug'         => 'leave_request',
                'label'        => 'Leave Request',
                'description'  => 'Apply for leave or time off',
                'icon'         => 'ti ti-calendar-time',
                'form_view'    => 'requests/forms/leave_request',
                'section_slug' => 'leave',
            ],

        ];
    }
}


function get_request_types(): array
{
    return requests_registry();
}

function get_request_type(string $slug): ?array
{
    $types = requests_registry();
    return $types[$slug] ?? null;
}

function is_valid_request_type(string $slug): bool
{
    return get_request_type($slug) !== null;
}

if (!function_exists('resolve_leave_approvers')) {
    function resolve_leave_approvers(int $requesterId): array
    {
        $CI =& get_instance();

        $approverType = get_company_setting('att_leave_approver', 'manager');
        $approvers = [];

        switch ($approverType) {

            case 'auto':
                // Auto-approval → notify requester only
                $approvers[] = $requesterId;
                break;

            case 'director':
                // Director = role-based
                $directorRoleId = $CI->db
                    ->select('id')
                    ->from('roles')
                    ->where('slug', 'director')
                    ->limit(1)
                    ->get()
                    ->row('id');

                if ($directorRoleId) {
                    $rows = $CI->db
                        ->select('id')
                        ->from('users')
                        ->where('user_role', (int)$directorRoleId)
                        ->where('is_active', 1)
                        ->get()
                        ->result_array();

                    foreach ($rows as $r) {
                        $approvers[] = (int)$r['id'];
                    }
                }
                break;

            case 'hr':
                // HR = department slug = human_resources
                $dept = $CI->db
                    ->select('hod')
                    ->from('departments')
                    ->where('slug', 'human_resources')
                    ->limit(1)
                    ->get()
                    ->row_array();

                if (!empty($dept['hod'])) {
                    $hodIds = json_decode($dept['hod'], true) ?: [$dept['hod']];
                    foreach ((array)$hodIds as $id) {
                        $approvers[] = (int)$id;
                    }
                }
                break;

            case 'manager':
            case 'teamlead':
                $team = $CI->db
                    ->select('manager_id, teamlead_id')
                    ->from('teams')
                    ->where('id', function () use ($CI, $requesterId) {
                        return $CI->db
                            ->select('emp_team')
                            ->from('users')
                            ->where('id', $requesterId)
                            ->limit(1)
                            ->get_compiled_select();
                    }, false)
                    ->get()
                    ->row_array();

                if ($approverType === 'manager' && !empty($team['manager_id'])) {
                    $approvers[] = (int)$team['manager_id'];
                }

                if ($approverType === 'teamlead' && !empty($team['teamlead_id'])) {
                    $approvers[] = (int)$team['teamlead_id'];
                }
                break;
        }

        return array_values(array_unique(array_filter($approvers)));
    }
}


if (!function_exists('notify_leave_approver')) {
    function notify_leave_approver(
        int $userId,
        array $leave,
        string $event = 'submitted'
    ): void {
        $CI =& get_instance();

        $user = $CI->users->get_user_by_id($userId);
        if (!$user) return;

        $url = base_url('requests/view/' . ($leave['request_no'] ?? ''));

        // In-app
        if (function_exists('notify_user')) {
            notify_user(
                $userId,
                'attendance',
                'Leave Request Submitted',
                'A new leave request requires your attention.',
                $url,
                ['channels' => ['in_app']]
            );
        }

        // Email
        if (!function_exists('app_mailer') || empty($user['email'])) return;

        $view = $event === 'approved'
            ? 'emails/templates/leave_approved'
            : 'emails/templates/leave_rejected';

        app_mailer()->send([
            'to'        => $user['email'],
            'subject'   => 'Leave Request Notification',
            'view'      => $view,
            'view_data' => [
                'recipient_name' => $user['fullname'] ?? 'there',
                'leave'          => $leave,
                'cta_url'        => $url,
                'brand'          => get_setting('companyname'),
            ],
        ]);
    }
}

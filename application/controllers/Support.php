<?php defined('BASEPATH') or exit('No direct script access allowed');

class Support extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Support_tickets_model', 'tickets');
        $this->load->model('Support_posts_model',   'posts');
        $this->load->model('User_model',                    'users');   // central user model
        $this->load->model('Emails_model',                  'emails');  // ← add
        $this->load->library('Merge_fields', null, 'merge_fields');     // ← add (unified manager)
        $this->load->library('App_mailer', null, 'app_mailer');

        $this->load->library(['form_validation', 'session']);
        $this->load->helper(['url', 'form', 'file']); // file for is_dir, mkdir, etc.

        // Load module policy (policy is now available as $this->policy)
        $this->load->library('Support_policy', null, 'policy');
    }

    /* ---------------------------
     * List / Manage
     * --------------------------- */
    public function index()
    {
        $this->guard_view(); // legacy coarse guard still applies for listing

        // Filters: limit list to "own" tickets if user lacks view_global
        $filters = [];
        if (!function_exists('staff_can') || !staff_can('view_global', 'support')) {
            $filters['requester_id'] = (int)$this->session->userdata('user_id');
        }

        // Optional query params for AJAX
        $limit  = (int)($this->input->get('limit')  ?? 100);
        $offset = (int)($this->input->get('offset') ?? 0);
        $q      = trim((string)($this->input->get('q') ?? ''));
        if ($q !== '') $filters['q'] = $q;

        $rows = $this->tickets->list_tickets($filters, $limit, $offset, ['last_activity_at' => 'DESC']);

        // Enrich with names/avatars (dept + users)
        $deptMap = $this->tickets->get_departments_map();

        $needUserIds = [];
        foreach ($rows as $r) {
            if (!empty($r['requester_id'])) $needUserIds[(int)$r['requester_id']] = true;
            if (!empty($r['assignee_id']))  $needUserIds[(int)$r['assignee_id']]  = true;
        }
        $userMap = $this->users->get_map_by_ids(array_keys($needUserIds)); // single user fetch

        foreach ($rows as &$r) {
            $rid = (int)($r['requester_id'] ?? 0);
            $aid = (int)($r['assignee_id']  ?? 0);

            $r['department_name']  = $deptMap[(int)$r['department_id']] ?? null;

            $r['requester_name']   = ($rid && isset($userMap[$rid])) ? ($userMap[$rid]['name'] ?? null)   : null;
            $r['requester_avatar'] = ($rid && isset($userMap[$rid])) ? ($userMap[$rid]['avatar'] ?? null) : null;

            $r['assignee_name']    = ($aid && isset($userMap[$aid])) ? ($userMap[$aid]['name'] ?? null)    : null;
            $r['assignee_avatar']  = ($aid && isset($userMap[$aid])) ? ($userMap[$aid]['avatar'] ?? null)  : null;
        }
        unset($r);

        // AJAX
        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'data'    => $rows,
                    'limit'   => $limit,
                    'offset'  => $offset,
                ]));
            return;
        }

        // Normal page
        $layout_data = [
            'page_title' => 'Support',
            'subview'    => 'support/manage',
            'view_data'  => [
                'page_title' => 'Support Tickets',
                'tickets'    => $rows,
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /* ---------------------------
     * Create
     * --------------------------- */
    public function create()
    {
        $this->guard('create'); // coarse legacy check; creation is not ticket-scoped

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // Validate posted fields (use emp_department because the view uses it)
            $this->form_validation->set_rules('subject',        'Subject',     'required|min_length[3]|trim');
            $this->form_validation->set_rules('emp_department', 'Department',  'required|integer|greater_than[0]');
            $this->form_validation->set_rules('priority',       'Priority',    'in_list[low,normal,high,urgent]');

            if ($this->form_validation->run() === false) {
                set_alert('danger', strip_tags(validation_errors()));
                redirect('support/create');
                return;
            }

            $uid   = (int)$this->session->userdata('user_id');
            $reqId = (int)$this->input->post('requester_id'); // may be missing if user can’t choose
            if ($reqId <= 0) { $reqId = $uid; }

            // Department selection rule: staff allowed to open to all departments?
            $department_id = (int)$this->input->post('emp_department');
            if (get_setting('support_staff_can_open_all_departments', 'yes') !== 'yes') {
                if (function_exists('get_user_department_ids')) {
                    $allowedDeptIds = (array)get_user_department_ids($uid);
                    if (!in_array($department_id, $allowedDeptIds, true)) {
                        set_alert('danger', 'You are not allowed to open tickets for this department.');
                        redirect('support/create');
                        return;
                    }
                }
            }

            // Normalize watchers (array of ints) — model will JSON encode
            $watchers = $this->input->post('watchers');
            if (is_array($watchers)) {
                $watchers = array_values(array_filter(array_map('intval', $watchers), static fn($v) => $v > 0));
            } else {
                $watchers = null;
            }

            $ticket = [
                'subject'       => (string)$this->input->post('subject', true),
                'department_id' => $department_id,
                'requester_id'  => $reqId,
                'priority'      => (string)($this->input->post('priority', true) ?: 'normal'),
                'tags'          => $this->parse_tags($this->input->post('tags', true)),
                'watchers'      => $watchers,
            ];

            // Gather attachments now (saves into /uploads/support) — count/extension limits enforced inside
            $attachments = $this->collect_attachments_from_request();

            // Body from rich-text editor; do not xss_clean here
            $first_post = [
                'body'        => (string)$this->input->post('body', false),
                'attachments' => $attachments,
            ];

            try {
                // Aligns with Support_tickets_model::create(array $ticket, array $first_post)
                $ticket_id = (int)$this->tickets->create($ticket, $first_post);
            } catch (Exception $e) {
                log_message('error', 'Support create failed: ' . $e->getMessage());
                set_alert('danger', 'Failed to create ticket.');
                redirect('support/create');
                return;
            }

            // === Emails & Notifications (Creation) ===
            // === Emails & Notifications (Creation) ===
            // Send via email template: support-ticket-created - This was new added for send ticket email template
            $this->send_support_template_email_by_slug(
                'support-ticket-created',
                (int)$reqId,
                [
                    'company'    => true,
                    'user_id'    => (int)$reqId,
                    'ticket_id'  => (int)$ticket_id,
                    // add more context if you later need: 'project_id'=>..., 'signoff_id'=>...
                ],
                // Fallbacks if template missing/inactive (subject, minimal HTML)
                'Ticket Created: ' . (string)($ticket['subject'] ?? ('#'.$ticket_id)),
                $this->render_simple_ticket_email(
                    'Your support ticket has been created successfully.',
                    (string)($ticket['subject'] ?? ('#'.$ticket_id)),
                    base_url('support/view/' . (int)$ticket_id)
                )
            );


// === Emails & Notifications (Creation) ===
if ($this->setting_yes('support_email_user_on_create')) {
    $this->send_ticket_email($ticket_id, 'created');
}

// === Department Head emails & notifications (Creation) ===
$deptHeadIds = $this->get_department_head_ids($department_id);

// Don't notify the requester as HOD if they are the same person
$deptHeadIds = array_values(array_unique(array_filter(
    array_map('intval', $deptHeadIds),
    fn($v) => $v > 0 && $v !== (int)$reqId
)));

if (!empty($deptHeadIds)) {
    $ticketUrlInternal = base_url('support/view/' . (int)$ticket_id);
    $subjectLine = (string)($ticket['subject'] ?? ('#'.$ticket_id));
    $brand = function_exists('get_setting') ? (get_setting('companyname') ?: 'Support') : 'Support';

    // Email to HOD(s)
    if ($this->setting_yes('support_email_dept_on_create')) {
        foreach ($deptHeadIds as $hodId) {
            $user   = $this->users->get_user_by_id((int)$hodId);
            $to     = trim((string)($user['email'] ?? ''));
            if ($to === '' || !function_exists('app_mailer')) { continue; }

            $html = $this->render_simple_ticket_email(
                'A new ticket has been created in your department.',
                $subjectLine,
                $ticketUrlInternal
            );
            app_mailer()->send([
                'to'       => $to,
                'subject'  => 'New ticket in your department: ' . $subjectLine,
                'message'  => $html,
                'mailtype' => 'html',
            ]);
        }
    }

    // In-app notification to HOD(s)
    if ($this->setting_yes('support_notify_dept_on_create') && function_exists('notify_user')) {
        foreach ($deptHeadIds as $hodId) {
            notify_user(
                (int)$hodId,
                'support',
                'New ticket in your department',
                'A new ticket was created for your department: "' . $subjectLine . '".',
                $ticketUrlInternal,
                ['channels' => ['in_app']]
            );
        }
    }
}

// Requester notifications on create
if (function_exists('notify_user') && $this->setting_yes('support_notify_user_on_create')) {
    $ticketUrl = base_url('support/view/' . $ticket_id);
    $subject   = $ticket['subject'] ?? ('#'.$ticket_id);
    notify_user(
        $reqId,
        'support',
        'Support ticket created',
        'Your ticket "' . $subject . '" has been created.',
        $ticketUrl,
        ['channels' => ['in_app']]
    );
}


            set_alert('success', 'Ticket created successfully.');
            redirect('support/view/' . $ticket_id);
            return;
        }

        // GET
        $layout_data = [
            'page_title' => 'Create Ticket',
            'subview'    => 'support/modals/create_ticket',
            'view_data'  => [
                'departments'      => $this->tickets->get_departments_rows(),
                'users_minimal'    => $this->users->get_active_minimal_list(),
                'current_user_id'  => (int)$this->session->userdata('user_id'),
                'recent_tickets'   => $this->tickets->list_tickets(
                    ['requester_id' => (int)$this->session->userdata('user_id')],
                    10,
                    0,
                    ['last_activity_at' => 'DESC']
                ),
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Send ticket lifecycle emails to the requester via app_mailer.
     * $event: 'created' | 'closed'
     * (Original behavior preserved.)
     */
    private function send_ticket_email(int $ticketId, string $event): void
    {
        if (!function_exists('app_mailer')) { return; } // hard guard

        // Fetch ticket (without posts for speed)
        $t = $this->tickets->find($ticketId, false);
        if (!$t || empty($t['requester_id'])) { return; }

        // Fetch requester (needs email)
        $requester = $this->users->get_user_by_id((int)$t['requester_id']);
        $toEmail   = trim((string)($requester['email'] ?? ''));
        $fullName  = trim(((string)($requester['firstname'] ?? '')).' '.((string)($requester['lastname'] ?? '')));
        if ($toEmail === '') { return; }

        // Common vars
        $ticketUrl = base_url('support/view/' . $ticketId);
        $subject   = (string)($t['subject'] ?? ('#'.$ticketId));
        $brand     = function_exists('get_setting') ? (get_setting('companyname') ?: 'Support') : 'Support';

        // Pick template + subject by event
        if ($event === 'created') {
            $emailSubject = 'Ticket Created: ' . $subject;
            $view        = 'emails/support/ticket_created_html';
            $viewData    = [
                'recipient_name' => $fullName ?: 'there',
                'ticket_subject' => $subject,
                'ticket_code'    => (string)($t['code'] ?? ''),
                'ticket_url'     => $ticketUrl,
                'brand'          => $brand,
            ];
        } elseif ($event === 'closed') {
            $emailSubject = 'Ticket Closed: ' . $subject;
            $view        = 'emails/support/ticket_closed_html';
            $viewData    = [
                'recipient_name' => $fullName ?: 'there',
                'ticket_subject' => $subject,
                'ticket_code'    => (string)($t['code'] ?? ''),
                'ticket_url'     => $ticketUrl,
                'brand'          => $brand,
            ];
        } else {
            return; // unsupported event
        }

        // Fire email (HTML template) via App_mailer
        app_mailer()->send([
            'to'        => $toEmail,
            'subject'   => $emailSubject,
            'view'      => $view,       // HTML template
            'view_data' => $viewData,
        ]);
    }

    /* ---------------------------
     * View
     * --------------------------- */
    public function view($id)
    {
        $id = (int)$id;
        $ticket = $this->tickets->find($id, true);

        if (!$ticket) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        // Policy-based view guard
        $uid = (int)$this->session->userdata('user_id');
        if (!$this->policy->can_view($ticket, $uid)) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        // Department name
        $deptMap = $this->tickets->get_departments_map();
        $ticket['department_name'] = isset($deptMap[(int)$ticket['department_id']])
            ? $deptMap[(int)$ticket['department_id']]
            : '#' . (int)$ticket['department_id'];

        // Collect user IDs for mapping
        $userIds = [];
        if (!empty($ticket['requester_id'])) $userIds[] = (int)$ticket['requester_id'];
        if (!empty($ticket['assignee_id']))  $userIds[] = (int)$ticket['assignee_id'];

        if (!empty($ticket['posts']) && is_array($ticket['posts'])) {
            foreach ($ticket['posts'] as $p) {
                if (!empty($p['author_id'])) $userIds[] = (int)$p['author_id'];
            }
        }

        // --- WATCHERS: include them in user resolution set
        $watcherIds = [];
        if (!empty($ticket['watchers']) && is_array($ticket['watchers'])) {
            foreach ($ticket['watchers'] as $wid) {
                $wid = (int)$wid;
                if ($wid > 0) {
                    $watcherIds[] = $wid;
                    $userIds[]    = $wid;
                }
            }
        }

        // De-duplicate and map
        $userIds = array_values(array_unique(array_filter($userIds, static fn($v) => (int)$v > 0)));
        $userMap = $this->users->get_map_by_ids($userIds);

        // Enrich names (and make avatars available for view if needed)
        $ticket['requester_name']   = (!empty($ticket['requester_id']) && isset($userMap[(int)$ticket['requester_id']])) ? $userMap[(int)$ticket['requester_id']]['name']   : null;
        $ticket['requester_avatar'] = (!empty($ticket['requester_id']) && isset($userMap[(int)$ticket['requester_id']])) ? $userMap[(int)$ticket['requester_id']]['avatar'] : null;
        $ticket['assignee_name']    = (!empty($ticket['assignee_id'])  && isset($userMap[(int)$ticket['assignee_id']]))  ? $userMap[(int)$ticket['assignee_id']]['name']    : null;
        $ticket['assignee_avatar']  = (!empty($ticket['assignee_id'])  && isset($userMap[(int)$ticket['assignee_id']]))  ? $userMap[(int)$ticket['assignee_id']]['avatar']  : null;

        if (!empty($ticket['posts']) && is_array($ticket['posts'])) {
            foreach ($ticket['posts'] as &$p) {
                $aid = (int)($p['author_id'] ?? 0);
                if ($aid && isset($userMap[$aid])) {
                    $p['author_name']   = $userMap[$aid]['name'];
                    $p['author_avatar'] = $userMap[$aid]['avatar'];
                }
            }
            unset($p);
        }

        // --- WATCHERS: build resolved watchers array for the view
        $ticket['watchers_resolved'] = [];
        if (!empty($watcherIds)) {
            foreach ($watcherIds as $wid) {
                if (isset($userMap[$wid])) {
                    $ticket['watchers_resolved'][] = [
                        'id'     => $wid,
                        'name'   => $userMap[$wid]['name']   ?? ('User #'.$wid),
                        'avatar' => $userMap[$wid]['avatar'] ?? null,
                    ];
                } else {
                    $ticket['watchers_resolved'][] = [
                        'id'     => $wid,
                        'name'   => 'User #'.$wid,
                        'avatar' => null,
                    ];
                }
            }
        }

        // Active users for Assign dropdown
        $active_users = $this->users->get_active_minimal_list();

        // Build capability flags for the view
        $perm = [
            'can_assign'          => $this->policy->can_assign($ticket, $uid),
            'can_change_status'   => $this->policy->can_change_status($ticket, $uid),
            'can_manage_watchers' => $this->policy->can_manage_watchers($ticket, $uid),
            'can_see_notes'       => $this->policy->can_see_internal_notes($ticket, $uid),
            'role_rank'           => $this->policy->role_rank($ticket, $uid),
        ];

        $view_data = [
            'ticket'              => $ticket,
            'active_users'        => $active_users,
            'is_assignee'         => $this->policy->is_assignee($ticket, $uid),
            'canAssign'           => $perm['can_assign'],
            'canManageWatchers'   => $perm['can_manage_watchers'],
            'canChangeStatus'     => $perm['can_change_status'],
            'canSeeNotes'         => $perm['can_see_notes'],
            'role_rank'           => $perm['role_rank'],
            'watcherWho'          => get_setting('support_user_added_watchers', 'both'),
        ];

        $this->load->view('layouts/master', [
            'page_title' => 'View Ticket',
            'title'      => 'Support Ticket',
            'subview'    => 'support/view',
            'view_data'  => $view_data,
        ]);
    }

    /* ---------------------------
     * Post reply / note
     * --------------------------- */
public function post($id)
{
    $id = (int)$id;
    $ticket = $this->tickets->find($id, false);
    if (!$ticket) show_404();

    $uid = (int)$this->session->userdata('user_id');

    // Must be able to view the ticket
    if (!$this->policy->can_view($ticket, $uid)) {
        show_error('Forbidden', 403);
        return;
    }

    // Determine intent
    $type = $this->input->post('type', true) === 'note' ? 'note' : 'message';

    // Who is acting?
    $isStaff     = function_exists('is_staff_logged_in') ? (bool)is_staff_logged_in() : false;
    $isRequester = (int)($ticket['requester_id'] ?? 0) === $uid;
    $isAssignee  = (int)($ticket['assignee_id']  ?? 0) === $uid;

    // Requester cannot add notes
    //if ($type === 'note' && $isRequester) {
        //set_alert('danger', 'Requesters cannot add notes.');
        //redirect('support/view/' . $id);
        //return;
    //}

    // Basic validation
    $body = (string)$this->input->post('body', false);
    if (trim($body) === '') {
        set_alert('danger', 'Message body is required.');
        redirect('support/view/' . $id);
        return;
    }

    $attachments = $this->collect_attachments_from_request();

    if ($type === 'note') {
        // Private internal note (only creator will see in the UI per view logic)
        $this->posts->add_note($id, $uid, $body, $attachments);
        set_alert('success', 'Note added.');
        redirect('support/view/' . $id);
        return;
    }

    // Regular ticket reply (message)
    $this->posts->add_message($id, $uid, $body, $attachments, $isStaff);

    // Auto-assign first replier if enabled and no assignee yet
    if (($ticket['assignee_id'] ?? null) === null && $this->setting_yes('support_auto_assign_first_replier')) {
        $this->tickets->assign($id, $uid);
    }

    // Default status on reply
    $statusOnReply = (string)(get_setting('support_default_status_on_reply') ?: 'in_progress');
    $this->tickets->set_status($id, $statusOnReply);

    // In-app/email notifications to Requester on reply (respect settings)
    $ticketUrl = base_url('support/view/' . (int)$id);
    $subject   = $ticket['subject'] ?? ('#' . (int)$id);

    if (!empty($ticket['requester_id'])) {
        if (function_exists('notify_user') && $this->setting_yes('support_notify_user_on_reply')) {
            notify_user(
                (int)$ticket['requester_id'],
                'support',
                'New reply on your ticket',
                'A new reply was posted on your ticket "' . $subject . '".',
                $ticketUrl
            );
        }
        if ($this->setting_yes('support_email_user_on_reply')) {
            $this->send_ticket_email_bare(
                (int)$ticket['requester_id'],
                'New reply: ' . $subject,
                $this->render_simple_ticket_email(
                    'A new reply was posted on your ticket.',
                    $subject,
                    $ticketUrl
                )
            );
        }
    }

    // (Optional) keep assignee in-app ping (no setting was specified for this one)
    if (!empty($ticket['assignee_id']) && (int)$ticket['assignee_id'] !== (int)$uid && function_exists('notify_user')) {
        notify_user(
            (int)$ticket['assignee_id'],
            'support',
            'New activity on assigned ticket',
            'A new reply was posted on a ticket assigned to you: "' . $subject . '".',
            $ticketUrl
        );
    }

    set_alert('success', 'Reply posted.');
    redirect('support/view/' . $id);
}


    /* ---------------------------
     * Assign / Status / Delete / Watchers
     * --------------------------- */
public function assign($id)
{
    $id = (int)$id;
    $ticket = $this->tickets->find($id, false);
    if (!$ticket) show_404();

    $uid = (int)$this->session->userdata('user_id');
    if (!$this->policy->can_assign($ticket, $uid)) {
        show_error('Forbidden', 403);
        return;
    }

    $assignee_id = (int)$this->input->post('assignee_id');
    $ok = $this->tickets->assign($id, $assignee_id);

    if ($ok) {
        $ticketUrl = base_url('support/view/' . (int)$id);
        $t = $this->tickets->find($id, false);
        $subject = $t['subject'] ?? ('#'.$id);

        // In-app notification to assignee (setting)
        if ($assignee_id && function_exists('notify_user') && $this->setting_yes('support_notify_user_on_assigned')) {
            notify_user(
                $assignee_id,
                'support',
                'Ticket assigned to you',
                'You were assigned a new support ticket "'.$subject.'".',
                $ticketUrl
            );
        }

        // Email to assignee (setting)
        if ($assignee_id && $this->setting_yes('support_email_user_on_assigned')) {
            $this->send_ticket_email_bare(
                (int)$assignee_id,
                'Ticket assigned: ' . $subject,
                $this->render_simple_ticket_email(
                    'A ticket has been assigned to you.',
                    $subject,
                    $ticketUrl
                )
            );
        }

        set_alert('success', 'Ticket assigned successfully.');
    } else {
        set_alert('danger', 'Failed to assign the ticket.');
    }
    redirect('support/view/' . $id);
}


public function status($id)
{
    $id = (int)$id;
    $ticket = $this->tickets->find($id, false);
    if (!$ticket) show_404();

    $uid = (int)$this->session->userdata('user_id');
    if (!$this->policy->can_change_status($ticket, $uid)) {
        show_error('Forbidden', 403);
        return;
    }

    $status = $this->input->post('status', true);
    $ok = $this->tickets->set_status($id, $status);

    if ($ok) {
        $ticketUrl = base_url('support/view/' . (int)$id);
        $t = $this->tickets->find($id, false);
        $subject = $t['subject'] ?? ('#'.$id);

        // Email to requester for any status change (respect setting)
        if (!empty($t['requester_id']) && $this->setting_yes('support_email_user_on_status_change')) {
            if (strtolower((string)$status) === 'closed') {
                // use dedicated template for close (your existing method)
                $this->send_ticket_email($id, 'closed');
            } else {
                // generic status change email
                $this->send_ticket_status_email($id, (string)$status);
            }
        }

        // In-app notify requester (respect setting)
        if (!empty($t['requester_id']) && function_exists('notify_user') && $this->setting_yes('support_notify_user_on_status_change')) {
            notify_user(
                (int)$t['requester_id'],
                'support',
                'Ticket status updated',
                'Your ticket for "'.$subject.'" is '.str_replace('_',' ', strtolower((string)$status)).'.',
                $ticketUrl,
                ['channels' => ['in_app']]
            );
        }

        set_alert('success', 'Ticket status updated successfully.');
    } else {
        set_alert('danger', 'Failed to update the ticket status.');
    }
    redirect('support/view/' . $id);
}


/**
 * Send a simple status-change email for non-closed statuses.
 */
private function send_ticket_status_email(int $ticketId, string $newStatus): void
{
    $t = $this->tickets->find($ticketId, false);
    if (!$t || empty($t['requester_id'])) return;

    $requesterId = (int)$t['requester_id'];
    $ticketUrl   = base_url('support/view/' . $ticketId);
    $subject     = (string)($t['subject'] ?? ('#'.$ticketId));
    $cleanStatus = str_replace('_', ' ', strtolower(trim($newStatus)));

    $this->send_ticket_email_bare(
        $requesterId,
        'Ticket status updated: ' . $subject,
        $this->render_simple_ticket_email(
            'Your ticket status has changed to: ' . ucfirst($cleanStatus) . '.',
            $subject,
            $ticketUrl
        )
    );
}

    public function delete($id)
    {
        // Deletion is a broad capability; keep your coarse guard
        $this->guard('delete');

        $id = (int)$id;
        $ok = $this->tickets->delete_ticket($id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Ticket deleted successfully.' : 'Delete failed.');
        redirect('support');
    }

    // Add watcher
    public function add_watcher($id)
    {
        $id  = (int)$id;
        $ticket = $this->tickets->find($id, false);
        if (!$ticket) show_404();
    
        $uid = (int)$this->session->userdata('user_id');
    
        // Policy: who can manage watchers
        if (!$this->policy->can_manage_watchers($ticket, $uid)) {
            set_alert('danger', 'You are not allowed to add watchers to this ticket.');
            redirect('support/view/' . $id);
            return;
        }
    
        $watchUserId = (int)$this->input->post('user_id');
        $ok = $this->tickets->add_watcher($id, $watchUserId);
    
        if ($ok) {
            $ticketUrlInternal = base_url('support/view/' . (int)$id);
            $subject = $ticket['subject'] ?? ('#'.$id);
    
            // --- Email (if enabled)
            if ($this->setting_yes('support_email_user_on_added_watcher')) {
                $this->send_ticket_email_bare(
                    $watchUserId,
                    'You were added as a watcher: ' . $subject,
                    $this->render_simple_ticket_email(
                        'You have been added as a watcher to this ticket.',
                        $subject,
                        $ticketUrlInternal
                    )
                );
            }
    
            // --- In-app notification (if enabled)
            if ($this->setting_yes('support_notify_user_on_added_watcher') && function_exists('notify_user')) {
                notify_user(
                    (int)$watchUserId,
                    'support',
                    'Watch this ticket',
                    'You were added as a watcher to the ticket: "' . $subject . '".',
                    $ticketUrlInternal,
                    ['channels' => ['in_app']]
                );
            }
    
            set_alert('success', 'New watcher added to this ticket.');
        } else {
            set_alert('danger', 'Failed to add watcher.');
        }
        redirect('support/view/' . $id);
    }

    // Remove watcher
public function remove_watcher($id)
{
    $id = (int)$id;
    $ticket = $this->tickets->find($id, false);
    if (!$ticket) show_404();

    $uid = (int)$this->session->userdata('user_id');

    if (!$this->policy->can_manage_watchers($ticket, $uid)) {
        set_alert('danger', 'You are not allowed to remove watchers from this ticket.');
        redirect('support/view/' . $id);
        return;
    }

    $watchUserId = (int)$this->input->post('user_id');
    $ok = $this->tickets->remove_watcher($id, $watchUserId);

    if ($ok) {
        $ticketUrlInternal = base_url('support/view/' . (int)$id);
        $subject = $ticket['subject'] ?? ('#'.$id);

        // Email (if enabled)
        if ($this->setting_yes('support_email_user_on_remove_watcher')) {
            $this->send_ticket_email_bare(
                $watchUserId,
                'Removed as watcher: ' . $subject,
                $this->render_simple_ticket_email(
                    'You have been removed as a watcher from this ticket.',
                    $subject,
                    $ticketUrlInternal
                )
            );
        }

// In-app notification (if enabled)
if ($this->setting_yes('support_notify_user_on_remove_watcher') && function_exists('notify_user')) {
    notify_user(
        (int) $watchUserId,
        'support',
        'Removed as watcher',
        'You were removed as a watcher from the ticket: "' . $subject . '".',
        null, // ← No URL when removed as watcher
        ['channels' => ['in_app']]
    );
}


        set_alert('success', 'Watcher removed from this ticket.');
    } else {
        set_alert('danger', 'Failed to remove watcher.');
    }

    redirect('support/view/' . $id);
}

    // List only "watched" tickets for current user
    public function watching()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { redirect('authentication/login'); return; }

        $limit  = (int)($this->input->get('limit')  ?? 100);
        $offset = (int)($this->input->get('offset') ?? 0);

        // tickets where current user is a watcher (model method exists)
        $rows = $this->tickets->list_watched_by_user($uid, $limit, $offset);

        // dept + requester/assignee like index()
        $deptMap = $this->tickets->get_departments_map();

        $needUserIds = [];
        $allWatcherIds = [];
        foreach ($rows as $r) {
            if (!empty($r['requester_id'])) $needUserIds[(int)$r['requester_id']] = true;
            if (!empty($r['assignee_id']))  $needUserIds[(int)$r['assignee_id']]  = true;

            $ws = $r['watchers'] ?? [];
            if (is_string($ws)) { $ws = json_decode($ws, true) ?: []; }
            foreach ($ws as $wid) { $wid = (int)$wid; if ($wid > 0) { $allWatcherIds[$wid] = true; } }
        }
        $userMap = $this->users->get_map_by_ids(array_keys($needUserIds + $allWatcherIds));

        foreach ($rows as &$r) {
            $rid = (int)($r['requester_id'] ?? 0);
            $aid = (int)($r['assignee_id']  ?? 0);

            $r['department_name']  = $deptMap[(int)$r['department_id']] ?? null;
            $r['requester_name']   = ($rid && isset($userMap[$rid])) ? ($userMap[$rid]['name'] ?? null)   : null;
            $r['requester_avatar'] = ($rid && isset($userMap[$rid])) ? ($userMap[$rid]['avatar'] ?? null) : null;
            $r['assignee_name']    = ($aid && isset($userMap[$aid])) ? ($userMap[$aid]['name'] ?? null)    : null;
            $r['assignee_avatar']  = ($aid && isset($userMap[$aid])) ? ($userMap[$aid]['avatar'] ?? null)  : null;

            // Build watchers_resolved for the avatar group in the <td>
            $ws = $r['watchers'] ?? [];
            if (is_string($ws)) { $ws = json_decode($ws, true) ?: []; }
            $r['watchers_resolved'] = [];
            foreach ($ws as $wid) {
                $wid = (int)$wid; if ($wid <= 0) continue;
                $r['watchers_resolved'][] = [
                    'id'     => $wid,
                    'name'   => $userMap[$wid]['name']   ?? ('User #'.$wid),
                    'avatar' => $userMap[$wid]['avatar'] ?? null,
                ];
            }
        }
        unset($r);

        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success'=>true,'data'=>$rows,'limit'=>$limit,'offset'=>$offset]));
            return;
        }

        $this->load->view('layouts/master', [
            'page_title' => 'Watching List',
            'subview'    => 'support/ticket_watcher',
            'view_data'  => [
                'tickets'    => $rows,
                'page_title' => 'Tickets Watching List'
            ],
        ]);
    }

    // Lite modal with limited timeline/details (only if current user is watcher)
    public function view_modal_lite($id)
    {
        $id  = (int)$id;
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) { show_error('Unauthorized', 401); return; }

        // Load ticket first (we’ll use policy below)
        $ticket = $this->tickets->find($id, true);
        if (!$ticket) { show_404(); return; }

        // Guard: user must be related (watcher/assignee/requester) OR dept head OR super admin
        if (
            !$this->policy->is_watcher($ticket, $uid) &&
            !$this->policy->is_assignee($ticket, $uid) &&
            !$this->policy->is_requester($ticket, $uid) &&
            !$this->policy->is_department_head($uid, (int)$ticket['department_id']) &&
            !$this->policy->is_super_admin($uid)
        ) {
            show_error('Forbidden', 403);
            return;
        }

        // ---- Filter to public posts only (skip notes/private)
        $posts = is_array($ticket['posts'] ?? null) ? $ticket['posts'] : [];
        $public = array_values(array_filter($posts, static function($p) {
            if (isset($p['type']) && strtolower((string)$p['type']) === 'note') return false;
            if (!empty($p['is_private'])) return false;
            return true;
        }));

        // Respect replies order and keep last 5
        $orderDesc = (function_exists('get_setting') && get_setting('support_replies_order') === 'descending');
        if ($orderDesc) {
            $public = array_slice($public, 0, 5);
        } else {
            $public = array_slice($public, -5);
        }

        // ---- Enrich department name
        $deptMap = $this->tickets->get_departments_map();
        $departmentName = $deptMap[(int)$ticket['department_id']] ?? ('#'.(int)$ticket['department_id']);

        // ---- Resolve requester/assignee names + avatars
        $needIds = [];
        if (!empty($ticket['requester_id'])) $needIds[] = (int)$ticket['requester_id'];
        if (!empty($ticket['assignee_id']))  $needIds[] = (int)$ticket['assignee_id'];

        foreach ($public as $p) {
            if (!empty($p['author_id'])) $needIds[] = (int)$p['author_id'];
        }

        // Resolve watchers into [{id,name,avatar}]
        $watcherIds = is_array($ticket['watchers'] ?? null) ? array_map('intval', $ticket['watchers']) : [];
        foreach ($watcherIds as $wid) { $needIds[] = (int)$wid; }

        $needIds = array_values(array_unique(array_filter($needIds)));
        $userMap = !empty($needIds) ? $this->users->get_map_by_ids($needIds) : [];

        $requester_name   = null; $requester_avatar = null;
        $assignee_name    = null; $assignee_avatar  = null;

        if (!empty($ticket['requester_id']) && isset($userMap[(int)$ticket['requester_id']])) {
            $requester_name   = $userMap[(int)$ticket['requester_id']]['name']   ?? null;
            $requester_avatar = $userMap[(int)$ticket['requester_id']]['avatar'] ?? null;
        }
        if (!empty($ticket['assignee_id']) && isset($userMap[(int)$ticket['assignee_id']])) {
            $assignee_name   = $userMap[(int)$ticket['assignee_id']]['name']   ?? null;
            $assignee_avatar = $userMap[(int)$ticket['assignee_id']]['avatar'] ?? null;
        }

        // Attach author info to posts
        foreach ($public as &$p) {
            $aid = (int)($p['author_id'] ?? 0);
            if ($aid && isset($userMap[$aid])) {
                $p['author_name']   = $userMap[$aid]['name']   ?? ($p['author_name'] ?? null);
                $p['author_avatar'] = $userMap[$aid]['avatar'] ?? ($p['author_avatar'] ?? null);
            }
        }
        unset($p);

        // Build watchers_resolved
        $watchers_resolved = [];
        foreach ($watcherIds as $wid) {
            if (isset($userMap[$wid])) {
                $watchers_resolved[] = [
                    'id'     => $wid,
                    'name'   => $userMap[$wid]['name']   ?? ('User #'.$wid),
                    'avatar' => $userMap[$wid]['avatar'] ?? null,
                ];
            } else {
                $watchers_resolved[] = ['id'=>$wid, 'name'=>'User #'.$wid, 'avatar'=>null];
            }
        }

        // ---- Build compact payload for the lite view
        $ticketLite = [
            'id'                => (int)$ticket['id'],
            'code'              => (string)($ticket['code'] ?? ''),
            'subject'           => (string)($ticket['subject'] ?? ''),
            'department_id'     => (int)$ticket['department_id'],
            'department_name'   => $departmentName,
            'status'            => (string)($ticket['status'] ?? 'open'),
            'priority'          => (string)($ticket['priority'] ?? 'normal'),
            'created_at'        => (string)($ticket['created_at'] ?? ''),
            'last_activity_at'  => (string)($ticket['last_activity_at'] ?? ''),
            'first_response_due_at' => (string)($ticket['first_response_due_at'] ?? ''),
            'resolution_due_at'     => (string)($ticket['resolution_due_at'] ?? ''),
            'tags'              => is_array($ticket['tags'] ?? null) ? $ticket['tags'] : [],
            // requester/assignee (for chips)
            'requester_id'      => (int)($ticket['requester_id'] ?? 0),
            'requester_name'    => $requester_name,
            'requester_avatar'  => $requester_avatar,
            'assignee_id'       => (int)($ticket['assignee_id'] ?? 0),
            'assignee_name'     => $assignee_name,
            'assignee_avatar'   => $assignee_avatar,
            // watchers (resolved for avatar stack)
            'watchers_resolved' => $watchers_resolved,
            // last 5 public posts with author info
            'posts'             => $public,
        ];

        // ---- Render lite modal body
        $this->load->view('support/modals/ticket_lite', ['ticket' => $ticketLite]);
    }

    /* ---------------------------
     * Internal helpers
     * --------------------------- */

    private function guard(string $action)
    {
        if (!function_exists('staff_can') || !staff_can($action, 'support')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    }

    private function guard_view()
    {
        if (!function_exists('staff_can') ||
            (!staff_can('view_global', 'support') && !staff_can('view_own', 'support'))) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    }

    /**
     * Kept for backward compatibility (some views/helpers may call it).
     * Internally delegates to policy->can_view when possible.
     */
    private function guard_can_view_ticket(array $ticket)
    {
        $uid = (int)$this->session->userdata('user_id');
        if ($this->policy && $this->policy->can_view($ticket, $uid)) {
            return;
        }

        // Fallback to legacy logic if policy is not available
        if (function_exists('staff_can') && staff_can('view_global', 'support')) return;

        $limitedToDept = ($this->setting_yes('support_staff_limited_to_dept'));
        $can = (
            (int)($ticket['requester_id'] ?? 0) === $uid ||
            (int)($ticket['assignee_id']  ?? 0) === $uid
        );

        if (!$can && $limitedToDept) {
            if (function_exists('user_belongs_to_department')) {
                $can = (bool)user_belongs_to_department($uid, (int)$ticket['department_id']);
            }
        }

        if (!$can) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    }

    private function parse_tags(?string $csv)
    {
        if (!$csv) return null;
        $tags = array_values(array_filter(array_map('trim', explode(',', $csv))));
        return $tags ?: null;
    }

    /**
     * Handle multi-file upload to /uploads/support/.
     * Returns [{name, path, size, mime}]
     */
    private function collect_attachments_from_request(): array
    {
        $out = [];
        if (empty($_FILES['attachments']) || empty($_FILES['attachments']['name'])) return $out;

        $baseDir = FCPATH . 'uploads/support/';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
        }

        // Allowed extensions from settings
        $allowed = (string)(get_setting('support_allowed_mime_types') ?? 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip');
        $allowedExt = array_filter(array_map('strtolower', array_map('trim', explode(',', $allowed))));

        $names = $_FILES['attachments']['name'];
        $types = $_FILES['attachments']['type'];
        $tmp   = $_FILES['attachments']['tmp_name'];
        $errs  = $_FILES['attachments']['error'];
        $sizes = $_FILES['attachments']['size'];

        $count = is_array($names) ? count($names) : 0;

        // Enforce max attachments per post (setting)
        $max = (int)(get_setting('support_max_attachments') ?? 0);
        if ($max > 0 && $count > $max) {
            $count = $max; // silently cap to max
        }

        for ($i = 0; $i < $count; $i++) {
            if ((int)$errs[$i] !== UPLOAD_ERR_OK) continue;
            $orig = (string)$names[$i];
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if ($allowedExt && $ext && !in_array($ext, $allowedExt, true)) {
                continue;
            }
            $tmpPath = $tmp[$i];
            $mime    = (string)$types[$i];
            $size    = (int)$sizes[$i];

            $safeName = time() . '_' . bin2hex(random_bytes(3)) . ($ext ? ('.'.$ext) : '');
            $destPath = $baseDir . $safeName;

            if (@move_uploaded_file($tmpPath, $destPath)) {
                $out[] = [
                    'name' => $orig,
                    'path' => base_url('uploads/support/'.$safeName),
                    'size' => $size,
                    'mime' => $mime,
                ];
            }
        }
        return $out;
    }

    private function setting_yes(string $key, string $default = 'yes'): bool
    {
        return (get_setting($key, $default) === 'yes');
    }

    /**
     * Returns a public URL for a ticket if configured (supports {code} or {ticket_id} tokens)
     */
    private function build_public_url(int $ticketId): ?string
    {
        $tpl = (string)(get_setting('support_ticket_public_url') ?? '');
        if ($tpl === '') return null;

        $ticket = $this->tickets->find($ticketId, false);
        $code   = (string)($ticket['code'] ?? '');
        $url = str_replace(
            ['{code}', '{ticket_id}'],
            [$code !== '' ? $code : (string)$ticketId, (string)$ticketId],
            $tpl
        );
        return $url;
    }

    /**
     * Simple HTML email helper (kept for future use). Uses the correct 'message' key.
     */
    private function send_ticket_email_bare(int $userId, string $subject, string $htmlBody): void
    {
        if (!function_exists('app_mailer')) { return; }
        $user = $this->users->get_user_by_id($userId);
        $toEmail = trim((string)($user['email'] ?? ''));
        if ($toEmail === '') return;

        app_mailer()->send([
            'to'       => $toEmail,
            'subject'  => $subject,
            'message'  => $htmlBody,  // IMPORTANT: correct key so it renders as HTML
            'mailtype' => 'html',
        ]);
    }

    /**
     * Renders a super-simple consistent HTML snippet for generic mails
     */
    private function render_simple_ticket_email(string $lead, string $ticketSubject, string $link): string
    {
        $brand = function_exists('get_setting') ? (get_setting('companyname') ?: 'Support') : 'Support';
        $lead  = htmlspecialchars($lead, ENT_QUOTES, 'UTF-8');
        $ts    = htmlspecialchars($ticketSubject, ENT_QUOTES, 'UTF-8');
        $href  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222;">
            <p>'.$lead.'</p>
            <p><strong>Ticket:</strong> '.$ts.'</p>
            <p><a href="'.$href.'">Open ticket</a></p>
            <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">
            <p style="color:#666">'.$brand.'</p>
        </div>';
    }

    /**
     * Check whether current user is allowed to add watchers based on setting
     * $whoCan: requester | assignee | both
     * (Kept for backward compatibility; controller now uses policy->can_manage_watchers)
     */
    private function current_user_can_add_watcher(int $ticketId, string $whoCan): bool
    {
        $ticket = $this->tickets->find($ticketId, false);
        if (!$ticket) return false;
        $uid = (int)$this->session->userdata('user_id');

        $isRequester = (int)($ticket['requester_id'] ?? 0) === $uid;
        $isAssignee  = (int)($ticket['assignee_id']  ?? 0) === $uid;

        switch ($whoCan) {
            case 'requester': return $isRequester;
            case 'assignee':  return $isAssignee;
            case 'both':      return ($isRequester || $isAssignee);
            default:          return true; // fallback
        }
    }




    /**
     * Resolve department head user IDs for a department.
     * Tries model's get_department_heads(); falls back to reading departments.hod
     * which may be an INT, CSV string ("12,34"), or JSON array "[12,34]".
     */
    private function get_department_head_ids(int $department_id): array
    {
        // Prefer model method if present (some deployments already have it)
        if (method_exists($this->tickets, 'get_department_heads')) {
            $ids = (array)$this->tickets->get_department_heads($department_id);
            return array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        }
    
        // Fallback: read 'hod' from departments.{id}
        $row = $this->db->select('hod')->from('departments')->where('id', $department_id)->get()->row_array();
        if (!$row) return [];
    
        $hod = $row['hod'];
    
        // JSON array?
        $json = json_decode((string)$hod, true);
        if (is_array($json)) {
            return array_values(array_unique(array_filter(array_map('intval', $json), fn($v) => $v > 0)));
        }
    
        // CSV string?
        if (is_string($hod) && strpos($hod, ',') !== false) {
            $parts = array_map('trim', explode(',', $hod));
            return array_values(array_unique(array_filter(array_map('intval', $parts), fn($v) => $v > 0)));
        }
    
        // Single int
        $id = (int)$hod;
        return $id > 0 ? [$id] : [];
    }



    /**
     * Send an email using an Email Template by slug with Merge Fields.
     * - $slug must exist in `emailtemplates` and be active.
     * - $toUserId is the recipient user id (we’ll pull their email).
     * - $ctx is the merge-field context (e.g., ['company'=>true,'user_id'=>..., 'ticket_id'=>...]).
     * - $fallbackSubject/$fallbackHtml are used if template is missing/inactive/invalid.
     */
    private function send_support_template_email_by_slug(
        string $slug,
        int $toUserId,
        array $ctx,
        string $fallbackSubject,
        string $fallbackHtml
    ): void {
        if (!function_exists('app_mailer')) { return; }
    
        // 1) Resolve recipient email
        $u = $this->users->get_user_by_id($toUserId);
        $toEmail = trim((string)($u['email'] ?? ''));
        if ($toEmail === '') { return; }
    
        // 2) Get template by slug
        $tpl = $this->emails->get_by_slug($slug);
        $templateOk = $tpl && !empty($tpl->active) && (int)$tpl->active === 1;
    
        // 3) Build merge map once
        // Ensure company + user/ticket ids are present in ctx
        $ctx = array_merge([
            'company'   => true,
            'user_id'   => (int)$toUserId,
            'ticket_id' => null,
        ], $ctx);
    
        $map = $this->merge_fields->context($ctx)->map();
    
        // 4) Prepare subject/body using template or fallback
        if ($templateOk) {
            $subjectRaw = (string)($tpl->subject ?? '');
            $bodyRaw    = (string)($tpl->message ?? '');
    
            // Replace tokens
            list($subject, $body) = $this->merge_fields->replace($subjectRaw, $bodyRaw, $map);
    
            // Safety: if template has empty subject/body after replacement, fallback
            $subject = trim($subject) !== '' ? $subject : $fallbackSubject;
            $body    = trim($body)    !== '' ? $body    : $fallbackHtml;
    
            app_mailer()->send([
                'to'       => $toEmail,
                'subject'  => $subject,
                'message'  => $body,   // use 'message' for raw HTML
                'mailtype' => 'html',
                // You can add 'fromname'/'fromemail' overrides if present in template:
                // 'fromname'  => (string)($tpl->fromname ?? ''),
                // 'fromemail' => (string)($tpl->fromemail ?? ''),
            ]);
        } else {
            // 5) Fallback path if template not found/inactive
            app_mailer()->send([
                'to'       => $toEmail,
                'subject'  => $fallbackSubject,
                'message'  => $fallbackHtml,
                'mailtype' => 'html',
            ]);
        }
    }
    
    
}
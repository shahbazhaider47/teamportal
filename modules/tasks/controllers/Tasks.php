<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tasks Controller — final aligned with view contracts.
 * - Accepts comment_html/comment/body
 * - Checklist adapters: /tasks/checklist/{toggle|delete|add}/{id}
 * - Attachments adapters: /tasks/attachments/{taskId}/upload and /tasks/attachments/{id}/delete
 */
class Tasks extends App_Controller
{
    protected int $uid = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('tasks/Tasks_model', 'tasks');
        $this->load->model('User_model',        'users');
        $this->load->model('tasks/Task_comment_replies_model', 'replies'); // <— definitive
        $this->load->library('tasks/Tasks_policy', null, 'policy');

        $this->load->library(['form_validation', 'session', 'ai/Todo_ai_helper']);
        $this->load->helper(['url', 'form', 'file']);

        $this->uid = (int) $this->session->userdata('user_id');
        if (!$this->uid) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Not authenticated.', 401);
            }
            redirect('authentication/login');
            exit;
        }
    }

    /* =========================================================================
     * Index (list/kanban/gantt) — unchanged semantics, minor polish
     * ========================================================================= */
    public function index()
    {
        if (!$this->policy->can_list($this->uid)) {
            $this->render_403_and_exit(); return;
        }

        $view    = $this->input->get('view', true) ?: $this->get_setting('tasks_default_view','list');
        $subview = $view === 'kanban' ? 'tasks/kanban' : ($view === 'gantt' ? 'tasks/gantt' : 'tasks/index');

        $assignees     = method_exists($this->users, 'get_active_minimal_list') ? $this->users->get_active_minimal_list() : [];
        $followersList = $assignees;

        $rows   = [];
        $total  = 0;
        $limit  = (int)($this->input->get('limit')  ?? 50);
        $offset = (int)($this->input->get('offset') ?? 0);
        $limit  = $limit <= 0 ? 50 : ($limit > 500 ? 500 : $limit);
        $offset = max(0, $offset);

        if ($view === 'list') {
            $filters = [
                'q'               => trim((string)$this->input->get('q')),
                'status'          => $this->input->get('status') ? (array)$this->input->get('status') : [],
                'priority'        => $this->input->get('priority') ? (array)$this->input->get('priority') : [],
                'assignee_id'     => ($this->input->get('assignee_id') !== null && $this->input->get('assignee_id') !== '') ? (int)$this->input->get('assignee_id') : null,
                'rel_type'        => $this->input->get('rel_type') ?: null,
                'rel_id'          => ($this->input->get('rel_id') !== null && $this->input->get('rel_id') !== '') ? (int)$this->input->get('rel_id') : null,
                'visible_to_team' => ($this->input->get('visible_to_team') !== null && $this->input->get('visible_to_team') !== '') ? (int)$this->input->get('visible_to_team') : null,
            ];
            if (!function_exists('staff_can') || !staff_can('view_global','tasks')) {
                $filters['scoped_user_id'] = $this->uid;
            }
            $order_by = $this->input->get('order_by') ?: 'duedate';
            $dir      = $this->input->get('dir') ?: 'desc';

            $result = $this->tasks->list_tasks(
                array_filter($filters, fn($v) => $v !== null && $v !== '' && $v !== []),
                $limit, $offset, $order_by, $dir
            );
            $rows  = $result['rows']  ?? [];
            $total = $result['total'] ?? 0;
        }

        $taskIds    = array_column($rows, 'id');
        $checkstats = $this->tasks->checklist_summary_batch($taskIds);
        $meta_counts = $rows    ? $this->tasks->build_meta_counters_for_rows($rows) : [];

        $this->load->view('layouts/master', [
            'page_title' => 'Tasks',
            'subview'    => $subview,
            'view_data'  => [
            'page_title'  => 'Tasks',
            'view'        => $view,
            'assignees'   => $assignees,
            'followers'   => $followersList,
            'rows'        => $rows,
            'total'       => $total,
            'limit'       => $limit,
            'offset'      => $offset,
            'has_prev'    => $offset > 0,
            'has_next'    => ($offset + $limit) < $total,
            'checkstats'  => $checkstats,
            'meta_counts' => $meta_counts, // <-- NEW
            ],
        ]);
    }

    /* =========================================================================
     * Create
     * ========================================================================= */
    public function create()
    {
        if (!$this->policy->can_create($this->uid)) return $this->forbid_json_or_redirect();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') { redirect('tasks'); return; }

        $this->form_validation->set_rules('name', 'Title', 'required|min_length[3]|trim');
        $this->form_validation->set_rules('priority', 'Priority', 'in_list[low,normal,high,urgent]');
        $this->form_validation->set_rules('assignee_id', 'Assignee', 'integer');

        if ($this->form_validation->run() === false) {
            $msg = strip_tags(validation_errors());
            if ($this->input->is_ajax_request()) return $this->json_error($msg, 422);
            set_alert('warning', $msg); redirect('tasks'); return;
        }

        // scalar guards
        $name        = (string) (is_array($this->input->post('name', true)) ? reset($this->input->post('name', true)) : $this->input->post('name', true));
        $priority    = (string) (is_array($this->input->post('priority', true)) ? reset($this->input->post('priority', true)) : $this->input->post('priority', true));
        $status      = (string) (is_array($this->input->post('status', true)) ? reset($this->input->post('status', true)) : $this->input->post('status', true));
        $startdate   = (string) (is_array($this->input->post('startdate', true)) ? reset($this->input->post('startdate', true)) : $this->input->post('startdate', true));
        $duedate     = (string) (is_array($this->input->post('duedate', true)) ? reset($this->input->post('duedate', true)) : $this->input->post('duedate', true));
        $relType     = (string) (is_array($this->input->post('rel_type', true)) ? reset($this->input->post('rel_type', true)) : $this->input->post('rel_type', true));
        $relIdRaw    = (is_array($this->input->post('rel_id', true)) ? reset($this->input->post('rel_id', true)) : $this->input->post('rel_id', true));
        $relId       = ($relIdRaw === '' || $relIdRaw === null) ? null : (int)$relIdRaw;
        $assigneeRaw = (is_array($this->input->post('assignee_id', true)) ? reset($this->input->post('assignee_id', true)) : $this->input->post('assignee_id', true));
        $assigneeId  = ($assigneeRaw === '' || $assigneeRaw === null) ? null : (int)$assigneeRaw;
        $visibleRaw  = (is_array($this->input->post('visible_to_team', true)) ? reset($this->input->post('visible_to_team', true)) : $this->input->post('visible_to_team', true));
        $visibleToTeam = ($visibleRaw === '' || $visibleRaw === null) ? 1 : (int)$visibleRaw;

        $recurring      = (int)($this->input->post('recurring') ?? 0);
        $recurring_type = (string) (is_array($this->input->post('recurring_type', true)) ? reset($this->input->post('recurring_type', true)) : $this->input->post('recurring_type', true));
        $repeat_every_r = (is_array($this->input->post('repeat_every', true)) ? reset($this->input->post('repeat_every', true)) : $this->input->post('repeat_every', true));
        $repeat_every   = ($repeat_every_r === '' || $repeat_every_r === null) ? null : (int)$repeat_every_r;
        $cycles         = (int)($this->input->post('cycles') ?? 0);
        $last_rec_date  = (string) (is_array($this->input->post('last_recurring_date', true)) ? reset($this->input->post('last_recurring_date', true)) : $this->input->post('last_recurring_date', true));

        $payload = [
            'name'                => $name,
            'description'         => (string)$this->input->post('description', false), // HTML allowed
            'priority'            => $priority ?: 'normal',
            'status'              => $status   ?: 'not_started',
            'startdate'           => ($startdate === '' ? null : $startdate),
            'duedate'             => ($duedate   === '' ? null : $duedate),
            'assignee_id'         => $assigneeId,
            'rel_type'            => ($relType ?: null),
            'rel_id'              => $relId,
            'addedfrom'           => $this->uid,
            'visible_to_team'     => $visibleToTeam,
            'recurring'           => $recurring,
            'recurring_type'      => ($recurring_type ?: null),
            'repeat_every'        => $repeat_every,
            'cycles'              => $cycles,
            'last_recurring_date' => ($last_rec_date ?: null),
        ];

        try {
            
            $taskId = (int)$this->tasks->create_task($payload);
            if ($taskId <= 0) throw new Exception('Failed to create task record.');
            
            $this->log_activity($taskId, 'task_created', [
                'title'      => $payload['name'],
                'status'     => $payload['status'],
                'priority'   => $payload['priority'],
                'assignee'   => $payload['assignee_id'],
                'rel'        => ['type'=>$payload['rel_type'],'id'=>$payload['rel_id']],
                'dates'      => ['start'=>$payload['startdate'],'due'=>$payload['duedate']],
                'recurring'  => [
                    'enabled' => (bool)$payload['recurring'],
                    'type'    => $payload['recurring_type'],
                    'every'   => $payload['repeat_every'],
                    'cycles'  => $payload['cycles']
                ]
            ]);
            
            if (!empty($payload['assignee_id'])) {
                $this->log_activity($taskId, 'assignee_set', ['from' => null, 'to' => (int)$payload['assignee_id']]);
            }

            // Checklist normalization (flat or nested)
            $rowsFlat = [];
            $check = $this->input->post('checklist');
            if (is_array($check)) {
                if (array_key_exists('description', $check)) {
                    foreach ((array)$check['description'] as $d) {
                        $d = is_array($d) ? reset($d) : $d;
                        $d = trim((string)$d); if ($d !== '') $rowsFlat[] = $d;
                    }
                } else {
                    foreach ($check as $d) {
                        $d = is_array($d) ? reset($d) : $d;
                        $d = trim((string)$d); if ($d !== '') $rowsFlat[] = $d;
                    }
                }
            }
            if ($rowsFlat) $this->tasks->create_checklist_items_batch($taskId, $rowsFlat, $this->uid);

             if (!empty($rowsFlat)) {
                 $this->log_activity($taskId, 'checklist_batch_created', [
                     'count' => count($rowsFlat),
                     'items' => array_slice($rowsFlat, 0, 10)
                 ]);
             }

        } catch (Exception $e) {
            log_message('error', 'Tasks create failed: ' . $e->getMessage());
            if ($this->input->is_ajax_request()) return $this->json_error('Failed to create task.', 500);
            set_alert('warning', 'Failed to create task.'); redirect('tasks'); return;
        }

        $this->send_assignment_notification($taskId, $payload);

        if ($this->input->is_ajax_request()) {
            return $this->json_ok(['message' => 'Task created successfully.', 'taskId' => $taskId, 'task' => $payload]);
        }
        set_alert('success', 'Task created successfully.');
        redirect('tasks/view/' . $taskId);
    }

    /* =========================================================================
     * View
     * ========================================================================= */
public function view($id)
{
    $id   = (int) $id;
    $task = $this->tasks->get_task($id);

    if (!$task) { $this->render_403_and_exit(); return; }
    if (!$this->policy->can_view($task, $this->uid)) { $this->render_403_and_exit(); return; }

    /* -------------------------
     * 1) Comments + ordering pref
     * ------------------------- */
    $comments = $this->tasks->list_comments($id);

    $pref = strtolower((string) (function_exists('get_setting')
        ? (get_setting('tasks_comments_order') ?: 'descending')
        : 'descending'));
    $asc       = in_array($pref, ['asc','ascending','oldest','oldest_first'], true);
    $orderDesc = !$asc; // true => Newest first (DESC), false => Oldest first (ASC)

    // Ensure ordering even if model doesn’t sort
    if (is_array($comments) && $comments) {
        usort($comments, function($a, $b) use ($orderDesc) {
            $ad = strtotime($a['dateadded'] ?? $a['created_at'] ?? 'now');
            $bd = strtotime($b['dateadded'] ?? $b['created_at'] ?? 'now');
            // DESC => newest first
            return $orderDesc ? ($bd <=> $ad) : ($ad <=> $bd);
        });
    }
    
    /* -------------------------
     * 2) Gather user IDs (task creator, assignee, commenters, followers)
     * ------------------------- */
    $userIds = [];
    if (!empty($task['addedfrom']))   $userIds[] = (int)$task['addedfrom'];
    if (!empty($task['assignee_id'])) $userIds[] = (int)$task['assignee_id'];
    foreach ($comments as $c) {
        if (!empty($c['user_id'])) $userIds[] = (int)$c['user_id'];
    }

    // Followers can be int[], CSV, or JSON — normalize to int[]
    $followers_raw = $task['followers'] ?? [];
    if (is_array($followers_raw)) {
        $followers = array_map('intval', $followers_raw);
    } elseif (is_string($followers_raw)) {
        $s = trim($followers_raw); $followers = [];
        if ($s !== '') {
            $dec = json_decode($s, true);
            $followers = is_array($dec)
                ? array_map('intval', $dec)
                : array_map('intval', preg_split('/\s*,\s*/', $s, -1, PREG_SPLIT_NO_EMPTY));
        }
    } elseif (is_numeric($followers_raw)) {
        $followers = [(int)$followers_raw];
    } else {
        $followers = [];
    }
    foreach ($followers as $fid) if ($fid > 0) $userIds[] = (int)$fid;

    $userIds = array_values(array_unique(array_filter($userIds)));

    /* -------------------------
     * 3) Resolve users (names/avatars)
     * ------------------------- */
    $userMap = $userIds ? $this->tasks->user_get_map_by_ids($userIds) : [];

    // Enrich task
    $task['addedfrom_name']   = $this->tasks->user_get_name((int) ($task['addedfrom'] ?? 0), $userMap);
    $task['addedfrom_avatar'] = $this->tasks->user_get_avatar((int) ($task['addedfrom'] ?? 0), $userMap);
    $task['assignee_name']    = $this->tasks->user_get_name((int) ($task['assignee_id'] ?? 0), $userMap);
    $task['assignee_avatar']  = $this->tasks->user_get_avatar((int) ($task['assignee_id'] ?? 0), $userMap);

    // Enrich comments
    foreach ($comments as &$c) {
        $uid = (int)($c['user_id'] ?? 0);
        $c['author_name']   = $this->tasks->user_get_name($uid, $userMap);
        $c['author_avatar'] = $this->tasks->user_get_avatar($uid, $userMap);
    }
    unset($c);

    $followers_resolved = $this->tasks->user_resolve_cards($followers);

    /* -------------------------
     * 4) Preload replies for ALL comments (bulk), hydrate authors, group by comment_id
     * ------------------------- */
    $commentIds = [];
    foreach ($comments as $cc) {
        $cid = (int)($cc['id'] ?? 0);
        if ($cid > 0) $commentIds[] = $cid;
    }
    $commentIds = array_values(array_unique($commentIds));

    $replies_by_comment = [];

    if ($commentIds) {
        // Prefer a model bulk method if present; else fallback to a direct query.
        if (isset($this->replies) && method_exists($this->replies, 'list_for_comments')) {
            $replyRows = $this->replies->list_for_comments($id, $commentIds);
        } else {
            // Fallback query (without assuming a taskid column on the replies table)
            $replyRows = $this->db->select('id, comment_id, user_id, reply, dateadded')
                ->from('task_comment_replies')
                ->where_in('comment_id', $commentIds)
                ->order_by('id', 'ASC')
                ->get()->result_array();
        }

        // Extend user map with reply authors if needed
        $replyUserIds = [];
        foreach ($replyRows as $r) {
            $u = (int)($r['user_id'] ?? 0);
            if ($u > 0) $replyUserIds[] = $u;
        }
        $replyUserIds = array_values(array_unique($replyUserIds));
        $missing      = array_diff($replyUserIds, array_keys($userMap));
        if ($missing) {
            $extraMap = $this->tasks->user_get_map_by_ids($missing);
            foreach ($extraMap as $k => $v) $userMap[$k] = $v;
        }

        // Hydrate & group
        foreach ($replyRows as &$r) {
            $ruid = (int)($r['user_id'] ?? 0);
            $r['author_name']   = $this->tasks->user_get_name($ruid, $userMap);
            $r['author_avatar'] = $this->tasks->user_get_avatar($ruid, $userMap);
        }
        unset($r);

        foreach ($replyRows as $r) {
            $cid = (int)($r['comment_id'] ?? 0);
            if ($cid > 0) $replies_by_comment[$cid][] = $r;
        }

        // Backfill replies_count into comments
        foreach ($comments as &$c) {
            $cid = (int)($c['id'] ?? 0);
            $c['replies_count'] = isset($replies_by_comment[$cid]) ? count($replies_by_comment[$cid]) : 0;
        }
        unset($c);
    }

    /* -------------------------
     * 5) Other data blocks
     * ------------------------- */
    $attachments  = $this->tasks->list_attachments($id);
    $checklist    = $this->tasks->list_checklist($id);
    $checkSummary = $this->tasks->checklist_summary($id);
    $activeUsers  = $this->tasks->user_get_active_minimal_list();

// NEW: activities
$activities   = method_exists($this->tasks, 'list_activity')
    ? $this->tasks->list_activity($id, 300)
    : [];
    
    $isAssignee         = $this->policy->is_assignee($task, $this->uid);
    $canAssign          = $this->policy->can_assign($task, $this->uid);
    $canManageFollowers = $this->policy->can_manage_followers($task, $this->uid);
    $canChangeStatus    = $this->policy->can_change_status($task, $this->uid);
    $canEdit            = $this->policy->can_edit($task, $this->uid);

    // Pre-render checklist partial
    $checklist_html = $this->load->view('tasks/partials/checklist', [
        'taskId'        => $id,
        'check_summary' => $checkSummary,
        'checklist'     => $checklist,
        'canEdit'       => $canEdit,
        'isAssignee'    => $isAssignee,
        'active_users'  => $activeUsers,
    ], true);

    /* -------------------------
     * 6) Render
     * ------------------------- */
    $this->load->view('layouts/master', [
        'page_title' => $task['name'] ?? 'Task Detail',
        'subview'    => 'tasks/view',
        'view_data'  => [
            'task'                => $task,
            'comments'            => $comments,
            'attachments'         => $attachments,
            'checklist'           => $checklist,
            'check_summary'       => $checkSummary,
            'checklist_html'      => $checklist_html,
            'followers_resolved'  => $followers_resolved,
            'active_users'        => $activeUsers,

            'is_assignee'         => $isAssignee,
            'canAssign'           => $canAssign,
            'canManageFollowers'  => $canManageFollowers,
            'canChangeStatus'     => $canChangeStatus,
            'canEdit'             => $canEdit,

            'orderDesc'           => $orderDesc,
            'assignee_id'         => (int)($task['assignee_id'] ?? 0),
            'followers'           => $followers,

            // NEW: preloaded replies
            'replies_by_comment'  => $replies_by_comment,
            'activities'         => $activities,
        ],
    ]);
}


// application/controllers/Tasks.php
public function checklist_panel($taskId)
{
    $taskId = (int)$taskId;
    $task   = $this->tasks->get_task_simple($taskId);
    if (!$task) { show_404(); return; }
    if (!$this->policy->can_view($task, $this->uid)) { $this->render_403_and_exit(); return; }

    $canEdit    = $this->policy->can_edit($task, $this->uid);
    $isAssignee = $this->policy->is_assignee($task, $this->uid);

    $checklist     = $this->tasks->list_checklist($taskId);
    $check_summary = $this->tasks->checklist_summary($taskId);

    $this->load->view('tasks/partials/checklist', [
        'taskId'        => $taskId,
        'check_summary' => $check_summary,
        'checklist'     => $checklist,
        'canEdit'       => $canEdit,
        'isAssignee'    => $isAssignee,
        'active_users'  => $this->tasks->user_get_active_minimal_list(), // add
    ]);
}

    /* =========================================================================
     * Comments
     * ========================================================================= */
    public function add_comment($taskId) { return $this->post_comment((int)$taskId); }

    public function post_comment($id)
    {
        $id   = (int)$id;
        $task = $this->tasks->get_task_simple($id);
        if (!$task) show_404();
        if (!$this->policy->can_view($task, $this->uid)) return $this->forbid_json_or_redirect();

        // Accept any of the three keys from the view
        $html   = (string)$this->input->post('comment_html', false); // allow HTML
        $plain  = (string)$this->input->post('comment', true);       // safe
        $legacy = (string)$this->input->post('body', false);         // legacy key fallback
        
        // Pick preferred source: HTML > plain > legacy
        $body = trim($html) !== '' ? $html : (trim($plain) !== '' ? nl2br($plain) : $legacy);
        
        // Minimal sanitize (keeps formatting); replace with purifier if you have it
        $body = $this->security->xss_clean((string)$body);
        
        if (trim((string)$body) === '') {
            set_alert('warning', 'Comment body is required.');
            redirect('tasks/view/' . $id); return;
        }
        
        $cid = (int)$this->tasks->add_comment($id, $this->uid, (string)$body);
        if ($cid <= 0) {
            set_alert('warning', 'Failed to add comment.');
            redirect('tasks/view/' . $id); return;
        }
        
        $this->log_activity($id, 'comment_added', [
            'comment_id' => $cid,
            'excerpt'    => $this->_excerpt((string)$body)
        ]);


        $fullTask = $this->tasks->get_task($id);
        $this->send_comment_notification($fullTask);

        set_alert('success', 'Comment added.');
        redirect('tasks/view/' . $id);
    }

    public function delete_comment($commentId)
    {
        $cid = (int)$commentId;
        $row = $this->db->get_where('task_comments', ['id' => $cid])->row_array();
        if (!$row) { set_alert('warning', 'Comment not found.'); redirect('tasks'); return; }

        $task = $this->tasks->get_task_simple((int)$row['taskid']);
        if (!$task) { set_alert('warning', 'Task not found.'); redirect('tasks'); return; }

        $is_author     = ((int)$row['user_id'] === $this->uid);
        $can_delete_any= $this->policy->is_super_admin($this->uid) || $this->policy->can_delete($task, $this->uid);

        if (!$is_author && !$can_delete_any) return $this->forbid_json_or_redirect();

        $ok = $this->tasks->delete_comment($cid);

        if ($ok) {
            $this->log_activity((int)$row['taskid'], 'comment_deleted', [
                'comment_id' => $cid
            ]);
        }

        set_alert($ok ? 'success' : 'danger', $ok ? 'Comment deleted.' : 'Delete failed.');
        redirect('tasks/view/' . (int)$row['taskid']);
    }

    /* =========================================================================
     * Assign / Status
     * ========================================================================= */
    public function assign($id)
    {
        $id   = (int)$id;
        $task = $this->tasks->get_task($id);
        if (!$task) show_404();
        if (!$this->policy->can_assign($task, $this->uid)) return $this->forbid_json_or_redirect();

        $assignee_id = ($this->input->post('assignee_id') === null || $this->input->post('assignee_id') === '') ? null : (int)$this->input->post('assignee_id');
        
        if ((int)($task['assignee_id'] ?? 0) === (int)$assignee_id) {
            set_alert('info', 'Assignee was not changed.');
            redirect('tasks/view/' . $id); return;
        }
        
        $from = (int)($task['assignee_id'] ?? 0);
        $ok   = $this->tasks->set_assignee($id, $assignee_id);
        
        if ($ok) {
            $this->log_activity($id, 'assignee_changed', ['from' => ($from ?: null), 'to' => ($assignee_id ?: null)]);
            $task['assignee_id'] = $assignee_id;
            $this->send_assignment_notification($id, $task);
            set_alert('success', 'Task assigned successfully.');
        } else {
            set_alert('warning', 'Failed to assign the task.');
        }
        
        redirect('tasks/view/' . $id);
    }

public function status($id)
{
    $id   = (int)$id;
    $task = $this->tasks->get_task($id);
    if (!$task) show_404();

    if (!$this->policy->can_change_status($task, $this->uid)) {
        return $this->forbid_json_or_redirect();
    }

    // Normalize & validate incoming status
    $status = strtolower(trim((string)$this->input->post('status', true)));
    if ($status === '') {
        if ($this->input->is_ajax_request()) return $this->json_error('Invalid status.', 422);
        set_alert('warning', 'Invalid status.');
        redirect('tasks/view/' . $id);
        return;
    }

    // Optional: block illegal transitions if your policy exposes it
    if (method_exists($this->policy, 'can_transition') && !$this->policy->can_transition($task, $status)) {
        $msg = 'This status change is not allowed.';
        if ($this->input->is_ajax_request()) return $this->json_error($msg, 403);
        set_alert('warning', $msg);
        redirect('tasks/view/' . $id);
        return;
    }
    
    // Extra guards for "completed"
    if ($status === 'completed') {
        // Enforce checklist if setting is ON
        $mustHaveNoPending = method_exists($this->policy, 'enforce_checklist_before_done')
            ? (bool)$this->policy->enforce_checklist_before_done()
            : false;

        $summary = $this->tasks->checklist_summary($id); // ['total'=>, 'done'=>, 'pending'=>, 'percent'=>]
        $pending = (int)($summary['pending'] ?? 0);

        // Build a transient shape for validation
        $taskForValidation                  = $task;
        $taskForValidation['checklist_done']  = $mustHaveNoPending ? ($pending === 0) : true;
        $taskForValidation['dependencies_ok'] = true; // keep your existing semantics

        // Policy-level final gate
        if (method_exists($this->policy, 'validate_completion_prereqs')) {
            list($canComplete, $reason) = $this->policy->validate_completion_prereqs($taskForValidation);
            if (!$canComplete) {
                $msg = $reason ?: 'Prerequisites for completion are not satisfied.';
                if ($this->input->is_ajax_request()) return $this->json_error($msg, 422);
                set_alert('warning', $msg);
                redirect('tasks/view/' . $id);
                return;
            }
        } else {
            // If no validate hook, still enforce checklist when required
            if ($mustHaveNoPending && $pending > 0) {
                $msg = 'All checklist items must be completed before marking the task as completed.';
                if ($this->input->is_ajax_request()) return $this->json_error($msg, 422);
                set_alert('warning', $msg);
                redirect('tasks/view/' . $id);
                return;
            }
        }
    }

    // Persist
    $ok = $this->tasks->set_status($id, $status, $this->uid, true);
    
    if ($ok) {
        $summary = null;
        if ($status === 'completed') {
            $summary = $this->tasks->checklist_summary($id);
            // existing validation using $summary ...
        }
        
        // after $ok:
        $this->log_activity($id, 'status_changed', [
            'from'              => (string)($task['status'] ?? ''),
            'to'                => (string)$status,
            'checklist_summary' => $summary ?? $this->tasks->checklist_summary($id)
        ]);
    }

    if ($ok) {
        // Fire any notifications
        if (method_exists($this, 'send_status_change_notification')) {
            $this->send_status_change_notification($task, $status);
        }

        if ($this->input->is_ajax_request()) {
            // Keep the response minimal for your table JS
            return $this->json_ok([
                'id'     => $id,
                'status' => $status,
                'message'=> 'Task status updated successfully.'
            ]);
        }

        set_alert('success', 'Task status updated successfully.');
    } else {
        if ($this->input->is_ajax_request()) {
            return $this->json_error('Failed to update the task status.', 500);
        }
        set_alert('warning', 'Failed to update the task status.');
    }

    redirect('tasks/view/' . $id);
}


    /* =========================================================================
     * Data (AJAX list)
     * ========================================================================= */
    public function list_json()
    {
        if (!$this->policy->can_list($this->uid)) return $this->json_error('Forbidden', 403);

        $normalizeArray = function ($v) {
            if (is_array($v)) return array_values(array_filter(array_map('trim', $v), 'strlen'));
            if ($v === null || $v === '') return [];
            return array_values(array_filter(array_map('trim', explode(',', (string)$v)), 'strlen'));
        };
        $toIntOrNull = function ($v) {
            if ($v === null || $v === '') return null;
            return (int)$v;
        };

        $filters = [
            'q'               => trim((string)$this->input->get_post('q')),
            'status'          => $normalizeArray($this->input->get_post('status')),
            'priority'        => $normalizeArray($this->input->get_post('priority')),
            'assignee_id'     => $this->input->get_post('assignee_id'),
            'addedfrom'       => $toIntOrNull($this->input->get_post('addedfrom', true)),
            'rel_type'        => (string) ($this->input->get_post('rel_type', true) ?: null),
            'rel_id'          => $toIntOrNull($this->input->get_post('rel_id', true)),
            'due_from'        => (string) ($this->input->get_post('due_from', true) ?: null),
            'due_to'          => (string) ($this->input->get_post('due_to', true)   ?: null),
            'visible_to_team' => $this->input->get_post('visible_to_team'),
        ];
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
        if (isset($filters['assignee_id']))     $filters['assignee_id']     = (int)$filters['assignee_id'];
        if (isset($filters['visible_to_team'])) $filters['visible_to_team'] = (int)$filters['visible_to_team'];
        if (!function_exists('staff_can') || !staff_can('view_global', 'tasks')) $filters['scoped_user_id'] = $this->uid;

        $limit  = (int)($this->input->get_post('limit')  ?? 50);
        $offset = (int)($this->input->get_post('offset') ?? 0);
        $limit  = $limit <= 0 ? 50 : ($limit > 500 ? 500 : $limit);
        $offset = max(0, $offset);
        $orderBy= (string)($this->input->get_post('order_by', true) ?? 'duedate');
        $dir    = strtolower((string)($this->input->get_post('dir', true) ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        try {
            $result = $this->tasks->list_tasks($filters, $limit, $offset, $orderBy, $dir);
            return $this->output->set_content_type('application/json')
                                ->set_output(json_encode(['success'=>true,'data'=>$result,'limit'=>$limit,'offset'=>$offset]));
        } catch (Throwable $e) {
            log_message('error', '[Tasks] list_json failed: ' . $e->getMessage());
            return $this->json_error('Unable to load tasks at the moment.', 500);
        }
    }



    public function kanban_move()
    {
        if (!$this->input->is_ajax_request()) {
            return $this->json_error('Invalid request', 400);
        }
    
        $this->output->set_content_type('application/json');
    
        $status = trim((string)$this->input->post('status'));
        $orders = $this->input->post('orders'); // array of [ ['id'=>..,'order'=>..], ... ]
        
        // NEW: the actual card that was dragged, if provided by JS
        $primaryId = (int)($this->input->post('primary_id') ?? 0);
        
        if ($status === '' || !is_array($orders)) {
            return $this->json_error('Missing status or orders', 400);
        }
    
        // Validate status domain
        $allowed = ['not_started','in_progress','in_review','completed','on_hold','cancelled'];
        if (!in_array($status, $allowed, true)) {
            return $this->json_error('Invalid status: ' . $status, 422);
        }
    
        // Validate and extract IDs
        $ids = [];
        foreach ($orders as $o) {
            $id = (int)($o['id'] ?? 0);
            if ($id > 0) $ids[] = $id;
        }
        if (!$ids) return $this->json_error('No valid task IDs provided', 400);
    
        // Verify tasks + permissions; capture pre-move statuses
        $before = []; // id => ['status'=>..., 'task'=>...]
        foreach ($ids as $id) {
            $task = $this->tasks->get_task_simple($id);
            if (!$task) return $this->json_error('Task not found: ' . $id, 404);
            if (!$this->policy->can_edit($task, $this->uid)) {
                return $this->json_error('No permission to modify task: ' . $id, 403);
            }
            $before[$id] = [
                'status' => (string)($task['status'] ?? ''),
                'task'   => $task,
            ];
        }
    
        $this->db->trans_start();
    
        try {
            // 1) Update status (only if different)
            $changed = []; // ids where status actually changed
            foreach ($ids as $idOne) {
                $old = (string)($before[$idOne]['status'] ?? '');
                if ($old !== $status) {
                    if (method_exists($this->tasks, 'set_status')) {
                        // false => suppress any heavy hooks; we’ll notify after commit
                        $this->tasks->set_status($idOne, $status, $this->uid, false);
                    } else {
                        $this->db->where('id', $idOne)->update('tasks', ['status' => $status]);
                    }
                    $changed[] = $idOne;
                }
            }
    
            // 2) Update Kanban orders
            $reordered = 0;
            foreach ($orders as $o) {
                $id    = (int)($o['id'] ?? 0);
                $order = (int)($o['order'] ?? 0);
                if ($id > 0 && $order > 0) {
                    if (method_exists($this->tasks, 'set_kanban_order')) {
                        $this->tasks->set_kanban_order($id, $order);
                    } else {
                        $this->db->where('id', $id)->update('tasks', ['kanban_order' => $order]);
                    }
                    $reordered++;
                }
            }
    
            // 3) Activity logs
            // 3a) Always: kanban_move (to_status + order)
            $moveLogs = array_map(function($o) use ($status) {
                return [
                    'taskid'      => (int)$o['id'],
                    'user_id'     => $this->uid,
                    'activity'    => 'kanban_move',
                    'description' => json_encode([
                        'to_status' => $status,
                        'order'     => (int)($o['order'] ?? 0),
                    ], JSON_UNESCAPED_SLASHES),
                ];
            }, $orders);
            $this->log_activity_batch($moveLogs);
    
            // 3b) Only for actually changed tasks: status_changed (from → to)
            if ($changed) {
                $statusLogs = [];
                foreach ($changed as $cid) {
                    $statusLogs[] = [
                        'taskid'      => $cid,
                        'user_id'     => $this->uid,
                        'activity'    => 'status_changed',
                        'description' => json_encode([
                            'from' => (string)($before[$cid]['status'] ?? ''),
                            'to'   => $status,
                        ], JSON_UNESCAPED_SLASHES),
                    ];
                }
                $this->log_activity_batch($statusLogs);
            }
    
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                throw new Exception('Database transaction failed');
            }
    
            // 4) Post-commit notifications ONLY for tasks whose status actually changed
            if (!empty($changed)) {
            
                // Default: notify all changed (fallback)
                $notifyIds = $changed;
            
                // If front-end told us which card was dragged, only notify for that one.
                if ($primaryId > 0) {
                    $notifyIds = array_values(array_intersect($notifyIds, [$primaryId]));
                }
            
                // Safety: if intersect removed everything for some reason, you can either:
                // - notify no one (quiet), OR
                // - fall back to first changed ID.
                // I'll keep it quiet – no spam is better.
                foreach ($notifyIds as $cid) {
                    $full = $this->tasks->get_task($cid);
                    if (is_array($full)) {
                        $this->send_status_change_notification($full, $status);
                    }
                }
            }
            
            return $this->json_ok([
                'message'            => 'Tasks updated successfully',
                'changed_status_ids' => $changed,
                'reordered'          => $reordered,
            ]);

    
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->json_error('Update failed: ' . $e->getMessage(), 500);
        }
    }



    /* =========================================================================
     * Checklist — primary endpoints
     * ========================================================================= */
public function checklist_add($taskId)
{
    $taskId = (int)$taskId;
    $task   = $this->tasks->get_task_simple($taskId);
    if (!$task) return $this->json_error('Task not found', 404);

    // Allow assignee or editors to add checklist items
    if (!$this->policy->can_edit($task, $this->uid) && !$this->policy->is_assignee($task, $this->uid)) {
        return $this->forbid_json_or_redirect();
    }

    // Required
    $desc = trim((string)$this->input->post('description', true));
    if ($desc === '') return $this->json_error('Description required.', 422);

    // NOTE: JS sends `list_order` — not `order`
    $listOrder = (int)$this->input->post('list_order', true);
    if ($listOrder <= 0) { $listOrder = 999; }

    // Persist assignee (nullable int)
    $assignedRaw = $this->input->post('assigned');
    $assigned    = ($assignedRaw === '' || $assignedRaw === null) ? null : (int)$assignedRaw;

    $payload = [
        'description' => $desc,
        'list_order'  => $listOrder,
        'addedfrom'   => $this->uid,
        'assigned'    => $assigned,           // <-- persist to task_checklist_items.assigned
        'finished'    => 0,
        'dateadded'   => date('Y-m-d H:i:s'), // optional but nice to keep consistent with schema
    ];

    $id = $this->tasks->add_checklist_item($taskId, $payload);
    if ($id <= 0) return $this->json_error('Failed to add item.', 500);

    // Prepare response (let model echo back resolved assignee if you support it)
    $out = ['id' => (int)$id] + $payload;

    $this->log_activity($taskId, 'checklist_item_added', [
        'item_id'     => (int)$id,
        'description' => $this->_excerpt($desc),
        'assigned'    => $assigned,
        'order'       => $listOrder
    ]);

    if ($this->input->is_ajax_request()) {
        $sum = $this->tasks->checklist_summary($taskId);
        return $this->json_ok([
            'id'      => (int)$id,
            'data'    => $out,
            'summary' => $sum
        ]);
    }

    set_alert('success', 'Checklist item added.');
    redirect('tasks/view/' . $taskId);
}


    public function toggle_checklist_item($itemId)
    {
        $itemId = (int)$itemId;
        $item   = $this->tasks->get_checklist_item($itemId);
        if (!$item) return $this->json_error('Item not found', 404);

        $task = $this->tasks->get_task_simple((int)$item['taskid']);
        if (!$task) return $this->json_error('Task not found', 404);
        if (!$this->policy->can_edit($task, $this->uid) && !$this->policy->is_assignee($task, $this->uid)) return $this->json_error('Forbidden', 403);

        $newVal = $this->tasks->toggle_checklist_item($itemId, $this->uid);

        $this->log_activity((int)$item['taskid'], 'Checklist Updated', [
            'item_id'  => $itemId,
            'finished' => (int)$newVal
        ]);
        
        if ($newVal === -1) return $this->json_error('Item not found', 404);

        $sum = $this->tasks->checklist_summary((int)$item['taskid']);
        return $this->json_ok(['finished'=>(int)$newVal,'summary'=>$sum,'itemId'=>$itemId]);
    }

    public function delete_checklist_item($itemId)
    {
        $itemId = (int)$itemId;
        $item   = $this->tasks->get_checklist_item($itemId);
        if (!$item) return $this->json_error('Item not found', 404);

        $task = $this->tasks->get_task_simple((int)$item['taskid']);
        if (!$task) return $this->json_error('Task not found', 404);
        if (!$this->policy->can_edit($task, $this->uid) && !$this->policy->is_assignee($task, $this->uid)) return $this->forbid_json_or_redirect();

        $ok = $this->tasks->delete_checklist_item($itemId);
        
        if ($ok) {
            $this->log_activity((int)$item['taskid'], 'Deleted checklist item', [
                'item_id' => $itemId,
                'desc'    => $this->_excerpt((string)($item['description'] ?? ''))
            ]);
        }
        
        if ($this->input->is_ajax_request()) {
            $sum = $this->tasks->checklist_summary((int)$item['taskid']);
            return $ok ? $this->json_ok(['summary'=>$sum]) : $this->json_error('Delete failed', 500);
        }
        set_alert($ok ? 'success' : 'danger', $ok ? 'Item removed.' : 'Delete failed.');
        redirect('tasks/view/'.(int)$item['taskid']);
    }

    /* ---------------------------------------------------------------------
     * Checklist — URL adapters for view:
     *   /tasks/checklist/toggle/{id}
     *   /tasks/checklist/delete/{id}
     *   /tasks/checklist/add/{taskId}
     * ------------------------------------------------------------------- */
    public function checklist($action = null, $id = null)
    {
        $action = (string)$action;
        $id     = (int)$id;

        if ($action === 'toggle') return $this->toggle_checklist_item($id);
        if ($action === 'delete') return $this->delete_checklist_item($id);
        if ($action === 'add')    return $this->checklist_add($id);

        show_404();
    }

    /* =========================================================================
     * Followers
     * ========================================================================= */
    public function set_followers($taskId)
    {
        $taskId = (int)$taskId;
        $task   = $this->tasks->get_task_simple($taskId);
        if (!$task) return $this->json_error('Task not found', 404);
        if (!$this->policy->can_manage_followers($task, $this->uid)) return $this->forbid_json_or_redirect();

        $followers = $this->input->post('followers');
        $followers = is_array($followers) ? array_values(array_filter(array_map('intval', $followers), static fn($v)=>$v>0)) : [];

        $ok = $this->tasks->set_followers($taskId, $followers);
        
        if ($ok) {
            $this->log_activity($taskId, 'followers_set', [
                'followers' => $followers
            ]);
        }

        if ($this->input->is_ajax_request()) {
            $resolved = $this->users->get_resolved_users($followers);
            return $ok ? $this->json_ok(['followers'=>$resolved]) : $this->json_error('Save failed', 500);
        }
        set_alert($ok ? 'success' : 'danger', $ok ? 'Followers updated.' : 'Failed to update followers.');
        redirect('tasks/view/'.$taskId);
    }

    public function add_follower($taskId)
    {
        $taskId = (int)$taskId;
        $task   = $this->tasks->get_task_simple($taskId);
        if (!$task) return $this->json_error('Task not found', 404);
        if (!$this->policy->can_manage_followers($task, $this->uid)) return $this->forbid_json_or_redirect();

        $uid = (int)$this->input->post('user_id');
        if ($uid <= 0) return $this->json_error('Invalid user', 422);

        $ok = $this->tasks->add_follower($taskId, $uid);

        if ($ok) {
            $this->log_activity($taskId, 'Added a task follower', ['user_id' => $uid]);
        }
        
        if ($this->input->is_ajax_request()) {
            $card = $this->users->get_resolved_users([$uid])[0] ?? ['id'=>$uid,'name'=>'User #'.$uid,'avatar'=>null];
            return $ok ? $this->json_ok(['follower'=>$card]) : $this->json_error('Failed to add', 500);
        }
        set_alert($ok ? 'success' : 'danger', $ok ? 'Follower added.' : 'Failed to add follower.');
        redirect('tasks/view/'.$taskId);
    }

    public function remove_follower($taskId)
    {
        $taskId = (int)$taskId;
        $task   = $this->tasks->get_task_simple($taskId);
        if (!$task) return $this->json_error('Task not found', 404);
        if (!$this->policy->can_manage_followers($task, $this->uid)) return $this->forbid_json_or_redirect();

        $uid = (int)$this->input->post('user_id');
        if ($uid <= 0) return $this->json_error('Invalid user', 422);

        $ok = $this->tasks->remove_follower($taskId, $uid);

        if ($ok) {
            $this->log_activity($taskId, 'follower_removed', ['user_id' => $uid]);
        }
        
        if ($this->input->is_ajax_request()) return $ok ? $this->json_ok(['removed_id'=>$uid]) : $this->json_error('Failed to remove', 500);

        set_alert($ok ? 'success' : 'danger', $ok ? 'Follower removed.' : 'Failed to remove.');
        redirect('tasks/view/'.$taskId);
    }

    /* =========================================================================
     * Attachments (primary)
     * ========================================================================= */
/* =========================================================================
 * Attachments (router)
 *   /tasks/attachments/{taskId}/upload
 *   /tasks/attachments/{id}/delete
 * ========================================================================= */
public function attachments($arg1 = null, $arg2 = null)
{
    // /tasks/attachments/{taskId}/upload
    if ($arg1 !== null && $arg2 === 'upload' && ctype_digit((string)$arg1)) {
        return $this->attachments_upload((int)$arg1);
    }
    // /tasks/attachments/{id}/delete
    if ($arg1 !== null && $arg2 === 'delete' && ctype_digit((string)$arg1)) {
        return $this->attachments_delete((int)$arg1);
    }
    show_404();
}

/* =========================================================================
 * Attachments (upload) — CI3 Upload requires a real absolute path
 * ========================================================================= */
public function attachments_upload($taskId)
{
    $taskId = (int)$taskId;
    $task   = $this->tasks->get_task_simple($taskId);
    if (!$task) return $this->forbid_json_or_redirect(404);

    if (!$this->policy->can_edit($task, $this->uid) && !$this->policy->is_assignee($task, $this->uid)) {
        return $this->forbid_json_or_redirect();
    }

    // --- Resolve absolute filesystem root (robust across hosts)
    $root = rtrim((string) (defined('FCPATH') && FCPATH ? FCPATH : dirname(APPPATH, 1)), "/\\");
    if ($root === '' || $root === '.' || !is_dir($root)) {
        $root = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
    }
    if ($root === '' || !is_dir($root)) {
        $msg = 'Cannot resolve document root for uploads.';
        return $this->input->is_ajax_request() ? $this->json_error($msg, 500) : (set_alert('warning', $msg) || redirect('tasks/view/'.$taskId));
    }

    // --- Build target folders
    $uploadsBaseAbs = $root . DIRECTORY_SEPARATOR . 'uploads';
    $tasksDirAbs    = $uploadsBaseAbs . DIRECTORY_SEPARATOR . 'tasks';

    // --- Ensure existence + writability
    if (!is_dir($uploadsBaseAbs) && !@mkdir($uploadsBaseAbs, 0775, true)) {
        $msg = 'Upload base folder could not be created: ' . $uploadsBaseAbs;
        return $this->input->is_ajax_request() ? $this->json_error($msg, 500) : (set_alert('warning', $msg) || redirect('tasks/view/'.$taskId));
    }
    if (!is_dir($tasksDirAbs) && !@mkdir($tasksDirAbs, 0775, true)) {
        $msg = 'Upload folder could not be created: ' . $tasksDirAbs;
        return $this->input->is_ajax_request() ? $this->json_error($msg, 500) : (set_alert('warning', $msg) || redirect('tasks/view/'.$taskId));
    }
    // last-ditch perms
    if (!is_writable($tasksDirAbs)) { @chmod($tasksDirAbs, 0775); }
    if (!is_writable($tasksDirAbs)) {
        $msg = 'Upload folder is not writable: ' . $tasksDirAbs . ' — fix ownership or set perms (e.g., 775).';
        return $this->input->is_ajax_request() ? $this->json_error($msg, 500) : (set_alert('warning', $msg) || redirect('tasks/view/'.$taskId));
    }

    // --- CI Upload config (ABSOLUTE path with trailing slash)
    $allowedCsv = (function_exists('get_setting') ? get_setting('tasks_allowed_mime_types') : '') ?: 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip';

    $config = [
        'upload_path'   => rtrim($tasksDirAbs, "/\\") . DIRECTORY_SEPARATOR,
        'allowed_types' => str_replace(',', '|', $allowedCsv),
        'max_size'      => 10240,     // 10MB
        'encrypt_name'  => true,
    ];

    $this->load->library('upload');
    $this->upload->initialize($config, true);

    if (!$this->upload->do_upload('file')) {
        $err = trim($this->upload->display_errors('', '')) ?: 'Upload failed.';
        log_message('error', '[Tasks] Upload error: ' . $err);
        return $this->input->is_ajax_request() ? $this->json_error($err, 422) : (set_alert('warning', $err) || redirect('tasks/view/'.$taskId));
    }
    
    $data = $this->upload->data(); // has file_name, full_path, client_name, etc.

    // --- DB save (web-relative path stored; absolute path never stored)
    $relPath = 'uploads/tasks/' . ($data['file_name'] ?? '');
    $save = [
        'taskid'       => $taskId,
        'file_name'    => $data['client_name'] ?? $data['file_name'],
        'file_path'    => $relPath,
        'uploaded_by'  => $this->uid,
        'uploaded_at'  => date('Y-m-d H:i:s'),
    ];

    $attId = (int)$this->tasks->create_attachment($save);
    if ($attId <= 0) {
        // Rollback file if DB insert failed
        $abs = $data['full_path'] ?? ($tasksDirAbs . DIRECTORY_SEPARATOR . ($data['file_name'] ?? ''));
        if (is_file($abs)) @unlink($abs);
        $msg = 'Failed to save attachment record.';
        return $this->input->is_ajax_request() ? $this->json_error($msg, 500) : (set_alert('warning', $msg) || redirect('tasks/view/'.$taskId));
    }

    $this->log_activity($taskId, 'attachment_uploaded', [
        'attachment_id' => $attId,
        'file_name'     => $save['file_name'],
        'file_path'     => $save['file_path'],
        'size_bytes'    => (int)($data['file_size'] ? $data['file_size'] * 1024 : 0),
        'mime'          => (string)($data['file_type'] ?? '')
    ]);

if ($this->input->is_ajax_request()) {
    $total = method_exists($this->tasks, 'count_attachments')
        ? $this->tasks->count_attachments($taskId)
        : count($this->tasks->list_attachments($taskId)); // fallback

    return $this->json_ok([
        'attachment'   => ['id' => $attId] + $save,
        'total_files'  => $total,
    ]);
}

set_alert('success', 'File uploaded.');
redirect('tasks/view/'.$taskId);

}

/* =========================================================================
 * Attachments (delete)
 * ========================================================================= */
public function attachments_delete($attachmentId)
{
    $att = $this->tasks->get_attachment((int)$attachmentId);
    if (!$att) return $this->forbid_json_or_redirect(404, 'Attachment not found.');

    $task = $this->tasks->get_task_simple((int)$att['taskid']);
    if (!$task) return $this->forbid_json_or_redirect(404, 'Task not found.');
    if (!$this->policy->can_edit($task, $this->uid) && !$this->policy->is_assignee($task, $this->uid)) {
        return $this->forbid_json_or_redirect();
    }

    $ok = $this->tasks->delete_attachment((int)$attachmentId);

    if ($ok) {
        $this->log_activity((int)$att['taskid'], 'attachment_deleted', [
            'attachment_id' => (int)$attachmentId,
            'file_name'     => (string)($att['file_name'] ?? ''),
            'file_path'     => (string)($att['file_path'] ?? '')
        ]);
    }

    // Remove file from disk (use the same robust root resolution)
    if ($ok && !empty($att['file_path'])) {
        $abs = $this->absolute_path((string)$att['file_path']);
        if (is_file($abs)) @unlink($abs);
    }

if ($this->input->is_ajax_request()) {
    if (!$ok) {
        return $this->json_error('Delete failed', 500);
    }

    $total = method_exists($this->tasks, 'count_attachments')
        ? $this->tasks->count_attachments((int)$att['taskid'])
        : count($this->tasks->list_attachments((int)$att['taskid']));

    return $this->json_ok(['total_files' => $total]);
}

set_alert($ok ? 'success' : 'danger', $ok ? 'Attachment deleted.' : 'Delete failed.');
redirect('tasks/view/' . (int)$att['taskid']);

}


    /* =========================================================================
     * Delete task
     * ========================================================================= */
    public function delete($id)
    {
        $id = (int)$id;
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            if ($this->input->is_ajax_request()) return $this->json_error('Invalid method.', 405);
            set_alert('warning', 'Invalid request method.'); redirect('tasks'); return;
        }

        $task = $this->tasks->get_task_simple($id);
        if (!$task) {
            if ($this->input->is_ajax_request()) return $this->json_error('Task not found.', 404);
            set_alert('warning', 'Task not found.'); redirect('tasks'); return;
        }

        if (!$this->policy->can_delete($task, $this->uid) && !$this->policy->is_super_admin($this->uid)) {
            return $this->forbid_json_or_redirect();
        }

        $attachments = $this->tasks->list_attachments($id);

        $snap = [
            'title'       => (string)($task['name'] ?? ''),
            'assignee'    => (int)($task['assignee_id'] ?? 0) ?: null,
            'status'      => (string)($task['status'] ?? ''),
            'attachments' => count((array)$attachments)
        ];
        
         $ok = $this->tasks->delete_task($id);
         if ($ok) { $this->log_activity($id, 'task_deleted', $snap); }

        if ($ok) {
            foreach ((array)$attachments as $att) {
                $rel = (string)($att['file_path'] ?? '');
                if ($rel !== '') {
                    $fs = $this->absolute_path($rel);
                    if (is_file($fs)) @unlink($fs);
                }
            }
            if ($this->input->is_ajax_request()) return $this->json_ok(['message'=>'Task deleted successfully.']);
            set_alert('success', 'Task deleted successfully.'); redirect('tasks'); return;
        }

        if ($this->input->is_ajax_request()) return $this->json_error('Failed to delete task.', 500);
        set_alert('warning', 'Failed to delete task.'); redirect('tasks');
    }

    /* =========================================================================
     * Small responders / helpers
     * ========================================================================= */
    private function json_ok(array $payload = [])
    {
        return $this->output->set_content_type('application/json')
                            ->set_output(json_encode(['success'=>true] + $payload));
    }

    private function json_error(string $message, int $status = 400)
    {
        return $this->output->set_status_header($status)
                            ->set_content_type('application/json')
                            ->set_output(json_encode(['success'=>false,'message'=>$message]));
    }

    private function forbid_json_or_redirect(int $status = 403, string $msg = 'Forbidden')
    {
        if ($this->input->is_ajax_request()) return $this->json_error($msg, $status);
        if ($status === 404) show_404();
        $this->render_403_and_exit();
    }

    private function render_403_and_exit()
    {
        $html = $this->load->view('errors/html/error_403',
            ['heading' => 'Access Denied', 'message' => 'You do not have permission to access this resource.'], true);
        $this->output->set_status_header(403)
                     ->set_content_type('text/html; charset=UTF-8')
                     ->set_output($html)
                     ->_display();
        exit;
    }

    private function setting_yes(string $key, string $default = 'yes'): bool
    {
        return (function_exists('get_setting') ? (get_setting($key, $default) === 'yes') : ($default === 'yes'));
    }

    /* =========================================================================
     * Notification helpers
     * ========================================================================= */
    private function send_assignment_notification($taskId, array $taskData)
    {
        $assigneeId = (int)($taskData['assignee_id'] ?? 0);
        if ($assigneeId <= 0) return;

        $taskUrl = base_url('tasks/view/' . $taskId);
        $title   = $taskData['name'] ?? ('#'.$taskId);

        if ($this->setting_yes('tasks_notify_user_on_assigned') && function_exists('notify_user')) {
            notify_user($assigneeId,'tasks','Task assigned to you','You were assigned a new task: "'.$title.'".',$taskUrl,['channels'=>['in_app']]);
        }
        if ($this->setting_yes('tasks_email_user_on_assigned')) {
            $this->send_task_email_bare($assigneeId,'Task assigned: '.$title,$this->render_simple_task_email('A task was assigned to you.',$title,$taskUrl));
        }
    }

    private function send_comment_notification(?array $taskData)
    {
        if (!$taskData) return;
        $id = (int)$taskData['id'];
        $taskUrl = base_url('tasks/view/' . $id);
        $title   = $taskData['name'] ?? ('#'.$id);

        $recipients = [];
        $assigneeId = (int)($taskData['assignee_id'] ?? 0);
        if ($assigneeId > 0) $recipients[$assigneeId] = true;
        foreach ((array)($taskData['followers'] ?? []) as $fid) { $fid=(int)$fid; if ($fid>0) $recipients[$fid]=true; }
        unset($recipients[$this->uid]);
        if (!$recipients) return;
        $uids = array_keys($recipients);

        if ($this->setting_yes('tasks_notify_followers_on_comment') && function_exists('notify_user')) {
            foreach ($uids as $uid) notify_user($uid,'tasks','New comment on a task','A new comment was posted on: "'.$title.'".',$taskUrl);
        }
        if ($this->setting_yes('tasks_email_followers_on_comment')) {
            foreach ($uids as $uid) $this->send_task_email_bare($uid,'New comment: '.$title,$this->render_simple_task_email('A new comment was posted on a task.',$title,$taskUrl));
        }
    }

    private function send_status_change_notification(array $taskData, string $newStatus)
    {
        $id = (int)$taskData['id'];
        $taskUrl = base_url('tasks/view/' . $id);
        $title   = $taskData['name'] ?? ('#'.$id);
        $statusLabel = ucfirst(str_replace('_',' ', $newStatus));

        $recipients = [];
        $assigneeId = (int)($taskData['assignee_id'] ?? 0);
        if ($assigneeId > 0) $recipients[$assigneeId] = true;
        foreach ((array)($taskData['followers'] ?? []) as $fid) { $fid=(int)$fid; if ($fid>0) $recipients[$fid]=true; }
        unset($recipients[$this->uid]);
        if (!$recipients) return;
        $uids = array_keys($recipients);

        if ($this->setting_yes('tasks_notify_user_on_status_change') && function_exists('notify_user')) {
            foreach ($uids as $uid) notify_user($uid,'tasks','Task status updated','Status changed for "' . $title . '" to ' . $statusLabel . '.',$taskUrl);
        }
        if ($this->setting_yes('tasks_email_user_on_status_change')) {
            foreach ($uids as $uid) $this->send_task_email_bare($uid,'Task status updated: '.$title,$this->render_simple_task_email('Status changed to: '.$statusLabel.'.',$title,$taskUrl));
        }
    }

    private function send_task_email_bare(int $userId, string $subject, string $htmlBody): void
    {
        if (!function_exists('app_mailer')) return;
        $user = $this->users->get_user_by_id($userId);
        $to   = trim((string)($user['email'] ?? ''));
        if ($to === '') return;

        app_mailer()->send(['to'=>$to,'subject'=>$subject,'message'=>$htmlBody,'mailtype'=>'html']);
    }

    private function render_simple_task_email(string $lead, string $taskTitle, string $link): string
    {
        $brand = function_exists('get_option') ? (get_option('companyname') ?: 'Tasks') : 'Tasks';
        $lead  = htmlspecialchars($lead, ENT_QUOTES, 'UTF-8');
        $ts    = htmlspecialchars($taskTitle, ENT_QUOTES, 'UTF-8');
        $href  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222;">
            <p>'.$lead.'</p>
            <p><strong>Task:</strong> '.$ts.'</p>
            <p><a href="'.$href.'">Open task</a></p>
            <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">
            <p style="color:#666">'.$brand.'</p>
        </div>';
    }



    /**
     * Centralized activity logger.
     * Writes to table: task_activity(id, taskid, user_id, activity, description, dateadded)
     */
    private function log_activity(int $taskId, string $activity, array $payload = [], ?int $userId = null): void
    {
        try {
            $row = [
                'taskid'     => $taskId,
                'user_id'    => $userId ?? $this->uid ?: null, // allow null for system actions
                'activity'   => substr($activity, 0, 64),
                'description'=> json_encode([
                    'payload' => $payload,
                    'meta'    => [
                        'ip'  => $this->input->ip_address(),
                        'ua'  => (string) $this->input->user_agent(),
                        'ts'  => date('Y-m-d H:i:s'),
                    ]
                ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'dateadded'  => date('Y-m-d H:i:s'),
            ];
            $this->db->insert('task_activity', $row);
        } catch (Throwable $e) {
            log_message('error', '[task_activity] log failed: '.$e->getMessage());
            // never break UX for logging
        }
    }
    
    /** Convenience to safely excerpt large bodies for logs */
    private function _excerpt(string $str, int $len = 180): string
    {
        $s = trim(strip_tags($str));
        if (mb_strlen($s) <= $len) return $s;
        return mb_substr($s, 0, $len - 1) . '…';
    }



    // /tasks/comment/{commentId}/replies/{taskId}         (GET)
    // /tasks/comment/{commentId}/replies/add/{taskId}     (POST)
    public function comment($commentId = null, $seg2 = null, $seg3 = null, $seg4 = null)
    {
        $commentId = (int)$commentId;
    
        if ($seg2 === 'replies') {
            if ($seg3 !== null && ctype_digit((string)$seg3) && $seg4 === null && $this->input->method(true) === 'GET') {
                return $this->list_replies((int)$seg3, $commentId);
            }
            if ($seg3 === 'add' && $seg4 !== null && ctype_digit((string)$seg4) && $this->input->method(true) === 'POST') {
                return $this->add_reply((int)$seg4, $commentId);
            }
        }
        show_404();
    }
    
    
    
    public function list_replies($taskId, $commentId)
    {
        $taskId    = (int)$taskId;
        $commentId = (int)$commentId;
    
        $task = $this->tasks->get_task_simple($taskId);
        if (!$task) return $this->json_error('Task not found', 404);
        if (!$this->policy->can_view($task, $this->uid)) return $this->json_error('Forbidden', 403);
    
        // Validate parent comment belongs to this task
        $parent = $this->db->select('id')->from('task_comments')
            ->where(['id'=>$commentId, 'taskid'=>$taskId])->get()->row_array();
        if (!$parent) return $this->json_error('Comment not found', 404);
    
        // 1) Primary: model-level fetch (assumes it supports task + comment filtering)
        $rows = $this->replies->list_for_comment($taskId, $commentId);
    
        // 2) Fallback: if nothing returned, try comment-only (for schemas without taskid on replies)
        if (empty($rows)) {
            $rows = $this->db->select('id, comment_id, user_id, reply, dateadded')
                ->from('task_comment_replies')
                ->where('comment_id', $commentId)
                ->order_by('id', 'ASC')->get()->result_array();
        }
    
        if (empty($rows)) {
            // nothing to show; success true with empty rows (UI keeps block hidden)
            return $this->json_ok(['rows'=>[]]);
        }
    
        // hydrate authors
        $userIds = [];
        foreach ($rows as $r) { $u = (int)($r['user_id'] ?? 0); if ($u>0) $userIds[] = $u; }
        $userIds = array_values(array_unique($userIds));
    
        $userMap = [];
        if ($userIds) {
            $us = $this->db->select('id, firstname, lastname, profile_image')
                ->from('users')->where_in('id', $userIds)->get()->result_array();
            foreach ($us as $u) {
                $nm  = trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? ''));
                $nm  = $nm !== '' ? $nm : 'User #'.(int)$u['id'];
                $ava = $this->_normalize_avatar_url((string)($u['profile_image'] ?? ''));
                $userMap[(int)$u['id']] = ['name'=>$nm, 'avatar'=>$ava];
            }
        }
    
        foreach ($rows as &$r) {
            $u = $userMap[(int)($r['user_id'] ?? 0)] ?? null;
            $r['author_name']   = $u['name']   ?? ('User #'.(int)($r['user_id'] ?? 0));
            $r['author_avatar'] = $u['avatar'] ?? '';
        }
        unset($r);
    
        return $this->json_ok(['rows'=>$rows]);
    }
    
    
    public function add_reply($taskId, $commentId)
    {
        $taskId    = (int)$taskId;
        $commentId = (int)$commentId;
        $userId    = (int)($this->session->userdata('user_id') ?? 0);
        $reply     = trim((string)$this->input->post('reply'));
    
        if ($userId <= 0)  return $this->json_error('Auth required', 401);
        if ($reply === '') return $this->json_error('Reply is required', 422);
    
        // Validate task & parent comment
        $task = $this->tasks->get_task_simple($taskId);
        if (!$task) return $this->json_error('Task not found', 404);
    
        $parent = $this->db->select('id')->from('task_comments')
            ->where(['id'=>$commentId, 'taskid'=>$taskId])->get()->row_array();
        if (!$parent) return $this->json_error('Comment not found', 404);
    
        // Permission: editor or assignee or follower
        $followers = [];
        $raw = $task['followers'] ?? [];
        if (is_array($raw)) { $followers = array_map('intval', $raw); }
        elseif (is_string($raw) && $raw!=='') {
            $dec = json_decode($raw, true);
            $followers = is_array($dec) ? array_map('intval', $dec)
                                        : array_map('intval', preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY));
        } elseif (is_numeric($raw)) { $followers = [(int)$raw]; }
    
        $can = $this->policy->can_edit($task, $this->uid)
            || $this->policy->is_assignee($task, $this->uid)
            || in_array($this->uid, $followers, true);
        if (!$can) return $this->json_error('Forbidden', 403);
    
        // Insert via model (some models return bool/0)
        $id = $this->replies->add($taskId, $commentId, $userId, $reply);
    
        // Robust fallback: accept insert_id or last matching row if model didn’t return an ID
        if ((int)$id <= 0) {
            $id = (int)$this->db->insert_id();
            if ($id <= 0) {
                // last-row lookup within 10s window
                $recent = $this->db->select('id')
                    ->from('task_comment_replies')
                    ->where(['comment_id' => $commentId, 'user_id' => $userId, 'reply' => $reply])
                    ->where('dateadded >=', date('Y-m-d H:i:s', time() - 10))
                    ->order_by('id', 'DESC')->limit(1)->get()->row_array();
                $id = (int)($recent['id'] ?? 0);
            }
        }
    
        if ($id <= 0) return $this->json_error('Failed to add reply', 500);
    
        // Hydrate author for client
        $u = $this->db->select('firstname, lastname, profile_image')->from('users')->where('id',$userId)->get()->row_array();
        $name = trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? '')); if ($name === '') $name = 'User #'.$userId;
        $img  = $this->_normalize_avatar_url((string)($u['profile_image'] ?? ''));
    
        $this->log_activity($taskId, 'Replied to a comment', [
            'comment_id' => $commentId,
            'reply_id'   => $id,
            'excerpt'    => $this->_excerpt($reply)
        ]);
        
        return $this->json_ok([
            'row' => [
                'id'            => $id,
                'taskid'        => $taskId,
                'comment_id'    => $commentId,
                'user_id'       => $userId,
                'reply'         => $reply,
                'dateadded'     => date('Y-m-d H:i:s'),
                'author_name'   => $name,
                'author_avatar' => $img,
            ],
        ]);
    }

    private function _normalize_avatar_url(string $path): string
    {
        $p = trim($path);
        if ($p === '') return '';
        if (preg_match('#^(https?:)?//#i', $p) || (strpos($p, 'data:') === 0)) return $p;
        $p = ltrim($p, '/\\');
        return rtrim(base_url(), '/').'/'.$p;
    }
    
    
    /**
     * Update task core details (title + description).
     * Accepts:
     *  - name                (string, optional; min 3 when present)
     *  - description_html    (string, optional; preferred when present; NOT xss_cleaned)
     *  - description         (string, optional; fallback when HTML not provided; NOT xss_cleaned)
     *
     * Supports:
     *  - POST form (application/x-www-form-urlencoded, multipart/form-data)
     *  - POST JSON (application/json)
     *
     * Permissions: must be able to edit the task.
     * Responses:
     *  - AJAX => JSON (application/json)
     *  - Non-AJAX => redirect back to /tasks/view/{id}
     */
    public function update_description($id)
    {
        $id   = (int)$id;
        $task = $this->tasks->get_task_simple($id);
        if (!$task) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Task not found.', 404);
            }
            show_404();
            return;
        }
    
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Invalid method.', 405);
            }
            set_alert('warning', 'Invalid request method.');
            redirect('tasks/view/' . $id);
            return;
        }
    
        // Permission gate
        if (!$this->policy->can_edit($task, $this->uid)) {
            return $this->forbid_json_or_redirect();
        }
    
        /* -------- Parse input (form or JSON) -------- */
        $contentType = strtolower((string)($this->input->server('CONTENT_TYPE') ?? $this->input->get_request_header('Content-Type', true)));
        $isJson = strpos($contentType, 'application/json') !== false;
    
        $in = [];
        if ($isJson) {
            $raw = $this->input->raw_input_stream;
            $dec = json_decode($raw, true);
            $in  = is_array($dec) ? $dec : [];
        }
    
        // Title (safe mode: TRUE => xss_clean + trim)
        $name = $isJson
            ? (isset($in['name']) ? trim((string)$in['name']) : '')
            : (string)$this->input->post('name', true);
    
        // Description: allow HTML (no xss_clean so pass FALSE)
        $descHtml = $isJson
            ? (isset($in['description_html']) ? (string)$in['description_html'] : '')
            : (string)$this->input->post('description_html', false);
    
        $descPlain = $isJson
            ? (isset($in['description']) ? (string)$in['description'] : '')
            : (string)$this->input->post('description', false);
    
        $description = trim($descHtml) !== '' ? $descHtml : $descPlain;
    
        /* -------- Validate -------- */
        // If neither field provided, reject
        $hasName = (trim($name) !== '');
        $hasDesc = (trim((string)$description) !== '');
    
        if (!$hasName && !$hasDesc) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Provide at least one field to update.', 422);
            }
            set_alert('warning', 'Provide at least one field to update.');
            redirect('tasks/view/' . $id);
            return;
        }
    
        if ($hasName && mb_strlen($name) < 3) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Title must be at least 3 characters.', 422);
            }
            set_alert('warning', 'Title must be at least 3 characters.');
            redirect('tasks/view/' . $id);
            return;
        }
    
        /* -------- Build update payload -------- */
        $fields = [];
        if ($hasName)  $fields['name'] = $name;
        if ($hasDesc)  $fields['description'] = $description; // store as-is (HTML allowed)
    
        if (!$fields) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Nothing to update.', 422);
            }
            set_alert('info', 'Nothing to update.');
            redirect('tasks/view/' . $id);
            return;
        }
    
        /* -------- Persist -------- */
        $ok = false;
        if (method_exists($this->tasks, 'update_task_fields')) {
            $ok = (bool)$this->tasks->update_task_fields($id, $fields);
        } else {
            // Fallback (kept minimal; ideally use model method)
            $this->db->where('id', $id)->update('tasks', $fields);
            $ok = ($this->db->affected_rows() >= 0);
        }
    
        if (!$ok) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Failed to update task details.', 500);
            }
            set_alert('danger', 'Failed to update task details.');
            redirect('tasks/view/' . $id);
            return;
        }
    
        /* -------- Respond -------- */
        if ($this->input->is_ajax_request()) {
            // Return the updated snapshot (minimal)
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'id'      => $id,
                    'updated' => array_keys($fields),
                    'data'    => $fields
                ]));
        }
    
        set_alert('success', 'Task details updated.');
        redirect('tasks/view/' . $id);
    }


    private function log_activity_batch(array $rows): void
    {
        if (!$rows) return;
        $now = date('Y-m-d H:i:s');
        foreach ($rows as &$r) { $r['dateadded'] = $now; }
        unset($r);
        try { $this->db->insert_batch('task_activity', $rows); }
        catch (Throwable $e) { log_message('error', '[task_activity] batch failed: '.$e->getMessage()); }
    }
    


    private function absolute_path(string $rel): string
    {
        $rel = ltrim($rel, "/\\");
        $root = rtrim((string) (defined('FCPATH') && FCPATH ? FCPATH : dirname(APPPATH, 1)), "/\\");
        if ($root === '' || !is_dir($root)) {
            $root = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
        }
        return $root . DIRECTORY_SEPARATOR . $rel;
    }

    private function get_setting(string $key, $default = null) {
        if (function_exists('get_setting')) return get_setting($key, $default);
        if (function_exists('get_option'))  return get_option($key) ?? $default;
        return $default;
    }


public function modal($id)
{
    $id = (int)$id;
    $task = $this->tasks->get_task($id);
    if (!$task) { show_404(); }

    // Optional enrichments
    $assignee = $task['assignee_id'] ? $this->db->where('id',$task['assignee_id'])->get('users')->row_array() : null;
    $followers = [];
    foreach (($task['followers'] ?? []) as $uid) {
        $row = $this->db->where('id',(int)$uid)->get('users')->row_array();
        if ($row) $followers[] = $row;
    }
    $checklist   = $this->tasks->list_checklist($id);
    $attachments = $this->tasks->list_attachments($id);
    $activity    = $this->tasks->list_activity($id, 15);

    // Render body only; CalendarModuleBridge injects this into #viewEventModal .modal-body
    $this->load->view('tasks/modals/quick_view', compact('task','assignee','followers','checklist','attachments','activity'));
}
    
}

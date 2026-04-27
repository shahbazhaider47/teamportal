<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_api Controller
 * All AJAX endpoints for the Team Chat module.
 * Every method returns JSON. No views are loaded here.
 *
 * Base URL: /team_chat_api/{method}
 */
class Team_chat_api extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!module_is_active(TEAM_CHAT_MODULE_NAME)) {
            $this->_json_error('Module inactive', 403);
        }

        if (!staff_can('access', 'team_chat')) {
            $this->_json_error('Access denied', 403);
        }

        $this->load->model([
            'User_model',
            'team_chat/Team_chat_conversation_model',
            'team_chat/Team_chat_message_model',
            'team_chat/Team_chat_attachment_model',
            'team_chat/Team_chat_reaction_model',
            'team_chat/Team_chat_notification_model',
        ]);

        $this->load->helper('team_chat/team_chat');
    }

    // ─────────────────────────────────────────────────────────
    // Helper — current user ID (replaces get_staff_user_id())
    // ─────────────────────────────────────────────────────────
    private function _uid(): int
    {
        return (int)$this->session->userdata('user_id');
    }

    // =========================================================
    // CONVERSATIONS
    // =========================================================

    public function conversations()
    {
        $this->_only_get();
        $this->_json_success(
            $this->Team_chat_conversation_model->get_user_conversations($this->_uid())
        );
    }

    public function conversation($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_uid();

        if (!$conversation_id) {
            $this->_json_error('Invalid conversation ID');
        }

        $this->_require_member($conversation_id, $user_id);

        $conversation             = $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id);
        $conversation['members']  = $this->Team_chat_conversation_model->get_members($conversation_id);

        $this->_json_success($conversation);
    }

    public function create_direct()
    {
        $this->_only_post();

        $user_id        = $this->_uid();
        $target_user_id = (int)$this->input->post('target_user_id');

        if (!$target_user_id || $target_user_id === $user_id) {
            $this->_json_error('Invalid target user');
        }

        $target = $this->User_model->get($target_user_id);

        if (!$target || !$target['is_active']) {
            $this->_json_error('User not found');
        }

        $conversation = $this->Team_chat_conversation_model->get_or_create_direct($user_id, $target_user_id);

        if (!$conversation) {
            $this->_json_error('Could not create conversation');
        }

        $this->_json_success($conversation);
    }

    public function create_group()
    {
        $this->_only_post();

        $user_id    = $this->_uid();
        $name       = trim($this->input->post('name'));
        $member_ids = $this->input->post('member_ids');

        if (empty($name)) {
            $this->_json_error('Group name is required');
        }

        if (!is_array($member_ids) || count($member_ids) < 1) {
            $this->_json_error('At least one member is required');
        }

        $member_ids   = array_map('intval', $member_ids);
        $member_ids[] = $user_id;
        $member_ids   = array_unique($member_ids);

        $conversation_id = $this->Team_chat_conversation_model->create_conversation([
            'type'       => 'group',
            'name'       => $name,
            'created_by' => $user_id,
        ], $member_ids);

        if (!$conversation_id) {
            $this->_json_error('Could not create group');
        }

        $this->_json_success(
            $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id)
        );
    }

    public function create_channel()
    {
        $this->_only_post();

        if (!staff_can('create_channel', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $user_id       = $this->_uid();
        $name          = trim($this->input->post('name'));
        $description   = trim($this->input->post('description'));
        $team_id       = (int)$this->input->post('team_id');
        $department_id = (int)$this->input->post('department_id');

        if (empty($name)) {
            $this->_json_error('Channel name is required');
        }

        $member_ids = [$user_id];

        if ($team_id) {
            $rows = $this->db->select('id')->where('emp_team', $team_id)->where('is_active', 1)->get('users')->result_array();
            foreach ($rows as $r) { $member_ids[] = (int)$r['id']; }
        } elseif ($department_id) {
            $rows = $this->db->select('id')->where('emp_department', $department_id)->where('is_active', 1)->get('users')->result_array();
            foreach ($rows as $r) { $member_ids[] = (int)$r['id']; }
        }

        $member_ids = array_unique($member_ids);

        $conversation_id = $this->Team_chat_conversation_model->create_conversation([
            'type'          => 'channel',
            'name'          => $name,
            'slug'          => team_chat_make_slug($name),
            'description'   => $description,
            'team_id'       => $team_id ?: null,
            'department_id' => $department_id ?: null,
            'created_by'    => $user_id,
        ], $member_ids, 'owner');

        if (!$conversation_id) {
            $this->_json_error('Could not create channel');
        }

        $this->_json_success(
            $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id)
        );
    }

    public function update_conversation($conversation_id = null)
    {
        $this->_only_post();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $update = [];

        $name = trim($this->input->post('name'));
        if ($name !== '') {
            $update['name'] = $name;
            $update['slug'] = team_chat_make_slug($name);
        }

        $description = $this->input->post('description');
        if ($description !== null) {
            $update['description'] = trim($description);
        }

        if (empty($update)) {
            $this->_json_error('Nothing to update');
        }

        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $conversation_id)->update('chat_conversations', $update);

        $this->_json_success(
            $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id)
        );
    }

    public function archive_conversation($conversation_id = null)
    {
        $this->_only_post();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $this->db->where('id', $conversation_id)
                 ->update('chat_conversations', ['is_archived' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        $this->_json_success(['archived' => true]);
    }

    // =========================================================
    // MEMBERS
    // =========================================================

    public function members($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        $this->_json_success(
            $this->Team_chat_conversation_model->get_members($conversation_id)
        );
    }

    public function add_members()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $user_ids        = $this->input->post('user_ids');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        if (!is_array($user_ids) || empty($user_ids)) {
            $this->_json_error('No users provided');
        }

        $added = [];
        foreach ($user_ids as $uid) {
            $uid = (int)$uid;
            if ($uid && !$this->Team_chat_conversation_model->is_member($conversation_id, $uid)) {
                $this->Team_chat_conversation_model->add_member($conversation_id, $uid, $user_id);
                $added[] = $uid;
                $this->Team_chat_message_model->create_system_message($conversation_id, $user_id, 'member_added', ['user_id' => $uid]);
            }
        }

        $this->_json_success([
            'added'   => $added,
            'members' => $this->Team_chat_conversation_model->get_members($conversation_id),
        ]);
    }

    public function remove_member()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $target_user_id  = (int)$this->input->post('user_id');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        if ($target_user_id !== $user_id) {
            $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);
        }

        $this->Team_chat_conversation_model->remove_member($conversation_id, $target_user_id);
        $this->Team_chat_message_model->create_system_message($conversation_id, $user_id, 'member_removed', ['user_id' => $target_user_id]);

        $this->_json_success(['removed' => $target_user_id]);
    }

    public function update_member_role()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $target_user_id  = (int)$this->input->post('user_id');
        $role            = $this->input->post('role');
        $user_id         = $this->_uid();

        if (!in_array($role, ['owner', 'admin', 'member'])) {
            $this->_json_error('Invalid role');
        }

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner']);

        $this->db->where('conversation_id', $conversation_id)
                 ->where('user_id', $target_user_id)
                 ->update('chat_members', ['role' => $role]);

        $this->_json_success(['updated' => true, 'role' => $role]);
    }

    public function mute_conversation()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $mute            = (int)$this->input->post('mute');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        $this->db->where('conversation_id', $conversation_id)
                 ->where('user_id', $user_id)
                 ->update('chat_members', ['is_muted' => ($mute ? 1 : 0)]);

        $this->_json_success(['muted' => (bool)$mute]);
    }

    // =========================================================
    // MESSAGES
    // =========================================================

    public function messages($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        $before_id = (int)$this->input->get('before_id');
        $limit     = min((int)($this->input->get('limit') ?: 50), 100);

        $messages = $this->Team_chat_message_model->get_messages($conversation_id, $user_id, $before_id, $limit);

        if (!$before_id) {
            $this->Team_chat_conversation_model->mark_as_read($conversation_id, $user_id);
        }

        $this->_json_success($messages);
    }

    public function send_message()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $body            = trim($this->input->post('body'));
        $parent_id       = (int)$this->input->post('parent_id');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        $conv = $this->db->select('is_read_only, is_archived')
                         ->where('id', $conversation_id)
                         ->get('chat_conversations')
                         ->row_array();

        if (!$conv)                { $this->_json_error('Conversation not found'); }
        if ($conv['is_read_only']) { $this->_json_error('This channel is read-only'); }
        if ($conv['is_archived'])  { $this->_json_error('This conversation is archived'); }
        if (empty($body))          { $this->_json_error('Message body is required'); }

        $message_id = $this->Team_chat_message_model->send_message([
            'conversation_id' => $conversation_id,
            'sender_id'       => $user_id,
            'parent_id'       => $parent_id ?: null,
            'type'            => 'text',
            'body'            => $body,
        ]);

        if (!$message_id) {
            $this->_json_error('Could not send message');
        }

        $this->Team_chat_notification_model->process_mentions($body, $message_id, $conversation_id, $user_id);

        $this->db->where('id', $conversation_id)->update('chat_conversations', [
            'last_message_id'  => $message_id,
            'last_activity_at' => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(
            $this->Team_chat_message_model->get_message($message_id, $user_id)
        );
    }

    public function edit_message($message_id = null)
    {
        $this->_only_post();

        $message_id = (int)$message_id;
        $body       = trim($this->input->post('body'));
        $user_id    = $this->_uid();

        if (empty($body)) { $this->_json_error('Body cannot be empty'); }

        $message = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        if ((int)$message['sender_id'] !== $user_id && !staff_can('delete_message', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $this->db->where('id', $message_id)->update('chat_messages', [
            'body'       => $body,
            'is_edited'  => 1,
            'edited_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(
            $this->Team_chat_message_model->get_message($message_id, $user_id)
        );
    }

    public function delete_message($message_id = null)
    {
        $this->_only_post();

        $message_id = (int)$message_id;
        $user_id    = $this->_uid();

        $message = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        if ((int)$message['sender_id'] !== $user_id && !staff_can('delete_message', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $this->db->where('id', $message_id)->update('chat_messages', [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(['deleted' => true, 'message_id' => $message_id]);
    }

    public function thread($parent_id = null)
    {
        $this->_only_get();

        $parent_id = (int)$parent_id;
        $user_id   = $this->_uid();

        $parent = $this->db->where('id', $parent_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$parent) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$parent['conversation_id'], $user_id);

        $this->_json_success([
            'parent'  => $this->Team_chat_message_model->get_message($parent_id, $user_id),
            'replies' => $this->Team_chat_message_model->get_thread_replies($parent_id, $user_id),
        ]);
    }

    public function mark_read()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);
        $this->Team_chat_conversation_model->mark_as_read($conversation_id, $user_id);

        $this->_json_success(['marked' => true]);
    }

    public function search_messages()
    {
        $this->_only_get();

        $user_id         = $this->_uid();
        $query           = trim($this->input->get('q'));
        $conversation_id = (int)$this->input->get('conversation_id');
        $limit           = min((int)($this->input->get('limit') ?: 30), 100);

        if (strlen($query) < 2) {
            $this->_json_error('Search term too short');
        }

        $this->_json_success(
            $this->Team_chat_message_model->search($query, $user_id, $conversation_id, $limit)
        );
    }

    // =========================================================
    // REACTIONS
    // =========================================================

    public function toggle_reaction()
    {
        $this->_only_post();

        $message_id = (int)$this->input->post('message_id');
        $emoji      = trim($this->input->post('emoji'));
        $user_id    = $this->_uid();

        if (!$message_id || empty($emoji)) { $this->_json_error('Invalid request'); }

        $message = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        $action    = $this->Team_chat_reaction_model->toggle($message_id, $user_id, $emoji);
        $reactions = $this->Team_chat_reaction_model->get_for_message($message_id, $user_id);

        $this->_json_success(['action' => $action, 'reactions' => $reactions]);
    }

    public function reactions($message_id = null)
    {
        $this->_only_get();

        $message_id = (int)$message_id;
        $user_id    = $this->_uid();

        $message = $this->db->where('id', $message_id)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        $this->_json_success(
            $this->Team_chat_reaction_model->get_for_message($message_id, $user_id)
        );
    }

    // =========================================================
    // PINS
    // =========================================================

    public function pin_message()
    {
        $this->_only_post();

        $message_id      = (int)$this->input->post('message_id');
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $exists = $this->db->where('message_id', $message_id)
                           ->where('conversation_id', $conversation_id)
                           ->count_all_results('chat_pins');

        if ($exists) { $this->_json_error('Message already pinned'); }

        $this->db->insert('chat_pins', [
            'conversation_id' => $conversation_id,
            'message_id'      => $message_id,
            'pinned_by'       => $user_id,
            'pinned_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(
            $this->Team_chat_message_model->get_pinned($conversation_id)
        );
    }

    public function unpin_message()
    {
        $this->_only_post();

        $message_id      = (int)$this->input->post('message_id');
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $this->db->where('message_id', $message_id)
                 ->where('conversation_id', $conversation_id)
                 ->delete('chat_pins');

        $this->_json_success(
            $this->Team_chat_message_model->get_pinned($conversation_id)
        );
    }

    public function pinned_messages($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        $this->_json_success(
            $this->Team_chat_message_model->get_pinned($conversation_id)
        );
    }

    // =========================================================
    // FILE UPLOAD
    // =========================================================

    public function upload()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        if (empty($_FILES['file']['name'])) {
            $this->_json_error('No file uploaded');
        }

        $result = $this->Team_chat_attachment_model->upload($conversation_id, $user_id);

        if (!$result['success']) {
            $this->_json_error($result['error']);
        }

        $this->_json_success($result['attachment']);
    }

    public function delete_attachment()
    {
        $this->_only_post();

        $attachment_id = (int)$this->input->post('attachment_id');
        $user_id       = $this->_uid();

        $attachment = $this->db->where('id', $attachment_id)->where('is_deleted', 0)->get('chat_attachments')->row_array();

        if (!$attachment) { $this->_json_error('Attachment not found'); }

        if ((int)$attachment['uploader_id'] !== $user_id && !staff_can('delete_message', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $this->db->where('id', $attachment_id)->update('chat_attachments', ['is_deleted' => 1]);

        $this->_json_success(['deleted' => true]);
    }

    // =========================================================
    // USERS
    // =========================================================

    /**
     * GET /team_chat_api/users/search?q={term}
     * Uses User_model::search_for_dropdown — searches fullname,
     * firstname, lastname, username, email. Active only.
     */
    public function search_users()
    {
        $this->_only_get();

        $q       = trim((string)($this->input->get('q') ?? ''));
        $user_id = $this->_uid();
        $limit   = min(max((int)($this->input->get('limit') ?: 20), 1), 50);

        $users = $this->_search_chat_users($q, $limit);
        $users = array_values(array_filter($users, function ($user) use ($user_id) {
            return (int)$user['id'] !== $user_id
                && strtolower((string)($user['user_role'] ?? '')) !== 'superadmin';
        }));

        $result = array_map([$this, '_normalize_chat_user'], $users);

        $this->_json_success(array_values($result));
    }

    public function online_users()
    {
        $this->_only_get();

        $select = 'id, firstname, lastname, fullname, username, email, emp_id, user_role, profile_image';
        if ($this->db->field_exists('last_seen_at', 'users')) {
            $select .= ', last_seen_at';
        }
        if ($this->db->field_exists('is_online', 'users')) {
            $select .= ', is_online';
            $this->db->where('is_online', 1);
        }

        $users = $this->db->select($select)
                          ->from('users')
                          ->where('is_active', 1)
                          ->limit(50)
                          ->get()
                          ->result_array();

        $this->_json_success(array_map([$this, '_normalize_chat_user'], $users));
    }

/**
 * GET /team_chat_api/users/{action}
 * Routes sub-actions: /users/search, /users/online
 */
/**
 * GET /team_chat_api/users/{action}
 * CI routes /users/search → users('search')
 */
public function users($action = null)
{
    switch ($action) {
        case 'search': $this->search_users(); break;
        case 'online': $this->online_users(); break;
        default:       $this->_json_error('Not found', 404);
    }
}

    // =========================================================
    // UNREAD
    // =========================================================
/**
 * GET /team_chat_api/unread
 */
public function unread()
{
    $this->unread_counts();
}
    public function unread_counts()
    {
        $this->_only_get();

        $user_id = $this->_uid();

        $this->db->select('mem.conversation_id, COUNT(cm.id) AS unread_count', false);
        $this->db->from('chat_members mem');
        $this->db->join('chat_messages cm',
            'cm.conversation_id = mem.conversation_id
             AND cm.id > COALESCE(mem.last_read_message_id, 0)
             AND cm.sender_id != ' . $user_id . '
             AND cm.is_deleted = 0', 'left');
        $this->db->where('mem.user_id', $user_id);
        $this->db->where('mem.left_at IS NULL', null, false);
        $this->db->group_by('mem.conversation_id');

        $rows   = $this->db->get()->result_array();
        $counts = [];

        foreach ($rows as $row) {
            $counts[(int)$row['conversation_id']] = (int)$row['unread_count'];
        }

        $this->_json_success(['counts' => $counts, 'total' => array_sum($counts)]);
    }

    // =========================================================
    // TYPING
    // =========================================================

    public function typing()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $is_typing       = (int)$this->input->post('is_typing');
        $user_id         = $this->_uid();

        $this->_require_member($conversation_id, $user_id);

        $this->db->where('id', $user_id)->update('users', [
            'last_activity_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(['typing' => (bool)$is_typing]);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function _require_member($conversation_id, $user_id)
    {
        if (!$this->Team_chat_conversation_model->is_member($conversation_id, $user_id)
            && !staff_can('view_all', 'team_chat')) {
            $this->_json_error('You are not a member of this conversation', 403);
        }
    }

    private function _require_role($conversation_id, $user_id, array $roles)
    {
        if (staff_can('manage_channel', 'team_chat')) {
            return;
        }

        $row = $this->db->select('role')
                        ->where('conversation_id', $conversation_id)
                        ->where('user_id', $user_id)
                        ->where('left_at IS NULL', null, false)
                        ->get('chat_members')
                        ->row_array();

        if (!$row || !in_array($row['role'], $roles)) {
            $this->_json_error('Insufficient role for this action', 403);
        }
    }

    private function _search_chat_users($query, $limit)
    {
        $query = trim((string)$query);
        $limit = min(max((int)$limit, 1), 50);

        if (method_exists($this->User_model, 'search_for_dropdown')) {
            return $this->User_model->search_for_dropdown($query !== '' ? $query : null, true, $limit);
        }

        $this->db->select('id, firstname, lastname, fullname, username, email, user_role, is_active, emp_id, profile_image')
                 ->from('users')
                 ->where('is_active', 1);

        if ($query !== '') {
            $this->db->group_start()
                     ->like('fullname', $query)
                     ->or_like('firstname', $query)
                     ->or_like('lastname', $query)
                     ->or_like('username', $query)
                     ->or_like('email', $query)
                     ->or_like('emp_id', $query)
                     ->group_end();
        }

        return $this->db->order_by('firstname', 'ASC')
                        ->order_by('lastname', 'ASC')
                        ->limit($limit)
                        ->get()
                        ->result_array();
    }

    private function _normalize_chat_user(array $user)
    {
        $name = trim((string)($user['fullname'] ?? ''));
        if ($name === '') {
            $name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }
        if ($name === '') {
            $name = $user['username'] ?? $user['email'] ?? ('User #' . (int)$user['id']);
        }

        $profile_image = !empty($user['profile_image']) ? ltrim((string)$user['profile_image'], '/') : null;

        return [
            'id'            => (int)$user['id'],
            'fullname'      => $name,
            'firstname'     => $user['firstname'] ?? '',
            'lastname'      => $user['lastname'] ?? '',
            'username'      => $user['username'] ?? '',
            'email'         => $user['email'] ?? '',
            'emp_id'        => $user['emp_id'] ?? '',
            'user_role'     => $user['user_role'] ?? '',
            'profile_image' => $profile_image,
            'avatar_url'    => $this->_chat_avatar_url($profile_image),
        ];
    }

    private function _chat_avatar_url($profile_image)
    {
        $profile_image = trim((string)$profile_image);
        if ($profile_image === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $profile_image)) {
            return $profile_image;
        }

        return base_url('uploads/users/profile/' . ltrim($profile_image, '/'));
    }

    private function _json_success($data = [], $message = 'OK')
    {
        $this->output
             ->set_content_type('application/json')
             ->set_status_header(200)
             ->set_output(json_encode(['success' => true, 'message' => $message, 'data' => $data]));
        exit;
    }

    private function _json_error($message = 'Error', $status = 400)
    {
        $this->output
             ->set_content_type('application/json')
             ->set_status_header($status)
             ->set_output(json_encode(['success' => false, 'message' => $message, 'data' => null]));
        exit;
    }

    private function _only_get()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'GET') {
            $this->_json_error('Method not allowed', 405);
        }
    }

    private function _only_post()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->_json_error('Method not allowed', 405);
        }
    }
}
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form', 'team_chat/team_chat']);
        $this->load->model([
            'User_model',
            'team_chat/Team_chat_conversation_model',
            'team_chat/Team_chat_message_model',
            'team_chat/Team_chat_attachment_model',
            'team_chat/Team_chat_reaction_model',
            'team_chat/Team_chat_notification_model',
        ]);
        if (!is_cli() && !team_chat_can('access')) {
            access_denied('team_chat');
        }
    }

    public function index()
    {
        $user_id = $this->user_id();
        $this->render('team_chat', [
            'title' => 'Team Chat',
            'user_id' => $user_id,
            'conversations' => $this->Team_chat_conversation_model->get_user_conversations($user_id),
            'active_conversation' => null,
            'messages' => [],
            'members' => [],
            'pinned_messages' => [],
            'teams' => $this->db->table_exists('teams') ? $this->db->select('id,name')->order_by('name','ASC')->get('teams')->result_array() : [],
            'departments' => $this->db->table_exists('departments') ? $this->db->select('id,name')->order_by('name','ASC')->get('departments')->result_array() : [],
            'js_config' => $this->js_config($user_id),
        ]);
    }

    public function conversation($conversation_id = null)
    {
        $conversation_id = (int)$conversation_id;
        if (!$conversation_id) redirect('team_chat');
        $user_id = $this->user_id();
        if (!$this->can_access_conversation($conversation_id, $user_id)) {
            set_alert('warning', _l('team_chat_access_denied'));
            redirect('team_chat');
        }
        $this->Team_chat_conversation_model->mark_as_read($conversation_id, $user_id);
        $conversation = $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id);
        $this->render('team_chat', [
            'title' => 'Team Chat',
            'user_id' => $user_id,
            'conversations' => $this->Team_chat_conversation_model->get_user_conversations($user_id),
            'active_conversation' => $conversation,
            'messages' => $this->Team_chat_message_model->get_messages($conversation_id, $user_id),
            'members' => $this->Team_chat_conversation_model->get_members($conversation_id),
            'pinned_messages' => $this->Team_chat_message_model->get_pinned($conversation_id, $user_id),
            'teams' => $this->db->table_exists('teams') ? $this->db->select('id,name')->order_by('name','ASC')->get('teams')->result_array() : [],
            'departments' => $this->db->table_exists('departments') ? $this->db->select('id,name')->order_by('name','ASC')->get('departments')->result_array() : [],
            'js_config' => $this->js_config($user_id, $conversation_id),
        ]);
    }

    public function users_search()
    {
        $this->only_get();
        $q = trim((string)$this->input->get('q'));
        $limit = min(max((int)($this->input->get('limit') ?: 20), 1), 50);
        $users = method_exists($this->User_model, 'search_for_dropdown')
            ? $this->User_model->search_for_dropdown($q, true, $limit)
            : $this->fallback_user_search($q, $limit);
        $current = $this->user_id();
        $out = [];
        foreach ($users as $user) {
            if ((int)$user['id'] === $current || strtolower((string)($user['user_role'] ?? '')) === 'superadmin') continue;
            $out[] = $this->normalize_user($user);
        }
        $this->json_success($out);
    }

    public function users_online()
    {
        $this->only_get();
        $this->json_success([]);
    }

    public function conversations_list() { $this->only_get(); $this->json_success($this->Team_chat_conversation_model->get_user_conversations($this->user_id())); }

    public function conversation_detail($conversation_id) { $this->only_get(); $this->require_member($conversation_id); $this->json_success($this->Team_chat_conversation_model->get_conversation($conversation_id, $this->user_id())); }

    public function conversation_create_direct()
    {
        $this->only_post();
        $target = (int)$this->input->post('target_user_id');
        if (!$target || $target === $this->user_id()) $this->json_error('Invalid user');
        $user = $this->User_model->get($target);
        if (!$user || (int)$user['is_active'] !== 1) $this->json_error('User not found');
        $conversation = $this->Team_chat_conversation_model->get_or_create_direct($this->user_id(), $target);
        $this->json_success($conversation);
    }

    public function conversation_create_group()
    {
        $this->only_post();
        $name = trim((string)$this->input->post('name'));
        $members = $this->posted_int_array('member_ids');
        if ($name === '' || !$members) $this->json_error('Group name and members are required');
        $members[] = $this->user_id();
        $id = $this->Team_chat_conversation_model->create_conversation(['type'=>'group','name'=>$name,'created_by'=>$this->user_id()], $members);
        $this->json_success($this->Team_chat_conversation_model->get_conversation($id, $this->user_id()));
    }

    public function conversation_create_channel()
    {
        $this->only_post();
        if (!team_chat_can('create_channel')) $this->json_error('Permission denied', 403);
        $name = trim((string)$this->input->post('name'));
        if ($name === '') $this->json_error('Channel name is required');
        $team_id = (int)$this->input->post('team_id');
        $department_id = (int)$this->input->post('department_id');
        $members = [$this->user_id()];
        if ($team_id) $members = array_merge($members, $this->users_by_field('emp_team', $team_id));
        if ($department_id) $members = array_merge($members, $this->users_by_field('emp_department', $department_id));
        $id = $this->Team_chat_conversation_model->create_conversation(['type'=>'channel','name'=>$name,'slug'=>team_chat_make_slug($name),'description'=>trim((string)$this->input->post('description')),'team_id'=>$team_id ?: null,'department_id'=>$department_id ?: null,'created_by'=>$this->user_id()], $members);
        $this->json_success($this->Team_chat_conversation_model->get_conversation($id, $this->user_id()));
    }

    public function conversation_update($conversation_id)
    {
        $this->only_post(); $this->require_admin($conversation_id);
        $name = trim((string)$this->input->post('name'));
        $update = ['updated_at'=>date('Y-m-d H:i:s')];
        if ($name !== '') { $update['name'] = $name; $update['slug'] = team_chat_make_slug($name); }
        if ($this->input->post('description') !== null) $update['description'] = trim((string)$this->input->post('description'));
        $this->db->where('id', (int)$conversation_id)->update('chat_conversations', $update);
        $this->json_success($this->Team_chat_conversation_model->get_conversation($conversation_id, $this->user_id()));
    }

    public function conversation_archive($conversation_id)
    {
        $this->only_post(); $this->require_admin($conversation_id);
        $this->db->where('id', (int)$conversation_id)->update('chat_conversations', ['is_archived'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
        $this->json_success(['archived'=>true]);
    }

    public function members_list($conversation_id) { $this->only_get(); $this->require_member($conversation_id); $this->json_success($this->Team_chat_conversation_model->get_members($conversation_id)); }

    public function members_add()
    {
        $this->only_post(); $cid = (int)$this->input->post('conversation_id'); $this->require_admin($cid);
        $added = [];
        foreach ($this->posted_int_array('user_ids') as $uid) {
            if ($this->Team_chat_conversation_model->add_member($cid, $uid, $this->user_id())) { $added[] = $uid; $this->Team_chat_notification_model->notify_member_added($cid, $uid, $this->user_id()); }
        }
        $this->json_success(['added'=>$added,'members'=>$this->Team_chat_conversation_model->get_members($cid)]);
    }

    public function members_remove()
    {
        $this->only_post(); $cid = (int)$this->input->post('conversation_id'); $uid = (int)$this->input->post('user_id');
        if ($uid !== $this->user_id()) $this->require_admin($cid); else $this->require_member($cid);
        $this->Team_chat_conversation_model->remove_member($cid, $uid);
        $this->json_success(['removed'=>$uid]);
    }

    public function members_update_role()
    {
        $this->only_post(); $cid = (int)$this->input->post('conversation_id'); $this->require_owner($cid);
        $role = $this->input->post('role'); if (!in_array($role, ['owner','admin','member'], true)) $this->json_error('Invalid role');
        $this->db->where('conversation_id', $cid)->where('user_id', (int)$this->input->post('user_id'))->update('chat_members', ['role'=>$role]);
        $this->json_success(['updated'=>true]);
    }

    public function members_mute()
    {
        $this->only_post(); $cid = (int)$this->input->post('conversation_id'); $this->require_member($cid);
        $this->db->where('conversation_id',$cid)->where('user_id',$this->user_id())->update('chat_members',['is_muted'=>(int)$this->input->post('mute') ? 1 : 0]);
        $this->json_success(['muted'=>(bool)$this->input->post('mute')]);
    }

    public function messages_list($conversation_id)
    {
        $this->only_get(); $this->require_member($conversation_id);
        $this->json_success($this->Team_chat_message_model->get_messages($conversation_id, $this->user_id(), (int)$this->input->get('before_id'), (int)($this->input->get('limit') ?: 50)));
    }

    public function messages_send()
    {
        $this->only_post(); $cid = (int)$this->input->post('conversation_id'); $this->require_member($cid);
        $body = trim((string)$this->input->post('body')); $attachments = $this->posted_int_array('attachment_ids');
        if ($body === '' && !$attachments) $this->json_error('Message body or attachment is required');
        $type = $attachments ? 'file' : 'text';
        foreach ($attachments as $aid) { $att = $this->Team_chat_attachment_model->get($aid); if ($att && ($att['category'] ?? '') === 'image') $type = 'image'; }
        $id = $this->Team_chat_message_model->send_message(['conversation_id'=>$cid,'sender_id'=>$this->user_id(),'parent_id'=>(int)$this->input->post('parent_id') ?: null,'type'=>$type,'body'=>$body]);
        foreach ($attachments as $aid) $this->Team_chat_attachment_model->attach_to_message($aid, $id);
        $this->Team_chat_notification_model->process_mentions($body, $id, $cid, $this->user_id());
        $this->json_success($this->Team_chat_message_model->get_message($id, $this->user_id()));
    }

    public function messages_edit($message_id)
    {
        $this->only_post(); $msg = $this->message_row($message_id); $this->require_member($msg['conversation_id']);
        if ((int)$msg['sender_id'] !== $this->user_id() && !team_chat_can('delete_message')) $this->json_error('Permission denied', 403);
        $body = trim((string)$this->input->post('body')); if ($body === '') $this->json_error('Body is required');
        $this->db->where('id',(int)$message_id)->update('chat_messages',['body'=>$body,'is_edited'=>1,'edited_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        $this->json_success($this->Team_chat_message_model->get_message($message_id, $this->user_id()));
    }

    public function messages_delete($message_id)
    {
        $this->only_post(); $msg = $this->message_row($message_id); $this->require_member($msg['conversation_id']);
        if ((int)$msg['sender_id'] !== $this->user_id() && !team_chat_can('delete_message')) $this->json_error('Permission denied', 403);
        $this->db->where('id',(int)$message_id)->update('chat_messages',['is_deleted'=>1,'deleted_by'=>$this->user_id(),'deleted_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        $this->json_success(['deleted'=>true,'message_id'=>(int)$message_id]);
    }

    public function messages_thread($parent_id) { $this->only_get(); $parent = $this->message_row($parent_id); $this->require_member($parent['conversation_id']); $this->json_success(['parent'=>$this->Team_chat_message_model->get_message($parent_id,$this->user_id()),'replies'=>$this->Team_chat_message_model->get_thread_replies($parent_id,$this->user_id())]); }
    public function messages_mark_read() { $this->only_post(); $cid=(int)$this->input->post('conversation_id'); $this->require_member($cid); $this->Team_chat_conversation_model->mark_as_read($cid,$this->user_id()); $this->json_success(['marked'=>true]); }
    public function messages_search() { $this->only_get(); $this->json_success($this->Team_chat_message_model->search($this->input->get('q'), $this->user_id(), (int)$this->input->get('conversation_id'))); }
    public function reactions_list($message_id) { $this->only_get(); $msg=$this->message_row($message_id); $this->require_member($msg['conversation_id']); $this->json_success($this->Team_chat_reaction_model->get_for_message($message_id,$this->user_id())); }
    public function reactions_toggle() { $this->only_post(); $msg=$this->message_row((int)$this->input->post('message_id')); $this->require_member($msg['conversation_id']); $action=$this->Team_chat_reaction_model->toggle($msg['id'],$this->user_id(),$this->input->post('emoji')); $this->json_success(['action'=>$action,'reactions'=>$this->Team_chat_reaction_model->get_for_message($msg['id'],$this->user_id())]); }
    public function pins_list($conversation_id) { $this->only_get(); $this->require_member($conversation_id); $this->json_success($this->Team_chat_message_model->get_pinned($conversation_id,$this->user_id())); }
    public function pins_add() { $this->only_post(); $cid=(int)$this->input->post('conversation_id'); $mid=(int)$this->input->post('message_id'); $this->require_admin($cid); $msg=$this->message_row($mid); if((int)$msg['conversation_id']!==$cid)$this->json_error('Invalid message'); $this->db->insert('chat_pins',['conversation_id'=>$cid,'message_id'=>$mid,'pinned_by'=>$this->user_id(),'pinned_at'=>date('Y-m-d H:i:s')]); $this->json_success($this->Team_chat_message_model->get_pinned($cid,$this->user_id())); }
    public function pins_remove() { $this->only_post(); $cid=(int)$this->input->post('conversation_id'); $this->require_admin($cid); $this->db->where('conversation_id',$cid)->where('message_id',(int)$this->input->post('message_id'))->delete('chat_pins'); $this->json_success($this->Team_chat_message_model->get_pinned($cid,$this->user_id())); }
    public function upload_file() { $this->only_post(); $cid=(int)$this->input->post('conversation_id'); $this->require_member($cid); $res=$this->Team_chat_attachment_model->upload($cid,$this->user_id()); if(empty($res['success']))$this->json_error($res['error'] ?? 'Upload failed'); $this->json_success($res['attachment']); }
    public function upload_attach() { $this->only_post(); $this->json_success(['attached'=>true]); }
    public function attachment_delete() { $this->only_post(); $att=$this->Team_chat_attachment_model->get((int)$this->input->post('attachment_id')); if(!$att)$this->json_error('Attachment not found'); if((int)$att['uploader_id']!==$this->user_id()&&!team_chat_can('delete_message'))$this->json_error('Permission denied',403); $this->Team_chat_attachment_model->soft_delete($att['id']); $this->json_success(['deleted'=>true]); }
    public function unread_counts() { $this->only_get(); $rows=$this->Team_chat_conversation_model->get_user_conversations($this->user_id()); $counts=[]; foreach($rows as $r)$counts[(int)$r['id']]=(int)$r['unread_count']; $this->json_success(['counts'=>$counts,'total'=>array_sum($counts)]); }
    public function typing() { $this->only_post(); $cid=(int)$this->input->post('conversation_id'); $this->require_member($cid); $this->json_success(['typing'=>(bool)$this->input->post('is_typing')]); }

    private function render($title, array $data)
    {
        if (function_exists('add_module_assets')) add_module_assets('team_chat', ['css'=>['team_chat.css'], 'js'=>['team_chat.js']]);
        $this->load->view('layouts/master', ['page_title'=>$title, 'subview'=>'team_chat/index', 'view_data'=>$data, 'hide_sidebar'=>true] + $data);
    }

    private function js_config($user_id, $conversation_id = 0)
    {
        $user = $this->User_model->get($user_id) ?: [];
        return ['userId'=>(int)$user_id,'userFullname'=>team_chat_user_display_name($user),'userAvatar'=>team_chat_user_avatar_url($user['profile_image'] ?? '', team_chat_user_display_name($user)),'activeConversationId'=>(int)$conversation_id,'baseUrl'=>site_url('team_chat'),'uploadUrl'=>site_url('team_chat/upload'),'csrfTokenName'=>$this->security->get_csrf_token_name(),'csrfHash'=>$this->security->get_csrf_hash(),'wsToken'=>team_chat_ws_token($user_id),'maxFileSizeMb'=>10,'canCreateChannel'=>team_chat_can('create_channel'),'canManageChannel'=>team_chat_can('manage_channel'),'canDeleteAny'=>team_chat_can('delete_message'),'canViewAll'=>team_chat_can('view_all')];
    }

    private function user_id() { return (int)$this->session->userdata('user_id'); }
    private function can_access_conversation($cid,$uid){ return $this->Team_chat_conversation_model->is_member($cid,$uid) || team_chat_can('view_all'); }
    private function require_member($cid){ if(!$this->can_access_conversation((int)$cid,$this->user_id()))$this->json_error('Conversation access denied',403); }
    private function require_admin($cid){ $this->require_member($cid); $role=$this->Team_chat_conversation_model->get_member_role($cid,$this->user_id()); if(!team_chat_can('manage_channel')&&!in_array($role,['owner','admin'],true))$this->json_error('Permission denied',403); }
    private function require_owner($cid){ $this->require_member($cid); $role=$this->Team_chat_conversation_model->get_member_role($cid,$this->user_id()); if(!team_chat_can('manage_channel')&&$role!=='owner')$this->json_error('Permission denied',403); }
    private function message_row($id){ $row=$this->db->where('id',(int)$id)->where('is_deleted',0)->get('chat_messages')->row_array(); if(!$row)$this->json_error('Message not found',404); return $row; }
    private function posted_int_array($key){ $v=$this->input->post($key); if($v===null)$v=$this->input->post($key.'[]'); if($v===null||$v==='')return []; if(!is_array($v))$v=[$v]; return array_values(array_unique(array_filter(array_map('intval',$v)))); }
    private function users_by_field($field,$value){ return array_column($this->db->select('id')->where($field,(int)$value)->where('is_active',1)->get('users')->result_array(),'id'); }
    private function fallback_user_search($q,$limit){ $this->db->select('id, firstname, lastname, fullname, username, email, user_role, is_active, emp_id, profile_image')->from('users')->where('is_active',1); if($q!=='')$this->db->group_start()->like('fullname',$q)->or_like('firstname',$q)->or_like('lastname',$q)->or_like('username',$q)->or_like('email',$q)->or_like('emp_id',$q)->group_end(); return $this->db->limit($limit)->order_by('firstname','ASC')->get()->result_array(); }
    private function normalize_user(array $u){ $name=team_chat_user_display_name($u); return ['id'=>(int)$u['id'],'fullname'=>$name,'firstname'=>$u['firstname']??'','lastname'=>$u['lastname']??'','username'=>$u['username']??'','email'=>$u['email']??'','emp_id'=>$u['emp_id']??'','user_role'=>$u['user_role']??'','profile_image'=>$u['profile_image']??null,'avatar_url'=>team_chat_user_avatar_url($u['profile_image']??'', $name)]; }
    private function only_get(){ if($this->input->server('REQUEST_METHOD')!=='GET')$this->json_error('Method not allowed',405); }
    private function only_post(){ if($this->input->server('REQUEST_METHOD')!=='POST')$this->json_error('Method not allowed',405); }
    private function json_success($data=[]){ $this->output->set_content_type('application/json')->set_output(json_encode(['success'=>true,'data'=>$data])); exit; }
    private function json_error($message='Error',$status=400){ $this->output->set_content_type('application/json')->set_status_header($status)->set_output(json_encode(['success'=>false,'message'=>$message,'data'=>null])); exit; }
}

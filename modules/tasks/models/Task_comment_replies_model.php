<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Task_comment_replies_model extends CI_Model
{
    protected $table = 'task_comment_replies';

    public function add(int $taskId, int $commentId, int $userId, string $reply): int
    {
        $row = [
            'taskid'     => $taskId,
            'comment_id' => $commentId,
            'user_id'    => $userId,
            'reply'      => trim($reply),
            'dateadded'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $row);
        return (int)$this->db->insert_id();
    }

    /** With author meta (name, avatar filename) */
    public function list_for_comment(int $taskId, int $commentId): array
    {
        return $this->db->select('r.*, 
                TRIM(CONCAT(COALESCE(u.firstname,"")," ",COALESCE(u.lastname,""))) AS author_name,
                u.profile_image AS author_avatar', false)
            ->from($this->table.' r')
            ->join('users u', 'u.id = r.user_id', 'left')
            ->where('r.taskid', $taskId)
            ->where('r.comment_id', $commentId)
            ->order_by('r.dateadded', 'ASC')
            ->get()->result_array();
    }

    /** Grouped fetch remains available if needed later */
    public function list_for_comments(int $taskId, array $commentIds): array
    {
        if (empty($commentIds)) return [];
        $rows = $this->db->select('r.*, 
                TRIM(CONCAT(COALESCE(u.firstname,"")," ",COALESCE(u.lastname,""))) AS author_name,
                u.profile_image AS author_avatar', false)
            ->from($this->table.' r')
            ->join('users u', 'u.id = r.user_id', 'left')
            ->where('r.taskid', $taskId)
            ->where_in('r.comment_id', array_map('intval', $commentIds))
            ->order_by('r.comment_id', 'ASC')
            ->order_by('r.dateadded', 'ASC')
            ->get()->result_array();

        $byParent = [];
        foreach ($rows as $r) {
            $cid = (int)$r['comment_id'];
            $byParent[$cid][] = $r;
        }
        return $byParent;
    }

    public function count_for_comment(int $taskId, int $commentId): int
    {
        return (int)$this->db->from($this->table)
            ->where('taskid', $taskId)
            ->where('comment_id', $commentId)
            ->count_all_results();
    }
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Notepad extends App_Controller
{
    protected int $uid = 0;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('apps/Notepad_model', 'notepad');
        $this->load->library(['session']);
        $this->uid = (int) ($this->session->userdata('user_id') ?? 0);
        if (!$this->uid) {
            if ($this->input->is_ajax_request()) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode(['ok'=>false,'error'=>'Unauthorized']));
                exit;
            }
            show_404();
        }
    }

    /** Main screen (renders through layouts/master) */
    public function index()
    {
        $folderId = (int)$this->input->get('folder_id');
        $q        = trim((string)$this->input->get('q'));
        $status   = trim((string)$this->input->get('status', true)); // 'active'|'archived'|''

        if ($status === '') $status = 'active';

        $folders = $this->notepad->folders_for_user($this->uid);
        $notes   = $this->notepad->notes_for_user($this->uid, [
            'folder_id' => $folderId ?: null,
            'q'         => $q ?: null,
            'status'    => $status,
        ]);

        $layout_data = [
            'page_title' => 'Notepad',
            'subview'    => 'apps/notepad/index',
            'view_data'  => [
                'page_title'       => 'Notepad',
                'folders'          => $folders,
                'notes'            => $notes,
                'active_folder_id' => $folderId ?: null,
                'q'                => $q,
                'status'           => $status,
            ],
        ];

        $this->load->view('layouts/master', $layout_data);
    }
    
    /* ---------- tiny helpers ---------- */
    private function to01($v)
    {
        return (in_array((string)$v, ['1','true','yes'], true) || $v === 1 || $v === true) ? 1 : 0;
    }
    private function json_ok($payload = []) { $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true] + $payload)); }
    private function json_error($msg, $code=400){ $this->output->set_status_header($code)->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>$msg])); }

    /* ================== FOLDERS ================== */

    /** GET /apps/notepad/folder_list */
    public function folder_list()
    {
        $rows = $this->notepad->folders_for_user($this->uid);
        return $this->json_ok(['folders' => $rows]);
    }

    /** POST /apps/notepad/folder_save */
    public function folder_save()
    {
        $name  = trim((string)$this->input->post('name', true));
        $color = trim((string)$this->input->post('color', true));
        $icon  = trim((string)$this->input->post('icon', true));
        if ($name === '') return $this->json_error('Folder name required', 422);

        $id = $this->notepad->folder_create($this->uid, [
            'name'  => $name,
            'color' => $color !== '' ? $color : null,
            'icon'  => $icon  !== '' ? $icon  : null,
        ]);
        if ($id <= 0) return $this->json_error('Unable to create folder', 500);
        return $this->json_ok(['id' => $id]);
    }

    /* ================== NOTES ================== */

    /** GET /apps/notepad/note_list[?folder_id=&status=] */
    public function note_list()
    {
        $folder_id = (int)$this->input->get('folder_id');
        $status    = (string)$this->input->get('status', true);
        if ($status === '' || !in_array($status, ['active','archived'], true)) $status = 'active';

        $rows = $this->notepad->notes_for_user($this->uid, [
            'folder_id' => $folder_id ?: null,
            'status'    => $status,
        ]);

        // include folder fields so UI can render color/icon/name without extra calls
        return $this->json_ok(['notes' => $rows]);
    }

    /** POST /apps/notepad/note_save */
    public function note_save()
    {
        $id          = (int)$this->input->post('id');
        $folder_id   = (int)$this->input->post('folder_id');
        $title       = (string)$this->input->post('title', true);
        $content     = (string)$this->input->post('content'); // allow HTML/markdown
        $is_pinned   = $this->to01($this->input->post('is_pinned'));     // fix cross-wiring
        $is_favorite = $this->to01($this->input->post('is_favorite'));   // fix cross-wiring
        $is_locked   = $this->to01($this->input->post('is_locked'));
        $status      = (string)$this->input->post('status', true);
        $color       = trim((string)$this->input->post('color', true));
        $sort_order  = (int)$this->input->post('sort_order');

        if (!in_array($status, ['active','archived'], true)) $status = 'active';

        $payload = [
            'user_id'     => $this->uid,             // assert ownership on create
            'folder_id'   => $folder_id ?: null,
            'title'       => $title,
            'content'     => $content,
            'is_pinned'   => $is_pinned,
            'is_favorite' => $is_favorite,
            'is_locked'   => $is_locked,
            'status'      => $status,
            'color'       => ($color !== '' ? $color : null),
            'sort_order'  => $sort_order,
            'updated_by'  => $this->uid,
        ];

        $noteId = $id > 0
            ? ($this->notepad->note_update($this->uid, $id, $payload) ? $id : 0)
            : $this->notepad->note_create($payload);

        if ($noteId <= 0) return $this->json_error('Unable to save note', 500);

        return $this->json_ok(['id' => $noteId, 'message' => 'Saved']);
    }

    /** POST /apps/notepad/note_delete/{id} */
    public function note_delete($id = 0)
    {
        $id = (int)$id ?: (int)$this->input->post('id');
        if ($id <= 0) return $this->json_error('Invalid id', 422);
        $ok = $this->notepad->note_soft_delete($this->uid, $id);
        return $ok ? $this->json_ok(['message'=>'Deleted']) : $this->json_error('Unable to delete', 500);
    }

    /** POST /apps/notepad/note_toggle_pin/{id} */
    public function note_toggle_pin($id = 0)
    {
        $id = (int)$id ?: (int)$this->input->post('id');
        if ($id <= 0) return $this->json_error('Invalid id', 422);
        $val = $this->to01($this->input->post('value'));
        $ok  = $this->notepad->note_partial_update($this->uid, $id, ['is_pinned'=>$val,'updated_by'=>$this->uid]);
        return $ok ? $this->json_ok(['id'=>$id,'is_pinned'=>$val]) : $this->json_error('Unable to update', 500);
    }

    /** POST /apps/notepad/note_toggle_favorite/{id} */
    public function note_toggle_favorite($id = 0)
    {
        $id = (int)$id ?: (int)$this->input->post('id');
        if ($id <= 0) return $this->json_error('Invalid id', 422);
        $val = $this->to01($this->input->post('value'));
        $ok  = $this->notepad->note_partial_update($this->uid, $id, ['is_favorite'=>$val,'updated_by'=>$this->uid]);
        return $ok ? $this->json_ok(['id'=>$id,'is_favorite'=>$val]) : $this->json_error('Unable to update', 500);
    }

}

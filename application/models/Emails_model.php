<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emails_model extends App_Model
{
    /** @var string */
    private const TBL = 'emailtemplates';
    /** @var string */
    private const PK  = 'emailtemplateid';

    /** Columns expected in table (baseline, single-language) */
    private const COLS_UPDATE = ['subject','fromname','fromemail','message','plaintext','active'];
    private const COLS_INDEX  = ['type','slug','name','order','active']; // for queries/sorting

    /**
     * Generic getter
     * @param array $where
     * @param 'result'|'result_array'|'row'|'row_array' $result_type
     */
    public function get($where = [], $result_type = 'result_array')
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        $q = $this->db->get(self::TBL);

        switch ($result_type) {
            case 'result':      return $q->result();
            case 'row':         return $q->row();
            case 'row_array':   return $q->row_array();
            case 'result_array':
            default:            return $q->result_array();
        }
    }

    /** Single by ID (matches controller contract) */
    public function get_email_template_by_id($id)
    {
        return $this->db
            ->where(self::PK, (int)$id)
            ->get(self::TBL)
            ->row();
    }

    /** Optional utility – single by slug */
    public function get_by_slug(string $slug)
    {
        return $this->db
            ->where('slug', $slug)
            ->get(self::TBL)
            ->row();
    }

    /** Insert new template */
    public function add_template(array $data)
    {
        $this->db->insert(self::TBL, $data);
        $id = (int)$this->db->insert_id();
        return $id > 0 ? $id : false;
    }

    /** Update single (allow-list payload) */
    public function update_single(int $id, array $data): bool
    {
        if ($id <= 0) return false;

        $payload = [];
        foreach (self::COLS_UPDATE as $k) {
            if (array_key_exists($k, $data)) {
                $payload[$k] = $data[$k];
            }
        }
        if (!$payload) return false;

        $this->db->where(self::PK, $id)->update(self::TBL, $payload);
        return $this->db->affected_rows() > 0;
    }

    /** Toggle by slug */
    public function mark_as(string $slug, $enabled): bool
    {
        $this->db->where('slug', $slug)
                 ->update(self::TBL, ['active' => (int)$enabled]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Bulk toggle by type
     * If you want to keep any “system” templates always enabled, you can add exclusions here.
     */
    public function mark_as_by_type(string $type, $enabled): bool
    {
        $this->db->where('type', $type);
        // Example parity exclusion; comment out if not needed:
        // $this->db->where('slug !=', 'two-factor-authentication');

        $this->db->update(self::TBL, ['active' => (int)$enabled]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Group templates by type for the index view
     * Sort priority: type ASC, order ASC (if present), name ASC
     */
    public function get_grouped_by_type(): array
    {
        // Avoid keyword collision for `order` column; disable identifier protection.
        // CI3 supports: order_by('`order` ASC', '', false) OR order_by('`order`','ASC',false)
        $this->db->order_by('type', 'ASC');
        $this->db->order_by('`order`', 'ASC', false); // safe for column literally named `order`
        $this->db->order_by('name', 'ASC');

        $rows = $this->db->get(self::TBL)->result_array();

        $out = [];
        foreach ($rows as $r) {
            $t = (string)($r['type'] ?? 'general');
            if (!isset($out[$t])) $out[$t] = [];
            $out[$t][] = $r;
        }
        return $out;
    }

    /** Convenience: list distinct types (for filters/UI) */
    public function list_types(): array
    {
        $rows = $this->db->select('DISTINCT(type) AS type', false)
                         ->from(self::TBL)->order_by('type','ASC')->get()->result_array();
        return array_values(array_filter(array_map(function($r){ return (string)($r['type'] ?? ''); }, $rows)));
    }

    /** Optional: get all active templates by type */
    public function get_active_by_type(string $type): array
    {
        return $this->db->where(['type'=>$type, 'active'=>1])
                        ->order_by('`order`','ASC', false)
                        ->order_by('name','ASC')
                        ->get(self::TBL)->result_array();
    }

    /** Optional: simple non-template send (kept for parity; better to call App_mailer directly) */
    public function send_simple_email($email, $subject, $message): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        // Delegate to helper — single source of truth
        return app_mailer()->send([
            'to'      => $email,
            'subject' => (string)$subject,
            'body'    => (string)$message,
            'mailtype'=> 'html',
        ]);
    }
}

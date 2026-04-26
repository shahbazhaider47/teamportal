<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crmproposals_model extends CI_Model
{
    protected $table       = 'crm_proposals';
    protected $table_items = 'crm_proposal_items';
    protected $pk          = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
     * CORE GETTERS
     * ======================================================= */

    public function get(int $id): ?array
    {
        $row = $this->db
            ->where($this->pk, $id)
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    public function get_by_number(string $proposalNumber): ?array
    {
        $proposalNumber = trim($proposalNumber);
        if ($proposalNumber === '') {
            return null;
        }

        $row = $this->db
            ->where('proposal_number', $proposalNumber)
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    public function get_by_public_token(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $row = $this->db
            ->where('public_token', $token)
            ->where('deleted_at IS NULL', null, false)
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    public function get_with_meta(int $id): ?array
    {
        $row = $this->db
            ->select("
                p.*,
                l.practice_name,
                l.contact_person,
                l.contact_email,
                l.contact_phone,
                creator.fullname      AS created_by_name,
                updater.fullname      AS updated_by_name,
                status_user.fullname  AS status_changed_by_name,
                cancel_user.fullname  AS cancelled_by_name,
                delete_user.fullname  AS deleted_by_name
            ")
            ->from($this->table . ' p')
            ->join('crm_leads l',          'l.id = p.lead_id',                'left')
            ->join('users creator',        'creator.id = p.created_by',       'left')
            ->join('users updater',        'updater.id = p.updated_by',       'left')
            ->join('users status_user',    'status_user.id = p.status_changed_by', 'left')
            ->join('users cancel_user',    'cancel_user.id = p.cancelled_by', 'left')
            ->join('users delete_user',    'delete_user.id = p.deleted_by',   'left')
            ->where('p.id', $id)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    public function get_all_with_meta(array $filters = []): array
    {
        $excludeDeleted   = array_key_exists('exclude_deleted', $filters) ? (bool)$filters['exclude_deleted'] : true;
        $q                = trim((string)($filters['q']                ?? ''));
        $status           = trim((string)($filters['status']           ?? ''));
        $leadId           = (int)($filters['lead_id']                  ?? 0);
        $forecastCategory = trim((string)($filters['forecast_category'] ?? ''));
        $dateFrom         = trim((string)($filters['date_from']        ?? ''));
        $dateTo           = trim((string)($filters['date_to']          ?? ''));

        $this->db
            ->select("
                p.*,
                l.practice_name,
                l.contact_person,
                l.contact_email,
                creator.fullname     AS created_by_name,
                updater.fullname     AS updated_by_name,
                status_user.fullname AS status_changed_by_name
            ")
            ->from($this->table . ' p')
            ->join('crm_leads l',       'l.id = p.lead_id',                'left')
            ->join('users creator',     'creator.id = p.created_by',       'left')
            ->join('users updater',     'updater.id = p.updated_by',       'left')
            ->join('users status_user', 'status_user.id = p.status_changed_by', 'left')
            ->order_by('p.created_at', 'DESC');

        if ($excludeDeleted) {
            $this->db->where('p.deleted_at IS NULL', null, false);
        }

        if ($status !== '') {
            $this->db->where('p.status', $status);
        }

        if ($leadId > 0) {
            $this->db->where('p.lead_id', $leadId);
        }

        if ($forecastCategory !== '') {
            $this->db->where('p.forecast_category', $forecastCategory);
        }

        if ($dateFrom !== '') {
            $this->db->where('DATE(p.created_at) >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $this->db->where('DATE(p.created_at) <=', $dateTo);
        }

        if ($q !== '') {
            $this->db->group_start()
                ->like('p.proposal_number', $q)
                ->or_like('p.title',        $q)
                ->or_like('p.summary',      $q)
                ->or_like('p.client_notes', $q)
                ->or_like('l.practice_name',  $q)
                ->or_like('l.contact_person', $q)
                ->or_like('l.contact_email',  $q)
                ->group_end();
        }

        return $this->db->get()->result_array();
    }

    public function count_by_status(bool $excludeDeleted = true): array
    {
        $this->db->select('status, COUNT(*) AS total')
            ->from($this->table);

        if ($excludeDeleted) {
            $this->db->where('deleted_at IS NULL', null, false);
        }

        $this->db->group_by('status');
        $rows = $this->db->get()->result_array();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int)$row['total'];
        }

        return $out;
    }

    /* =========================================================
     * PROPOSAL CRUD
     * ======================================================= */

    public function insert(array $data): int
    {
        if (empty($data['public_token'])) {
            $data['public_token'] = $this->generate_public_token();
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table, $data);

        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): bool
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return (bool)$this->db
            ->where($this->pk, $id)
            ->update($this->table, $data);
    }

    public function soft_delete(int $id, int $deletedBy = 0): bool
    {
        $payload = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($deletedBy > 0) {
            $payload['deleted_by'] = $deletedBy;
            $payload['updated_by'] = $deletedBy;
        }

        return (bool)$this->db
            ->where($this->pk, $id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $payload);
    }

    public function restore(int $id, int $actorId = 0): bool
    {
        $payload = [
            'deleted_at' => null,
            'deleted_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($actorId > 0) {
            $payload['updated_by'] = $actorId;
        }

        return (bool)$this->db
            ->where($this->pk, $id)
            ->update($this->table, $payload);
    }

    /* =========================================================
     * ITEMS — bulk operations
     * ======================================================= */

    /**
     * Get all items for a proposal ordered by id.
     */
    public function get_items(int $proposalId): array
    {
        return $this->db
            ->where('proposal_id', $proposalId)
            ->order_by('id', 'ASC')
            ->get($this->table_items)
            ->result_array();
    }

    /**
     * Delete all items for a proposal and re-insert.
     * Use this on proposal save/update.
     */
    public function replace_items(int $proposalId, array $items): bool
    {
        $this->db->trans_begin();

        $this->db->where('proposal_id', $proposalId)->delete($this->table_items);

        foreach ($items as $item) {
            $name = trim((string)($item['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $this->db->insert($this->table_items, [
                'proposal_id'     => $proposalId,
                'item_type'       => $item['item_type']       ?? 'service',
                'item_name'       => $name,
                'description'     => $item['description']     ?? null,
                'quantity'        => $item['quantity']         ?? 1,
                'unit_price'      => $item['unit_price']       ?? 0,
                'discount_type'   => $item['discount_type']   ?? 'none',
                'discount_value'  => $item['discount_value']  ?? 0,
                'discount_amount' => $item['discount_amount'] ?? 0,
                'line_total'      => $item['line_total']       ?? 0,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }

    /* =========================================================
     * ITEMS — single row operations
     * (absorbed from Crmproposalitems_model)
     * ======================================================= */

    /**
     * Get a single item row by its own primary key.
     */
    public function get_item(int $itemId): ?array
    {
        $row = $this->db
            ->where('id', $itemId)
            ->limit(1)
            ->get($this->table_items)
            ->row_array();

        return $row ?: null;
    }

    /**
     * Alias of get_items() — kept for compatibility with any
     * code that previously called Crmproposalitems_model::get_by_proposal().
     */
    public function get_items_by_proposal(int $proposalId): array
    {
        return $this->get_items($proposalId);
    }

    /**
     * Insert a single item row and return its new id.
     */
    public function insert_item(array $data): int
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table_items, $data);

        return (int)$this->db->insert_id();
    }

    /**
     * Update a single item row by its own primary key.
     */
    public function update_item(int $itemId, array $data): bool
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return (bool)$this->db
            ->where('id', $itemId)
            ->update($this->table_items, $data);
    }

    /**
     * Delete all items belonging to a proposal.
     * Alias kept for compatibility with Crmproposalitems_model::delete_by_proposal().
     */
    public function delete_items_by_proposal(int $proposalId): bool
    {
        return (bool)$this->db
            ->where('proposal_id', $proposalId)
            ->delete($this->table_items);
    }

    /**
     * Allowed columns for item inserts/updates (safe list).
     */
    public function allowed_item_columns(): array
    {
        return [
            'proposal_id',
            'item_type',
            'item_name',
            'description',
            'quantity',
            'unit_price',
            'discount_type',
            'discount_value',
            'discount_amount',
            'line_total',
        ];
    }

    /* =========================================================
     * BUSINESS METHODS
     * ======================================================= */

    public function change_status(int $proposalId, string $status, int $actorId = 0, ?string $declineReason = null): bool
    {
        $allowed = [
            'draft', 'pending_review', 'sent', 'viewed',
            'approved', 'declined', 'expired', 'cancelled',
        ];

        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'status'            => $status,
            'status_changed_at' => $now,
            'updated_at'        => $now,
            'status_changed_by' => $actorId > 0 ? $actorId : null,
            'updated_by'        => $actorId > 0 ? $actorId : null,
        ];

        if ($status === 'sent') {
            $data['sent_at'] = $now;
        }

        if ($status === 'viewed') {
            $proposal = $this->get($proposalId);
            if ($proposal && empty($proposal['viewed_at'])) {
                $data['viewed_at'] = $now;
            }
        }

        if ($status === 'approved') {
            $data['approved_at']    = $now;
            $data['declined_at']    = null;
            $data['decline_reason'] = null;
            $data['cancelled_at']   = null;
            $data['cancelled_by']   = null;
        }

        if ($status === 'declined') {
            $data['declined_at']    = $now;
            $data['decline_reason'] = ($declineReason !== null && trim($declineReason) !== '')
                ? trim($declineReason)
                : null;
            $data['approved_at']    = null;
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] = $now;
            $data['cancelled_by'] = $actorId > 0 ? $actorId : null;
        }

        if ($status !== 'declined') {
            $data['decline_reason'] = null;
        }

        return $this->update($proposalId, $data);
    }

    /* =========================================================
     * UTILITIES
     * ======================================================= */

    public function is_number_unique(string $proposalNumber, ?int $excludeId = null): bool
    {
        $this->db->where('proposal_number', $proposalNumber);

        if ($excludeId) {
            $this->db->where($this->pk . ' !=', (int)$excludeId);
        }

        return $this->db->count_all_results($this->table) === 0;
    }

    public function generate_proposal_number(): string
    {
        $year = date('Y');

        $row = $this->db
            ->select('id, proposal_number')
            ->from($this->table)
            ->like('proposal_number', 'PR-' . $year . '-', 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        $next = 1;

        if ($row && !empty($row['proposal_number'])) {
            if (preg_match('/PR\-' . preg_quote($year, '/') . '\-(\d+)$/', $row['proposal_number'], $m)) {
                $next = ((int)$m[1]) + 1;
            }
        }

        return 'PR-' . $year . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function generate_public_token(int $length = 40): string
    {
        do {
            $token  = bin2hex(random_bytes((int)ceil($length / 2)));
            $token  = substr($token, 0, $length);
            $exists = $this->db
                ->where('public_token', $token)
                ->count_all_results($this->table) > 0;
        } while ($exists);

        return $token;
    }

    public function allowed_columns(): array
    {
        return [
            'proposal_number',
            'lead_id',
            'title',
            'summary',
            'terms_and_conditions',
            'subtotal',
            'discount_type',
            'discount_value',
            'discount_amount',
            'discount_scope',
            'tax_rate',
            'tax_amount',
            'total_value',
            'billing_cycle',
            'payment_terms',
            'validity_days',
            'start_date',
            'go_live_date',
            'status',
            'status_changed_at',
            'status_changed_by',
            'forecast_category',
            'public_token',
            'pdf_path',
            'internal_notes',
            'client_notes',
            'sent_at',
            'viewed_at',
            'expires_at',
            'approved_at',
            'declined_at',
            'decline_reason',
            'cancelled_at',
            'cancelled_by',
            'created_by',
            'updated_by',
            'deleted_at',
            'deleted_by',
        ];
    }
}
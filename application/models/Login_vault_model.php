<?php defined('BASEPATH') or exit('No direct script access allowed');

class Login_vault_model extends CI_Model
{
    protected string $table       = 'tblloginvault';
    protected string $sharesTable = 'tblloginvault_shares';

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
     * CREATE
     * ======================================================= */

    /**
     * Create a new vault record.
     *
     * @param  array $data
     * @return int   Inserted ID (0 on failure)
     */
    public function create(array $data): int
    {
        $insert = [
            'owner_user_id'      => (int)   ($data['owner_user_id']      ?? 0),
            'title'              => (string) ($data['title']              ?? ''),
            'description'        =>           $data['description']        ?? null,
            'type'               => (string) ($data['type']               ?? 'website'),
            'login_url'          =>           $data['login_url']          ?? null,
            'username'           =>           $data['username']           ?? null,
            'login_email'        =>           $data['login_email']        ?? null,
            'login_phone'        =>           $data['login_phone']        ?? null,
            'login_pin'          =>           $data['login_pin']          ?? null,
            'password_encrypted' => (string) ($data['password_encrypted'] ?? ''),
            'is_tfa'             =>           !empty($data['is_tfa']) ? 1 : 0,
            'tfa_secret'         =>           $data['tfa_secret']         ?? null,
            'permissions'        => (string) ($data['permissions']        ?? 'private'),
            'created_by'         => (int)   ($data['created_by']          ?? 0),
            'created_at'         =>           $data['created_at']         ?? date('Y-m-d H:i:s'),
            'updated_by'         => null,
            'updated_at'         => null,
            'deleted_at'         => null,
        ];

        $this->db->insert($this->table, $insert);

        return (int) $this->db->insert_id();
    }

    /* =========================================================
     * READ — LIST
     * ======================================================= */

    /**
     * Get vault entries visible to a user:
     *   - Own records (owner_user_id = $userId)
     *   - Records shared with the user individually (share_type='Staff', share_ids contains $userId)
     *   - Records shared with the user's team/department/position
     *     (those resolved by the controller/helper; here we surface own + staff-shared only)
     *
     * We return a unified list with a `share_count` column and an `access_type`
     * flag so the view knows whether this is an owned or shared entry.
     *
     * @param  int  $userId
     * @param  bool $globalView  When true (admin/superadmin), return ALL non-deleted records.
     * @return array
     */
    public function get_list_for_user(int $userId, bool $globalView = false): array
    {
        if ($userId <= 0) {
            return [];
        }

        $shareCountSub = "(
            SELECT COUNT(*)
              FROM {$this->sharesTable} s
             WHERE s.vault_id = v.id
               AND s.revoked_at IS NULL
        )";

        if ($globalView) {
            // Admins / global-view users see everything
            $this->db
                ->select("v.*, ({$shareCountSub}) AS share_count, 'owned' AS access_type", false)
                ->from($this->table . ' v')
                ->where('v.deleted_at IS NULL', null, false)
                ->order_by('v.created_at', 'DESC');

            return $this->db->get()->result_array();
        }

        // ---- Own records ----
        $owned = $this->db
            ->select("v.*, ({$shareCountSub}) AS share_count, 'owned' AS access_type", false)
            ->from($this->table . ' v')
            ->where('v.owner_user_id', $userId)
            ->where('v.deleted_at IS NULL', null, false)
            ->order_by('v.created_at', 'DESC')
            ->get()->result_array();

        // ---- Records shared directly with this Staff member ----
        // share_type = 'Staff' and share_ids JSON array contains $userId
        $sharedRaw = $this->db
            ->select("v.*, ({$shareCountSub}) AS share_count, 'shared' AS access_type", false)
            ->from($this->sharesTable . ' sh')
            ->join($this->table . ' v', 'v.id = sh.vault_id', 'inner')
            ->where('sh.share_type', 'Staff')
            ->where('sh.revoked_at IS NULL', null, false)
            ->where('v.deleted_at IS NULL', null, false)
            ->where('v.owner_user_id !=', $userId) // already in owned
            ->where("JSON_CONTAINS(sh.share_ids, '" . (int)$userId . "')", null, false)
            ->order_by('v.created_at', 'DESC')
            ->get()->result_array();

        // Merge, de-duplicate by vault ID (owned wins)
        $seen   = array_column($owned, null, 'id');
        foreach ($sharedRaw as $row) {
            if (!isset($seen[$row['id']])) {
                $seen[$row['id']] = $row;
            }
        }

        // Sort combined list by created_at DESC
        $merged = array_values($seen);
        usort($merged, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return $merged;
    }

    /* =========================================================
     * READ — SINGLE
     * ======================================================= */

    /**
     * Get a vault by ID, scoped to its owner.
     */
    public function get_by_id(int $id, int $userId): ?array
    {
        $row = $this->db
            ->where('id', $id)
            ->where('owner_user_id', $userId)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    /**
     * Get a vault by ID regardless of owner (for global-view / admin users).
     */
    public function get_by_id_any_owner(int $id): ?array
    {
        $row = $this->db
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    /* =========================================================
     * UPDATE
     * ======================================================= */

    /**
     * Update a vault record.
     *
     * @param  int   $id
     * @param  int   $userId      Used to scope the WHERE when not $globalEdit
     * @param  array $data
     * @param  bool  $globalEdit  When true, skip the owner_user_id constraint
     * @return bool
     */
    public function update(int $id, int $userId, array $data, bool $globalEdit = false): bool
    {
        $this->db->where('id', $id);

        if (!$globalEdit) {
            $this->db->where('owner_user_id', $userId);
        }

        return (bool) $this->db->update($this->table, $data);
    }

    /* =========================================================
     * DELETE
     * ======================================================= */

    /**
     * Soft-delete a vault record.
     *
     * @param  int  $id
     * @param  int  $userId
     * @param  bool $globalDelete  When true, skip the owner_user_id constraint
     * @return bool
     */
    public function soft_delete(int $id, int $userId, bool $globalDelete = false): bool
    {
        $this->db->where('id', $id);

        if (!$globalDelete) {
            $this->db->where('owner_user_id', $userId);
        }

        $this->db->where('deleted_at IS NULL', null, false);

        return (bool) $this->db->update($this->table, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
    }

    /* =========================================================
     * SHARING
     * ======================================================= */

    /**
     * Upsert a share record.
     * Revokes previous active shares of the same scope for the same vault
     * before inserting the new one.
     */
    public function add_share(array $data): bool
    {
        $vaultId   = (int)   ($data['vault_id']   ?? 0);
        $shareType = trim((string) ($data['share_type'] ?? ''));
        $createdBy = (int)   ($data['created_by']  ?? 0);
        $perms     = in_array(($data['permissions'] ?? ''), ['view', 'edit', 'delete'], true)
                        ? $data['permissions']
                        : 'view';

        if ($vaultId <= 0 || $shareType === '' || $createdBy <= 0) {
            log_message('error', 'Share rejected — invalid payload: ' . json_encode($data));
            return false;
        }

        // Validate share_ids
        $shareIds = null;
        if (!empty($data['share_ids']) && is_array($data['share_ids'])) {
            $ids = array_values(array_unique(array_map('intval', $data['share_ids'])));
            $ids = array_filter($ids, fn($i) => $i > 0);
            if (!empty($ids)) {
                $shareIds = json_encode(array_values($ids));
            }
        }

        if ($shareIds === null) {
            log_message('error', 'Share rejected — empty share_ids after sanitisation');
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Revoke any existing active share of the same type for this vault
        $this->db
            ->where('vault_id', $vaultId)
            ->where('share_type', $shareType)
            ->where('revoked_at IS NULL', null, false)
            ->update($this->sharesTable, ['revoked_at' => $now]);

        return (bool) $this->db->insert($this->sharesTable, [
            'vault_id'    => $vaultId,
            'share_type'  => $shareType,
            'share_ids'   => $shareIds,
            'permissions' => $perms,
            'created_by'  => $createdBy,
            'created_at'  => $now,
            'revoked_at'  => null,
        ]);
    }

    /**
     * Get share records for a vault (active only).
     */
    public function get_shares_for_vault(int $vaultId): array
    {
        return $this->db
            ->where('vault_id', $vaultId)
            ->where('revoked_at IS NULL', null, false)
            ->order_by('created_at', 'DESC')
            ->get($this->sharesTable)
            ->result_array();
    }

    /**
     * Get share-target options by type for the share modal dropdown.
     * Used by Login_vault::get_share_scope_items().
     * This method is a thin passthrough; real queries are done in the controller.
     * Kept here for compatibility if called directly.
     */
    public function get_share_targets(string $type): array
    {
        // Delegated to controller — return empty; controller handles all cases.
        return [];
    }

    /**
     * Revoke all active shares for a vault.
     * Called when a vault is deleted or made private.
     */
    public function revoke_all_shares(int $vaultId): bool
    {
        return (bool) $this->db
            ->where('vault_id', $vaultId)
            ->where('revoked_at IS NULL', null, false)
            ->update($this->sharesTable, ['revoked_at' => date('Y-m-d H:i:s')]);
    }
}
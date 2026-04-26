<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crmleads_model extends CI_Model
{
    protected $table = 'crm_leads';
    protected $pk    = 'id';

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

    public function get_by_uuid(string $uuid): ?array
    {
        $row = $this->db
            ->where('lead_uuid', $uuid)
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    public function get_by_email(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') return null;

        $row = $this->db
            ->where('contact_email', $email)
            ->where('is_deleted', 0)
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    /**
     * Listing with meta (assigned_to name, created_by, updated_by)
     * Supports filters + search.
     */
public function get_all_with_meta(array $filters = []): array
{
    $excludeDel = array_key_exists('exclude_deleted', $filters) ? (bool)$filters['exclude_deleted'] : true;

    $q          = trim((string)($filters['q'] ?? ''));
    $status     = trim((string)($filters['lead_status'] ?? ''));
    $quality    = trim((string)($filters['lead_quality'] ?? ''));
    $assignedTo = (int)($filters['assigned_to'] ?? 0);
    $dateFrom   = trim((string)($filters['date_from'] ?? ''));
    $dateTo     = trim((string)($filters['date_to'] ?? ''));

    $this->db
        ->select("
            l.*,
            assignee.fullname AS assigned_to_name,
            creator.fullname AS created_by_name,
            updater.fullname AS updated_by_name
        ")
        ->from($this->table . ' l')
        ->join('users assignee', 'assignee.id = l.assigned_to', 'left')
        ->join('users creator', 'creator.id = l.created_by', 'left')
        ->join('users updater', 'updater.id = l.updated_by', 'left')
        ->order_by('l.created_at', 'DESC');

    if ($excludeDel) {
        $this->db->where('l.is_deleted', 0);
    }

    if ($status !== '') {
        $this->db->where('l.lead_status', $status);
    }

    if ($quality !== '') {
        $this->db->where('l.lead_quality', $quality);
    }

    if ($assignedTo > 0) {
        $this->db->where('l.assigned_to', $assignedTo);
    }

    if ($dateFrom !== '') {
        $this->db->where('DATE(l.created_at) >=', $dateFrom);
    }

    if ($dateTo !== '') {
        $this->db->where('DATE(l.created_at) <=', $dateTo);
    }

    if ($q !== '') {
        if (!empty($filters['use_fulltext'])) {
            $this->db->where(
                "MATCH(l.practice_name, l.contact_person, l.contact_email, l.internal_notes) AGAINST (" . $this->db->escape($q) . " IN BOOLEAN MODE)",
                null,
                false
            );
        } else {
            $this->db->group_start()
                ->like('l.practice_name', $q)
                ->or_like('l.contact_person', $q)
                ->or_like('l.contact_email', $q)
                ->or_like('l.contact_phone', $q)
                ->or_like('l.internal_notes', $q)
                ->or_like('assignee.fullname', $q)
                ->group_end();
        }
    }

    return $this->db->get()->result_array();
}

    public function count_by_status(bool $excludeDeleted = true): array
    {
        $this->db->select('lead_status, COUNT(*) AS total')
            ->from($this->table);

        if ($excludeDeleted) {
            $this->db->where('is_deleted', 0);
        }

        $this->db->group_by('lead_status');
        $rows = $this->db->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['lead_status']] = (int)$r['total'];
        }
        return $out;
    }

    /* =========================================================
     * CRUD
     * ======================================================= */
    
    public function insert(array $data): int
    {
        $data['practice_name'] = trim((string)($data['practice_name'] ?? ''));
        if ($data['practice_name'] === '') {
            return 0;
        }
    
        $data['lead_status']  = $data['lead_status'] ?? 'new';
        $data['lead_source']  = $data['lead_source'] ?? 'manual';
        if (!isset($data['forecast_category']) || $data['forecast_category'] === null) {
            $data['forecast_category'] = 'pipeline';
        }
    
        $this->load->helper('crm');
    
        if (!isset($data['forecast_probability']) || $data['forecast_probability'] === null) {
            $default_probability = (int) crm_setting('crm_default_forecast_probability', 0);
            $data['forecast_probability'] = $default_probability;
        }
    
        $followup_days = (int) crm_setting('crm_default_followup_days', 3);
        if ($followup_days < 1) {
            $followup_days = 3;
        }
    
        $data['next_followup_date'] = date('Y-m-d', strtotime("+{$followup_days} days"));
    
        $auto_assign = (int) crm_setting('crm_auto_assign_new_leads', 0);
        if ($auto_assign === 1 && empty($data['assigned_to'])) {
            $default_assignee = (int) crm_setting('crm_default_assignee_id', 0);
            if ($default_assignee > 0) {
                $data['assigned_to'] = $default_assignee;
                $data['assigned_at'] = date('Y-m-d H:i:s');
                $data['assigned_by'] = $data['created_by'] ?? null;
            }
        }
    
        if (empty($data['lead_uuid'])) {
            $data['lead_uuid'] = $this->generate_uuid_v4();
        }
    
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
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

    /**
     * Soft delete (keeps record)
     */
    public function soft_delete(int $id, int $deletedBy = 0): bool
    {
        $payload = [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
        ];
        if ($deletedBy > 0) {
            $payload['deleted_by'] = $deletedBy;
        }

        return (bool)$this->db
            ->where($this->pk, $id)
            ->update($this->table, $payload);
    }

    public function restore(int $id): bool
    {
        return (bool)$this->db
            ->where($this->pk, $id)
            ->update($this->table, [
                'is_deleted' => 0,
                'deleted_by' => null,
                'deleted_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /* =========================================================
     * BUSINESS METHODS
     * ======================================================= */
    public function assign(int $leadId, int $assignedTo, int $assignedBy = 0): bool
    {
        $data = [
            'assigned_to' => $assignedTo > 0 ? $assignedTo : null,
            'assigned_by' => $assignedBy > 0 ? $assignedBy : null,
            'assigned_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
    
        if ($assignedBy > 0) {
            $data['updated_by'] = $assignedBy;
        }
    
        return $this->update($leadId, $data);
    }

    public function mark_verified(int $leadId, int $verifiedBy): bool
    {
        return $this->update($leadId, [
            'data_verified' => 1,
            'verified_by'   => $verifiedBy,
            'verified_date' => date('Y-m-d H:i:s'),
        ]);
    }
    
    public function mark_unverified(int $leadId, int $updatedBy = 0): bool
    {
        $data = [
            'data_verified' => 0,
            'verified_by'   => null,
            'verified_date' => null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
    
        if ($updatedBy > 0) {
            $data['updated_by'] = $updatedBy;
        }
    
        return $this->update($leadId, $data);
    }

    // =====================================================================
    // MODEL METHOD — CrmLeads::change_status()
    // =====================================================================
    
    public function change_status(int $leadId, string $status, int $updatedBy = 0, array $extra = []): bool
    {
        $data = [
            'lead_status' => $status,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
    
        if ($updatedBy > 0) {
            $data['updated_by'] = $updatedBy;
        }
    
        // ── Extra fields from the modal ──────────────────────────────────
        $allowedExtraFields = [
            'lead_quality',
            'forecast_probability',
            'forecast_category',
            'loss_reason',
        ];
    
        foreach ($allowedExtraFields as $field) {
            if (array_key_exists($field, $extra)) {
                // Write null as NULL (clears the field), skip keys not present
                $data[$field] = $extra[$field];
            }
        }
    
        // ── Auto-set actual_close_date on terminal statuses ──────────────
        if (in_array($status, ['contract_signed', 'lost', 'disqualified'], true)) {
            $lead = $this->get($leadId);
            if ($lead && empty($lead['actual_close_date'])) {
                $data['actual_close_date'] = date('Y-m-d');
            }
        }
    
        // ── Clear loss_reason when moving away from lost/disqualified ────
        if (!in_array($status, ['lost', 'disqualified'], true) && !array_key_exists('loss_reason', $extra)) {
            $data['loss_reason'] = null;
        }
    
        return $this->update($leadId, $data);
    }

    /* =========================================================
     * IMPORT (CSV)
     * ======================================================= */

    /**
     * Import CSV rows into crm_leads.
     * - Expects header row with column names.
     * - Inserts new leads; if lead_uuid matches existing -> updates.
     * - Returns summary.
     */
    public function import_from_csv(string $filepath, array $options = []): array
    {
        $batchId   = $options['import_batch_id'] ?? ('BATCH-' . date('YmdHis'));
        $source    = $options['import_source_file'] ?? basename($filepath);
        $actorId   = (int)($options['actor_id'] ?? 0);

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        if (!is_readable($filepath)) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['File not readable']];
        }

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Failed to open file']];
        }

        $header = fgetcsv($handle);
        if (!$header || !is_array($header)) {
            fclose($handle);
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Invalid CSV header']];
        }

        // normalize headers
        $cols = array_map(function ($h) {
            $h = strtolower(trim((string)$h));
            $h = str_replace(' ', '_', $h);
            return $h;
        }, $header);

        // allow only real table columns
        $allowed = $this->allowed_columns();

        $this->db->trans_begin();

        $rowNum = 1; // header row
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (!is_array($row) || count($row) === 0) {
                $skipped++;
                continue;
            }

            $raw = [];
            foreach ($cols as $i => $colName) {
                $raw[$colName] = $row[$i] ?? null;
            }

            // must have practice_name
            $practiceName = trim((string)($raw['practice_name'] ?? ''));
            if ($practiceName === '') {
                $skipped++;
                continue;
            }

            // build insert/update payload from allowed columns only
            $payload = [];
            foreach ($raw as $k => $v) {
                if (!in_array($k, $allowed, true)) {
                    continue;
                }
                // sanitize common
                if (is_string($v)) $v = trim($v);
                $payload[$k] = ($v === '' ? null : $v);
            }

            $payload['practice_name']      = $practiceName;
            $payload['is_imported']        = 1;
            $payload['import_date']        = date('Y-m-d H:i:s');
            $payload['import_batch_id']    = $batchId;
            $payload['import_source_file'] = $source;
            if ($actorId > 0) {
                $payload['created_by'] = $payload['created_by'] ?? $actorId;
                $payload['updated_by'] = $payload['updated_by'] ?? $actorId;
            }

            // lead_uuid: update if exists else insert
            $uuid = trim((string)($payload['lead_uuid'] ?? ''));
            if ($uuid === '') {
                $uuid = $this->generate_uuid_v4();
                $payload['lead_uuid'] = $uuid;
            }

            $existing = $this->get_by_uuid($uuid);
            if ($existing) {
                unset($payload['created_at']); // keep original
                $payload['updated_at'] = date('Y-m-d H:i:s');
                $ok = $this->update((int)$existing['id'], $payload);
                if ($ok) $updated++; else $errors[] = "Row {$rowNum}: update failed";
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $newId = $this->insert($payload);
                if ($newId > 0) $inserted++; else $errors[] = "Row {$rowNum}: insert failed";
            }
        }

        fclose($handle);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $errors[] = 'Import transaction failed';
        } else {
            $this->db->trans_commit();
        }

        return compact('inserted', 'updated', 'skipped', 'errors') + ['import_batch_id' => $batchId];
    }

    /* =========================================================
     * UTILITIES
     * ======================================================= */

    public function is_uuid_unique(string $uuid, ?int $excludeId = null): bool
    {
        $this->db->where('lead_uuid', $uuid);
        if ($excludeId) {
            $this->db->where($this->pk . ' !=', (int)$excludeId);
        }
        return $this->db->count_all_results($this->table) === 0;
    }

    public function allowed_columns(): array
    {
        // All columns you allow from POST/CSV (safe list)
        return [
            'lead_uuid',
            'practice_name',
            'contact_person',
            'contact_email',
            'contact_phone',
            'alternate_phone',
            'website',
            'practice_type',
            'specialty',
            'patient_volume_per_month',
            'current_billing_provider',
            'current_emr_system',
            'monthly_claim_volume',
            'current_billing_method',
            'monthly_collections',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'lead_source',
            'lead_status',
            'lead_quality',
            'assigned_to',
            'assigned_by',
            'assigned_at',
            'initial_contact_date',
            'last_contact_date',
            'next_followup_date',
            'demo_date',
            'proposal_date',
            'actual_close_date',
            'practice_needs',
            'pain_points',
            'decision_criteria',
            'key_decision_makers',
            'internal_notes',
            'import_batch_id',
            'import_source_file',
            'import_date',
            'is_imported',
            'preferred_contact_method',
            'best_time_to_contact',
            'referred_by',
            'referral_type',
            'data_verified',
            'verified_by',
            'verified_date',
            'created_by',
            'updated_by',
            'is_deleted',
            'deleted_by',
            'deleted_at',
        ];
    }

    /**
     * UUID v4 generator (no ext needed)
     */
    public function generate_uuid_v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

public function get_forecast_summary(array $filters = []): array
{
    $this->db->from($this->table . ' l');

    $this->_apply_forecast_filters($filters, false);

    $baseValueSql = $this->_forecast_base_value_sql();
    $probSql      = $this->_forecast_probability_sql();
    $weightedSql  = "(($baseValueSql) * ($probSql) / 100)";

    $this->db->select("
        COUNT(l.id) AS open_leads,
        COALESCE(SUM($baseValueSql), 0) AS pipeline_value,
        COALESCE(SUM($weightedSql), 0) AS weighted_value,
        COALESCE(SUM(CASE WHEN l.forecast_category = 'commit' THEN $weightedSql ELSE 0 END), 0) AS commit_value,
        COALESCE(SUM(CASE WHEN l.forecast_category = 'best_case' THEN $weightedSql ELSE 0 END), 0) AS best_case_value,
        COALESCE(SUM(CASE WHEN l.lead_status = 'contract_signed' THEN $baseValueSql ELSE 0 END), 0) AS won_value
    ", false);

    $row = $this->db->get()->row_array();

    return $row ?: [
        'open_leads'     => 0,
        'pipeline_value' => 0,
        'weighted_value' => 0,
        'commit_value'   => 0,
        'best_case_value'=> 0,
        'won_value'      => 0,
    ];
}

public function get_forecast_by_stage(array $filters = []): array
{
    $this->db->from($this->table . ' l');

    $this->_apply_forecast_filters($filters, false);

    $baseValueSql = $this->_forecast_base_value_sql();
    $probSql      = $this->_forecast_probability_sql();
    $weightedSql  = "(($baseValueSql) * ($probSql) / 100)";

    $this->db->select("
        l.lead_status,
        COUNT(l.id) AS total_leads,
        COALESCE(SUM($baseValueSql), 0) AS pipeline_value,
        COALESCE(SUM($weightedSql), 0) AS weighted_value,
        COALESCE(AVG($probSql), 0) AS avg_probability
    ", false);

    $this->db->group_by('l.lead_status');
    $this->db->order_by($this->_pipeline_stage_order_sql(), 'ASC', false);

    return $this->db->get()->result_array();
}

public function get_forecast_by_owner(array $filters = []): array
{
    $this->db
        ->from($this->table . ' l')
        ->join('users assignee', 'assignee.id = l.assigned_to', 'left');

    $this->_apply_forecast_filters($filters, false);

    $baseValueSql = $this->_forecast_base_value_sql();
    $probSql      = $this->_forecast_probability_sql();
    $weightedSql  = "(($baseValueSql) * ($probSql) / 100)";

    $this->db->select("
        l.assigned_to,
        assignee.fullname AS assigned_to_name,
        COUNT(l.id) AS total_leads,
        COALESCE(SUM($baseValueSql), 0) AS pipeline_value,
        COALESCE(SUM($weightedSql), 0) AS weighted_value
    ", false);

    $this->db->group_by('l.assigned_to, assignee.fullname');
    $this->db->order_by('weighted_value', 'DESC');

    return $this->db->get()->result_array();
}

public function get_forecast_leads(array $filters = []): array
{
    $this->db
        ->from($this->table . ' l')
        ->join('users assignee', 'assignee.id = l.assigned_to', 'left');

    $this->_apply_forecast_filters($filters, false);

    $baseValueSql = $this->_forecast_base_value_sql();
    $probSql      = $this->_forecast_probability_sql();
    $weightedSql  = "(($baseValueSql) * ($probSql) / 100)";

    $this->db->select("
        l.*,
        assignee.fullname AS assigned_to_name,
        $baseValueSql AS base_value,
        $probSql AS resolved_probability,
        $weightedSql AS weighted_value
    ", false);

    $this->db->order_by('weighted_value', 'DESC');
    $this->db->order_by('l.expected_close_date', 'ASC');

    return $this->db->get()->result_array();
}


protected function _apply_forecast_filters(array $filters = [], bool $includeClosedWon = false): void
{
    $excludeDeleted   = array_key_exists('exclude_deleted', $filters) ? (bool)$filters['exclude_deleted'] : true;
    $dateFrom         = trim((string)($filters['date_from'] ?? ''));
    $dateTo           = trim((string)($filters['date_to'] ?? ''));
    $assignedTo       = (int)($filters['assigned_to'] ?? 0);
    $forecastCategory = trim((string)($filters['forecast_category'] ?? ''));

    if ($excludeDeleted) {
        $this->db->where('l.is_deleted', 0);
    }

    if ($assignedTo > 0) {
        $this->db->where('l.assigned_to', $assignedTo);
    }

    if ($forecastCategory !== '') {
        $this->db->where('l.forecast_category', $forecastCategory);
    }

    if ($dateFrom !== '') {
        $this->db->where('l.expected_close_date >=', $dateFrom);
    }

    if ($dateTo !== '') {
        $this->db->where('l.expected_close_date <=', $dateTo);
    }

    if ($includeClosedWon) {
        $this->db->where_not_in('l.lead_status', ['lost', 'disqualified']);
    } else {
        $this->db->where_not_in('l.lead_status', ['lost', 'disqualified', 'contract_signed']);
    }
}

protected function _forecast_base_value_sql(): string
{
    return "COALESCE(l.estimated_monthly_revenue, l.monthly_collections, 0)";
}


protected function _forecast_probability_sql(): string
{
    return "
        COALESCE(
            l.forecast_probability,
            CASE l.lead_status
                WHEN 'new' THEN 10
                WHEN 'contacted' THEN 20
                WHEN 'qualified' THEN 40
                WHEN 'demo_scheduled' THEN 50
                WHEN 'demo_completed' THEN 60
                WHEN 'proposal_sent' THEN 70
                WHEN 'negotiation' THEN 80
                WHEN 'contract_sent' THEN 90
                WHEN 'contract_signed' THEN 100
                WHEN 'lost' THEN 0
                WHEN 'disqualified' THEN 0
                ELSE 0
            END
        )
    ";
}

protected function _pipeline_stage_order_sql(): string
{
    return "
        CASE l.lead_status
            WHEN 'new' THEN 1
            WHEN 'contacted' THEN 2
            WHEN 'qualified' THEN 3
            WHEN 'demo_scheduled' THEN 4
            WHEN 'demo_completed' THEN 5
            WHEN 'proposal_sent' THEN 6
            WHEN 'negotiation' THEN 7
            WHEN 'contract_sent' THEN 8
            WHEN 'contract_signed' THEN 9
            WHEN 'lost' THEN 10
            WHEN 'disqualified' THEN 11
            ELSE 99
        END
    ";
}


public function get_with_meta(int $id): ?array
{
    $row = $this->db
        ->select("
            l.*,
            assignee.fullname AS assigned_to_name,
            assigner.fullname AS assigned_by_name,
            creator.fullname AS created_by_name,
            updater.fullname AS updated_by_name,
            verifier.fullname AS verified_by_name
        ")
        ->from($this->table . ' l')
        ->join('users assignee', 'assignee.id = l.assigned_to', 'left')
        ->join('users assigner', 'assigner.id = l.assigned_by', 'left')
        ->join('users creator', 'creator.id = l.created_by', 'left')
        ->join('users updater', 'updater.id = l.updated_by', 'left')
        ->join('users verifier', 'verifier.id = l.verified_by', 'left')
        ->where('l.id', $id)
        ->limit(1)
        ->get()
        ->row_array();

    return $row ?: null;
}


    public function update_forecast_fields(int $leadId, array $data): bool
    {
        $allowed = [
            'estimated_monthly_revenue',
            'estimated_setup_fee',
            'estimated_annual_value',
            'forecast_probability',
            'forecast_category',
            'expected_close_date',
            'updated_by',
        ];
    
        $payload = [];
    
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }
    
        if (!isset($payload['updated_at'])) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }
    
        return $this->update($leadId, $payload);
    }
    

public function count_this_month(): int
{
    $start = date('Y-m-01 00:00:00');
    $end   = date('Y-m-t 23:59:59');

    return (int)$this->db
        ->where('is_deleted', 0)
        ->where('created_at >=', $start)
        ->where('created_at <=', $end)
        ->count_all_results($this->table);
}

public function get_pipeline_value(): float
{
    $row = $this->db
        ->select('SUM(monthly_collections) as total')
        ->where('is_deleted', 0)
        ->where_not_in('lead_status', ['lost', 'disqualified'])
        ->get($this->table)
        ->row();

    return (float)($row->total ?? 0);
}

    public function insert_from_public_form(array $input): int
    {
        $data = [
            'practice_name'       => trim($input['practice_name'] ?? ''),
            'contact_person'      => trim($input['contact_person'] ?? ''),
            'contact_email'       => trim($input['contact_email'] ?? ''),
            'contact_phone'       => trim($input['contact_phone'] ?? ''),
            'practice_type'       => trim($input['practice_type'] ?? ''),
            'specialty'           => trim($input['specialty'] ?? ''),
            'monthly_collections' => is_numeric($input['monthly_collections'] ?? null) ? $input['monthly_collections'] : null,
            'website'             => trim($input['website'] ?? ''),
            'internal_notes'      => trim($input['internal_notes'] ?? ''),
        ];
    
        if ($data['practice_name'] === '') {
            return 0;
        }
    
        // ✅ FORCE SYSTEM SAFE DEFAULTS
        $data['lead_uuid']        = $this->generate_uuid_v4();
        $data['lead_source']      = 'Website Inquiry';
        $data['lead_status']      = 'new';
        $data['forecast_category']= 'pipeline'; // 🔥 CRITICAL FIX
        $data['created_at']       = date('Y-m-d H:i:s');
    
        $this->db->insert($this->table, $data);
    
        // ✅ CHECK INSERT SUCCESS
        if ($this->db->affected_rows() > 0) {
            return (int)$this->db->insert_id();
        }
    
        log_message('error', 'Public lead insert failed: ' . json_encode($data));
        return 0;
    }

}
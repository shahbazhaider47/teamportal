<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscriptions_model extends CI_Model
{
    /* ───────────────────────────── Table names ───────────────────────────── */

    protected $T_SUBS;
    protected $T_CATEGORIES;
    protected $T_PAYMENTS;

    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    
        $this->T_SUBS       = $this->db->dbprefix('subscriptions');
        $this->T_CATEGORIES = $this->db->dbprefix('subscription_categories');
        $this->T_PAYMENTS   = $this->db->dbprefix('subscription_payments');
    }

    /* ───────────────────────────── Meta / Options ───────────────────────────── */

    public function get_categories(): array
    {
        return $this->db->select('id, name, color')
            ->from($this->T_CATEGORIES)
            ->order_by('name', 'ASC')
            ->get()->result_array();
    }

    public function get_base_currency(): string
    {
        $row = $this->db->select('currency')
            ->from($this->T_SUBS)
            ->where('currency IS NOT NULL', null, false)
            ->limit(1)->get()->row_array();

        return $row['currency'] ?? 'USD';
    }

    /* ───────────────────────────── Listing / Search ───────────────────────────── */
    
    public function list(): array
    {
        // COUNT
        $count_builder = $this->base_list_query(true);
        $total = (int)$count_builder->count_all_results();
    
        // DATA
        $builder = $this->base_list_query(false);
        $builder->order_by('s.next_renewal_date', 'ASC');
    
        $rows = $builder->get()->result_array();
    
        return [
            'items' => $rows,
            'total' => $total,
        ];
    }

    protected function base_list_query(bool $for_count)
    {
        $b = $this->db->from($this->T_SUBS . ' s');
    
        if (!$for_count) {
            $users = $this->db->dbprefix('users');
            $b->select("
                s.*,
                c.name  AS category_name,
                c.color AS category_color,
                COALESCE(
                    NULLIF(u.fullname, ''),
                    NULLIF(CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.lastname,'')), ' '),
                    NULLIF(u.username,''),
                    u.email
                ) AS assigned_name
            ", false);
            $b->join($this->T_CATEGORIES . ' c', 'c.id = s.category_id', 'left');
            $b->join("$users u", 'u.id = s.assigned_to', 'left');
        }
    
        // no filters
        return $b;
    }


    /* ───────────────────────────── Single / Details ───────────────────────────── */

    public function find(int $id): ?array
    {
        $users = $this->db->dbprefix('users');
    
        $row = $this->db->select("
                    s.*,
                    c.name  AS category_name,
                    c.color AS category_color,
                    COALESCE(
                        NULLIF(u.fullname, ''),
                        NULLIF(CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.lastname,'')), ' '),
                        NULLIF(u.username,''),
                        u.email
                    ) AS assigned_name
                ", false)
            ->from($this->T_SUBS . ' s')
            ->join($this->T_CATEGORIES . ' c', 'c.id = s.category_id', 'left')
            ->join("$users u", 'u.id = s.assigned_to', 'left')
            ->where('s.id', $id)
            ->get()->row_array();
    
        return $row ?: null;
    }


    /* ───────────────────────────── Create / Update / Delete ───────────────────────────── */

    public function create(array $payload)
    {
        $this->normalize_subscription($payload, true);

        if (empty($payload['next_renewal_date']) && ($payload['subscription_type'] ?? 'recurring') === 'recurring') {
            $payload['next_renewal_date'] = $this->compute_next_renewal_date(
                $payload['start_date'] ?? date('Y-m-d'),
                $payload['payment_cycle'] ?? null,
                $payload['cycle_days'] ?? null
            );
        }

        $this->db->insert($this->T_SUBS, $payload);
        $id = (int)$this->db->insert_id();

        return $id ?: false;
    }

    public function update(int $id, array $payload): bool
    {
        $this->normalize_subscription($payload, false);

        if (array_key_exists('next_renewal_date', $payload)
            && ($payload['next_renewal_date'] === null || $payload['next_renewal_date'] === '')
            && ($payload['subscription_type'] ?? 'recurring') === 'recurring') {
            $payload['next_renewal_date'] = $this->compute_next_renewal_date(
                $payload['start_date'] ?? date('Y-m-d'),
                $payload['payment_cycle'] ?? null,
                $payload['cycle_days'] ?? null
            );
        }

        $this->db->where('id', $id)->update($this->T_SUBS, $payload);
        return $this->db->affected_rows() >= 0;
    }

    public function delete(int $id): bool
    {
        $this->db->where('id', $id)->delete($this->T_SUBS);
        return $this->db->affected_rows() > 0;
    }

    /* ───────────────────────────── Password / Security helpers ───────────────────────────── */
    
    public function set_account_password(int $subscription_id, string $plain): bool
    {
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        $enc  = $this->encryption->encrypt($plain);   // safe, uses your app key
    
        $this->db->where('id', $subscription_id)->update($this->T_SUBS, [
            'account_password'     => $hash,          // keep for verification
            'account_password_enc' => $enc,           // for Reveal
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);
    
        return $this->db->affected_rows() >= 0;
    }

    public function verify_account_password(int $subscription_id, string $plain): ?bool
    {
        $row = $this->db->select('account_password')->from($this->T_SUBS)->where('id', $subscription_id)->get()->row_array();
        if (!$row || empty($row['account_password'])) {
            return null;
        }
        return password_verify($plain, $row['account_password']);
    }

    public function get_account_password_plain(int $subscription_id): ?string
    {
        $row = $this->db->select('account_password_enc')
            ->from($this->T_SUBS)
            ->where('id', $subscription_id)
            ->get()->row_array();
    
        if (!$row || empty($row['account_password_enc'])) return null;
    
        $plain = $this->encryption->decrypt($row['account_password_enc']);
        return $plain !== false ? $plain : null;
    }

    /* ───────────────────────────── Payments ───────────────────────────── */

    public function payments(int $subscription_id): array
    {
        return $this->db->from($this->T_PAYMENTS)
            ->where('subscription_id', $subscription_id)
            ->order_by('payment_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get()->result_array();
    }

    public function get_payment(int $payment_id): ?array
    {
        $row = $this->db->from($this->T_PAYMENTS)->where('id', $payment_id)->get()->row_array();
        return $row ?: null;
    }

    public function add_payment(int $subscription_id, array $data)
    {
        $data['subscription_id'] = $subscription_id;

        // normalize
        $data['amount']   = isset($data['amount'])   ? (float)$data['amount'] : 0.0;
        $data['currency'] = isset($data['currency']) ? trim((string)$data['currency']) : 'USD';
        $data['method']   = isset($data['method'])   ? trim((string)$data['method']) : null;
        $data['notes']    = isset($data['notes'])    ? trim((string)$data['notes']) : null;

        $this->db->insert($this->T_PAYMENTS, $data);
        $pid = (int)$this->db->insert_id();
        if (!$pid) return false;

        // Update last_payment_date on subscription
        $this->db->where('id', $subscription_id)->update($this->T_SUBS, [
            'last_payment_date' => $data['payment_date'] ?? date('Y-m-d'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        // If recurring, roll next_renewal_date by one cycle
        $sub = $this->db->from($this->T_SUBS)->where('id', $subscription_id)->get()->row_array();
        if ($sub && ($sub['subscription_type'] ?? 'recurring') === 'recurring') {
            $anchor = $sub['next_renewal_date'] ?: ($data['payment_date'] ?? date('Y-m-d'));
            $next   = $this->compute_next_renewal_date(
                $anchor,
                $sub['payment_cycle'] ?? null,
                $sub['cycle_days'] ?? null,
                true // advance
            );
            if ($next) {
                $this->db->where('id', $subscription_id)->update($this->T_SUBS, [
                    'next_renewal_date' => $next,
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $pid;
    }

    public function update_payment(int $payment_id, array $data): bool
    {
        // normalize
        if (isset($data['amount']))   $data['amount']   = (float)$data['amount'];
        if (isset($data['currency'])) $data['currency'] = trim((string)$data['currency']);
        if (isset($data['method']))   $data['method']   = trim((string)$data['method']);
        if (isset($data['notes']))    $data['notes']    = trim((string)$data['notes']);

        $this->db->where('id', $payment_id)->update($this->T_PAYMENTS, $data);
        return $this->db->affected_rows() >= 0;
    }

public function delete_payment(int $payment_id): bool
{
    if ($payment_id <= 0) return false;

    $this->db->where('id', $payment_id)->limit(1)->delete($this->T_PAYMENTS);

    return $this->db->affected_rows() === 1;
}




    /* ───────────────────────────── Date math helpers ───────────────────────────── */

    public function compute_next_renewal_date(string $anchorDate, ?string $cycle, ?int $cycle_days, bool $advance = false): ?string
    {
        try {
            $dt = new DateTime($anchorDate);
        } catch (Throwable $e) {
            $dt = new DateTime();
        }

        $cycle = strtolower((string)$cycle);

        if ($advance) {
            switch ($cycle) {
                case 'monthly':   $dt->modify('+1 month');  break;
                case 'quarterly': $dt->modify('+3 months'); break;
                case 'annually':  $dt->modify('+1 year');   break;
                case 'custom':    $dt->modify('+' . max(1, (int)$cycle_days) . ' days'); break;
                default:          $dt->modify('+1 month');
            }
        } else {
            switch ($cycle) {
                case 'monthly':   $dt->modify('+1 month');  break;
                case 'quarterly': $dt->modify('+3 months'); break;
                case 'annually':  $dt->modify('+1 year');   break;
                case 'custom':    $dt->modify('+' . max(1, (int)$cycle_days) . ' days'); break;
                default:          /* keep anchor as provided */ break;
            }
        }

        return $dt->format('Y-m-d');
    }

    /* ───────────────────────────── Export & Simple Reports ───────────────────────────── */

    public function list_for_export(): array
    {
        $users = $this->db->dbprefix('users');
    
        $b = $this->db->select("
                s.id, s.title, s.vendor, s.subscription_type, s.payment_cycle,
                s.next_renewal_date, s.amount, s.currency, s.auto_renew, s.status,
                s.assigned_to,
                COALESCE(
                    NULLIF(u.fullname, ''),
                    NULLIF(CONCAT(COALESCE(u.firstname,''),' ',COALESCE(u.lastname,'')), ' '),
                    NULLIF(u.username,''),
                    u.email
                ) AS assigned_name,
                s.payment_method_id,
                c.name AS category_name
            ", false)
            ->from($this->T_SUBS . ' s')
            ->join($this->T_CATEGORIES . ' c', 'c.id = s.category_id', 'left')
            ->join("$users u", 'u.id = s.assigned_to', 'left');
    
        $b->order_by('s.next_renewal_date', 'ASC');
    
        return $b->get()->result_array();
    }


    public function stats_summary(): array
    {
        $b = $this->db->select('status, COUNT(*) as cnt, SUM(amount) as total_amount')
            ->from($this->T_SUBS . ' s');
        $b->group_by('status');
        $rows = $b->get()->result_array();
    
        $by_status = [];
        $total_count = 0;
        $total_amount = 0.0;
        foreach ($rows as $r) {
            $by_status[$r['status']] = [
                'count'  => (int)$r['cnt'],
                'amount' => (float)$r['total_amount'],
            ];
            $total_count  += (int)$r['cnt'];
            $total_amount += (float)$r['total_amount'];
        }
    
        $monthly = $this->monthly_equivalent_sum();
    
        return [
            'total'          => $total_count,
            'amount_total'   => $total_amount,
            'amount_monthly' => $monthly,
            'by_status'      => $by_status,
        ];
    }


    public function spend_by_category(): array
    {
        $b = $this->db->select('c.name AS category, c.color, s.payment_cycle, s.cycle_days, SUM(s.amount) AS total')
            ->from($this->T_SUBS . ' s')
            ->join($this->T_CATEGORIES . ' c', 'c.id = s.category_id', 'left');
    
        $b->group_by('c.id, c.name, c.color, s.payment_cycle, s.cycle_days');
        $rows = $b->get()->result_array();
    
        $out = [];
        foreach ($rows as $r) {
            $key   = $r['category'] ?? 'Uncategorized';
            $equiv = $this->to_monthly_equivalent((float)$r['total'], (string)$r['payment_cycle'], (int)$r['cycle_days']);
            if (!isset($out[$key])) {
                $out[$key] = ['category' => $key, 'color' => $r['color'], 'monthly' => 0.0];
            }
            $out[$key]['monthly'] += $equiv;
        }
        return array_values($out);
    }


public function spend_over_time(): array
{
    $b = $this->db->select("
            DATE_FORMAT(p.payment_date, '%Y-%m-01') as month,
            SUM(p.amount) as total
        ")
        ->from($this->T_PAYMENTS . ' p')
        ->join($this->T_SUBS . ' s', 's.id = p.subscription_id', 'inner');

    $b->group_by('month')->order_by('month', 'ASC');

    return $b->get()->result_array();
}


protected function monthly_equivalent_sum(): float
{
    $b = $this->db->select('payment_cycle, cycle_days, SUM(amount) as total')
        ->from($this->T_SUBS . ' s');
    $b->group_by('payment_cycle, cycle_days');
    $rows = $b->get()->result_array();

    $sum = 0.0;
    foreach ($rows as $r) {
        $sum += $this->to_monthly_equivalent((float)$r['total'], (string)$r['payment_cycle'], (int)$r['cycle_days']);
    }
    return $sum;
}


    protected function to_monthly_equivalent(float $amount, string $cycle, ?int $cycle_days = null): float
    {
        $cycle = strtolower(trim($cycle));
        switch ($cycle) {
            case 'monthly':   return $amount;
            case 'quarterly': return $amount / 3.0;
            case 'annually':  return $amount / 12.0;
            case 'custom':
                $days = max(1, (int)$cycle_days);
                return $amount * (30.4375 / $days);
            default:
                return $amount; // treat unknown as monthly
        }
    }

    /* ───────────────────────────── Normalization ───────────────────────────── */

    protected function normalize_subscription(array &$p, bool $is_create): void
    {
        foreach ([
            'title','vendor','vendor_url','account_email','account_phone',
            'subscription_type','payment_cycle','currency','license_key',
            'status','notes','meta','tfa_source','backup_codes'
        ] as $k) {
            if (array_key_exists($k, $p) && $p[$k] !== null) {
                $p[$k] = trim((string)$p[$k]);
            }
        }

        if (isset($p['category_id']))          $p['category_id']          = $p['category_id']          !== '' ? (int)$p['category_id']          : null;
        if (isset($p['payment_method_id']))    $p['payment_method_id']    = $p['payment_method_id']    !== '' ? (int)$p['payment_method_id']    : null;
        if (isset($p['assigned_to']))          $p['assigned_to']          = $p['assigned_to']          !== '' ? (int)$p['assigned_to']          : null;
        if (isset($p['cycle_days']))           $p['cycle_days']           = $p['cycle_days']           !== '' ? (int)$p['cycle_days']           : null;
        if (isset($p['amount']))               $p['amount']               = $p['amount']               !== '' ? (float)$p['amount']             : 0.0;
        if (isset($p['seats']))                $p['seats']                = $p['seats']                !== '' ? (int)$p['seats']                : null;
        if (isset($p['reminder_days_before'])) $p['reminder_days_before'] = ($p['reminder_days_before'] !== '' && $p['reminder_days_before'] !== null) ? (int)$p['reminder_days_before'] : 7;
        if (isset($p['grace_days']))           $p['grace_days']           = ($p['grace_days'] !== '' && $p['grace_days'] !== null) ? (int)$p['grace_days'] : 0;
        if (isset($p['auto_renew']))           $p['auto_renew']           = !empty($p['auto_renew']) ? 1 : 0;
        if (isset($p['tfa_status']))           $p['tfa_status']           = !empty($p['tfa_status']) ? 1 : 0;

        foreach (['start_date','next_renewal_date','end_date','last_payment_date'] as $k) {
            if (array_key_exists($k, $p) && ($p[$k] === '' || $p[$k] === null)) {
                $p[$k] = null;
            }
        }

        $now = date('Y-m-d H:i:s');
        if ($is_create) {
            if (!isset($p['created_at'])) $p['created_at'] = $now;
        }
        $p['updated_at'] = $now;

        // Never accept plain password here
        if (isset($p['account_password']) && $p['account_password'] !== null) {
            // assume already hashed if present
        }
    }



// File: modules/subscriptions/models/Subscriptions_model.php
public function get_assignees(array $opts = []): array
{
    // $opts: ['q' => 'john', 'limit' => 50]
    $this->load->model('User_model', 'users');

    $q     = isset($opts['q']) ? (string)$opts['q'] : null;
    $limit = isset($opts['limit']) ? (int)$opts['limit'] : 50;

    // Always active-only at DB level
    $rows = $this->users->search_for_dropdown($q, true, $limit);

    // Normalize output -> id, name, email
    return array_map(function($r){
        $name = $r['fullname']
            ?: trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''));
        if ($name === '') $name = $r['username'] ?: ($r['email'] ?? '');
        return [
            'id'    => (int)$r['id'],
            'name'  => $name,
            'email' => $r['email'] ?? '',
        ];
    }, $rows);
}

    
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_permissions_model extends CI_Model
{
    protected $table = 'user_permissions';

    /**
     * Returns:
     *  [
     *    'grants' => [...],
     *    'denies' => [...],
     *    'updated_at' => 'YYYY-mm-dd HH:ii:ss'|null,
     *    '_source' => 'row'|'empty'   // 'empty' means no row existed
     *  ]
     */
    public function get_by_user_id(int $user_id): array
    {
        $row = $this->db->where('user_id', $user_id)
                        ->limit(1)
                        ->get('user_permissions')
                        ->row_array();
    
        if (!$row) {
            // signal to helper that there's no row yet
            return [
                '_source'    => 'empty',
                'grants'     => [],
                'denies'     => [],
                'updated_at' => null,
            ];
        }
    
        // decode JSON/text to arrays safely
        $grants = $row['grants'] ?? '[]';
        $denies = $row['denies'] ?? '[]';
    
        $grants = is_array($grants) ? $grants : json_decode($grants, true);
        $denies = is_array($denies) ? $denies : json_decode($denies, true);
    
        $grants = is_array($grants) ? $grants : [];
        $denies = is_array($denies) ? $denies : [];
    
        // keep only strings
        $grants = array_values(array_filter($grants, 'is_string'));
        $denies = array_values(array_filter($denies, 'is_string'));
    
        return [
            '_source'    => 'db',
            'grants'     => $grants,
            'denies'     => $denies,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }


    public function save(int $user_id, array $grants, array $denies): bool
    {
        $now = date('Y-m-d H:i:s');

        $payload = [
            'user_id'    => $user_id,
            'grants'     => json_encode(array_values(array_unique($grants))),
            'denies'     => json_encode(array_values(array_unique($denies))),
            'updated_at' => $now,
        ];

        $exists = (bool) $this->db->where('user_id', $user_id)->count_all_results($this->table);
        if ($exists) {
            return $this->db->where('user_id', $user_id)->update($this->table, $payload);
        }

        $payload['created_at'] = $now;
        return $this->db->insert($this->table, $payload);
    }

    /**
     * Apply defaults for a user only if they currently have no row.
     * Returns true if defaults were applied, false otherwise.
     */
    public function apply_defaults_if_missing(int $user_id, array $defaults): bool
    {
        // Expecting: ['grants' => [...], 'denies' => [...]]
        $g = $defaults['grants'] ?? [];
        $d = $defaults['denies'] ?? [];
    
        // Flatten + strings only
        $norm = function($arr) {
            $flat = [];
            foreach ((array)$arr as $item) {
                if (is_array($item)) {
                    foreach ($item as $inner) {
                        if (is_string($inner)) $flat[] = $inner;
                    }
                } elseif (is_string($item)) {
                    $flat[] = $item;
                }
            }
            // unique + reindex
            return array_values(array_unique($flat));
        };
    
        $grants = $norm($g);
        $denies = $norm($d);
    
        // Upsert row with JSON (or text) fields
        $now = date('Y-m-d H:i:s');
    
        // Check existence again to be safe under race
        $exists = $this->db->where('user_id', $user_id)
                           ->count_all_results('user_permissions') > 0;
    
        if ($exists) {
            return (bool) $this->db->where('user_id', $user_id)
                ->update('user_permissions', [
                    'grants'     => json_encode($grants),
                    'denies'     => json_encode($denies),
                    'updated_at' => $now,
                ]);
        }
    
        return (bool) $this->db->insert('user_permissions', [
            'user_id'    => $user_id,
            'grants'     => json_encode($grants),
            'denies'     => json_encode($denies),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

}

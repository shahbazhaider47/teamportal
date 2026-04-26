<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class System_settings_model extends CI_Model
{
    protected $table = 'system_settings';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all settings by group (default: 'system')
     */
    public function get_all($group_key = 'system')
    {
        $this->db->where('group_key', $group_key);
        $result = $this->db->get($this->table)->result();

        $settings = [];
        foreach ($result as $row) {
            $settings[$row->key] = $row->value;
        }
        return $settings;
    }

    /**
     * Get a specific setting key from a group
     */
    public function get($key, $group_key = 'system', $default = null)
    {
        $row = $this->db
            ->where('group_key', $group_key)
            ->where('key', $key)
            ->get($this->table)
            ->row();

        return $row ? $row->value : $default;
    }

    /**
     * Set or update a single setting in a group
     */
    public function set($key, $value, $group_key = 'system', $clear_cache = true)
    {
        // Convert array to JSON before storing
        if (is_array($value)) {
            $value = json_encode($value);
        }
    
        $exists = $this->db
            ->where('key', $key)
            ->where('group_key', $group_key)
            ->get($this->table)
            ->num_rows() > 0;
    
        $data = ['value' => $value];
    
        $result = $exists
            ? $this->db->update($this->table, $data, ['key' => $key, 'group_key' => $group_key])
            : $this->db->insert($this->table, array_merge(['key' => $key, 'group_key' => $group_key], $data));
    
        if ($clear_cache) {
            $this->clear_cache($group_key);
        }
    
        return $result;
    }

    /**
     * Set multiple keys in a group
     */
    public function set_batch(array $settings, $group_key = 'system')
    {
        $success = true;

        foreach ($settings as $key => $value) {
            if (! $this->set($key, $value, $group_key, false)) {
                $success = false;
            }
        }

        $this->clear_cache($group_key);
        return $success;
    }

    /**
     * Alias for set_batch
     */
    public function upsert(array $settings, $group_key = 'system')
    {
        return $this->set_batch($settings, $group_key);
    }

    /**
     * Clear cached config (optional hook if using caching)
     */
    private function clear_cache($group_key)
    {
        $CI =& get_instance();
        if (!isset($CI->cache)) {
            $CI->load->driver('cache');
        }
        $CI->cache->file->delete('system_settings_' . $group_key);
    }
}

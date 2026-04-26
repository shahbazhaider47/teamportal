<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication_model extends CI_Model
{
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_ATTEMPT_WINDOW = 15; // minutes
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password)
    {
        $user = $this->db->where('email', $email)
                         ->get('users')
                         ->row_array();
    
        // If no user or wrong password, return FALSE
        if (!$user || !password_verify($password, $user['password'])) {
            return FALSE;
        }
    
        // If password needs rehashing, update it
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->db->where('id', $user['id'])
                     ->update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }
    
        // Return user regardless of is_active
        return $user;
    }
    
    /**
     * Check for brute force attempts
     */
    public function is_brute_force($email)
    {
        $time_window = date('Y-m-d H:i:s', strtotime('-' . self::LOGIN_ATTEMPT_WINDOW . ' minutes'));
        
        $attempts = $this->db->where('email', $email)
                            ->where('attempt_time >=', $time_window)
                            ->where('success', 0)
                            ->count_all_results('login_attempts');
        
        return $attempts >= self::MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Log failed login attempt
     */
    public function log_failed_attempt($email)
    {
        $this->db->insert('login_attempts', [
            'email' => $email,
            'ip_address' => $this->input->ip_address(),
            'attempt_time' => date('Y-m-d H:i:s'),
            'success' => 0
        ]);
    }
    
    /**
     * Handle social media user authentication
     */
    public function handle_social_user($provider, $profile)
    {
        // Check if user exists by email
        $user = $this->db->where('email', $profile['email'])
                        ->get('users')
                        ->row_array();
        
        if ($user) {
            return $user;
        }
        
        // Create new user if not exists
        $user_data = [
            'firstname' => $profile['first_name'] ?? '',
            'lastname' => $profile['last_name'] ?? '',
            'email' => $profile['email'],
            'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), // Random password
            'user_role' => 'user', // Default role
            'provider' => $provider,
            'provider_id' => $profile['id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($this->db->insert('users', $user_data)) {
            $user_data['id'] = $this->db->insert_id();
            return $user_data;
        }
        
        return FALSE;
    }
    
    /**
     * Create new user account
     */
    //public function create_user($user_data)
    //{
        //return $this->db->insert('users', $user_data);
    //}
}
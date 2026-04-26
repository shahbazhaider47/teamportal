<?php
// File: application/models/Search_model.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Database is autoloaded via autoload.php, so $this->db is ready.
    }

    /**
     * Search across multiple tables for the given $term.
     * In this minimal example, we only search the ‘users’ table (as “staff”).
     *
     * The view expects an array of groups, each group with:
     *  - 'search_heading' => (string) heading text
     *  - 'type'           => (string) must match one of the view’s case‐labels (‘staff’, etc.)
     *  - 'result'         => (array) each item is an associative array with fields
     *                        the view’s switch‐block knows how to use.
     *
     * Here, we return a single group:
     *    [0] => [
     *        'search_heading' => 'Users',
     *        'type'           => 'staff',
     *        'result'         => [ array of user‐rows → (staffid, firstname, lastname) ]
     *    ]
     *
     * @param string $term
     * @return array
     */
    public function search_all($term) {
        $term_like = '%' . $this->db->escape_like_str($term) . '%';

        // 1) Search users by firstname OR lastname OR email
        $this->db->select('id AS staffid, firstname, lastname');
        $this->db->from('users');
        $this->db->group_start();
        $this->db->like('firstname', $term_like);
        $this->db->or_like('lastname',  $term_like);
        $this->db->or_like('email',     $term_like);
        $this->db->group_end();
        $this->db->where('is_active', 1);  // only active users
        $query = $this->db->get();
        $users = $query->result_array();

        $results = [];

        if (! empty($users)) {
            $results[] = [
                'search_heading' => 'Users',
                'type'           => 'staff',     // maps to the ‘staff’ case in your view
                'result'         => $users,
            ];
        }

        // 2) You can append more groups if you later search other tables:
        //    e.g. $results[] = [ 'search_heading'=>'Clients', 'type'=>'clients', 'result'=>$clientsArray ];

        return $results;
    }
}

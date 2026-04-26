<?php
// File: application/controllers/Search.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends App_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Search_model');
    }

    /**
     * Entry point for “?q=…” search requests.
     * Now wraps output in the main layout instead of returning only a dropdown.
     */
    public function index() {
        // 1) Get the query string
        $term = $this->input->get('q', TRUE);
        if ($term === NULL || trim($term) === '') {
            // If no search term, redirect back or show a message
            set_alert('danger', 'Please enter a search term');
            redirect('', 'refresh');
            return;
        }

        // 2) Fetch all matching results
        $search_data = $this->Search_model->search_all($term);

        // 3) Prepare data for the view
        $view_data = [
            'term'   => $term,
            'result' => $search_data,
        ];

        // 4) Load the “results” subview inside the main layout
        $layout_data = [
            'page_title' => 'Search Results for “' . html_escape($term) . '”',
            'subview'    => 'search',  // application/views/search/results.php
            'view_data'  => $view_data,
        ];
        $this->load->view('layouts/master', $layout_data);
    }
}

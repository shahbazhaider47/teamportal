<?php defined('BASEPATH') or exit('No direct script access allowed');

class Feedback extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Feedback_model', 'feedback');
        $this->load->model('Department_model', 'departments');
        $this->load->library('form_validation');
    }

    /* =======================
     * ADMIN: LIST & ANALYTICS
     * ======================= */

    public function index()
    {
        $layout_data = [
            'page_title' => 'Employee Feedback',
            'subview'    => 'feedback/manage',
            'view_data'  => [
                'forms' => $this->feedback->get_forms(),
                'table_id' => 'feedbackTable'
            ]
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /* =======================
     * ADMIN: CREATE FORM
     * ======================= */

public function create()
{
    // Load required data for the form
    $departments = $this->departments->get_all_departments();

    $this->load->model('User_model');
    $staff_members = $this->User_model->get_all_users();

    // Handle form submit
    if ($this->input->post()) {

        // Basic validation
        $this->form_validation->set_rules('title', 'Form Title', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('frequency', 'Frequency', 'required');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === false) {
            set_alert('warning', 'Please fill all required fields.');
            goto load_form;
        }

        // Questions validation
        $questions = $this->input->post('questions');
        if (!is_array($questions) || empty($questions)) {
            set_alert('warning', 'At least one question is required.');
            goto load_form;
        }

        // Clean questions
        $questions = array_values(array_filter($questions, function ($q) {
            return !empty(trim($q['label'] ?? ''));
        }));

        if (count($questions) === 0) {
            set_alert('warning', 'At least one valid question is required.');
            goto load_form;
        }

        // Build form schema
        $schema = [
            'questions' => $questions,
            'settings'  => [
                'rating_scale'   => (int) $this->input->post('rating_scale') ?: 5,
                'allow_comments' => 1,
            ]
        ];

        // Prepare insert data (ALWAYS DRAFT)
        $data = [
            'title'                => $this->input->post('title', true),
            'description'          => $this->input->post('description', true),
            'frequency'            => $this->input->post('frequency'),
            'is_required'          => (int) $this->input->post('is_required'),
            'start_date'           => $this->input->post('start_date'),
            'end_date'             => $this->input->post('end_date') ?: null,
            'assigned_departments' => is_array($this->input->post('assigned_departments'))
                                        ? implode(',', $this->input->post('assigned_departments'))
                                        : null,
            'reviewers'            => is_array($this->input->post('reviewers'))
                                        ? implode(',', $this->input->post('reviewers'))
                                        : null,
            'form_schema'          => json_encode($schema, JSON_UNESCAPED_UNICODE),
            'status'               => 'draft',
            'notify_participants'  => 0,
            'notify_reviewers'     => 0,
            'created_by'           => (int) $this->session->userdata('user_id'),
            'created_at'           => date('Y-m-d H:i:s')
        ];

        $form_id = $this->feedback->create_form($data);

        if (!$form_id) {
            set_alert('danger', 'Failed to save feedback form.');
            goto load_form;
        }

        set_alert('success', 'Feedback form saved as draft.');
        redirect('feedback');
        return;
    }

    load_form:
    $this->load->view('layouts/master', [
        'page_title' => 'Create Feedback Form',
        'subview'    => 'feedback/create_form',
        'view_data'  => compact('departments', 'staff_members')
    ]);
}


    /* =======================
     * CUSTOM VALIDATION
     * ======================= */

    public function validate_date_range()
    {
        $start_date = strtotime($this->input->post('start_date'));
        $end_date = strtotime($this->input->post('end_date'));
        
        if ($end_date && $end_date <= $start_date) {
            $this->form_validation->set_message('validate_date_range', 'End date must be after start date.');
            return false;
        }
        return true;
    }

    /* =======================
     * NOTIFICATION FUNCTION
     * ======================= */

    private function send_participant_notifications($form_id, $departments)
    {
        // Get participants based on assigned departments
        $this->load->model('User_model');
        
        $where = [];
        if ($departments) {
            $dept_ids = explode(',', $departments);
            $where['department'] = $dept_ids;
        }
        
        $participants = $this->User_model->get('', $where);
        
        foreach ($participants as $participant) {
            // Send email notification
            $this->send_feedback_notification_email($participant['email'], $form_id);
            
            // You could also add to notifications table
            $notification_data = [
                'description' => 'A new feedback form has been assigned to you.',
                'touserid' => $participant['staffid'],
                'link' => 'feedback/submit/' . $form_id,
                'additional_data' => json_encode(['form_id' => $form_id]),
                'date' => date('Y-m-d H:i:s')
            ];
            
            // Load notifications model if exists
            if (method_exists($this, 'load_model')) {
                $this->load->model('notifications_model');
                $this->notifications_model->add($notification_data);
            }
        }
    }

    private function send_feedback_notification_email($email, $form_id)
    {
        // Load email library and send notification
        $this->load->library('email');
        
        $form = $this->feedback->get_form($form_id);
        $subject = "New Feedback Form: " . $form['title'];
        $message = "You have been assigned a new feedback form.\n\n";
        $message .= "Title: " . $form['title'] . "\n";
        $message .= "Description: " . $form['description'] . "\n";
        $message .= "Due Date: " . $form['end_date'] . "\n\n";
        $message .= "Click here to submit your feedback: " . site_url('feedback/submit/' . $form_id);
        
        // Configure and send email
        $this->email->from(get_option('smtp_email'), get_option('companyname'));
        $this->email->to($email);
        $this->email->subject($subject);
        $this->email->message($message);
        
        $this->email->send();
    }

    /* =======================
     * EMPLOYEE: SUBMIT (Enhanced)
     * ======================= */

    public function submit($id)
    {
        $form = $this->feedback->get_form($id);
        if (!$form || $form['status'] !== 'active') {
            show_404();
        }

        // Check if form is still within date range
        $now = time();
        $start_date = strtotime($form['start_date']);
        $end_date = $form['end_date'] ? strtotime($form['end_date']) : null;
        
        if ($now < $start_date) {
            show_error('This feedback form is not yet available.');
        }
        
        if ($end_date && $now > $end_date) {
            show_error('This feedback form has expired.');
        }

        $user_id = (int)$this->session->userdata('user_id');
        
        // Check if user belongs to assigned departments
        if ($form['assigned_departments']) {
            $this->load->model('User_model');
            $staff = $this->User_model->get($user_id);
            $assigned_depts = explode(',', $form['assigned_departments']);
            
            if (!in_array($staff['department'], $assigned_depts)) {
                show_error('You are not assigned to this feedback form.');
            }
        }

        if ($this->feedback->has_submitted($id, $user_id)) {
            show_error('Feedback already submitted.');
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('answers', 'Answers', 'required');
            
            if ($this->form_validation->run() === FALSE) {
                // Reload with validation errors
                $layout_data = [
                    'page_title' => 'Submit Feedback',
                    'subview'    => 'feedback/submit',
                    'view_data'  => [
                        'form'   => $form,
                        'schema' => json_decode($form['form_schema'], true)
                    ]
                ];
                $this->load->view('layouts/master', $layout_data);
                return;
            }

            $answers = $this->input->post('answers');
            $comments = $this->input->post('comments');
            
            $score_sum   = 0;
            $score_count = 0;
            $category_scores = [];

            $schema = json_decode($form['form_schema'], true);
            
            foreach ($answers as $qid => $val) {
                if (is_numeric($val)) {
                    $score_sum += (float)$val;
                    $score_count++;
                    
                    // Track category scores
                    $question_category = $schema['questions'][$qid]['category'] ?? 'general';
                    if (!isset($category_scores[$question_category])) {
                        $category_scores[$question_category] = ['sum' => 0, 'count' => 0];
                    }
                    $category_scores[$question_category]['sum'] += (float)$val;
                    $category_scores[$question_category]['count']++;
                }
            }

            // Calculate average scores
            $average_score = $score_count ? round($score_sum / $score_count, 2) : null;
            
            // Calculate category averages
            $category_averages = [];
            foreach ($category_scores as $category => $data) {
                if ($data['count'] > 0) {
                    $category_averages[$category] = round($data['sum'] / $data['count'], 2);
                }
            }

            $insert = [
                'form_id'           => (int)$id,
                'user_id'           => $user_id,
                'answers'           => json_encode($answers, JSON_UNESCAPED_UNICODE),
                'comments'          => json_encode($comments, JSON_UNESCAPED_UNICODE),
                'average_score'     => $average_score,
                'category_scores'   => json_encode($category_averages),
                'submitted_at'      => date('Y-m-d H:i:s'),
                'ip_address'        => $this->input->ip_address(),
                'user_agent'        => $this->input->user_agent()
            ];

            $submission_id = $this->feedback->submit_feedback($insert);
            
            if ($submission_id) {
                // Send notification to reviewers
                $this->notify_reviewers($form, $submission_id);
                
                set_alert('success', 'Thank you for your feedback');
                redirect('dashboard');
            } else {
                set_alert('warning', 'Failed to submit feedback. Please try again');
                redirect('feedback/submit/' . $id);
            }
            return;
        }

        $layout_data = [
            'page_title' => 'Submit Feedback',
            'subview'    => 'feedback/submit',
            'view_data'  => [
                'form'   => $form,
                'schema' => json_decode($form['form_schema'], true)
            ]
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    private function notify_reviewers($form, $submission_id)
    {
        if ($form['reviewers'] && $form['notify_reviewers']) {
            $reviewer_ids = explode(',', $form['reviewers']);
            $this->load->model('User_model');
            
            foreach ($reviewer_ids as $reviewer_id) {
                $notification_data = [
                    'description' => 'New feedback submission received.',
                    'touserid' => $reviewer_id,
                    'link' => 'feedback/view_submission/' . $submission_id,
                    'additional_data' => json_encode([
                        'form_title' => $form['title'],
                        'submission_id' => $submission_id
                    ]),
                    'date' => date('Y-m-d H:i:s')
                ];
                
                if (method_exists($this, 'load_model')) {
                    $this->load->model('notifications_model');
                    $this->notifications_model->add($notification_data);
                }
            }
        }
    }

    /* =======================
     * ANALYTICS VIEW
     * ======================= */

    public function view($id)
    {
        $form = $this->feedback->get_form($id);
        if (!$form) {
            show_404();
        }

        // Check if user is reviewer or admin
        $user_id = (int)$this->session->userdata('user_id');
        $is_reviewer = $form['reviewers'] && in_array($user_id, explode(',', $form['reviewers']));
        $is_admin = $this->session->userdata('admin') == 1;
        
        if (!$is_reviewer && !$is_admin) {
            show_error('You do not have permission to view this feedback analytics.');
        }

        $submissions = $this->feedback->get_submissions($id);
        $stats = $this->feedback->get_form_stats($id);
        $category_analytics = $this->feedback->get_category_analytics($id);

        $layout_data = [
            'page_title' => 'Feedback Analytics: ' . $form['title'],
            'subview'    => 'feedback/analytics',
            'view_data'  => [
                'form'             => $form,
                'submissions'      => $submissions,
                'stats'            => $stats,
                'category_analytics' => $category_analytics,
                'schema'           => json_decode($form['form_schema'], true)
            ]
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /* =======================
     * VIEW SUBMISSION
     * ======================= */

    public function view_submission($id)
    {
        $submission = $this->feedback->get_submission($id);
        if (!$submission) {
            show_404();
        }

        $form = $this->feedback->get_form($submission['form_id']);
        
        // Check permissions
        $user_id = (int)$this->session->userdata('user_id');
        $is_reviewer = $form['reviewers'] && in_array($user_id, explode(',', $form['reviewers']));
        $is_admin = $this->session->userdata('admin') == 1;
        $is_owner = $submission['user_id'] == $user_id;
        
        if (!$is_reviewer && !$is_admin && !$is_owner) {
            show_error('You do not have permission to view this submission.');
        }

        $data = [
            'submission' => $submission,
            'form'       => $form,
            'schema'     => json_decode($form['form_schema'], true),
            'answers'    => json_decode($submission['answers'], true),
            'comments'   => json_decode($submission['comments'], true)
        ];

        if ($this->input->is_ajax_request()) {
            $this->load->view('feedback/view_modal', $data);
        } else {
            $layout_data = [
                'page_title' => 'View Feedback Submission',
                'subview'    => 'feedback/view_submission',
                'view_data'  => $data
            ];
            $this->load->view('layouts/master', $layout_data);
        }
    }

    /* =======================
     * EXPORT FUNCTIONALITY
     * ======================= */

    public function export($form_id)
    {
        if (!staff_can('export', 'general')) {
            show_error('Permission denied.');
        }

        $form = $this->feedback->get_form($form_id);
        $submissions = $this->feedback->get_submissions_with_user_info($form_id);
        $schema = json_decode($form['form_schema'], true);

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="feedback_' . $form_id . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Headers
        $headers = ['Submission ID', 'Submitted At', 'User', 'Department', 'Average Score'];
        foreach ($schema['questions'] as $qid => $question) {
            if (!empty($question['label'])) {
                $headers[] = $question['label'];
            }
        }
        $headers[] = 'Comments';
        
        fputcsv($output, $headers);

        // Data rows
        foreach ($submissions as $submission) {
            $answers = json_decode($submission['answers'], true);
            $comments = json_decode($submission['comments'], true);
            
            $row = [
                $submission['id'],
                $submission['submitted_at'],
                $submission['full_name'] ?: 'Anonymous',
                $submission['department_name'] ?: 'N/A',
                $submission['average_score']
            ];
            
            foreach ($schema['questions'] as $qid => $question) {
                if (!empty($question['label'])) {
                    $row[] = $answers[$qid] ?? '';
                }
            }
            
            $row[] = implode("\n", array_filter($comments ?? []));
            
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }



public function edit($id)
{
    $form = $this->feedback->get_form($id);
    if (!$form) {
        show_404();
    }

    $departments = $this->departments->get_all_departments();

    $this->load->model('User_model');
    $staff_members = $this->User_model->get_all_users();

    $schema = json_decode($form['form_schema'], true);

    if ($this->input->post()) {

        $this->form_validation->set_rules('title', 'Form Title', 'required|trim');
        $this->form_validation->set_rules('frequency', 'Frequency', 'required');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === false) {
            set_alert('warning', 'Please fix the errors and try again.');
            goto load_view;
        }

        $questions = array_values(array_filter(
            $this->input->post('questions') ?? [],
            fn($q) => !empty(trim($q['label'] ?? ''))
        ));

        if (empty($questions)) {
            set_alert('warning', 'At least one question is required.');
            goto load_view;
        }

        $new_schema = [
            'questions' => $questions,
            'settings'  => [
                'rating_scale' => (int) $this->input->post('rating_scale') ?: 5,
                'allow_comments' => 1
            ]
        ];

        $update = [
            'title'                => $this->input->post('title', true),
            'description'          => $this->input->post('description', true),
            'frequency'            => $this->input->post('frequency'),
            'is_required'          => (int) $this->input->post('is_required'),
            'start_date'           => $this->input->post('start_date'),
            'end_date'             => $this->input->post('end_date') ?: null,
            'assigned_departments' => is_array($this->input->post('assigned_departments'))
                                        ? implode(',', $this->input->post('assigned_departments'))
                                        : null,
            'reviewers'            => is_array($this->input->post('reviewers'))
                                        ? implode(',', $this->input->post('reviewers'))
                                        : null,
            'form_schema'          => json_encode($new_schema, JSON_UNESCAPED_UNICODE),
            'updated_at'           => date('Y-m-d H:i:s')
        ];

        $this->feedback->update_form($id, $update);

        set_alert('success', 'Feedback form updated successfully.');
        redirect('feedback');
        return;
    }

    load_view:
    $this->load->view('layouts/master', [
        'page_title' => 'Edit Feedback Form',
        'subview'    => 'feedback/edit',
        'view_data'  => compact('form','schema','departments','staff_members')
    ]);
}
    
}
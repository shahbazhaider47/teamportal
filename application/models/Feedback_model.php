<?php defined('BASEPATH') or exit('No direct script access allowed');

class Feedback_model extends CI_Model
{
    protected $forms_table = 'employee_feedback_forms';
    protected $sub_table   = 'employee_feedback_submissions';

    /* =======================
     * FORMS
     * ======================= */

    public function get_forms($status = null, $user_id = null)
    {
        if ($status) {
            $this->db->where('status', $status);
        }
        
        // Filter by assigned departments if user provided
        if ($user_id) {
            $this->load->model('staff_model');
            $staff = $this->staff_model->get($user_id);
            
            if ($staff && $staff['department']) {
                $this->db->group_start();
                $this->db->where('assigned_departments IS NULL');
                $this->db->or_like('assigned_departments', $staff['department']);
                $this->db->group_end();
            }
        }

        return $this->db
            ->order_by('created_at', 'DESC')
            ->get($this->forms_table)
            ->result_array();
    }

    public function get_form($id)
    {
        return $this->db
            ->where('id', (int)$id)
            ->get($this->forms_table)
            ->row_array();
    }

    public function create_form(array $data)
    {
        $this->db->insert($this->forms_table, $data);
        return $this->db->insert_id();
    }

    public function update_form($id, array $data)
    {
        return $this->db
            ->where('id', (int)$id)
            ->update($this->forms_table, $data);
    }

    public function delete_form($id)
    {
        // First delete all submissions
        $this->db->where('form_id', $id)->delete($this->sub_table);
        
        // Then delete the form
        return $this->db->where('id', $id)->delete($this->forms_table);
    }

    /* =======================
     * SUBMISSIONS
     * ======================= */

    public function has_submitted($form_id, $user_id)
    {
        return $this->db
            ->where([
                'form_id' => (int)$form_id,
                'user_id' => (int)$user_id
            ])
            ->count_all_results($this->sub_table) > 0;
    }

    public function submit_feedback(array $data)
    {
        $this->db->insert($this->sub_table, $data);
        return $this->db->insert_id();
    }

    public function get_submissions($form_id, $limit = null, $offset = null)
    {
        $this->db->where('form_id', (int)$form_id);
        
        if ($limit) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db
            ->order_by('submitted_at', 'DESC')
            ->get($this->sub_table)
            ->result_array();
    }

    public function get_submissions_with_user_info($form_id)
    {
        return $this->db
            ->select('s.*, u.firstname, u.lastname, u.email, d.name as department_name')
            ->from($this->sub_table . ' as s')
            ->join('staff as u', 'u.staffid = s.user_id', 'left')
            ->join('departments as d', 'd.departmentid = u.department', 'left')
            ->where('s.form_id', (int)$form_id)
            ->order_by('s.submitted_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_submission($id)
    {
        return $this->db
            ->where('id', (int)$id)
            ->get($this->sub_table)
            ->row_array();
    }

    public function get_user_submissions($user_id, $limit = null)
    {
        $this->db->where('user_id', $user_id);
        
        if ($limit) {
            $this->db->limit($limit);
        }
        
        return $this->db
            ->order_by('submitted_at', 'DESC')
            ->get($this->sub_table)
            ->result_array();
    }

    /* =======================
     * ANALYTICS & STATS
     * ======================= */

    public function get_form_stats($form_id)
    {
        return $this->db
            ->select('
                AVG(average_score) as avg_score,
                COUNT(id) as total,
                MIN(submitted_at) as first_submission,
                MAX(submitted_at) as last_submission,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->where('form_id', (int)$form_id)
            ->get($this->sub_table)
            ->row_array();
    }

    public function get_category_analytics($form_id)
    {
        $submissions = $this->db
            ->select('category_scores')
            ->where('form_id', (int)$form_id)
            ->get($this->sub_table)
            ->result_array();

        $category_totals = [];
        $category_counts = [];

        foreach ($submissions as $submission) {
            $scores = json_decode($submission['category_scores'], true);
            
            if ($scores) {
                foreach ($scores as $category => $score) {
                    if (!isset($category_totals[$category])) {
                        $category_totals[$category] = 0;
                        $category_counts[$category] = 0;
                    }
                    $category_totals[$category] += $score;
                    $category_counts[$category]++;
                }
            }
        }

        $averages = [];
        foreach ($category_totals as $category => $total) {
            $averages[$category] = $category_counts[$category] > 0 
                ? round($total / $category_counts[$category], 2) 
                : 0;
        }

        arsort($averages); // Sort by highest average

        return [
            'averages' => $averages,
            'total_responses' => array_sum($category_counts)
        ];
    }

    public function get_question_analytics($form_id)
    {
        $form = $this->get_form($form_id);
        $schema = json_decode($form['form_schema'], true);
        $questions = $schema['questions'] ?? [];
        
        $submissions = $this->db
            ->select('answers')
            ->where('form_id', (int)$form_id)
            ->get($this->sub_table)
            ->result_array();

        $question_stats = [];
        
        foreach ($questions as $qid => $question) {
            if (empty($question['label'])) continue;
            
            $question_stats[$qid] = [
                'label' => $question['label'],
                'category' => $question['category'] ?? 'general',
                'type' => $question['type'] ?? 'rating',
                'total_responses' => 0,
                'average' => 0,
                'distribution' => []
            ];
            
            // Initialize distribution for rating questions
            if ($question['type'] == 'rating') {
                $scale = $schema['settings']['rating_scale'] ?? 5;
                for ($i = 1; $i <= $scale; $i++) {
                    $question_stats[$qid]['distribution'][$i] = 0;
                }
            }
        }

        // Calculate statistics
        foreach ($submissions as $submission) {
            $answers = json_decode($submission['answers'], true);
            
            foreach ($answers as $qid => $answer) {
                if (isset($question_stats[$qid])) {
                    $question_stats[$qid]['total_responses']++;
                    
                    if (is_numeric($answer)) {
                        $question_stats[$qid]['average'] += $answer;
                        
                        // Update distribution
                        if ($question_stats[$qid]['type'] == 'rating') {
                            $rating = (int)round($answer);
                            if (isset($question_stats[$qid]['distribution'][$rating])) {
                                $question_stats[$qid]['distribution'][$rating]++;
                            }
                        }
                    }
                }
            }
        }

        // Calculate averages
        foreach ($question_stats as &$stat) {
            if ($stat['total_responses'] > 0) {
                $stat['average'] = round($stat['average'] / $stat['total_responses'], 2);
            }
        }

        return $question_stats;
    }

    /* =======================
     * DASHBOARD WIDGET DATA
     * ======================= */

    public function get_dashboard_stats($user_id)
    {
        // Forms assigned to user
        $this->load->model('staff_model');
        $staff = $this->staff_model->get($user_id);
        
        $assigned_forms = $this->db
            ->where('status', 'active')
            ->group_start()
            ->where('assigned_departments IS NULL')
            ->or_like('assigned_departments', $staff['department'])
            ->group_end()
            ->count_all_results($this->forms_table);

        // Forms submitted by user
        $submitted_forms = $this->db
            ->select('COUNT(DISTINCT form_id) as total')
            ->where('user_id', $user_id)
            ->get($this->sub_table)
            ->row()->total ?? 0;

        // Pending forms
        $pending_forms = $assigned_forms - $submitted_forms;

        // Average rating given by user
        $user_avg = $this->db
            ->select_avg('average_score')
            ->where('user_id', $user_id)
            ->get($this->sub_table)
            ->row()->average_score ?? 0;

        return [
            'assigned_forms' => $assigned_forms,
            'submitted_forms' => $submitted_forms,
            'pending_forms' => max(0, $pending_forms),
            'user_average' => round($user_avg, 2)
        ];
    }
}
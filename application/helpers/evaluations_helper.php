<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('eval_review_types')) {
    function eval_review_types(): array
    {
        return [
            'monthly'    => 'Monthly',
            'bi-annual'  => 'Bi-Annual',
            'annual'     => 'Annual',
            'quarterly'  => 'Quarterly',
            'probation'  => 'Probation',
            'custom'     => 'Custom',
        ];
    }
}

if (!function_exists('eval_statuses')) {
    function eval_statuses(): array
    {
        return [
            'draft'     => 'Draft',
            'submitted' => 'Submitted',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
        ];
    }
}

if (!function_exists('eval_status_badge')) {
    function eval_status_badge(string $status): string
    {
        $map = [
            'draft'     => 'secondary',
            'submitted' => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
        ];
        $color = $map[$status] ?? 'secondary';
        $label = ucfirst($status);
        return '<span class="badge bg-light-' . $color . ' text-' . $color . '">' . html_escape($label) . '</span>';
    }
}

if (!function_exists('eval_score_badge')) {
    function eval_score_badge(?float $score): string
    {
        if ($score === null) {
            return '<span class="text-muted small">—</span>';
        }

        if ($score >= 4.5) {
            $color = 'success';
            $label = 'Excellent';
        } elseif ($score >= 3.5) {
            $color = 'primary';
            $label = 'Good';
        } elseif ($score >= 2.5) {
            $color = 'info';
            $label = 'Satisfactory';
        } elseif ($score >= 1.5) {
            $color = 'warning';
            $label = 'Fair';
        } else {
            $color = 'danger';
            $label = 'Poor';
        }

        return '<span class="badge bg-light-' . $color . ' text-' . $color . '">'
             . number_format($score, 1) . ' — ' . $label
             . '</span>';
    }
}

if (!function_exists('eval_achievement_badge')) {
    function eval_achievement_badge(?float $pct): string
    {
        if ($pct === null) {
            return '<span class="text-muted small">—</span>';
        }

        if ($pct >= 100) {
            $color = 'success';
        } elseif ($pct >= 80) {
            $color = 'primary';
        } elseif ($pct >= 60) {
            $color = 'warning';
        } else {
            $color = 'danger';
        }

        return '<span class="badge bg-light-' . $color . ' text-' . $color . '">'
             . number_format($pct, 1) . '%'
             . '</span>';
    }
}

if (!function_exists('eval_pass_fail_badge')) {
    function eval_pass_fail_badge(?string $value): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-muted small">—</span>';
        }
        $map = [
            'pass' => ['success', 'Pass'],
            'fail' => ['danger',  'Fail'],
            'na'   => ['secondary','N/A'],
        ];
        $v     = strtolower($value);
        $color = $map[$v][0] ?? 'secondary';
        $label = $map[$v][1] ?? ucfirst($value);
        return '<span class="badge bg-light-' . $color . ' text-' . $color . '">' . $label . '</span>';
    }
}

if (!function_exists('eval_attendance_options')) {
    function eval_attendance_options(): array
    {
        return ['Poor', 'Fair', 'Satisfactory', 'Good', 'Excellent'];
    }
}

if (!function_exists('eval_phone_usage_options')) {
    function eval_phone_usage_options(): array
    {
        return ['Always', '>Average', 'Average', 'Normal', 'Rarely'];
    }
}

if (!function_exists('eval_review_periods')) {
    function eval_review_periods(string $review_type = 'monthly', int $year = 0): array
    {
        if (!$year) {
            $year = (int) date('Y');
        }

        $periods = [];

        switch ($review_type) {
            case 'monthly':
                for ($m = 1; $m <= 12; $m++) {
                    $periods[] = date('F', mktime(0, 0, 0, $m, 1)) . ' ' . $year;
                }
                break;
            case 'quarterly':
                $periods = [
                    'Q1 ' . $year . ' (Jan–Mar)',
                    'Q2 ' . $year . ' (Apr–Jun)',
                    'Q3 ' . $year . ' (Jul–Sep)',
                    'Q4 ' . $year . ' (Oct–Dec)',
                ];
                break;
            case 'bi-annual':
                $periods = [
                    'H1 ' . $year . ' (Jan–Jun)',
                    'H2 ' . $year . ' (Jul–Dec)',
                ];
                break;
            case 'annual':
                $periods = ['Annual ' . $year];
                break;
            default:
                $periods = ['Custom'];
        }

        return $periods;
    }
}

if (!function_exists('eval_verdict_options')) {
    function eval_verdict_options(): array
    {
        return [
            'Excellent'                   => 'Excellent — Exceeds all expectations',
            'Good'                        => 'Good — Meets and often exceeds expectations',
            'Satisfactory'                => 'Satisfactory — Meets expectations',
            'Needs Improvement'           => 'Needs Improvement — Below expectations in key areas',
            'Unsatisfactory'              => 'Unsatisfactory — Does not meet expectations',
            'Probation Warning'           => 'Probation Warning — Performance under review',
        ];
    }
}

if (!function_exists('eval_criteria_type_label')) {
    function eval_criteria_type_label(string $type): string
    {
        $map = [
            'rating'     => 'Rating (1–5)',
            'pass_fail'  => 'Pass / Fail',
            'target'     => 'Work Target',
            'attendance' => 'Attendance Rating',
            'phone'      => 'Phone Usage',
            'text'       => 'Free Text',
        ];
        return $map[$type] ?? ucfirst($type);
    }
}

if (!function_exists('eval_format_period')) {
    function eval_format_period(string $review_period, string $review_type): string
    {
        if ($review_period) {
            return $review_period;
        }
        return ucfirst($review_type);
    }
}

if (!function_exists('eval_score_color')) {
    function eval_score_color(?float $score): string
    {
        if ($score === null) return 'text-muted';
        if ($score >= 4.5)   return 'text-success';
        if ($score >= 3.5)   return 'text-primary';
        if ($score >= 2.5)   return 'text-info';
        if ($score >= 1.5)   return 'text-warning';
        return 'text-danger';
    }
}
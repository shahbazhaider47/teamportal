<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Todo_ai_helper
 *
 * Centralized "AI"-like heuristic for detecting text that should
 * offer an "Add to My To-Do" button.
 *
 * Usage:
 *   $this->load->library('ai/Todo_ai_helper', [], 'todo_ai');
 *   $show = $this->todo_ai->should_trigger_button($someText);
 */
class Todo_ai_helper
{
    /**
     * Simple phrase buckets for different intentions.
     * You can tweak/extend these as you see patterns in real data.
     *
     * @var array<string, string[]>
     */
    protected array $rules = [
        'followup' => [
            'follow up',
            'follow-up',
            'need to follow',
            'get back to',
            'circle back',
            'check back',
            'touch base',
            'schedule',
        ],
        'do_later' => [
            'do this later',
            'later on',
            'not now',
            'when free',
            'when you get time',
            'once you have time',
            'once you get time',
            'after finishing',
            'after we finish',
            'after this one',
        ],
        'reminder' => [
            'remind me',
            'remember to',
            'don\'t forget',
            'pls remind',
            'please remind',
            'note to self',
        ],
        'priority' => [
            'high priority',
            'top priority',
            'urgent',
            'asap',
            'as soon as possible',
            'must do',
            'needs to be done',
            'have to do',
        ],
    ];

    /**
     * Regex rules for dates/time-like phrases (tomorrow, next week, etc.)
     * Keep these simple and safe.
     *
     * @var string[]
     */
    protected array $regexRules = [
        // "tomorrow", "next week", "next month", "next monday", etc.
        '/\b(tomorrow|next week|next month|next (mon(day)?|tue(sday)?|wed(nesday)?|thu(rsday)?|fri(day)?|sat(urday)?|sun(day)?))\b/i',

        // "by Friday", "by 20th", "before monday"
        '/\b(by|before)\s+(mon(day)?|tue(sday)?|wed(nesday)?|thu(rsday)?|fri(day)?|sat(urday)?|sun(day)?|\d{1,2}(st|nd|rd|th)?|tomorrow)\b/i',
    ];

    /**
     * Optional configuration in future (e.g. to override rules).
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['rules']) && is_array($config['rules'])) {
            // Allow overriding / extending rules from config if needed
            $this->rules = $config['rules'];
        }

        if (!empty($config['regexRules']) && is_array($config['regexRules'])) {
            $this->regexRules = $config['regexRules'];
        }
    }

    /**
     * Main entrypoint for your views/controllers.
     *
     * Return TRUE if the text looks like something that should be
     * easy to add to "My To-Do".
     */
    public function should_trigger_button(?string $text): bool
    {
        $text = trim((string)$text);
        if ($text === '') {
            return false;
        }

        // Normalize for simpler matching.
        $haystack = mb_strtolower($text, 'UTF-8');

        // 1) Simple phrase checks.
        foreach ($this->rules as $bucket => $phrases) {
            foreach ($phrases as $phrase) {
                $phrase = mb_strtolower($phrase, 'UTF-8');
                if ($phrase !== '' && mb_strpos($haystack, $phrase) !== false) {
                    return true;
                }
            }
        }

        // 2) Regex-based time/date hints.
        foreach ($this->regexRules as $pattern) {
            if (@preg_match($pattern, $text)) {
                if (preg_match($pattern, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * (Optional) If in future you want more detail:
     * returns which buckets matched (followup, do_later, reminder, priority).
     */
    public function analyze(?string $text): array
    {
        $text = trim((string)$text);
        if ($text === '') {
            return [];
        }

        $haystack = mb_strtolower($text, 'UTF-8');
        $matches  = [];

        foreach ($this->rules as $bucket => $phrases) {
            foreach ($phrases as $phrase) {
                $phrase = mb_strtolower($phrase, 'UTF-8');
                if ($phrase !== '' && mb_strpos($haystack, $phrase) !== false) {
                    $matches[$bucket] = true;
                    break;
                }
            }
        }

        foreach ($this->regexRules as $pattern) {
            if (@preg_match($pattern, $text)) {
                if (preg_match($pattern, $text)) {
                    $matches['time_hint'] = true;
                    break;
                }
            }
        }

        return array_keys($matches);
    }
}

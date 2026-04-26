<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// File: application/libraries/AppHooks.php
class AppHooks
{
    protected $actions = [];

    public function add_action($tag, $callback, $priority = 10)
    {
        $this->actions[$tag][$priority][] = $callback;
    }

    public function do_action($tag, ...$args)
    {
        if (!isset($this->actions[$tag])) return;

        ksort($this->actions[$tag]);
        foreach ($this->actions[$tag] as $callbacks) {
            foreach ($callbacks as $cb) {
                call_user_func_array($cb, $args);
            }
        }
    }

    // Optional filters
    protected $filters = [];

    public function add_filter($tag, $callback, $priority = 10)
    {
        $this->filters[$tag][$priority][] = $callback;
    }

    public function apply_filters($tag, $value, ...$args)
    {
        if (!isset($this->filters[$tag])) return $value;

        ksort($this->filters[$tag]);
        foreach ($this->filters[$tag] as $callbacks) {
            foreach ($callbacks as $cb) {
                $value = call_user_func_array($cb, array_merge([$value], $args));
            }
        }

        return $value;
    }
}


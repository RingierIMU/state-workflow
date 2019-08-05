<?php

if (!function_exists('event')) {
    $events = null;

    /**
     * @param $ev
     */
    function event($ev)
    {
        global $events;
        $events[] = $ev;
    }
}

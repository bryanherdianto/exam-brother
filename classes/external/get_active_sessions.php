<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

class get_active_sessions extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get all active exam sessions
     * @return array
     */
    public static function execute() {
        global $DB;

        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/myplugin:monitor', $context);

        // Get active sessions
        $sessions = $DB->get_records('local_myplugin_sessions', ['status' => 'active'], 'starttime DESC');

        $result = [];
        foreach ($sessions as $session) {
            $user = $DB->get_record('user', ['id' => $session->userid]);
            $alertcount = $DB->count_records('local_myplugin_alerts', ['sessionid' => $session->id]);

            $result[] = [
                'sessionid' => $session->id,
                'userid' => $session->userid,
                'username' => fullname($user),
                'examname' => $session->examname,
                'starttime' => $session->starttime,
                'alertcount' => $alertcount,
            ];
        }

        return [
            'success' => true,
            'sessions' => $result,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'sessions' => new external_multiple_structure(
                new external_single_structure([
                    'sessionid' => new external_value(PARAM_INT, 'Session ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'User full name'),
                    'examname' => new external_value(PARAM_TEXT, 'Exam name'),
                    'starttime' => new external_value(PARAM_INT, 'Start timestamp'),
                    'alertcount' => new external_value(PARAM_INT, 'Number of alerts'),
                ])
            ),
        ]);
    }
}

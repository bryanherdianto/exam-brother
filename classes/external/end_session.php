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

class end_session extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }

    /**
     * End an exam session
     * @param int $sessionid
     * @return array
     */
    public static function execute($sessionid) {
        global $DB, $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid' => $sessionid,
        ]);

        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);

        // Get session
        $session = $DB->get_record('local_myplugin_sessions', ['id' => $params['sessionid']], '*', MUST_EXIST);

        // Check permissions
        if ($session->userid != $USER->id && !has_capability('local/myplugin:monitor', $context)) {
            throw new \moodle_exception('nopermission', 'error');
        }

        // Update session
        $session->status = 'completed';
        $session->endtime = time();
        $session->timemodified = time();

        $DB->update_record('local_myplugin_sessions', $session);

        return [
            'success' => true,
            'message' => 'Session ended successfully',
            'sessionid' => $params['sessionid'],
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }
}

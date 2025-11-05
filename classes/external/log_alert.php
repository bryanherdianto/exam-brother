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

class log_alert extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'alerttype' => new external_value(PARAM_TEXT, 'Type of alert'),
            'description' => new external_value(PARAM_TEXT, 'Alert description'),
            'screenshot' => new external_value(PARAM_RAW, 'Base64 encoded screenshot'),
            'severity' => new external_value(PARAM_INT, 'Alert severity level', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Log a cheating alert
     * @param int $sessionid
     * @param int $userid
     * @param string $alerttype
     * @param string $description
     * @param string $screenshot
     * @param int $severity
     * @return array
     */
    public static function execute($sessionid, $userid, $alerttype, $description, $screenshot, $severity) {
        global $DB;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid' => $sessionid,
            'userid' => $userid,
            'alerttype' => $alerttype,
            'description' => $description,
            'screenshot' => $screenshot,
            'severity' => $severity,
        ]);

        // Validate context
        $context = \context_system::instance();
        self::validate_context($context);

        // Check if session exists and is active
        $session = $DB->get_record('local_myplugin_sessions', ['id' => $params['sessionid']], '*', MUST_EXIST);

        // Insert alert
        $alert = new \stdClass();
        $alert->sessionid = $params['sessionid'];
        $alert->userid = $params['userid'];
        $alert->alerttype = $params['alerttype'];
        $alert->description = $params['description'];
        $alert->severity = $params['severity'];
        $alert->timecreated = time();

        $alertid = $DB->insert_record('local_myplugin_alerts', $alert);

        // Process and save screenshot if provided
        if (!empty($params['screenshot'])) {
            try {
                // Remove data URL prefix if present
                $imagedata = preg_replace('/^data:image\/\w+;base64,/', '', $params['screenshot']);
                
                // Validate it's valid base64
                if (base64_decode($imagedata, true) === false) {
                    error_log('Invalid base64 data for screenshot');
                } else {
                    $screenshot_record = new \stdClass();
                    $screenshot_record->alertid = $alertid;
                    $screenshot_record->sessionid = $params['sessionid'];
                    $screenshot_record->userid = $params['userid'];
                    $screenshot_record->imagedata = $imagedata;
                    $screenshot_record->timecreated = time();

                    $screenshotid = $DB->insert_record('local_myplugin_screenshots', $screenshot_record);
                    error_log('Screenshot saved successfully with ID: ' . $screenshotid);
                }
            } catch (\Exception $e) {
                error_log('Error saving screenshot: ' . $e->getMessage());
            }
        } else {
            error_log('No screenshot data provided');
        }

        return [
            'success' => true,
            'alertid' => $alertid,
            'message' => 'Alert logged successfully',
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'alertid' => new external_value(PARAM_INT, 'Alert ID'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}

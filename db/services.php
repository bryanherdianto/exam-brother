<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_myplugin_log_alert' => [
        'classname'   => 'local_myplugin\external\log_alert',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Log a cheating alert with screenshot',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'local_myplugin_end_session' => [
        'classname'   => 'local_myplugin\external\end_session',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'End an exam monitoring session',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'local_myplugin_get_active_sessions' => [
        'classname'   => 'local_myplugin\external\get_active_sessions',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get all active exam sessions',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];

$services = [
    'Exam Monitor Services' => [
        'functions' => [
            'local_myplugin_log_alert',
            'local_myplugin_end_session',
            'local_myplugin_get_active_sessions',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];

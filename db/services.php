<?php

$functions = [

    'mod_offlinequiz_get_offlinequizzes_by_courses' => [
        'classname'     => 'mod_offlinequiz_external',
        'methodname'    => 'get_offlinequizzes_by_courses',
        'classpath'     => 'mod/offlinequiz/externallib.php',
        'description'   => 'Get all offlinequizzes in the given courses',
        'type'          => 'read',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_offlinequiz_get_offlinequiz' => [
        'classname'     => 'mod_offlinequiz_external',
        'methodname'    => 'get_offlinequiz',
        'classpath'     => 'mod/offlinequiz/externallib.php',
        'description'   => 'Get offlinequizze with the given id',
        'type'          => 'read',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_offlinequiz_get_attempt_review' => [
        'classname'     => 'mod_offlinequiz_external',
        'methodname'    => 'get_attempt_review',
        'classpath'     => 'mod/offlinequiz/externallib.php',
        'description'   => "Get current user's review for the specified offlinequiz.",
        'type'          => 'read',
        'capabilities'  => 'mod/offlinequiz:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

];

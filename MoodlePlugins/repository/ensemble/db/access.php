<?php

defined('MOODLE_INTERNAL') || die();
// sets default permissions --wise open
$capabilities = array(

    'repository/ensemble:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    )
);
?>


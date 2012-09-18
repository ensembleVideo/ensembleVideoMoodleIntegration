<?php

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


<?php
include('lib/admin.php');

// access-checking for reporting

$access = array(
	'janitor' => array(
		'delete' => 1,
		'ban_req' => 1,
		'escalate' => 1,
		'clear' => 1,
	),
	'mod' => array(
		'delete' => 1,
		'delete_ip' => 1,
		'ban' => 1,
		'clear' => 1,
		'ban_reporter' => 1,
		'clear_reporter' => 1,
		'delete_reporter' => 1,
		'admin_php' => 1,
		'cleanup' => 1,
		'clear_alert' => 1,
		'is_developer' => false
	)
);

$access['manager'] = array_merge( $access['janitor'], $access['mod'] );
$access['manager']['is_manager'] = true;

// Let admins do janitor stuff too, just in case...
$access['admin'] = array_merge( $access['janitor'], $access['manager'] );
$access['admin']['is_admin'] = true;
$access['admin']['nuke'] = 1;
$access['admin']['manual_ban'] = 1;


?>

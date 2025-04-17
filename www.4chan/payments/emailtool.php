<?php
if( !isset( $_POST ) ) die();
if( !isset( $_POST['secretkey'] ) || $_POST['secretkey'] != '078210aa9a08824a60e6a00ac0fc9587' ) die();


function send_suspicious_email( $details, $why )
{
	$dump = print_r( $details, true );

	$subject = '[Warning] Suspicious 4chan Pass Purchase';
	$msg = <<<EMAIL
A suspicious 4chan Pass purchase has been made.

Purchase Details
================
$dump

Reason
======
$why
EMAIL;

	$headers = <<<HEADERS
From: 4chan Pass <4chanpass@4chan.org>
MIME-Version: 1.0
Content-Type: text/plain; charset=ISO-8859-1; format=flowed
Content-Transfer-Encoding: 7bit
HEADERS;

	mail( '4chanpass@4chan.org', $subject, $msg, $headers, '-f 4chanpass@4chan.org' );
}

send_suspicious_email($_POST['details'], $_POST['why']);
die();

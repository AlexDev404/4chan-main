<?php
if ($_SERVER['HTTP_HOST'] !== 'reports.4chan.org') {
  header('Location: https://reports.4chan.org');
  die();
}

if (!isset($_COOKIE['4chan_auser']) || !isset($_COOKIE['apass'])) {
  if ($_SERVER['REQUEST_METHOD'] == 'POST'
    || (isset($_GET['action']) && $_GET['action'] !== 'ban_requests')) {
    header('Content-type: application/json');
    echo json_encode(
      array(
        'status' => 'error',
        'message' => 'You need to log in first'
      )
    );
  }
  else {
    header('HTTP/1.0 403 Forbidden');
    require_once('views/denied.tpl.php');
  }
  
  die();
}

require_once 'lib/db.php';
require_once 'access.php';
require_once 'lib/archives.php';
require_once 'lib/auth.php';

require_once 'ReportQueue-test.php';

require_once 'csp.php';

mysql_global_connect();

$my_access = access_check();

if (!$my_access || !is_array($my_access)) {
  die();
}

if ($my_access && $_COOKIE['4chan_auser'] === 'desuwa' && isset($_COOKIE['as_janitor'])) {
  //$my_access = $access['janitor'];
  $my_access['ban'] = 0;
  $my_access['board'] = array('test', 'g');
}

if ($_COOKIE['4chan_auser'] !== 'desuwa') {
  die();
}

//die();

if ($my_access['is_developer']) {
  $mysql_suppress_err = false;
  ini_set('display_errors', 1);
  error_reporting(E_ALL & ~E_NOTICE);
}

$ctrl = new ReportQueue($my_access, isset($is_count_reports));

define('IN_APP', true);

$ctrl->run();

?>

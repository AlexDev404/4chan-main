<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Banner Contest - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/banner_contest.css?25">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
</head>
<body>
<header><h1 id="title">4chan Banner Contest</h1></header>
<div id="content">
  <div id="success-cnt">
    <h3>Your banner was submitted successfully and is now awaiting approval.</h3>
    <p>You can check the status of your banner or delete it at any time during the submission phase using the following link:<br>https://www.4chan.org/banner-contest/status/<?php echo $this->private_id ?></p>
  </div>
</div>
<footer>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

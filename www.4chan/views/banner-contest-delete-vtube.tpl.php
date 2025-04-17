<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Vtuber Competition - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/banner_contest.css?25">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
</head>
<body>
<header><h1 id="title">4chan Vtuber Competition</h1></header>
<div id="content">
  <div id="del-form-cnt">
    <h3>Are you sure you want to delete this image?</h3>
    <form id="del-form" action="" method="POST" enctype="application/x-www-form-urlencoded">
      <?php echo captcha_form(true) ?>
      <input type="hidden" name="_bctkn" value="<?php echo $this->_tkn ?>">
      <input type="hidden" name="key" value="<?php echo $this->private_id ?>">
      <button class="del-btn" type="submit" name="action" value="confirm_delete">Delete</button> or <button type="submit" name="action" value="check_status">Check Status</button>
    </form>
  </div>
</div>
<footer>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

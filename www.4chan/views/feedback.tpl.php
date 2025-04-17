<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html<?php if (IS_4CHANNEL) echo ' class="is_channel"'; ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Feedback - 4chan</title>
  <?php if (isset($this->redirect_to)): ?>
  <meta http-equiv="refresh" content="<?php echo($this->redirect_time); ?>;URL=<?php echo $this->redirect_to ?>">
  <?php endif ?>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/feedback.css?6">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="//s.4cdn.org/js/feedback.js?6"></script>
  <noscript>
    <style type="text/css">
      #form {
        display: block;
      }
      #captcha-cnt { height: auto; }
    </style>
  </noscript>
</head>
<body>
<header>
  <h1 id="title">4chan Feedback</h1>
</header>
<section id="content">
<?php
/**
 * Index
 */
if ($this->mode === 'index'): ?>
<div id="form">
  <h2>Submit Feedback</h2>
  <form method="post" enctype="multipart/form-data">
    <div class="form-col">
      <label for="form-cat">Category:</label>
      <select name="category" id="form-cat">
        <?php foreach ($this->categoriesLabels as $id => $category): ?>
        <option value="<?php echo $id ?>"><?php echo $category ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <label for="form-subject">Subject:</label>
    <input maxlength="<?php echo $this->max_len_subject ?>" type="text" name="subject" id="form-subject" required>
    <label for="form-message">Message:</label>
    <textarea maxlength="<?php echo $this->max_len_message ?>" name="message" rows="8" cols="40" id="form-message" required></textarea>
    <div id="captcha-cnt"><label>Verification:</label>
    <?php echo captcha_form(true) ?>
    </div>
    <button type="submit" id="form-submit">Submit</button>
    <input type="hidden" name="action" value="submit">
  </form>
</div>
<?php
/**
 * Error
 */
elseif ($this->mode === 'error'): ?>
<div class="feedback error"><?php echo $this->message ?></div>
<?php
/**
 * Success
 */
elseif ($this->mode === 'success'): ?>
<div class="feedback success"><?php echo $this->message ?></div>
<?php endif ?>
</section>
<footer>
<ul><?php $current_page = 'feedback';
  foreach ($frontpage_footer as $label => $url):
    if ($url == $current_page) {
      $class = ' class="current-page"';
    }
    else {
      $class = '';
    }
  ?>
  <li<?php echo $class ?>><a href="<?php echo $url ?>"><?php echo $label ?></a></li>
  <?php endforeach ?>
</ul>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

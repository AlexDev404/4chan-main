<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Blotter - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/blotter.css?4">
  <link href="//www.4chan.org/blotter?atom" rel="alternate" title="Recent Blotter Entries" type="application/atom+xml">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
</head>
<body>
<header>
  <h1 id="title">4chan Blotter</h1>
</header>
<section id="content">
<table id="entries">
  <thead>
    <tr>
      <th class="col-date">Date</th>
      <th>Message</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($this->messages as $message): ?>
    <tr id="msg-<?php echo $message['id'] ?>">
      <td class="col-date"><?php echo date('m/d/y', $message['date']) ?></td>
      <td class="col-msg"><?php echo $message['content'] ?></td>
    </tr>
    <?php endforeach ?>
  </tbody><?php if ($this->has_next_page): $lastid = end($this->messages); $lastid = (int)$lastid['id']; ?>
  <tfoot>
    <tr>
      <td colspan="3">
        <a href="?offset=<?php echo $lastid ?>">Next</a>
      </td>
    </tr>
  </tfoot><?php endif ?>
</table>
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

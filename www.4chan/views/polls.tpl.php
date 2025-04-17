<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Polls - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/polls.css?15">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create','UA-166538-1', {'sampleRate': 1});
ga('set', 'anonymizeIp', true);
ga('send','pageview')</script>
</head>
<body>
<header><h1 id="title">4chan Polls</h1></header>
<div id="content">
<div id="intro">
<p>The stories and information found here are artistic works of fiction and falsehood.<br>Only a fool would take anything found here as fact.</p>
</div>
<?php if (!empty($this->items)): ?>
<h3 class="event-hdr">Current Polls</h3>
<table id="entries" class="event-tbl">
<tbody>
  <?php foreach ($this->items as $poll): ?>
    <tr>
      <td><a href="polls/<?php echo $poll['id'] ?>"><?php echo $poll['title'] ?></a></td>
    </tr>
  <?php endforeach ?>
</tbody>
</table>
<?php endif ?>
</div>
<footer>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

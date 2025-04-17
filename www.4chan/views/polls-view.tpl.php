<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Polls - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/polls.css?15">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="//s.4cdn.org/js/polls.js?4"></script>
  <script type="text/javascript">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create','UA-166538-1', {'sampleRate': 1});
ga('set', 'anonymizeIp', true);
ga('send','pageview')</script>
</head>
<body data-tkn="<?php echo $this->_tkn ?>">
<header><h1 id="title">4chan Polls</h1></header>
<div id="content">
<div class="poll-cnt">
<h3 class="poll-hdr"><?php echo $this->poll['title'] ?></h3>
<?php if ($this->poll['description'] !== ''): ?>
<div id="poll-desc"><?php echo $this->poll['description'] ?></div>
<?php endif ?>
<?php if (isset($this->scores)): ?>
<table id="entries" class="poll-res-tbl">
<?php foreach ($this->options as $option):
if (isset($this->scores[$option['id']])) {
  $score = $this->scores[$option['id']];
  $perc = round($score / $this->poll['vote_count'] * 100, 2);
}
else {
  $score = 0;
  $perc = 0;
}
?>
  <tr>
    <th><?php echo $perc ?>% (<?php echo $score ?>)</th>
    <td><?php echo $option['caption'] ?></td>
  </tr>
<?php endforeach ?>
<tr class="poll-res-total">
  <td></td>
  <td>Total votes: <?php echo $this->poll['vote_count'] ?></td>
</tr>
</table>
<a class="back-link" href="/polls/<?php echo $this->poll_id ?>">Back to Options</a>
<?php else: ?>
<form id="poll-form" action="" method="POST" enctype="application/x-www-form-urlencoded">
<table id="entries" class="opts-tbl">
<tbody>
  <?php $i = 1; foreach ($this->options as $opt): ?>
    <tr>
      <td class="col-opt"><input type="radio" name="id" value="<?php echo $opt['id'] ?>"></td>
      <td><?php echo $opt['caption'] ?></td>
    </tr>
  <?php ++$i; endforeach ?>
</tbody>
</table><input type="hidden" name="_ptkn" value="<?php echo $this->_tkn ?>">
<button class="vote-btn" name="action" value="vote" type="submit">Vote</button><a class="res-link" href="/polls">Back to Polls</a><a class="res-link" href="/polls/results/<?php echo $this->poll_id ?>">View Results</a>
</form>
<?php endif ?>
</div>
</div>
<footer>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

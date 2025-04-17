<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>4chan - Bans</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/bans.css?9">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="//s.4cdn.org/js/bans.js?6"></script>
  <script type="text/javascript">
    var postPreviews = <?php echo $this->previews ?>;
  </script>
</head>
<body>
<header>
  <h1 id="title">4chan Bans</h1>
  <div id="description">
    The purpose of this page is to give users insight into what content is being removed, and why.<br>Below is a sample of recent bans (not a comprehensive list of <i>all</i> bans), updated once per hour.<br><br>If you haven't already done so, please familiarize yourself with the <a href="/rules">Rules</a>.
  </div>
</header>
<table id="log-entries">
<thead>
  <tr>
    <th class="col-board">Board</th>
    <th class="col-action">Action</th>
    <th class="col-length">Length</th>
    <th class="col-post">Post</th>
    <th class="col-reason">Reason</th>
    <th class="col-time">Time</th>
  </tr>
</thead>
<tbody>
<?php foreach ($this->entries as $entry): ?>
  <tr>
    <td><?php if ($entry['board']) echo '/' . $entry['board'] . '/' ?></td>
    <td><?php echo $entry['type'] ?></td>
    <td><?php echo($entry['type'] == 'Warn' ? 'n/a' : $this->templates[$entry['ban_template']]['length']) ?></td>
    <td><span<?php if (isset($entry['preview_id'])) echo(' class="preview-link" data-pid="' . $entry['preview_id'] . '"') ?>>View<?php if ($entry['is_op']) echo ' (OP)'; ?></span></td>
    <td><?php echo($this->templates[$entry['ban_template']]['name']) ?></td>
    <td class="time" data-utc="<?php echo $entry['time'] ?>"></td>
  </tr>
<?php endforeach ?>
</tbody>
</table>
<footer>
<ul><?php $current_page = 'bans';
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
All trademarks and copyrights on this page are owned by their respective parties.<br>
The rest is Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

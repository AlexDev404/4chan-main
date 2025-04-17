<?php if (!defined('IN_APP')) die() ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Messages</title>
  <link rel="stylesheet" type="text/css" href="/css/reportqueue-test.css?138">
  <link rel="shortcut icon" href="/image/favicon-team.ico" type="image/x-icon">
</head>
<body>
<header>
  <h1 id="title">Messages</h1>
</header>
<div id="menu">
  <a href="/" class="button button-light">Reports</a>
</div>
<div id="content">
<table class="items-table">
<thead>
  <tr>
    <th>Created on</th>
    <th>Boards</th>
    <th class="col-msg">Message</th>
  </tr>
</thead>
<tbody>
  <?php foreach ($this->messages as $msg): ?>
  <tr<?php if ($this->ts && $this->ts > $msg['created_on']) { $this->ts = false; ?> class="item-read"<?php } ?>>
    <td class="col-date"><?php echo date(self::DATE_FORMAT, $msg['created_on']) ?></td>
    <td class="col-boards"><?php echo str_replace(',', ', ', $msg['boards']) ?></td>
    <td class="col-msg"><?php echo $msg['content'] ?></td>
  </tr>
  <?php endforeach ?>
</tbody>
<tfoot>
  <tr>
    <td colspan="3" class="page-nav"><?php if ($this->previous_offset !== $this->offset): ?><a href="?action=staffmessages&amp;offset=<?php echo $this->previous_offset ?>">&laquo; Previous</a><?php endif ?><?php if ($this->next_offset): ?><a href="?action=staffmessages&amp;offset=<?php echo $this->next_offset ?>">Next &raquo;</a><?php endif ?></td>
  </tr>
</tfoot>
</table>
</div>
</body>
</html>

<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Vtuber Competition - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/banner_contest.css?25">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="//s.4cdn.org/js/banner_contest.min.js?4"></script>
  <script type="text/javascript">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create','UA-166538-1', {'sampleRate': 1});
ga('set', 'anonymizeIp', true);
ga('send','pageview')</script>
</head>
<body data-tkn="<?php echo $this->get_csrf_token() ?>">
<header><h1 id="title">4chan Vtuber Competition</h1></header>
<div id="content"><?php if (!$this->board): ?>
<div id="intro">
<p>
4chan is creating a Vtuber – a Virtual YouTuber – and we want you to submit the design! The winning design(s) will win a <a href="https://www.4chan.org/pass">4chan Pass</a> for life. Anyone can submit a design, but only 4chan Pass users can vote. Artists submitting designs can choose whether to be credited or to remain anonymous. One vote per Pass, so no ballot box stuffing!
</p>
<?php if (!empty($this->submit_boards)): ?><p>You can submit your design by clicking on the [Submit Design] link below.</p><?php endif ?>
</div>
<?php endif ?>
<?php if (!empty($this->submit_boards)): ?>
<div id="links-cnt">[<a id="form-link" data-cmd="toggle-form" href="#form">Submit Design</a>]</div>
<div id="form" class="hidden">
  <h2>Submit Design</h2>
  <form action="" method="POST" enctype="multipart/form-data">
    <label for="field-author">Name (optional)</label>
    <input type="text" maxlength="150" name="author" id="field-author">
    <label for="field-email">E-Mail (optional)</label>
    <input type="text" maxlength="150" name="email" id="field-email">
    <label for="field-file">File</label>
    <input required type="file" name="file" id="field-file">
    <div id="captcha-cnt"><label>Verification:</label>
    <?php echo captcha_form() ?>
    </div><input type="hidden" name="board" value="all">
    <button type="submit" name="action" value="submit">Submit</button>
  </form>
  <div id="form-footer">
  <p>Basic Rules:</p>
  <ol>
    <li>The design must be female.</li>
    <li>The design must be "safe for work"</li>
    <li>The design must prominently feature the 4chan clover logo.</li>
    <li>The design must be an entirely original work.</li>
    <li>You agree that 4chan may use the design for commercial purposes.</li>
    <li>Images submitted must be no larger than 2048&times;2048 pixels and 5MB, and must be in either JPG or PNG format.</li>
  </ol>
  <p>All entries are subject to the approval of 4chan Staff.</p>
  <p>We'll have a page that displays all the winning submissions, upon which the submitter can choose to receive credit by displaying a name, or remain anonymous. Submitting your email address is optional, but necessary in order for winners to claim their 4chan Pass. Email addresses will not be displayed publicly.</p>
  </div>
</div><?php endif ?>
<div class="board-menu">Contests in voting phase: <?php if (empty($this->vote_boards)): ?>None<?php else: ?><a href="/banner-contest/all">Virtual Youtuber</a><?php endif ?></div>
<?php if ($this->board): // listing mode ?>
<?php if (empty($this->items) || !$this->votable): ?>
<div class="no-results">Nothing found.</div>
<?php else: ?>
<table id="entries">
<thead>
  <tr>
    <th>Image</th>
    <?php if (!$this->votable): ?><th class="col-score">Score</th><?php endif ?>
    <?php if ($this->votable): ?><th class="col-meta"></th><?php endif ?>
  </tr>
</thead>
<tbody>
  <?php foreach ($this->items as $banner): ?>
    <tr>
      <td class="col-banner"><?php if ($banner['th_width'] > 0): ?>
      <a class="banner-url" href="<?php echo $this->get_image_url($banner) ?>"><img class="" width="<?php echo $banner['th_width'] ?>" height="<?php echo $banner['th_height'] ?>" alt="" src="<?php echo $this->get_image_url($banner, true) ?>"></a>
      <?php else: ?>
      <img class="" width="<?php echo $banner['width'] ?>" height="<?php echo $banner['height'] ?>" alt="" src="<?php echo $this->get_image_url($banner) ?>">
      <?php endif ?></td>
      <?php if (!$this->votable): ?><td class="col-score"><?php echo $banner['score2'] ?></td><?php endif ?>
      <?php if ($this->votable): ?><td class="col-meta"><button class="vote-btn" data-board="<?php echo $banner['board'] ?>" id="btn-<?php echo $banner['id'] ?>" data-cmd="pre-vote" type="button">Vote</button></td><?php endif ?>
    </tr>
  <?php endforeach ?>
</tbody>
<?php if (isset($this->offset)): ?>
<tfoot>
  <tr>
    <td colspan="3" class="page-nav"><?php if ($this->previous_offset !== $this->offset): ?><a href="/banner-contest/<?php echo $this->board ?><?php if ($this->previous_offset > 0): ?>/<?php echo $this->previous_offset ?><?php endif ?>">&laquo; Previous</a><?php endif ?><?php if ($this->next_offset): ?><a href="/banner-contest/<?php echo $this->board ?>/<?php echo $this->next_offset ?>">Next &raquo;</a><?php endif ?></td>
  </tr>
</tfoot>
<?php endif ?>
</table>
<?php endif ?>
<?php else: // Index mode ?>
<h3 class="event-hdr">Schedule</h3>
<table id="entries" class="event-tbl">
<thead>
  <tr>
    <th class="col-type">Type</th>
    <th class="col-date">Starts on</th>
    <th class="col-date">Ends on</th>
  </tr>
</thead>
<tbody>
  <?php foreach ($this->items as $event): ?>
    <tr>
      <td class="col-type"><?php echo $this->_event_types[$event['event_type']] ?></td>
      <td class="col-date"><?php echo $event['starts_on'] ? date(self::DATE_FORMAT_SHORT, $event['starts_on']) : '' ?></td>
      <td class="col-date"><?php echo $event['ends_on'] ? date(self::DATE_FORMAT_SHORT, $event['ends_on']) : '' ?></td>
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

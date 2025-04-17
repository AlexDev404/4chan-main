<?php if (!defined('IN_APP')) die(); ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Feedback - 4chan</title>
  <?php if (isset($this->redirect_to)): ?>
  <meta http-equiv="refresh" content="<?php echo($this->redirect_time); ?>;URL=<?php echo $this->redirect_to ?>">
  <?php endif ?>
  <link rel="stylesheet" type="text/css" href="https://team.4chan.org/css/feedback-review.css?3">
  <link rel="shortcut icon" href="https://team.4chan.org/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="https://team.4chan.org/js/helpers.js"></script>
  <script type="text/javascript" src="https://team.4chan.org/js/admincore.js"></script>
  <script type="text/javascript" src="https://team.4chan.org/js/feedback-admin.js"></script>
</head>
<body>
<header>
  <h1 id="title">4chan Feedback</h1>
</header>
<?php if ($this->mode === 'index' || $this->mode === 'read'): ?>
<div id="nav-links">
  <a href="?action=review">All</a>
  <span class="sep"></span>
  <?php foreach ($this->categories as $cat_id => $cat_name): ?>
  <a data-tip="<?php echo $this->categoriesLabels[$cat_id] ?>" <?php if ($this->current_cat === $cat_id) echo 'class="active-cat" ' ?>href="?action=review&amp;cat=<?php echo $cat_id ?>"><?php echo $cat_name ?> (<?php echo $this->cat_count[$cat_id] ?>)</a>
  <?php endforeach ?>
  <span class="sep"></span>
  <a href="?action=review&amp;answered=1">Answered</a>
  <a href="?action=review&amp;answered=2">Dismissed</a>
  <a href="?action=review&amp;answered=3">Drafts (<?php echo $this->draft_count ?>)</a>
</div>
<?php endif ?>
<section id="content">
<?php
/**
 * Index
 */
if ($this->mode === 'index'): ?>
<div id="links-cnt">
</div>
<table id="entries" class="items-table">
  <tbody>
    <tr>
      <th class="col-id">No.</th>
      <?php
      $answer_cols = true;
      $col = 5;
      $is_admin = false;
      
      if (!isset($_GET['answered'])) {
        $col -= 1;
        $answer_cols = false;
      }
      else if ($this->is_draft_mode && ($is_admin = has_level('admin'))) {
        $col += 1;
      }
      ?>
      <th>Question</th>
      <?php if ($answer_cols): ?>
      <th>Response</th>
      <th class="col-st">Answered By</th>
      <?php endif ?>
      <th class="col-date">Date</th>
      <?php if (!$answer_cols): ?>
      <th class="col-x">Dismiss</th>
      <?php elseif ($is_admin): ?>
      <th class="col-x">Action</th>
      <?php endif ?>
    </tr>
    <?php if (empty($this->suggestions)): ?>
    <tr>
      <td colspan="<?php echo $col ?>" id="col-empty"><?php echo $this->errstr['no_results'] ?></td>
    </tr>
    <?php else: ?>
    <?php foreach ($this->suggestions as $s): ?>
    <tr>
      <td class="col-id"><a href="?action=read&amp;id=<?php echo $s['id'] ?>"><?php echo $s['id'] ?></a></td>
      <td class="col-msg"><h3>[<?php echo $this->categories[$s['category']] ?>] <?php echo $s['subject'] ?></h3><?php echo $s['message'] ?></td>
      <?php if ($answer_cols): ?>
      <td class="col-rep"><?php if (isset($this->replies[$s['id']])) { echo $this->replies[$s['id']]['message']; } ?></td>
      <td class="col-st"><?php if ($s['updated_capcode'] !== 'none'): ?><span class="reply-capcode capcode-<?php echo $s['updated_capcode'] ?>"><?php echo $this->reply_labels[$s['updated_capcode']] ?></span> <img class="reply-icon" src="//s.4cdn.org/image/<?php echo $this->reply_icons[$s['updated_capcode']] ?>" alt="status"><?php endif ?></td>
      <?php endif ?>
      <td class="col-date" data-utc="<?php echo $s['updated_ts'] ?>"><?php echo $s['updated_date'] ?></td>
      <?php if (!$answer_cols && $this->can_dismiss): ?>
      <td class="col-x"><a href="#" class="button btn-other dismiss-link" data-id="<?php echo $s['id'] ?>">Dismiss</a></td>
      <?php elseif ($is_admin): ?>
      <td class="col-x"><a href="#" class="button btn-other approve-link" data-id="<?php echo $s['id'] ?>">Approve</a><br><br><a href="#" class="button btn-other dismiss-link" data-id="<?php echo $s['id'] ?>">Dismiss</a></td>
      <?php endif ?>
    </tr>
    <?php endforeach ?>
    <?php endif ?>
  </tbody><?php if ($this->has_next_page): $lastid = end($this->suggestions); $lastid = (int)$lastid['id']; ?>
  <tfoot>
    <tr>
      <td colspan="<?php echo $col ?>">
        <a href="?<?php if ($this->get_params) { echo $this->get_params . '&amp;'; } ?>offset=<?php echo $lastid ?>">Next</a>
      </td>
    </tr>
  </tfoot>
  <?php endif ?>
</table>
<?php
/**
 * Read
 */
elseif ($this->mode === 'read'):
if ($this->reply) {
  $msg = str_replace('<br>', "\n", $this->reply['message']);
  if ($this->capcodes[0] == 'admin') {
    $btn = 'Publish';
  }
  else {
    $btn = 'Update';
  }
  $hidden = '<input type="hidden" name="update" value="1">';
}
else {
  $msg = $hidden = '';
  $btn = 'Submit';
}
?>
<table class="items-table" id="entries">
  <tbody>
    <tr>
      <th>No.</th>
      <th>Question</th>
      <th class="col-date">Date / IP</th>
     </tr>
    </tr>
    <tr>
      <td class="col-id"><?php echo $this->suggestion['id'] ?></td>
      <td class="col-msg"><h3>[<?php echo $this->categories[$this->suggestion['category']] ?>] <?php echo $this->suggestion['subject'] ?></h3><?php echo $this->suggestion['message'] ?><div class="meta-blk"><div><b>UA:</b> <?php echo htmlspecialchars($this->suggestion['extra']['http_ua']) ?></div><div><b>Lang:</b> <?php echo htmlspecialchars($this->suggestion['extra']['http_lang']) ?></div></div></td>
      <td class="col-date"><?php echo $this->reply ? ($this->reply['updated_on'] ? $this->reply['updated_on'] : $this->reply['created_on']) : $this->suggestion['created_on'] ?>
        <div class="uid"><?php echo $this->suggestion['ip'] ?></div>
        <div class="uid"><a href="https://team.4chan.org/search#{&quot;ip&quot;:&quot;<?php echo $this->suggestion['ip'] ?>&quot;}" title="Multi search">Search (IP)</a></div>
        <?php if ($this->suggestion['extra']['4chan_pass']): ?><div class="uid"><a href="https://team.4chan.org/search#{&quot;password&quot;:&quot;<?php echo $this->suggestion['extra']['4chan_pass'] ?>&quot;}" title="Multi search">Search (pwd)</a></div><?php endif ?>
        <div class="uid"><a href="https://team.4chan.org/bans?action=search&amp;ip=<?php echo $this->suggestion['ip'] ?>" title="Active bans by IP">Bans (IP)</a></div>
        <?php if ($this->suggestion['extra']['4chan_pass']): ?>
          <div class="uid"><a href="https://team.4chan.org/bans?action=search&amp;password=<?php echo $this->suggestion['extra']['4chan_pass'] ?>" title="Active bans by password">Bans (pwd)</a></div>
        <?php endif ?>
        <div class="uid"><a href="https://team.4chan.org/manager/iprangebans?action=search&amp;mode=ip&amp;q=<?php echo $this->suggestion['ip'] ?>" title="Check for range ban">Range Bans</a></div>
        <div class="uid"><?php echo implode('<br>', $this->suggestion['geo']) ?></div>
      </td>
    </tr>
  </tbody>
</table>
<!--<div id="form">
  <h3>Reply</h3>
  <form action="" method="POST" enctype="multipart/form-data">
    <table>
      <tr>
        <th>Message</th>
      </tr>
      <tr>
        <td><textarea name="message" rows="8" cols="40" required><?php echo $msg ?></textarea></td>
      </tr>
      <tr>
        <th>Reply as</th>
      </tr>
      <tr>
        <td><select name="capcode" id="reply-as-capcode">
        <?php foreach ($this->capcodes as $capcode): ?>
        <option value="<?php echo $capcode ?>"<?php if ($this->suggestion['updated_capcode'] == $capcode) { echo(' selected="selected"'); } ?>><?php echo $this->reply_labels[$capcode] ?></option>
        <?php endforeach ?></td>
      </tr>
      <tfoot>
        <tr>
          <td colspan="2">
            <button class="button btn-other" type="submit" name="action" value="update"><?php echo $btn ?></button>
            <?php if ($this->reply): ?>
            <button type="submit" id="form-dismiss" name="dismiss" value="1">Dismiss</button>
            <?php endif ?>
          </td>
        <tr>
      </tfoot>
    </table>
    <?php echo csrf_tag() ?>
    <input type="hidden" name="action" value="reply"><?php echo $hidden ?>
    <input type="hidden" name="id" value="<?php echo $this->suggestion['id'] ?>">
  </form>
</div>-->
<?php
/**
 * Error
 */
elseif ($this->mode === 'error'): ?>
<div class="status-error"><?php echo $this->message ?></div>
<?php
/**
 * Success
 */
elseif ($this->mode === 'success'): ?>
<div class="status-success"><?php echo $this->message ?></div>
<?php endif ?>
</section>
<footer>
</footer>
</body>
</html>

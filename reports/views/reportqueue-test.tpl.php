<?php if (!defined('IN_APP')) die() ?><!DOCTYPE html>
<html data-css-nonce="<?php echo CSPHeaders::$css_nonce ?>">
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reports</title>
  <link rel="stylesheet" type="text/css" href="/css/reportqueue-test.css?214">
  <link rel="shortcut icon" href="/image/favicon-team.ico" type="image/x-icon">
  <script type="text/javascript" src="/js/helpers.js?22"></script>
  <script type="text/javascript" src="/js/admincore.js?35"></script>
  <script type="text/javascript" src="/js/031d60ebf8d41a9f/reportqueue-test.js?283"></script>
  <?php if ($this->isMod): ?><script type="text/javascript" src="/js/d8d9b0cdc33f3418/reportqueue-mod-test.js?100"></script><? endif ?>
</head>
<body <?php echo csrf_attr() ?>>
<header>
  <form autocomplete="off" action="#" id="filter-form"><input name="q" type="text" id="search-box" placeholder="Filter"><?php if ($this->isMod): ?><button id="rep-cat-btn" type="button">&#9662;</button><?php endif ?><button id="search-btn" type="submit">⌕</button><button id="reset-btn" class="hidden" type="reset">&times;</button><span data-tip-delay="500" data-tip="Filter by content (comment, nametrip, subject)<?php if (!$this->isMod): ?> or thread (ID or post URL)<?php else: ?>, thread (ID or post URL) or reporter IP<?php endif ?>" class="search-help-tt">?</span><?php if ($this->isMod): ?><div id="js-rep-cats-dl" class="hidden"></div><?php endif ?></form>
  <h1 id="title">Reports</h1><div id="cfg-btn" tabindex="-1"><span>&hellip;</span><div><label><input data-cmd="toggle-dt" id="cfg-cb-dt" type="checkbox" autocomplete="off">Dark Theme</label></div></div>
</header>
<div id="menu">
  <span id="refresh-btn" data-cmd="refresh" class="button button-light left">Refresh</span>
  <span id="settings-btn" data-tip="Settings" data-cmd="show-settings" class="button button-light right"><span class="icon icon-cog"></span></span>
  <?php if ($this->isMod): ?><a class="button button-light right" href="https://team.4chan.org">Team</a> <span data-tip="Ban Requests" data-cmd="show-ban-requests" class="button button-light right">BR</span><?php endif ?>
  <span id="cleared-btn" data-tip="Show/Hide Cleared Reports" data-cmd="toggle-cleared" class="button button-light right">CLR</span>
  <?php if ($this->isMod): ?><span id="extrafetch-btn" data-tip="Show/Hide Ignored Reports" data-cmd="toggle-ignored" class="button button-light right">IGN</span><?php endif ?>
  <ul id="board-menu" class="desktop">
    <li data-cmd="switch-board">All</li>
    <?php if (!isset($this->access['board'])): ?><li data-cmd="switch-board" class="txt-xs" data-slug="_ws_" data-tip="Worksafe Boards Only">WS</li><?php endif ?>
    <?php foreach($this->boardlist as $board): ?>
    <li data-cmd="switch-board" class="board-slug"><?php echo $board; ?></li>
    <?php endforeach ?>
    <li id="more-slugs"><span data-cmd="reset-menu">&hellip;</span></li>
  </ul>
</div>
<div id="content">
<div id="items"></div>
</div>
<div data-cmd="shift-panel" id="panel-stack" tabindex="-1"></div>
</body>
</html>

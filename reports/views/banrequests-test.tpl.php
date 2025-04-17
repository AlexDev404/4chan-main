<?php if (!defined('IN_APP')) die() ?><!DOCTYPE html>
<html data-css-nonce="<?php echo CSPHeaders::$css_nonce ?>">
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Ban Requests</title>
  <link rel="stylesheet" type="text/css" href="/css/reportqueue-test.css?216">
  <link rel="shortcut icon" href="/image/favicon-team.ico" type="image/x-icon">
  <script type="text/javascript" src="/js/helpers.js?22"></script>
  <script type="text/javascript" src="/js/admincore.js?36"></script>
  <script type="text/javascript" src="/js/d8d9b0cdc33f3418/banrequests-test.js?154"></script>
</head>
<body class="page-ban-requests" <?php echo csrf_attr() ?>>
<header>
  <form class="desktop" autocomplete="off" action="#" id="filter-form"><input name="q" type="text" id="search-box" placeholder="Filter"><button id="search-btn" type="submit">⌕</button><button id="reset-btn" class="hidden" type="reset">&times;</button></form>
  <h1 id="title">Reports</h1><div id="cfg-btn"><span>&hellip;</span><div><label><input data-cmd="toggle-dt" id="cfg-cb-dt" type="checkbox" autocomplete="off">Dark Theme</label></div></div>
</header>
<div id="menu">
  <span id="refresh-btn" data-cmd="refresh" class="button button-light left">Refresh</span>
  <span id="settings-btn" data-tip="Settings" data-cmd="show-settings" class="button button-light right"><span class="icon icon-cog"></span></span>
  <a class="button button-light right" href="https://team.4chan.org">Team</a>
  <span data-cmd="show-report-queue" data-tip="Report Queue" class="button button-light right">RQ</span>
  <ul id="board-menu" class="desktop">
    <li data-cmd="switch-board">All</li>
    <?php foreach($this->boardlist as $board): ?>
    <li data-cmd="switch-board" class="board-slug"><?php echo $board; ?></li>
    <?php endforeach ?>
    <li id="more-slugs"><span data-cmd="reset-menu">&hellip;</span></li>
  </ul>
</div>
<div id="content">
<div id="items"></div>
</div>
<div data-cmd="shift-panel" id="panel-stack"></div>
</body>
</html>

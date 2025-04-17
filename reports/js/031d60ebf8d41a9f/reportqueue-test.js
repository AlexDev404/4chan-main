var RQ = {
  HIGHLIGHT_THRES: 500,
  MODE_REPORTS: 0,
  MODE_CONTEXT: 1,
  OLD_THRES: 259200, // 3 days
  
  syncDelay: 15000,
  syncInterval: null,
  syncEnabled: false
};

RQ.init = function() {
  this.settings = this.getSettings();
  
  this.applyCustomCSS();
  
  this.board = null;
  
  this.mode = this.MODE_REPORTS;
  
  this.cleared_only = false;
  this.extraFetch = null;
  this.archivedOnly = false;
  this.showOld = false;
  
  this.activeFilter = null;
  
  this.isMobileLayout = false;
  
  this.xhr = {};
  
  this.threadCache = {};
  this.templateCache = null;
  
  this.quoteTimeout = null;
  this.quoteDetachTimeout = null;
  this.activeQuoteLink = null;
  this.activeQuoteLeaf = null;
  
  this.protocol = this.settings.useSSL ? 'https://' : 'http://';
  this.thumbServer = 'i.4cdn.org';
  this.imageServer = 'i.4cdn.org';
  this.imageServer2 = 'is2.4chan.org';
  this.flashServer = 'i.4cdn.org';
  
  this.fileDeleted = 's.4cdn.org/image/filedeleted-res.gif';
  
  this.quickBanMode = 'banreq';
  this.quickBanHeader = 'Quick Ban Request';
  
  this.focusedReport = null;
  this.focusedPost = null;
  
  this.clickCommands = {
    'toggle-cleared': RQ.onToggleClearedClick,
    'toggle-archived': RQ.onToggleArchivedClick,
    'switch-board': RQ.onBoardLinkClick,
    'toggle-checked': RQ.onCheckboxClick,
    'refresh': RQ.refreshReports,
    'show-old': RQ.showOldReports,
    'show-settings': RQ.showSettings,
    'save-settings': RQ.onSaveSettingsClick,
    'reset-menu': RQ.resetCustomMenu,
    'delete': RQ.onDeleteClick,
    'delete-file': RQ.onDeleteFileClick,
    'clear': RQ.onClearClick,
    'ban-request': RQ.onBanRequestClick,
    'show-context': RQ.onShowContextClick,
    'focus': RQ.onFocusClick,
    'shift-panel': RQ.shiftPanel,
    'toggle-dt': RQ.onToggleDTClick,
    'pick-rid': RQ.onPickReport,
    'hl-tid': RQ.onHlTidClick,
    'show-q-ban': RQ.onShowQuickBanClick,
    'quick-ban': RQ.onQuickBanClick
  };
  
  this.delayedJob = null;
  this.panelStack = null;
  
  this.currentCount = 0;
  
  this.searchTimeout = null;
  this.qBanCntTimeout = null;
  
  Keybinds.init(this);
  
  if (this.settings.imageHover) {
    ImageHover.init();
  }
  
  //LazyLoader.init();
  
  this.resolveQuery();
  
  Tip.init();
  
  $.on(document, 'click', RQ.onClick);
  $.on(document, 'mouseover', RQ.onMouseOver);
  $.on(document, 'mouseout', RQ.onMouseOut);
  $.on(document, 'DOMContentLoaded', RQ.run);
  $.on(window, 'beforeunload', RQ.onBeforeUnload);
  $.on(window, 'message', RQ.onMessage);
  $.on(window, 'hashchange', RQ.onHashChange);
  $.on(document, $.visibilitychange, RQ.onVisibilityChange);
};

RQ.run = function() {
  $.off(document, 'DOMContentLoaded', RQ.run);
  
  RQ.isMobileLayout = (window.matchMedia
    && window.matchMedia('(max-device-width: 640px)').matches);
  
  RQ.initGlobalMessage();
  
  RQ.initMobileBoardSelector();
  
  if (localStorage.getItem('dark-theme')) {
    $.addClass($.docEl, 'dark-theme');
    $.id('cfg-cb-dt').checked = true;
  }
  
  $.on($.id('filter-form'), 'submit', RQ.onFilterSubmit);
  $.on($.id('filter-form'), 'reset', RQ.onFilterReset);
  $.on($.id('search-box'), 'focus', RQ.onSearchFocus);
  $.on($.id('search-box'), 'keydown', RQ.onSearchKeyDown);
  
  $.on($.id('cfg-btn'), 'focus', RQ.onCfgBtnFocusChange);
  $.on($.id('cfg-btn'), 'blur', RQ.onCfgBtnFocusChange);
  
  RQ.runExtra && RQ.runExtra();
  
  if (RQ.settings.enableKeybinds) {
    if (RQ.settings.keyRemap) {
      Keybinds.remap(RQ.settings.keyRemap);
    }
    Keybinds.enable();
  }
  
  RQ.panelStack = $.id('panel-stack');
  
  RQ.applyCustomMenu(RQ.settings.customMenu);
  
  if (RQ.settings.hideThumbnails) {
    $.addClass(document.body, 'no-thumbnails');
  }
  
  if (RQ.cleared_only) {
    $.addClass($.id('cleared-btn'), 'active');
  }
  
  if (RQ.extraFetch) {
    $.addClass($.id('extrafetch-btn'), 'active');
  }
  
  RQ.showReports(RQ.board, RQ.cleared_only, RQ.extraFetch);
};

RQ.applyCustomCSS = function(css) {
  var nonce, el;
  
  if (el = $.id('js-custom-css')) {
    el.parentNode.removeChild(el);
  }
  
  if (css === undefined) {
    css = RQ.settings.customCSS;
  }
  
  if (!css) {
    return;
  }
  
  if (!(nonce = $.docEl.getAttribute('data-css-nonce'))) {
    return;
  }
  
  el = $.el('style');
  el.id = 'js-custom-css';
  el.setAttribute('nonce', nonce);
  el.textContent = css;
  
  document.head.appendChild(el);
};

RQ.initMobileBoardSelector = function() {
  var ul, boards, el, sel, node, i, wrap, idx;
  
  if (!RQ.isMobileLayout) {
    return;
  }
  
  if (RQ.settings.noMobileBoardMenu) {
    $.removeClass($.id('board-menu'), 'desktop');
    return;
  }
  
  ul = $.id('board-menu');
  boards = $.cls('board-slug', ul);
  
  sel = $.el('select');
  sel.id = 'board-menu-mobile';
  
  idx = 0;
  
  el = $.el('option');
  el.value = el.textContent = 'All';
  sel.appendChild(el);
  
  for (i = 0; node = boards[i]; ++i) {
    el = $.el('option');
    el.value = el.textContent = node.textContent;
    
    if (RQ.board === el.value) {
      idx = i + 1;
    }
    
    sel.appendChild(el);
  }
  
  wrap = $.el('div');
  wrap.id = 'board-sel-wrap';
  wrap.textContent = 'Board: ';
  wrap.appendChild(sel);
  
  ul.parentNode.insertBefore(wrap, ul);
  
  sel.selectedIndex = idx;
  
  $.on(sel, 'change', RQ.onMobileBoardSelectorChanged);
};

RQ.onMobileBoardSelectorChanged = function() {
  RQ.onBoardLinkClick($.id('board-menu-mobile').selectedOptions[0]);
};

RQ.onCfgBtnFocusChange = function() {
  clearTimeout(this._delay);
  this._delay = setTimeout(function(el) { el.classList.toggle('js-evt-toggle') }, 250, this);
};

// ---

RQ.addCommands = function(cmds) {
  var key;
  
  for (key in cmds) {
    RQ.clickCommands[key] = cmds[key];
  }
};

RQ.parseResponse = function(data) {
  try {
    return JSON.parse(data);
  }
  catch (e) {
    return {
      status: 'error',
      message: 'Something went wrong (' + e.toString() + ')'
    };
  }
};

RQ.resolveQuery = function() {
  var hash, board;
  
  RQ.board = null;
  RQ.cleared_only = false;
  RQ.extraFetch = null;
  RQ.activeFilter = null;
  
  if (location.hash) {
    hash = location.hash.split('/');
    
    board = hash[1].split('-');
    
    RQ.board = board[0] !== '' ? board[0] : null;
    
    if (board[1]) {
      if (board[1] === 'cleared') {
        RQ.cleared_only = true;
      }
      else {
        RQ.extraFetch = board[1];
      }
    }
    
    if (hash[2]) {
      RQ.activeFilter = RQ.parseSearchQuery(decodeURIComponent(hash[2]));
    }
  }
  
  if (!RQ.activeFilter) {
    RQ.clearSearch(true);
  }
};

RQ.getItemNode = function(board, pid) {
  if (RQ.mode == RQ.MODE_CONTEXT) {
    return $.id('context-' + board + '-' + pid);
  }
  else {
    return $.id('report-' + board + '-' + pid);
  }
};

RQ.getItemUID = function(el) {
  var uid;
  
  if ((uid = el.id.split('-')).length == 3) {
    return { board: uid[1], pid: uid[2], tid: el.getAttribute('data-tid') };
  }
  
  return null;
};

RQ.getPostUID = function(el) {
  let board = el.getAttribute('data-board');
  let pid = el.getAttribute('data-pid');
  return [board, pid];
};

RQ.isPostArchived = function(board, pid) {
  var el = RQ.getItemNode(board, pid);
  return el && el.hasAttribute('data-archived');
};

RQ.initGlobalMessage = function() {
  if (localStorage.getItem('rq-gmsg')) {
    return;
  }
  
  RQ.showMessage('If you are deleting or ban requesting threads,'
    + ' you <strong>must</strong> be present in the <del>IRC</del> Discord channel!',
    'error',
    false,
    function() {
      localStorage.setItem('rq-gmsg', Date.now());
      RQ.hideMessage();
    }
  );
};

RQ.showFileTip = function(t) {
  return (RQ.settings.hideThumbnails ? '<div class="thumbs-off-msg">Thumbnails Disabled</div>' : '')
    + $.escapeHTML(t.getAttribute('data-meta')
    + ', ' + $.prettyBytes(t.getAttribute('data-fsize')));
};

RQ.updateMenuCounts = function(data) {
  var c, nodes = $.cls('board-slug');
  
  for (const el of nodes) {
    if (c = data[el.textContent]) {
      if ($.hasClass(el, 'disabled')) {
        $.removeClass(el, 'disabled');
      }
      
      el.setAttribute('data-tip', c);
    }
    else if (!$.hasClass(el, 'disabled')) {
      $.addClass(el, 'disabled');
      el.removeAttribute('data-tip');
    }
  }
};

/**
 * Quote preview
 */
RQ.clearQuotePreviews = function() {
  clearTimeout(RQ.quoteTimeout);
  clearTimeout(RQ.quoteDetachTimeout);
  RQ.activeQuoteLeaf = null;
  RQ.triggerQuoteDetach();
};

RQ.onQuoteLinkMouseOver = function(el) {
  clearTimeout(RQ.quoteTimeout);
  RQ.triggerQuoteDetach();
  
  if (el.classList.contains('deleted')) {
    return;
  }
  
  RQ.quoteTimeout = setTimeout(RQ.triggerQuotePreview, 300, el);
};

RQ.onQuoteLinkMouseOut = function() {
  clearTimeout(RQ.quoteTimeout);
  RQ.activeQuoteLink = null;
  RQ.quoteDetachTimeout = setTimeout(RQ.triggerQuoteDetach, 150);
};

RQ.onQuoteLeafMouseOver = function() {
  RQ.activeQuoteLeaf = this;
};

RQ.onQuoteLeafMouseOut = function() {
  RQ.activeQuoteLeaf = null;
  RQ.quoteDetachTimeout = setTimeout(RQ.triggerQuoteDetach, 150);
};

RQ.triggerQuoteDetach = function() {
  var el = RQ.activeQuoteLeaf;
  
  if (!el) {
    if (el = $.id('js-quote-tree')) {
      el.textContent = '';
    }
    
    return;
  }
  
  while (el.nextElementSibling) {
    el.parentNode.removeChild(el.nextElementSibling);
  }
};

RQ.triggerQuotePreview = function(el) {
  var uid, board, tid, pid, cnt;
  
  [ , board, tid, pid ] = el.getAttribute('href')
    .match(/(?:\/([a-z0-9]+)\/thread\/)?([0-9]+)?#p([0-9]+)$/);
  
  if (!board) {
    cnt = el.parentNode.parentNode;
    
    if (cnt.classList.contains('report') || cnt.classList.contains('context-post')) {
      uid = RQ.getItemUID(cnt);
      board = uid.board;
      tid = uid.tid || uid.pid;
    }
    else {
      board = cnt.getAttribute('data-board');
      tid = cnt.getAttribute('data-tid');
    }
  }
  
  RQ.activeQuoteLink = el;
  
  if (RQ.threadCache[board + tid]) {
    RQ.showQuotePreview(board, tid, pid, el);
  }
  else {
    RQ.fetchQuotePreview(board, tid, pid, el);
  }
};

RQ.fetchQuotePreview = function(board, tid, pid, el) {
  if (RQ.threadCache[board + tid] === true) {
    return;
  }
  
  RQ.threadCache[board + tid] = true;
  
  $.xhr('GET', '//api.4chan.org/' + board + '/thread/' + tid + '.json', {
    onload: RQ.onQuotePreviewLoaded,
    onerror: RQ.onQuotePreviewError,
    board: board,
    tid: tid,
    pid: pid,
    el: el
  });
};

RQ.disableQuoteLink = function(el) {
  el.classList.add('deleted');
};

RQ.onQuotePreviewLoaded = function() {
  var resp, el;
  
  el = this.el;
  
  this.el = null;
  
  if (this.status == 404) {
    RQ.disableQuoteLink(el);
    RQ.threadCache[this.board + this.tid] = false;
    return;
  }
  
  resp = RQ.parseResponse(this.responseText);
  
  RQ.threadCache[this.board + this.tid] = resp.posts;
  
  RQ.showQuotePreview(this.board, this.tid, this.pid, el);
};

RQ.showQuotePreview = function(board, tid, pid, tgt) {
  var cnt, el, thread, p, post;
  
  if (RQ.activeQuoteLink !== tgt) {
    return;
  }
  
  thread = RQ.threadCache[board + tid];
  
  if (!thread) {
    return;
  }
  
  for (p of thread) {
    if (p.no == pid) {
      post = p;
      break;
    }
  }
  
  if (!post) {
    RQ.disableQuoteLink(tgt);
    return;
  }
  
  cnt = $.id('js-quote-tree');
  
  if (!cnt) {
    cnt = $.el('div');
    cnt.id = 'js-quote-tree';
    document.body.appendChild(cnt);
  }
  
  el = $.el('div');
  el.className = 'quote-preview';
  el.setAttribute('data-board', board);
  el.setAttribute('data-tid', tid);
  el.innerHTML = RQ.buildQuotePreviewHTML(board, post);
  
  $.on(el, 'mouseover', RQ.onQuoteLeafMouseOver);
  $.on(el, 'mouseout', RQ.onQuoteLeafMouseOut);
  
  cnt.appendChild(el);
  
  RQ.alignQuotePreview(el, tgt);
};

RQ.buildQuotePreviewHTML = function(board, post) {
  var html = `<div><span class="post-name">${post.name}</span></div>`;
  
  if (post.sub !== undefined) {
    html += `<div class="post-subject">${post.sub}</div>`;
  }
  
  html += '<div class="post-content">';
  
  if (post.ext) {
    if (post.filedeleted) {
      html += '<div><img class="post-thumb-deleted" src="//'
        + this.fileDeleted + '" alt="File deleted"></div>';
    }
    else if (board === 'f') {
      html += '<a target="_blank" href="'
        + this.linkToFlash(post.filename) + '">'
        + '<div class="post-swf" title="' + post.filename + '.swf">' + post.filename + '</div></a>';
    }
    else {
      html += '<a class="post-thumb-link" target="_blank" href="'
        + this.linkToImage(board, post.tim, post.ext, post.no) + '">'
        + '<img data-tip data-tip-cb="RQ.showFileTip" data-meta="'
            + post.filename + post.ext + "\n" + post.w + '&times;' + post.h
            + '" data-fsize="' + post.fsize + '" class="post-thumb'
            + (post.spoiler ? ' thumb-spoiler' : '') + '" src="'
          + this.linkToThumb(board, post.tim)
          + '" data-width="' + post.w + '" alt="">'
        + '</a>';
    }
  }
  
  if (post.com !== undefined) {
    html += post.com + '</div>';
  }
  else {
    html += '</div>';
  }
  
  return html;
};

RQ.alignQuotePreview = function(el, tgt) {
  var elAABB, tgtAABB, pad, left, top;
  
  pad = 4;
  
  elAABB = el.getBoundingClientRect();
  tgtAABB = tgt.getBoundingClientRect();
  
  left = tgtAABB.right + pad;
  top = tgtAABB.top + window.pageYOffset;
  
  if (left + elAABB.width + pad > $.docEl.clientWidth) {
    left = tgtAABB.left - pad - elAABB.width;
    
    if (left < 0) {
      left = pad;
      top += tgtAABB.height + pad;
    }
  }
  
  el.style.left = left + 'px';
  el.style.top = top + 'px';
};

RQ.onQuotePreviewError = function() {
  var el = this.el;
  
  this.el = null;
  
  RQ.disableQuoteLink(el);
  RQ.threadCache[this.board + this.tid] = false;
};

// ---

RQ.onPickReport = function(btn) {
  var nodes, rid, rids;
  
  btn.classList.toggle('cb-a');
  btn.classList.toggle('rid-picked');
  
  nodes = $.cls('rid-picked');
  
  rids = [];
  
  for (let el of nodes) {
    if (rid = el.parentNode.parentNode.getAttribute('data-rid')) {
      rids.push(rid);
    }
  }
  
  $.id('search-box').value = 'rid:' + rids.join(',');
};

RQ.onToggleDTClick = function() {
  var el = $.id('cfg-cb-dt');
  
  if (el.checked !== $.hasClass($.docEl, 'dark-theme')) {
    if (el.checked) {
      $.addClass($.docEl, 'dark-theme');
      localStorage.setItem('dark-theme', '1');
    }
    else {
      $.removeClass($.docEl, 'dark-theme');
      localStorage.removeItem('dark-theme');
    }
  }
};

/**
 * Sync
 */
RQ.triggerSync = function() {
  var board;
  
  if (RQ.xhr.sync) {
    RQ.xhr.sync.abort();
  }
  
  if (RQ.board) {
    board = '&board=' + RQ.board;
  }
  else {
    board = '';
  }
  
  RQ.xhr.sync = $.xhr('GET', '?action=get_report_ids' + board, {
    onload: RQ.onSyncLoaded,
    onerror: RQ.onSyncError
  });
};

RQ.enableSync = function() {
  if (RQ.syncEnabled) {
    return;
  }
  RQ.syncInterval = setInterval(RQ.triggerSync, RQ.syncDelay);
  RQ.syncEnabled = true;
};

RQ.disableSync = function() {
  if (!RQ.syncEnabled) {
    return;
  }
  if (RQ.xhr.sync) {
    RQ.xhr.sync.abort();
    RQ.xhr.sync = null;
  }
  clearInterval(RQ.syncInterval);
  RQ.syncInterval = null;
  RQ.syncEnabled = false;
};

RQ.onSyncLoaded = function() {
  var i, report, resp, reports, nodes, hash;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status !== 'success') {
    console.log("Couldn't sync reports");
    return;
  }
  
  reports = resp.data.reports;
  
  RQ.updateMenuCounts(resp.data.counts);
  
  hash = {};
  
  for (i = 0; report = reports[i]; ++i) {
    hash['report-' + report[0] + '-' + report[1]] = true;
  }
  
  nodes = $.cls('report');
  
  for (i = 0; report = nodes[i]; ++i) {
    if (!hash[report.id]
      && !$.hasClass(report, 'disabled')
      && !$.hasClass(report, 'processing')
      && !$.hasClass(report, 'report-cat-prio')
    ) {
      $.addClass(report, 'disabled');
      RQ.currentCount--;
    }
  }
  
  RQ.setPageTitle(RQ.board, RQ.currentCount);
  
  if (RQ.currentCount === 0) {
    RQ.disableSync();
  }
  
  RQ.xhr.sync = null;
};

RQ.onSyncError = function() {
  RQ.xhr.sync = null;
  console.log("Couldn't sync reports (HTTP " + this.status + ')');
};

/**
 * Event handlers
 */
RQ.onFilterSubmit = function(e) {
  e.preventDefault();
  e.stopPropagation();
  
  RQ.applySearch($.id('search-box').value);
};

RQ.onFilterReset = function() {
  RQ.clearSearch();
};

RQ.onSearchKeyDown = function(e) {
  var el;
  
  if (e.keyCode == 27) {
    if ((el = $.id('search-box')) && el.value === '') {
      el.blur();
    }
    
    RQ.clearSearch();
    
    return;
  }
};

RQ.processMessage = function(data) {
  if (!data) {
    return {};
  }
  
  data = data.split('-');
  
  return {
    cmd: data[0],
    type: data[1],
    id: data.slice(2).join('-')
  };
};

RQ.onMessage = function(e) {
  var msg;
  
  if (e.origin !== 'https://sys.4chan.org') {
    return;
  }
  
  msg = RQ.processMessage(e.data);
  
  if (msg.type !== 'ban') {
    return;
  }
  
  if (msg.cmd === 'done') {
    if (RQ.closeBan) {
      RQ.onBanDone(msg.id);
    }
    else {
      RQ.onBanRequestDone(msg.id);
    }
  }
  else if (msg.cmd === 'start') {
    if (RQ.onBanStart) {
      RQ.onBanStart(msg.id);
    }
    else {
      RQ.onBanRequestStart(msg.id);
    }
  }
  else if (msg.cmd === 'cancel') {
    if (RQ.closeBan) {
      RQ.closeBan(msg.id);
    }
    else {
      RQ.closeBanRequest(msg.id);
    }
  }
  else if (msg.cmd === 'error') {
    RQ.notify("This post doesn't exist anymore");
    
    if (RQ.closeBan) {
      RQ.onBanDone(msg.id);
    }
    else {
      RQ.onBanRequestDone(msg.id);
    }
  }
};

RQ.onVisibilityChange = function() {
  if (document[$.hidden]) {
    RQ.disableSync();
  }
  else {
    RQ.enableSync();
  }
};

RQ.onBeforeUnload = function() {
  if (RQ.delayedJob) {
    RQ.runDelayedJob();
  }
};

RQ.onClick = function(e) {
  var t, cmd;
  
  if (e.which != 1 || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) {
    return;
  }
  
  if ((t = e.target) == document) {
    return;
  }
  
  if ((cmd = t.getAttribute('data-cmd')) && (cmd = RQ.clickCommands[cmd])) {
    e.stopPropagation();
    cmd(t, e);
  }
  
  if (t.classList.contains('quotelink')) {
    e.stopPropagation();
    e.preventDefault();
  }
};

RQ.onMouseOver = function(e) {
  let cmd = e.target.getAttribute('data-cmd');
  
  if (e.target.classList.contains('quotelink')) {
    RQ.onQuoteLinkMouseOver(e.target);
  }
};

RQ.onMouseOut = function(e) {
  let cmd = e.target.getAttribute('data-cmd');
  
  if (e.target.classList.contains('quotelink')) {
    RQ.onQuoteLinkMouseOut();
  }
};

RQ.onToggleClearedClick = function(button) {
  RQ.extraFetch = null;
  $.id('extrafetch-btn') && $.removeClass($.id('extrafetch-btn'), 'active');
  
  if ($.hasClass(button, 'active')) {
    if (RQ.board) {
      location.hash = '/' + RQ.board;
    }
    else {
      location.hash = '';
    }
    RQ.cleared_only = false;
    RQ.showReports(RQ.board, false);
    $.removeClass(button, 'active');
  }
  else {
    location.hash = '/' + (RQ.board || '') + '-cleared';
    RQ.cleared_only = true;
    RQ.showReports(RQ.board, true);
    $.addClass(button, 'active');
  }
};

RQ.onToggleArchivedClick = function(button) {
  if ($.hasClass(button, 'active')) {
    RQ.showReports(RQ.board, RQ.cleared_only, RQ.extraFetch);
  }
  else {
    RQ.archivedOnly = true;
    $.addClass(button, 'active');
    RQ.filterArchivedOnly();
  }
};

RQ.filterArchivedOnly = function() {
  var i, cnt, el, nodes;
  
  nodes = $.cls('report');
  
  cnt = $.id('items');
  cnt.style.display = 'none';
  
  for (i = nodes.length - 1; el = nodes[i]; i--) {
    if (!el.hasAttribute('data-archived')) {
      cnt.removeChild(el);
    }
  }
  
  if (!cnt.children.length) {
    cnt.innerHTML = '<div class="load-empty">Nothing found</div>';
  }
  
  cnt.style.display = '';

  //LazyLoader.load();
};

RQ.onBoardLinkClick = function(button) {
  var hash, board;
  
  if (button.hasAttribute('data-slug')) {
    board = button.getAttribute('data-slug');
  }
  else {
    board = button.textContent;
  }
  
  if (board == 'All') {
    RQ.board = null;
    hash = '/';
  }
  else {
    RQ.board = board;
    hash = '/' + board;
  }
  
  if (RQ.cleared_only) {
    hash += '-cleared';
  }
  
  if (RQ.extraFetch) {
    hash += '-' + RQ.extraFetch;
  }
  
  RQ.applyCustomMenu(RQ.settings.customMenu);
  
  location.hash = hash;
};

RQ.onHashChange = function() {
  RQ.resolveQuery();
  RQ.showReports(RQ.board, RQ.cleared_only, RQ.extraFetch);
};

RQ.setContextMode = function() {
  RQ.mode = RQ.MODE_CONTEXT;
};

RQ.setReportsMode = function() {
  RQ.focusedPost = null;
  RQ.mode = RQ.MODE_REPORTS;
};

RQ.clearChildReports = function(board, tid) {
  var i, nodes, el;
  
  nodes = $.cls('report');
  
  for (i = 0; el = nodes[i]; ++i) {
    if (el.getAttribute('data-tid') == tid
      && el.getAttribute('data-board') == board
      && !$.hasClass(el, 'processing')
      && !$.hasClass(el, 'disabled')) {
      $.addClass(el, 'disabled');
      RQ.currentCount--;
    }
  }
  
  RQ.setPageTitle(RQ.board, RQ.currentCount);
};

// ---

RQ.onCheckboxClick = function(button) {
  var klass = 'checked', check = '✔';
  
  if ($.hasClass(button, klass)) {
    $.removeClass(button, klass);
    button.textContent = '';
  }
  else {
    $.addClass(button, klass);
    button.textContent = check;
  }
};

RQ.refreshReports = function() {
  if ($.id('image-hover')) {
    ImageHover.hide();
  }
  else if ($.id('swf-preview')) {
    ImageHover.hideSWF();
  }
  
  if (RQ.delayedJob) {
    RQ.runDelayedJob();
  }
  
  RQ.threadCache = {};
  RQ.templateCache = null;
  
  RQ.clearQuotePreviews();
  RQ.clearQuickBanDataNode();
  RQ.clearExtraToolsNode && RQ.clearExtraToolsNode();
  
  RQ.applyCustomMenu(RQ.settings.customMenu);
  RQ.focusedReport = null;
  RQ.showReports(RQ.board, RQ.cleared_only, RQ.extraFetch);
};

RQ.setPageTitle = function(board, count) {
  var title;
  
  if (!board) {
    title = 'All';
  }
  else if (board[0] === '_') {
    title = board.replace(/_/g, '').toUpperCase();
  }
  else {
    title = '/' + board + '/';
  }
  
  if (count < 0) {
    RQ.currentCount = count = 0;
  }
  
  document.title = 'Reports (' + count + ') - ' + title;
  $.id('title').textContent = 'Reports - ' + title;
};

RQ.toggleThumbnails = function() {
  if (RQ.settings.hideThumbnails) {
    RQ.settings.hideThumbnails = false;
    $.removeClass(document.body, 'no-thumbnails');
  }
  else {
    RQ.settings.hideThumbnails = true;
    $.addClass(document.body, 'no-thumbnails');
  }
  
  $.setItem('rq-settings', JSON.stringify(RQ.settings));
};

RQ.linkToImage = function(board, file, ext) {
  return '//' + this.imageServer + '/' + board + '/' + file + ext;
};

RQ.linkToFlash = function(fileName) {
  return this.protocol + this.flashServer + '/f/' + fileName + '.swf';
};

RQ.linkToThumb = function(board, file) {
  return '//' + this.thumbServer + '/' + board + '/' + file + 's.jpg';
};

RQ.linkToPost = function(board, pid, tid) {
  return this.protocol + 'boards.' + $L.d(board) + '/' + board + '/thread/'
    + (+tid !== 0 ? (tid + '#p' + pid) : pid);
};

/**
 * Search
 */
RQ.onSearchFocus = function() {
  Tip && Tip.hide();
};

RQ.focusSearch = function() {
  var el;
  
  if (el = $.id('search-box')) {
    el.focus();
  }
};

RQ.parseSearchQuery = function(query) {
  var type, m, pid, tid, board;
  
  if (m = query.match(/^(cnt|pid|tid|rid|ip|cat):(.*)/)) {
    type = m[1];
    query = m[2];
  }
  else if (m = query.match(/^https?:\/\/boards.(?:4chan|4channel).org\/([a-z0-9]+)\/thread\/([0-9]+)(?:[^#]*#p([0-9]+)$)?/i)) {
    board = m[1];
    tid = m[2];
    //pid = m[3];
    
    //if (pid && pid != tid) {
    //  type = 'pid';
    //}
    //else {
      type = 'tid';
      pid = tid;
    //}
    
    query = '/' + board + '/' + pid;
  }
  else if (RQ.showRepDetails && /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(query.trim())) {
    type = 'ip';
    query = query.trim();
  }
  else {
    type = 'cnt';
  }
  
  return [ type, query ];
};

RQ.applySearch = function(str) {
  var type, query;
  
  if (str === undefined || str === '') {
    RQ.clearSearch(!RQ.activeFilter);
    return;
  }
  
  [ type, query ] = RQ.parseSearchQuery(str);
  
  RQ.execSearch(type, query);
};

RQ.execSearch = function(type, query) {
  var esc, cnt, nodes, el, i, r, linkRgx, pid, board, rids;
  
  if (query === '' || query === undefined) {
    RQ.clearSearch();
    return;
  }
  
  RQ.activeFilter = [ type, query ];
  
  RQ.updateSearchProps(type, query);
  
  cnt = $.id('items');
  
  RQ.showMessage('Processing&hellip;', 'notify', false);
  
  $.addClass(cnt, 'hidden');
  
  if (type === 'tid' || type === 'pid') {
    nodes = $.cls('post-link-btn', cnt);
    
    [ , board, pid ] = query.split('/');
    
    if (type === 'tid') {
      linkRgx = new RegExp('/' + board + '/thread/' + pid + '(?:#|$)');
    }
    else {
      linkRgx = new RegExp('/' + board + '/thread/(' + pid + '$|[^#]*#p' + pid + '$)');
    }
    
    for (i = 0; el = nodes[i]; ++i) {
      el.parentNode.parentNode.classList.remove('hidden-i');
      
      if (!linkRgx.test(el.href)) {
        el.parentNode.parentNode.classList.add('hidden-i');
      }
    }
  }
  else if (type === 'rid') {
    nodes = $.cls('report', cnt);
    
    rids = query.split(/[, ]+/);
    
    for (el of nodes) {
      el.classList.remove('hidden-i');
      
      if (rids.indexOf(el.getAttribute('data-rid')) === -1) {
        el.classList.add('hidden-i');
      }
      else {
        el = el.getElementsByClassName('cb')[0];
        el.classList.add('cb-a');
        el.classList.add('rid-picked');
      }
    }
    
    query = rids.join(',');
  }
  else if (type === 'ip') {
    RQ.showRepDetails(query);
  }
  else if (type === 'cat') {
    nodes = $.cls('report', cnt);
    
    rids = query.split(/[, ]+/);
    
    let q = new RegExp('\\b' + query + '\\b');
    
    for (el of nodes) {
      el.classList.remove('hidden-i');
      
      if (!q.test(el.getAttribute('data-cats'))) {
        el.classList.add('hidden-i');
      }
    }
  }
  else {
    esc = ['/', '.', '*', '+', '?', '(', ')', '[', ']', '{', '}', '\\' ].join('|\\');
    esc = new RegExp('(\\' + esc + ')', 'g');
    
    r = query.replace(esc, '\\$1');
    r = new RegExp(r, 'i');
    
    nodes = $.cls('post-content');
    
    for (i = 0; el = nodes[i]; ++i) {
      el.parentNode.classList.remove('hidden-i');
      
      if (!r.test(el.textContent)) {
        el.parentNode.classList.add('hidden-i');
      }
    }
    
    nodes = $.cls('post-subject');
    
    for (i = 0; el = nodes[i]; ++i) {
      if (r.test(el.textContent)) {
        el.parentNode.parentNode.classList.remove('hidden-i');
      }
    }
    
    nodes = $.cls('post-author');
    
    for (i = 0; el = nodes[i]; ++i) {
      if (r.test(el.textContent)) {
        $.removeClass(el.parentNode.parentNode, 'hidden-i');
      }
    }
  }
  
  $.removeClass(cnt, 'hidden');
  
  //LazyLoader.load();
  
  RQ.hideMessage();
};

RQ.updateSearchProps = function(type, query) {
  var hash, path, input, search;
  
  input = $.id('search-box');
  
  if (!input) {
    return;
  }
  
  hash = location.hash.split('/');
  
  path = hash[1];
  
  if (path === undefined) {
    path = '';
  }
  
  if (!type) {
    if (hash[2] !== undefined && hash[2] !== '') {
      history.pushState(null, '', '#/' + path);
    }
    
    $.addClass($.id('reset-btn'), 'hidden');
    
    input.value = '';
  }
  else {
    search = type + ':' + query;
    
    if (hash[2] === undefined || hash[2] !== search) {
      history.pushState(null, '', '#/' + path + '/' + type + ':' + encodeURIComponent(query));
    }
    
    if (input.value !== search) {
      input.value = search;
    }
    
    $.removeClass($.id('reset-btn'), 'hidden');
  }
  
};

RQ.clearSearch = function(propsOnly) {
  var cnt, nodes, el;
  
  RQ.activeFilter = null;
  
  RQ.updateSearchProps();
  
  if (propsOnly) {
    return;
  }
  
  RQ.showMessage('Processing&hellip;', 'notify', false);
  
  cnt = $.id('items');
  
  $.addClass(cnt, 'hidden');
  
  nodes = $.cls('report');
  
  for (el of nodes) {
    el.classList.remove('hidden-i');
  }
  
  nodes = $.qsa('.rid-picked');
  
  for (el of nodes) {
    el.classList.remove('cb-a');
    el.classList.remove('rid-picked');
  }
  
  $.removeClass(cnt, 'hidden');
  
  //LazyLoader.load();
  
  RQ.hideMessage();
};

/**
 * UI Panels
 */
RQ.createPanel = function(id, html, attributes, onCreate) {
  var attr, panel;
  
  panel = document.createElement('div');
  panel.id = id + '-panel';
  
  for (attr in attributes) {
    panel.setAttribute(attr, attributes[attr]);
  }
  
  $.addClass(panel, 'panel');
  
  if (html) {
    panel.innerHTML = html;
  }
  
  if (onCreate) {
    onCreate.call(panel);
  }
  
  return panel;
};

RQ.showPanel = function(id, html, title, attributes, onCreate) {
  var previous, panel, content;
  
  ImageHover.hide();
  
  if (previous = RQ.panelStack.lastElementChild) {
    previous.style.display = 'none';
  }
  
  if (!$.hasClass(RQ.panelStack, 'backdrop')) {
    $.addClass(RQ.panelStack, 'backdrop');
    $.on($.docEl, 'wheel', RQ.onBackdropWheel);
  }
  
  if (title) {
    html = '<div class="panel-header">'
      + '<span data-cmd="shift-panel" class="button clickbox">&times;</span>'
      + '<h3>' + title + '</h3>'
    + '</div>' + (html || '');
  }
  
  panel = RQ.createPanel(id, html, attributes, onCreate);
  RQ.panelStack.appendChild(panel);
  
  if (content = $.cls('panel-content', panel)[0]) {
    content.focus();
    content.style.maxHeight =
      ($.docEl.clientHeight - content.getBoundingClientRect().top * 2) + 'px';
  }
};

RQ.onBackdropWheel = function(e) {
  var el;
  
  if (e.target === RQ.panelStack) {
    e.preventDefault();
    return;
  }
  
  el = $.cls('panel-content', RQ.panelStack);
  
  if (!el[0]) {
    return;
  }
  
  el = el[el.length - 1];
  
  if (e.deltaY < 0 && el.scrollTop <= 0) {
    e.preventDefault();
    return;
  }
  
  if (e.deltaY > 0 && el.scrollTop >= el.scrollTopMax) {
    e.preventDefault();
    return;
  }
};

RQ.isPanelStackEmpty = function() {
  var i, panel, nodes;
  
  nodes = RQ.panelStack.children;
  
  for (i = 0; panel = nodes[i]; ++i) {
    if (!panel.hasAttribute('data-processing')) {
      return false;
    }
  }
  
  return true;
};

RQ.getPreviousPanel = function() {
  var i, panel, nodes;
  
  nodes = RQ.panelStack.children;
  
  for (i = nodes.length - 1; panel = nodes[i]; i--) {
    if (panel.style.display == 'none' && !panel.hasAttribute('data-processing')) {
      return panel;
    }
  }
  
  return null;
};

RQ.closePanel = function(id) {
  var previous, panel;
  
  if (panel = RQ.getPanel(id)) {
    if (!panel.hasAttribute('data-processing')) {
      if (previous = RQ.getPreviousPanel()) {
        previous.style.display = 'block';
      }
      else {
        $.removeClass(RQ.panelStack, 'backdrop');
        $.off($.docEl, 'wheel', RQ.onBackdropWheel);
      }
    }
    
    RQ.panelStack.removeChild(panel);
  }
};

RQ.hidePanel = function(id) {
  var previous, panel;
  
  if (panel = RQ.getPanel(id)) {
    if (previous = RQ.getPreviousPanel()) {
      previous.style.display = 'block';
    }
    else {
      $.removeClass(RQ.panelStack, 'backdrop');
      $.off($.docEl, 'wheel', RQ.onBackdropWheel);
    }
    
    panel.style.display = 'none';
    panel.setAttribute('data-processing', '1');
  }
};

RQ.shiftPanel = function() {
  var cb, panel = RQ.panelStack.lastElementChild;
  
  if (!panel) {
    return;
  }
  
  if (cb = panel.getAttribute('data-close-cb')) {
    RQ['close' + cb]();
  }
  else {
    RQ.closePanel(RQ.getPanelId(panel));
  }
};

RQ.getPanelId = function(el) {
  return el.id.split('-').slice(0, -1).join('-');
};

RQ.getPanel = function(id) {
  return $.id(id + '-panel');
};

RQ.showPanelError = function(id, msg) {
  var panel, cnt;
  
  panel = $.id(id + '-panel');
  
  if (!panel) {
    return;
  }
  
  cnt = $.cls('panel-content', panel)[0];
  
  if (!cnt) {
    return;
  }
  
  cnt.innerHTML = '<div class="panel-error">' + msg + '</div>';
};

/**
 * Notifications
 */
RQ.messageTimeout = null;

RQ.showMessage = function(msg, type, timeout, onClick) {
  var el;
  
  RQ.hideMessage();
  
  el = document.createElement('div');
  el.id = 'feedback';
  el.title = 'Dismiss';
  el.innerHTML = '<span class="feedback-' + type + '">' + msg + '</span>';
  
  $.on(el, 'click', onClick || RQ.hideMessage);
  
  document.body.appendChild(el);
  
  if (timeout) {
    RQ.messageTimeout = setTimeout(RQ.hideMessage, timeout);
  }
};

RQ.hideMessage = function() {
  var el = $.id('feedback');
  
  if (el) {
    if (RQ.messageTimeout) {
      clearTimeout(RQ.messageTimeout);
      RQ.messageTimeout = null;
    }
    
    $.off(el, 'click', RQ.hideMessage);
    
    document.body.removeChild(el);
  }
};

RQ.error = function(msg) {
  RQ.showMessage(msg || 'Something went wrong', 'error', 5000);
};

RQ.notify = function(msg) {
  RQ.showMessage(msg, 'notify', 3000);
};

RQ.showLoadError = function(msg) {
  $.id('items').innerHTML = '<div class="load-error">' + msg + '</div>';
};

RQ.showLoadEmpty = function() {
  $.id('items').innerHTML = '<div class="load-empty">Nothing found</div>';
};

RQ.showLoadSpinner = function() {
  $.id('items').innerHTML = '<div id="load-spinner" class="load-loading">Loading&hellip;</div>';
};

/**
 * Settings
 */
RQ.settingsList = {
  useSSL: [ 'Use HTTPS when linking to posts', true ],
  imageHover: [ 'Expand thumbnails on hover', true ],
  enableKeybinds: [ 'Enable keyboard shortcuts', false ],
  hideThumbnails: [ 'Hide thumbnails', false ],
  hideOldReports: [ 'Hide cleared reports older than 3 days', true ],
  noMobileBoardMenu: [ 'Don\'t use the mobile navigation menu', false ]
};

RQ.getSettings = function() {
  var key, settings, keyRemap;
  
  if (settings = $.getItem('rq-settings')) {
    settings = JSON.parse(settings);
    
  }
  else {
    settings = {};
  }
  
  for (key in RQ.settingsList) {
    if (settings[key] === undefined) {
      settings[key] = RQ.settingsList[key][1];
    }
  }
  
  if (keyRemap = $.getItem('rq-keyremap')) {
    settings.keyRemap = JSON.parse(keyRemap);
  }
  
  return settings;
};

RQ.saveSettings = function(settings, keyMap) {
  var i, json, clear;
  
  clear = true;
  
  for (i in settings) {
    json = JSON.stringify(settings);
    $.setItem('rq-settings', json);
    clear = false;
    break;
  }
  
  if (clear) {
    $.removeItem('rq-settings');
  }
  
  if (keyMap) {
    json = JSON.stringify(keyMap);
    $.setItem('rq-keyremap', json);
  }
  else {
    $.removeItem('rq-keyremap');
  }
};

RQ.onSaveSettingsClick = function() {
  var i, el, settings, panel, opts, menu, needRefresh, nodes, keyRemap, keyMap,
    fromCode, toCode, css;
  
  settings = {};
  
  panel = RQ.getPanel('settings');
  
  opts = $.cls('option-item', panel);
  
  for (i = 0; el = opts[i]; ++i) {
    settings[el.getAttribute('data-key')] = $.hasClass(el, 'checked');
  }
  
  if (menu = $.id('custom-menu-field').value) {
    settings.customMenu = menu;
  }
  
  if (css = $.id('custom-css-field').value.trim()) {
    settings.customCSS = css;
  }
  
  Keybinds.disable();
  
  nodes = $.tag('kbd', panel);
  keyRemap = [];
  keyMap = [];
  
  for (i = 0; el = nodes[i]; ++i) {
    if (toCode = +el.getAttribute('data-remap')) {
      fromCode = +el.getAttribute('data-id');
      
      if (fromCode != toCode) {
        keyMap.push([ fromCode, toCode ]);
      }
      
      keyRemap.push([ fromCode, toCode ]);
    }
    
  }
  
  if (keyRemap.length > 0) {
    if (!Keybinds.remap(keyRemap, true)) {
      return;
    }
  }
  
  if (keyMap.length === 0) {
    keyMap = null;
  }
  
  if (settings.enableKeybinds) {
    Keybinds.enable();
  }
  
  if (settings.imageHover) {
    if (!ImageHover.enabled) {
      ImageHover.init();
    }
  }
  else {
    ImageHover.disable();
  }
  
  if (settings.useSSL != RQ.settings.useSSL) {
    RQ.protocol = settings.useSSL ? 'https://' : 'http://';
    needRefresh = true;
  }
  
  if (settings.customMenu != RQ.settings.customMenu) {
    RQ.resetCustomMenu();
    RQ.applyCustomMenu(settings.customMenu);
  }
  
  if (settings.customCSS != RQ.settings.customCSS) {
    RQ.applyCustomCSS(css);
  }
  
  if (settings.hideThumbnails != RQ.settings.hideThumbnails) {
    $[settings.hideThumbnails ? 'addClass' : 'removeClass'](
      document.body, 'no-thumbnails'
    );
  }
  
  RQ.settings = settings;
  
  RQ.saveSettings(settings, keyMap);
  
  if (needRefresh) {
    RQ.refreshReports();
  }
  
  RQ.closeSettings();
};

RQ.resetCustomMenu = function() {
  var i, el, nav, boards, more;
  
  nav = $.id('board-menu');
  nav.style.display = 'none';
  
  boards = $.cls('board-slug', nav);
  
  for (i = 0; el = boards[i]; ++i) {
    el.style.display = null;
  }
  
  more = $.id('more-slugs');
  more.style.display = 'none';
  
  nav.style.display = 'block';
};

RQ.applyCustomMenu = function(str) {
  var i, slug, el, nav, boards, more, nodes, hash;
  
  if (RQ.isMobileLayout && !RQ.settings.noMobileBoardMenu) {
    return;
  }
  
  if (!str) {
    return;
  }
  
  boards = str.split(/[, ]+/);
  
  hash = {};
  
  for (i = 0; slug = boards[i]; ++i) {
    hash[slug] = true;
  }
  
  nav = $.id('board-menu');
  nav.style.display = 'none';
  
  nodes = $.cls('board-slug', nav);
  
  for (i = 0; el = nodes[i]; ++i) {
    if (!hash[el.textContent]) {
      el.style.display = 'none';
    }
  }
  
  more = $.id('more-slugs');
  more.style.display = 'inline';
  
  nav.style.display = 'block';
};

RQ.showSettings = function() {
  var label, key, html;
  
  if (RQ.getPanel('settings')) {
    RQ.closeSettings();
    return;
  }
  
  html = '<div class="panel-content"><h4>Options</h4>'
    + '<ul class="options-set">';
  
  for (key in RQ.settingsList) {
    html += '<li><span data-cmd="toggle-checked"'
      + ' data-key="' + key + '"'
      + ' class="option-item button clickbox'
      + (RQ.settings[key] ? ' checked">✔' : '">')
      + '</span> ' + RQ.settingsList[key][0] + '</li>';
  }
  
  html += '</ul><h4>Keyboard Shortcuts (click key icon to change)</h4><ul class="options-set">';
  
  for (key in Keybinds.labels) {
    label = Keybinds.labels[key];
    html += '<li><kbd data-cmd="prompt-key" data-id="'
      + key + '"' + (label[2] ? ('data-remap="' + label[2] + '"') : '') + '>'
      + label[0] + '</kbd> '
      + label[1] + '</li>';
  }
  
  html += '</ul><h4>Custom Board List</h4><ul class="options-set">'
    + '<li><input id="custom-menu-field" class="option-field" value="'
    + (RQ.settings.customMenu ? RQ.settings.customMenu : '')
    + '" placeholder="example: a,jp,tg" type="text"></li>'
    + '</ul>';
  
  html += '</ul><h4>Custom CSS</h4><textarea id="custom-css-field" class="options-set">'
    + (RQ.settings.customCSS ? $.escapeHTML(RQ.settings.customCSS) : '')
    + '</textarea><ul><li class="center"><a href="/login?action=do_logout" class="button btn-logout">Logout</a></li></ul>';
  
  html += '</div><div class="panel-footer">'
      + '<span data-cmd="save-settings" class="button">Save</span>'
    + '</div>';
  
  RQ.showPanel('settings', html, 'Settings');
};

RQ.closeSettings = function() {
  RQ.closePanel('settings');
};

RQ.confirmPubBan = function(item) {
  if (!item) {
    return false;
  }
  
  if (!item.hasAttribute('data-banned')) {
    return true;
  }
  
  return confirm('This user was publicly banned.\nAre you sure you want to proceed?'); 
};

/**
 * Ban request
 */
RQ.onBanRequestClick = function(button) {
  var item, uid;
  
  item = button.parentNode.parentNode;
  uid = RQ.getItemUID(item);
  
  if (!uid) {
    return;
  }
  
  if (!RQ.confirmPubBan(item)) {
    return;
  }
  
  RQ.requestBan(uid.board, uid.pid);
};

RQ.banRequestFocused = function() {
  var uid, focused;
  
  focused = RQ.getFocusedItem();
  
  if (!focused) {
    return;
  }

  if (!RQ.confirmPubBan(focused)) {
    return;
  }
  
  uid = RQ.getItemUID(focused);
  
  RQ.requestBan(uid.board, uid.pid);
};

RQ.requestBan = function(board, pid) {
  var title, html, panelId, attrs;
  
  title = 'Ban request No.' + pid + ' on /' + board + '/';
  
  panelId = board + '-' + pid;
  
  html = '<div class="panel-content" tabindex="-1">'
      + '<iframe src="https://sys.4chan.org/'
      + board + '/admin?mode=admin&admin=banreq&id=' + pid
    + '&noheader=true" frameborder="0"></iframe>'
    + '</div>';
  
  attrs = { 'class': 'banrequest-panel' };
  
  if (RQ.mode == RQ.MODE_CONTEXT) {
    attrs['data-is-context'] = 1;
  }
  
  RQ.showPanel('banrequest-' + panelId, html, title, attrs, RQ.onCreateIframe);
};

RQ.onCreateIframe = function() {
  var el = $.tag('iframe', this)[0];
  el.style.width = '399px';
  el.addEventListener('load', RQ.onIframeLoaded, false);
};

RQ.onIframeLoaded = function() {
  this.style.width = '400px';
};

RQ.onBanRequestDone = function(id) {
  var isContext, panel = RQ.getPanel('banrequest-' + id);
  
  if (!panel) {
    return;
  }
  
  isContext = panel.hasAttribute('data-is-context');
  
  RQ.closeBanRequest(id);
  RQ.setDisabled(id, isContext);
};

RQ.onBanRequestStart = function(id) {
  var uid, focused, panel = RQ.getPanel('banrequest-' + id);
  
  RQ.setProcessing(id, panel.hasAttribute('data-is-context'));
  
  focused = RQ.getFocusedItem();
  
  if (focused) {
    uid = RQ.getItemUID(focused);
    
    if (id == (uid.board + '-' + uid.pid)) {
      RQ.focusNext();
    }
  }
  
  RQ.panelStack.focus();
  
  RQ.hideBanRequest(id);
};

RQ.hideBanRequest = function(id) {
  RQ.hidePanel('banrequest-' + id);
};

RQ.closeBanRequest = function(id) {
  RQ.closePanel('banrequest-' + id);
};

/**
 * Focusing
 */
RQ.onFocusClick = function(el) {
  RQ.focusItem(el.parentNode.parentNode);
};

RQ.focusNext = function() {
  if (RQ.mode == RQ.MODE_REPORTS) {
    RQ.focusNextReport();
  }
  else {
    RQ.focusNextPost();
  }
};

RQ.focusPrevious = function() {
  if (RQ.mode == RQ.MODE_REPORTS) {
    RQ.focusPreviousReport();
  }
  else {
    RQ.focusPreviousPost();
  }
};

RQ.focusNextReport = function() {
  var el, node;
  
  node = null;
  
  if (el = RQ.focusedReport) {
    while (el = el.nextElementSibling) {
      if ($.hasClass(el, 'hidden') || $.hasClass(el, 'hidden-i')) {
        continue;
      }
      
      if ($.hasClass(el, 'disabled') || $.hasClass(el, 'processing')) {
        continue;
      }
      
      node = el;
      
      break;
    }
  }
  
  RQ.focusItem(node || $.id('items').firstElementChild);
};

RQ.focusPreviousReport = function() {
  var el, node;
  
  node = null;
  
  if (el = RQ.focusedReport) {
    while (el = el.previousElementSibling) {
      if (!$.hasClass(el, 'disabled') && !$.hasClass(el, 'processing')) {
        node = el;
        break;
      }
    }
    
    RQ.focusItem(node);
  }
};

RQ.focusNextPost = function() {
  var el;
  
  if (RQ.focusedPost && (el = RQ.focusedPost.nextElementSibling)) {
    RQ.focusItem(el);
  }
  else {
    RQ.focusItem($.id('context-preview').firstElementChild);
  }
};

RQ.focusPreviousPost = function() {
  var el;
  
  if (RQ.focusedPost && (el = RQ.focusedPost.previousElementSibling)) {
    RQ.focusItem(el);
  }
};

RQ.focusItem = function(el) {
  var cnt, rect, focusMargin, focused;
  
  focusMargin = 10;
  
  ImageHover.hide();
  
  focused = RQ.getFocusedItem();
  
  if (focused) {
    $.removeClass(focused, 'focused');
  }
  
  if (!el) {
    RQ.setFocusedItem(null);
    return;
  }
  
  $.addClass(el, 'focused');
  
  rect = el.getBoundingClientRect();
  
  if (RQ.mode == RQ.MODE_CONTEXT) {
    cnt = $.id('context-preview');
    if (el.offsetTop < cnt.scrollTop || rect.bottom > cnt.clientHeight) {
      cnt.scrollTop = el.offsetTop - focusMargin;
    }
  }
  else {
    if (rect.top < 0 || rect.bottom > $.docEl.clientHeight) {
      window.scrollBy(0, rect.top - focusMargin);
    }
  }
  
  RQ.setFocusedItem(el);
};

RQ.setFocusedItem = function(el) {
  if (RQ.mode == RQ.MODE_CONTEXT) {
    RQ.focusedPost = el;
  }
  else {
    RQ.focusedReport = el;
  }
};

RQ.getFocusedItem = function() {
  if (RQ.mode == RQ.MODE_CONTEXT) {
    return RQ.focusedPost;
  }
  else {
    return RQ.focusedReport;
  }
};

/**
 * Reports
 */
RQ.getReportControls = function() {
  return `<span data-cmd="ban-request" title="Ban Request" class="button btn-ls">BR</span>
<span data-cmd="show-q-ban" class="button btn-rs btn-hmg"><span>&hellip;</span></span>`;
};

RQ.contextControls = '<span data-cmd="ban-request" title="Ban request" \
class="button right" data-is-context="1">Ban Request</span>';

RQ.reportControlsArc = RQ.contextControlsArc = '';

RQ.setDisabled = function(uid, isContext) {
  var el, type;
  
  type = isContext ? 'context-' : 'report-';
  el = $.id(type + uid);
  
  if (!el) {
    return;
  }
  
  $.removeClass(el, 'processing');
  
  if ($.hasClass(el, 'disabled')) {
    return;
  }
  
  if (!isContext) {
    RQ.currentCount--;
    RQ.setPageTitle(RQ.board, RQ.currentCount);
  }
  
  $.addClass(el, 'disabled');
};

RQ.setProcessing = function(uid, isContext) {
  var el, type;
  
  type = isContext ? 'context-' : 'report-';
  
  if (el = $.id(type + uid)) {
    $.addClass(el, 'processing');
  }
};

RQ.unsetProcessing = function(uid, isContext) {
  var el, type;
  
  type = isContext ? 'context-' : 'report-';
  
  if (el = $.id(type + uid)) {
    $.removeClass(el, 'processing');
  }
};

RQ.buildReports = function(data) {
  var i, report, post, html, tripcode, thumb, hasFile, sub,
    hideOld, oldThres, oldCount, reports;
  
  reports = data.reports;
  
  RQ.updateMenuCounts(data.counts);
  
  RQ.buildReportsExtra && RQ.buildReportsExtra(data);
  
  if (!reports[0]) {
    document.dispatchEvent(new CustomEvent('4chanReportsReady'));
    return RQ.showLoadEmpty();
  }
  
  hideOld = this.cleared_only && !this.showOld && this.settings.hideOldReports;
  
  if (hideOld) {
    oldThres = 0 | (Date.now() / 1000 - RQ.OLD_THRES);
    oldCount = 0;
  }
  
  html = '';
  
  for (i = 0; report = reports[i]; ++i) {
    if (hideOld && report.ts < oldThres) {
      oldCount += 1;
      continue;
    }
    
    // FIXME
    try {
      post = JSON.parse(report.post);
    }
    catch(e) {
      console.log(e);
      post = {};
    }
    
    hasFile = false;
    
    if (post.trip) {
      tripcode = '<span class="post-trip">' + post.trip + '</span>';
    }
    else {
      tripcode = '';
    }
    
    if (post.ext) {
      if (post.filedeleted) {
        thumb = '<div><img class="post-thumb-deleted" src="//'
          + this.fileDeleted + '" alt="File deleted"></div>';
      }
      else if (report.board === 'f') {
        hasFile = true;
        thumb = '<a target="_blank" href="'
          + this.linkToFlash(post.filename) + '">'
          + '<div class="post-swf" title="' + post.filename + '.swf">' + post.filename + '</div></a>';
      }
      else {
        hasFile = true;
        thumb = '<a class="post-thumb-link" target="_blank" href="'
          + this.linkToImage(report.board, post.tim, post.ext, post.no) + '">'
          + '<img data-tip data-tip-cb="RQ.showFileTip" data-meta="'
              + post.filename + post.ext + "\n" + post.w + '&times;' + post.h
              + '" data-fsize="' + post.fsize + '" class="post-thumb' + (post.spoiler ? ' thumb-spoiler' : '') + '" src="'
            + this.linkToThumb(report.board, post.tim)
            + '" loading="lazy" data-width="' + post.w + '" alt="">'
          + '</a>';
      }
    }
    else {
      thumb = '';
    }
    
    sub = '<span data-tip="Highlight Same Thread" class="cb cb-hl-tid" data-cmd="hl-tid"></span>';
    
    if (post.sub) {
      sub = '<div title="' + post.sub
        + '" class="post-subject">' + sub + post.sub + '</div>';
    }
    else if (post.rel_sub) {
      sub = '<div title="' + post.rel_sub
        + '" class="post-subject post-rel-sub">' + sub + post.rel_sub + '</div>';
    }
    else {
      sub = '<div class="post-subject">' + sub + '</div>';
    }
    
    html += '<article data-rid="' + report.id + '" id="report-' + report.board + '-' + report.no
      + '"'
      + (report.cats ? (' data-cats="' + report.cats + '"') : '')
      + (post.resto === 0 ? '' : (' data-tid="' + post.resto + '"'))
      + ' data-board="' + report.board + '"'
      + ' data-pid="' + report.no + '"'
      + (post.archived ? ' data-archived="1"' : '')
      + (report.uid ? (' data-uid="' + report.uid + '"') : '')
      + ' data-tuid="' + report.board + (post.resto ? post.resto : post.no) + '"'
      + (post.com && post.com.indexOf('red;">(USER') !== -1 ? ' data-banned' : '')
      + ' class="report'
        + ((report.weight >= RQ.HIGHLIGHT_THRES || report.hl) ? ' report-cat-prio' : '')
      + '"><div class="post-meta">'
      + '<span data-tip="Select Report" class="cb cb-w" data-cmd="pick-rid"></span>'
      + (report.weight !== undefined ? ('<span class="report-count" data-cmd="focus" data-tip="'
        + report.count + ' report' + $.pluralise(report.count)
        + (report.cleared_by ? ('\nCleared by ' + report.cleared_by) : '')
        + '">'
        + (report.weight)) : '')
      + '</span>'
      + '<span class="report-board">/' + report.board + '/</span>'
      + '<div class="post-author">'
        + (report.uid ? '<span data-tip="Highlight Same Poster" class="cb cb-hl-uid" data-cmd="hl-uid"></span>' : '')
        +'<span class="post-name">' + (post.name || '') + '</span>' + tripcode
      + '</div>'
        + sub
      + '</div>'
      + '<div class="post-content">' + thumb + (post.com || '') + '</div>'
      + '<div class="report-controls">'
      + '<span data-cmd="delete" class="button' + (hasFile ? ' btn-ls' : '') + '">Delete</span>';
    
    if (hasFile) {
      html += '<span data-cmd="delete-file" data-tip="Delete File" class="button btn-rs">File</span>';
    }
    
    html += '<span data-cmd="clear" class="button flx-pr">Clear</span>'
      + (post.archived ? RQ.reportControlsArc : RQ.getReportControls(report.board, report.no))
      + '<a data-cmd="show-context" href="'
        + RQ.linkToPost(report.board, report.no, post.resto)
        + '" target="_blank" class="button post-link-btn">View '
        + (post.resto === 0 ? ' (OP)' : '')
      + '</a>'
      + '</div></article>';
  }
  
  $.id('items').innerHTML = html;
  
  RQ.updateOldCount(oldCount);
  
  document.dispatchEvent(new CustomEvent('4chanReportsReady'));
};

RQ.removeShowOldBtn = function() {
  var el = $.id('show-old-btn');
  
  if (el) {
    el.parentNode.removeChild(el);
  }
};

RQ.updateOldCount = function(count) {
  var el, ref;
  
  RQ.removeShowOldBtn();
  
  if (!count) {
    return;
  }
  
  ref = $.id('cleared-btn');
  
  el = $.el('span');
  el.className = 'button button-light right';
  el.id = 'show-old-btn';
  el.setAttribute('data-cmd', 'show-old');
  el.textContent = 'Hidden: ' + count;
  el.setAttribute('data-tip', 'Old reports hidden. Click to show.');
  
  ref.parentNode.insertBefore(el, ref);
};

RQ.showOldReports = function() {
  RQ.showOld = true;
  RQ.removeShowOldBtn();
  RQ.showReports(RQ.board, RQ.cleared_only, RQ.extraFetch);
};

RQ.showReports = function(board, cleared_only, extra) {
  var query;
  
  if (RQ.xhr.get) {
    RQ.xhr.get.abort();
    RQ.xhr.get = null;
  }
  
  if (RQ.archivedOnly) {
    RQ.archivedOnly = false;
    $.removeClass($.id('archived-btn'), 'active');
  }
  
  if (extra) {
    extra = '&' + extra;
  }
  else {
    extra = '';
  }
  
  query = '?action=get_reports'
    + (cleared_only ? '&cleared_only' : '') + extra;
  
  if (board) {
    query += '&board=' + board;
  }
  
  RQ.showLoadSpinner();
  
  RQ.disableSync();
  RQ.enableSync();
  
  RQ.xhr.get = $.xhr('GET', query, {
    onload: RQ.onReportsLoaded,
    onerror: RQ.onXhrError,
    //onprogress: RQ.onReportsProgress,
    board: board
  });
};

RQ.onReportsProgress = function(e) {
  if (e.lengthComputable) {
    $.id('load-spinner').textContent = 'Loading '
      + Math.round((e.loaded / e.total) * 100) + '%';
  }
};

RQ.onReportsLoaded = function() {
  var resp;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    RQ.currentCount = resp.data.reports.length;
    RQ.setPageTitle(this.board, RQ.currentCount);
    if (resp.data.templates) {
      RQ.templateCache = resp.data.templates;
    }
    RQ.buildReports(resp.data);
    
    if (RQ.activeFilter) {
      RQ.execSearch(RQ.activeFilter[0], RQ.activeFilter[1]);
    }
  }
  else {
    $.id('items').textContent = '';
    
    if (resp.status === 'error') {
      RQ.showLoadError(resp.message);
    }
  }
  
  RQ.showOld = false;
  
  RQ.xhr.get = null;
};

RQ.onXhrError = function() {
  RQ.error('Something went wrong');
};

/**
 * Delayed jobs
 */
RQ.delayedJobTimeout = 3000;

RQ.addDelayedJob = function() {
  var args;
  
  if (RQ.delayedJob) {
    RQ.runDelayedJob();
  }
  
  args = Array.prototype.slice.call(arguments);
  
  RQ.delayedJob = {
    fn: args.shift(),
    args: args,
    timeOut: setTimeout(RQ.runDelayedJob, RQ.delayedJobTimeout)
  };
};

RQ.runDelayedJob = function() {
  var job = RQ.delayedJob;
  
  clearTimeout(job.timeOut);
  
  job.fn.apply(this, job.args);
  
  RQ.delayedJob = null;
};

RQ.cancelDelayedJob = function() {
  var args, item;
  
  if (!RQ.delayedJob) {
    return;
  }
  
  args = RQ.delayedJob.args;
  
  clearTimeout(RQ.delayedJob.timeOut);
  
  item = RQ.getItemNode(args[0], args[1]);
  
  $.removeClass(item, 'processing');
  
  if (RQ.getFocusedItem()) {
    RQ.focusItem(item);
  }
  
  RQ.delayedJob = null;
};

/**
 * Context preview
 */
RQ.showContext = function(board, tid, pid) {
  var query, title, html, thread;
  
  RQ.closeContext();
  
  RQ.setContextMode();
  
  title = 'Thread No.' + tid + ' on /' + board + '/';
  
  html = '<div class="panel-content" tabindex="-1" id="context-preview">'
      + '<div class="spinner"></div>'
    + '</div>';
  
  RQ.showPanel('context', html, title, { 'data-close-cb': 'Context' });
  
  if ((thread = RQ.threadCache[board + tid]) && thread !== true) {
    RQ.buildContext(thread, board, pid);
    return;
  }
  
  query = '//api.4chan.org/' + board + '/thread/' + tid + '.json';
  
  RQ.xhr.get = $.xhr('GET', query, {
    onload: RQ.onContextLoaded,
    onerror: RQ.onContextError,
    board: board,
    tid: tid,
    pid: pid
  });
};

RQ.closeContext = function() {
  if (RQ.xhr.get) {
    RQ.xhr.get.abort();
    RQ.xhr.get = null;
  }
  
  RQ.closePanel('context');
  
  RQ.setReportsMode();
};

RQ.previewFocused = function() {
  var uid;
  
  if (RQ.mode == RQ.MODE_REPORTS && RQ.focusedReport) {
    uid = RQ.getItemUID(RQ.focusedReport);
    RQ.showContext(uid.board, uid.tid || uid.pid, uid.pid);
  }
};

RQ.onShowContextClick = function(button, e) {
  var report, uid;
  
  e.preventDefault();
  
  report = button.parentNode.parentNode;
  uid = RQ.getItemUID(report);
  
  if (!uid) {
    return;
  }
  
  RQ.showContext(uid.board, uid.tid || uid.pid, uid.pid);
};

RQ.onContextLoaded = function() {
  var resp;
  
  RQ.xhr.get = null;
  
  if (this.status == 404) {
    RQ.showPanelError('context', "This thread doesn't exist anymore");
    return;
  }
  
  resp = RQ.parseResponse(this.responseText);
  
  RQ.threadCache[this.board + this.tid] = resp.posts;
  
  RQ.buildContext(resp.posts, this.board, this.pid);
};

RQ.onContextError = function() {
  RQ.showPanelError('context', 'Error loading preview');
  RQ.xhr.get = null;
};

RQ.buildContext = function(posts, board, pid) {
  var cnt, i, html, tripcode, hasFile, thumb, capcode, controls, el,
    is_archived, thumbSize, post;
  
  cnt = $.id('context-preview');
  
  thumbSize = 130;
  
  is_archived = posts[0] && posts[0].archived;
  
  html = '';
  
  for (i = 0; post = posts[i]; ++i) {
    hasFile = false;
    
    if (post.capcode) {
      capcode = '<span class="post-capcode post-capcode-' + post.capcode + '">'
        + $.capitalise(post.capcode) + '</span>';
    }
    else {
      capcode = '';
    }
    
    if (post.trip) {
      tripcode = '<span class="post-trip">' + post.trip + '</span>';
    }
    else {
      tripcode = '';
    }
    
    if (post.ext) {
      if (post.filedeleted) {
        thumb = '<div><img class="post-thumb" src="//'
          + this.fileDeleted + '" alt="File deleted"></div>';
      }
      else if (board === 'f') {
        hasFile = true;
        thumb = '<a target="_blank" href="'
          + this.linkToFlash(post.filename) + '">'
          + '<div class="post-swf" title="' + post.filename + '.swf">'
            + post.filename
          + '</div></a>';
      }
      else {
        hasFile = true;
        thumb = '<a class="post-thumb-link" target="_blank" href="'
          + this.linkToImage(board, post.tim, post.ext, post.no) + '">'
          + '<img class="post-thumb" width="'
            + post.tn_w + '" height="'
            + post.tn_h + '" src="'
            + this.linkToThumb(board, post.tim) + '" alt="">'
          + '</a>';
      }
    }
    else {
      thumb = '';
    }
    
    controls = '<div class="post-controls">'
      + '<span class="button" data-cmd="delete">Delete</span>'
      + (hasFile ? '<span class="button" data-cmd="delete-file">File Only</span>' : '')
      + (is_archived ? '' : RQ.contextControls)
    + '</div>';
    
    html += '<div id="context-' + board + '-' + post.no
      + (is_archived ? '" data-archived="1' : '')
      + '" class="context-post" data-board="' + board + '" data-tid="' + post.resto + '">'
      + '<span class="post-no">No.' + post.no + '</span>'
      + '<div class="post-author"><span class="post-name">'
      + (post.name || '') + '</span>' + tripcode + capcode + '</div>'
      + (post.sub ? ('<div class="post-subject">' + post.sub + '</div>') : '')
      + '<div class="post-content">' + thumb + (post.com || '') + '</div>'
        + controls
      + '</div>';
  }
  
  cnt.innerHTML = html;
  
  if (el = $.id('context-' + board + '-' + pid)) {
    RQ.focusItem(el);
  }
  
  document.dispatchEvent(new CustomEvent('4chanReportContextReady'));
};

/**
 * Deletion
 */
RQ.deleteFocused = function(fileOnly) {
  var uid, focused;
  
  if (focused = RQ.getFocusedItem()) {
    if (!RQ.confirmPubBan(focused)) {
      return;
    }
    
    ImageHover.hide();
    
    uid = RQ.getItemUID(focused);
    
    $.addClass(focused, 'processing');
    
    RQ.addDelayedJob(
      RQ.deletePost,
      uid.board,
      uid.pid,
      fileOnly,
      RQ.isPostArchived(uid.board, uid.pid)
    );
    
    RQ.focusNext();
  }
};

RQ.deleteFileFocused = function() {
  RQ.deleteFocused(true);
};

RQ.onDeleteFileClick = function(button) {
  var item, uid;
  
  item = button.parentNode.parentNode;
  
  if (uid = RQ.getItemUID(item)) {
    $.addClass(item, 'processing');
    
    RQ.addDelayedJob(RQ.deletePost,
      uid.board,
      uid.pid,
      true,
      RQ.isPostArchived(uid.board, uid.pid)
    );
  }
};

RQ.onDeleteClick = function(button) {
  var item, uid;
  
  item = button.parentNode.parentNode;
  
  if (!RQ.confirmPubBan(item)) {
    return;
  }
  
  if (uid = RQ.getItemUID(item)) {
    $.addClass(item, 'processing');
    
    RQ.addDelayedJob(
      RQ.deletePost,
      uid.board,
      uid.pid,
      false,
      RQ.isPostArchived(uid.board, uid.pid)
    );
  }
};

RQ.deletePost = function(board, pid, fileOnly, isArchived) {
  var path, params = {
    mode: isArchived ? 'arcdel' : 'usrdel',
    pwd: 'janitorise'
  };
  
  params[pid] = 'delete';
  
  if (fileOnly) {
    params['onlyimgdel'] = 'on';
  }
  
  path = '/post';
  
  $.xhr('POST', 'https://sys.4chan.org/' + board + path,
    {
      onload: RQ.onPostDeleted,
      onerror: RQ.onXhrError,
      withCredentials: true,
      board: board,
      pid: pid,
      fileOnly: fileOnly,
      isContext: RQ.mode == RQ.MODE_CONTEXT
    },
    params
  );
};

RQ.onPostDeleted = function() {
  var el, resp;
  
  el = RQ.getItemNode(this.board, this.pid);
  
  if (!el) {
    return;
  }
  
  $.removeClass(el, 'processing');
  
  if (this.status == 200) {
    if ($.hasClass(el, 'disabled')) {
      return;
    }
    
    if (/Updating index|Can't find the post/.test(this.responseText)) {
      $.addClass(el, 'disabled');
      
      if (this.fileOnly && (el = $.cls('post-thumb', el)[0])) {
        el.src = '//' + RQ.fileDeleted;
        $.removeClass(el, 'post-thumb');
        $.addClass(el, 'post-thumb-deleted');
        $.removeClass(el.parentNode, 'post-thumb-link');
      }
      
      if (!this.isContext) {
        RQ.currentCount--;
        RQ.setPageTitle(RQ.board, RQ.currentCount);
        
        if (!el.hasAttribute('data-tid') && !this.fileOnly) {
          RQ.clearChildReports(this.board, this.pid);
        }
      }
    }
    else {
      if (resp = this.responseText.match(/"errmsg"[^>]*>(.*?)<\/span/)) {
        el = document.createElement('span');
        el.innerHTML = resp[1];
        RQ.error(el.textContent);
      }
      else {
        RQ.error();
      }
    }
  }
  else {
    RQ.error('Bad status code (' + this.status + ')');
  }
};

/**
 * Clearing
 */
RQ.clearFocused = function() {
  var uid;
  
  if (RQ.mode == RQ.MODE_REPORTS && RQ.focusedReport) {
    ImageHover.hide();
    
    uid = RQ.getItemUID(RQ.focusedReport);
    
    $.addClass(RQ.focusedReport, 'processing');
    
    RQ.addDelayedJob(RQ.clearReport, uid.board, uid.pid);
    
    RQ.focusNext();
  }
};

RQ.onClearClick = function(button) {
  var report, uid;
  
  report = button.parentNode.parentNode;
  
  if (uid = RQ.getItemUID(report)) {
    $.addClass(report, 'processing');
    RQ.addDelayedJob(RQ.clearReport, uid.board, uid.pid);
  }
};

RQ.clearReport = function(board, pid, hard_clear) {
  var data = {
    action: 'clear_report',
    board: board,
    no: pid,
    '_tkn': $.getToken()
  };
  
  if (hard_clear) {
    data.hard = 1;
  }
  
  $.xhr('POST', '',
    {
      onload: RQ.onReportCleared,
      onerror: RQ.onXhrError,
      board: board,
      pid: pid,
    },
    data
  );
};

RQ.onReportCleared = function() {
  var resp, el;
  
  resp = RQ.parseResponse(this.responseText);
  
  el = RQ.getItemNode(this.board, this.pid);
  
  $.removeClass(el, 'processing');
  
  if ($.hasClass(el, 'disabled')) {
    return;
  }
  
  if (resp.status === 'success') {
    $.addClass(el, 'disabled');
    RQ.currentCount--;
    RQ.setPageTitle(RQ.board, RQ.currentCount);
  }
  else {
    RQ.error(resp.message);
  }
};

/**
 * Quick Ban
 * 
 */
RQ.clearQuickBanDataNode = function() {
  let dl = $.id('js-qb-tpl-dl');
  
  if (dl) {
    $.off(document, 'click', RQ.clearQuickBanDataNode);
    dl.parentNode.removeChild(dl);
  }
};

RQ.onShowQuickBanClick = function(btn) {
  RQ.clearQuickBanDataNode();
  
  let el = $.parentByCls(btn, 'report');
  
  if (!el) {
    return;
  }
  
  let board = el.getAttribute('data-board');
  
  if (!board) {
    return;
  }
  
  let pid = el.getAttribute('data-pid');
  let has_image = !!$.cls('post-thumb', el)[0];
  let is_ws = !$L.nws[board];
  let is_op = !el.hasAttribute('data-tid');
  
  let dl = RQ.buildQuickBanDataNode(board, pid, is_op, has_image, is_ws);
  
  if (!dl) {
    return;
  }
  
  dl.id = 'js-qb-tpl-dl';
  dl.className = 'dl-panel';
  $.on(document, 'click', RQ.clearQuickBanDataNode);
  
  document.body.appendChild(dl);
  
  let rect = btn.getBoundingClientRect();
  let left = rect.left - (dl.offsetWidth - rect.width) / 2;
  let top = rect.top - dl.offsetHeight - 4;
  
  dl.style.top = (top + window.pageYOffset) + 'px';
  dl.style.left = (left + window.pageXOffset) + 'px';
};

RQ.buildQuickBanDataNode = function(board, post_id, is_op, has_image, is_ws) {
  if (RQ.templateCache === null) {
    return;
  }
  
  let dl = $.el('div');
  
  let templates = [];
  
  if (RQ.templateCache[board]) {
    templates = templates.concat(RQ.templateCache[board]);
  }
  
  if (RQ.templateCache.global) {
    templates = templates.concat(RQ.templateCache.global);
  }
  
  dl.innerHTML = `<div class="qb-dl-hdr">${RQ.quickBanHeader}</div>`;
  
  for (let tpl of templates) {
    if (tpl.file_only && !has_image) {
      continue;
    }
    
    if (tpl.ws_only && !is_ws) {
      continue;
    }
    
    if (tpl.op_only && !is_op) {
      continue;
    }
    
    if (tpl.skip && tpl.skip.indexOf(board) !== -1) {
      continue;
    }
    
    let div = $.el('div');
    
    div.setAttribute('data-cmd', 'quick-ban');
    div.setAttribute('data-tpl', tpl.no);
    div.setAttribute('data-board', board);
    div.setAttribute('data-pid', post_id);
    
    div.innerHTML = tpl.name;
    
    dl.appendChild(div);
  }
  
  return dl;
};

RQ.onQuickBanClick = function(btn) {
  let board = btn.getAttribute('data-board');
  let pid = btn.getAttribute('data-pid');
  let tpl_id = btn.getAttribute('data-tpl');
  
  if (!board) {
    return;
  }
  
  RQ.quickBanPost(board, pid, tpl_id);
};

RQ.quickBanPost = function(board, pid, tpl_id) {
  let params = {
    submit: '1',
    by_tpl: tpl_id,
    _tkn: $.getToken()
  };
  
  RQ.setProcessing(`${board}-${pid}`);
  
  $.xhr('POST', 'https://sys.4chan.org/'
    + board + '/admin?mode=admin&admin=' + RQ.quickBanMode + '&id=' + pid + '&noheader=true',
    {
      onload: RQ.onQuickBanDone,
      onerror: RQ.onXhrError,
      withCredentials: true,
      board: board,
      pid: pid,
    },
    params
  );
};

RQ.onQuickBanDone = function() {
  RQ.unsetProcessing(`${this.board}-${this.pid}`);
  
  if (/Banning | submitted| exist| archived/.test(this.responseText)) {
    RQ.setDisabled(`${this.board}-${this.pid}`);
  }
  else {
    RQ.error();
  }
};

/**
 * Image expansion
 */
RQ.toggleExpandFocused = function() {
  var thumb, focused;
  
  if ($.id('image-hover')) {
    return ImageHover.hide();
  }
  else if ($.id('swf-preview')) {
    return ImageHover.hideSWF();
  }
  
  focused = RQ.getFocusedItem();
  
  if (!focused) {
    return;
  }
  
  if (thumb = $.cls('post-thumb', focused)[0]) {
    ImageHover.show(thumb);
  }
  else if (thumb = $.cls('post-swf', focused)[0]) {
    ImageHover.showSWF(thumb);
  }
};

RQ.clearHighlight = function() {
  var i, nodes;
  
  nodes = $.cls('report-hl');
  
  for (i = nodes.length - 1; i >= 0; i--) {
    nodes[i].classList.remove('report-hl-tid', 'report-hl-uid');
  }
  
  nodes = $.cls('cb-hl');
  
  for (i = nodes.length - 1; i >= 0; i--) {
    nodes[i].classList.remove('cb-hl', 'cb-a');
  }
};

RQ.onHlTidClick = function(btn) {
  var el, tid, nodes;
  
  if (btn.classList.contains('cb-a')) {
    RQ.clearHighlight();
    return;
  }
  
  RQ.clearHighlight();
  
  tid = btn.parentNode.parentNode.parentNode.getAttribute('data-tuid');
  
  if (!tid) {
    return;
  }
  
  nodes = $.qsa('.report[data-tuid="' + tid + '"]');
  
  for (el of nodes) {
    el.classList.add('report-hl', 'report-hl-tid');
    $.cls('cb-hl-tid', el)[0].classList.add('cb-hl', 'cb-a');
  }
};

/**
 * Keybinds
 */
Keybinds.map = {
  // Esc
  27: RQ.shiftPanel,
  // Left arrow
  37: RQ.focusPrevious,
  // Right arrow
  39: RQ.focusNext,
  // B
  66: RQ.banRequestFocused,
  // C
  67: RQ.clearFocused,
  // F
  70: RQ.deleteFileFocused,
  // D
  68: RQ.deleteFocused,
  // I
  73: RQ.toggleExpandFocused,
  // R
  82: RQ.refreshReports,
  // S
  83: RQ.focusSearch,
  // U
  85: RQ.cancelDelayedJob,
  // V
  86: RQ.previewFocused,
  // T
  84: RQ.toggleThumbnails
};
  
Keybinds.labels = {
  82: [ 'R', 'Refresh page' ],
  83: [ 'S', 'Focus search field' ],
  39: [ '&#8594;', 'Focus next report' ],
  37: [ '&#8592;', 'Focus previous report' ],
  27: [ 'Esc', 'Close panel' ],
  84: [ 'T', 'Toggle thumbails' ],
  73: [ 'I', 'Expand thumbnail' ],
  86: [ 'V', 'Preview focused post' ],
  67: [ 'C', 'Clear focused report' ],
  68: [ 'D', 'Delete focused post' ],
  70: [ 'F', 'Delete focused file' ],
  66: [ 'B', 'Ban request focused post' ],
  85: [ 'U', 'Undo last action' ]
};

RQ.closeKeyPrompt = function() {
  $.off(document, 'keydown', Keybinds.resolvePrompt);
  
  if (RQ.settings.enableKeybinds) {
    Keybinds.enable();
  }
  
  RQ.closePanel('key-prompt');
};

/**
 * Init
 */
RQ.init();

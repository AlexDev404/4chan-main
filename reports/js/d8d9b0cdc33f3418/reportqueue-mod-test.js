RQ.onShowBanRequestsClick = function() {
  window.location = '?action=ban_requests';
};

RQ.pidSearchLink = function(board, pid) {
  return 'https://team.4chan.org/search?action=from_pid&board=' + board
    + '&pid=' + pid;
};

RQ.runExtra = function() {
  let el;
  
  if (el = $.id('rep-cat-btn')) {
    $.on(el, 'click', RQ.toggleRepCatList);
  }
};

RQ.toggleRepCatList = function(e) {
  e.stopPropagation();
  
  if ($.id('js-rep-cats-dl').classList.contains('hidden')) {
    RQ.showRepCatList();
  }
  else {
    RQ.hideRepCatList();
  }
};

RQ.showRepCatList = function() {
  let el = $.id('js-rep-cats-dl');
  
  if (!el.classList.contains('hidden')) {
    return;
  }
  
  el.classList.remove('hidden');
  
  $.on(document, 'click', RQ.hideRepCatList);
};

RQ.hideRepCatList = function() {
  $.off(document, 'click', RQ.hideRepCatList);
  
  $.id('js-rep-cats-dl').classList.add('hidden');
};

/**
 * Multi
 */
RQ.onMultiClick = function(btn) {
  let [board, pid] = RQ.getPostUID(btn);
   
  if (!board) {
    return;
  }
  
  window.open(RQ.pidSearchLink(board, pid));
};

RQ.multiFocused = function() {
  var uid;
  
  if (!RQ.focusedReport) {
    return;
  }
  
  uid = RQ.getItemUID(RQ.focusedReport);
  
  window.open(RQ.pidSearchLink(uid.board, uid.pid));
};

/**
 * Extra
 */
RQ.buildRepCatsDatalist = function(repCats) {
  let dl = $.id('js-rep-cats-dl');
  
  let btn = $.id('rep-cat-btn');
  
  dl.innerHTML = '';
  
  if (!repCats) {
    btn.classList.add('hidden');
    return;
  }
  
  let empty = true;
  
  for (let catId in repCats) {
    empty = false;
    
    let div = $.el('div');
    
    div.setAttribute('data-id', catId);
    div.innerHTML = repCats[catId];
    
    $.on(div, 'click', RQ.onFilterRepCatClick);
    
    dl.appendChild(div);
  }
  
  if (empty) {
    btn.classList.add('hidden');
  }
  else {
    btn.classList.remove('hidden');
  }
};

RQ.buildReportsExtra = function(data) {
  RQ.buildRepCatsDatalist(data.rep_cats);
};

RQ.onFilterRepCatClick = function() {
  let el = $.id('search-box');
  
  el.value = `cat:${+this.getAttribute('data-id')}`;
  
  RQ.applySearch(el.value);
};

/**
 * Reporters
 */
RQ.showReporters = function(board, pid) {
  var query, html, title;
  
  RQ.closeReporters();
  
  title = 'Reporters for No.' + pid + ' on /' + board + '/';
  
  html = '<div class="panel-content" tabindex="-1" id="reporters-list">'
      + '<div class="spinner"></div>'
    + '</div>'
    + '<div class="panel-footer" data-pid="' + pid + '" data-board="' + board + '">'
      + '<span class="button" data-cmd="ban-reporters">Ban Reporters</span>'
      + '<span class="button" data-warn="1" data-cmd="ban-reporters">Warn Reporters</span>'
    + '</div>';
  
  RQ.showPanel('reporters', html, title, { 'class': 'panel-s' });
  
  query = '?action=get_reporters&board=' + board + '&no=' + pid;
  
  RQ.xhr.get = $.xhr('GET', query, {
    board: board,
    pid: pid,
    onload: RQ.onReportersLoaded,
    onerror: RQ.onReportersError
  });
};

RQ.closeReporters = function() {
  if (RQ.xhr.get) {
    RQ.xhr.get.abort();
    RQ.xhr.get = null;
  }
  
  RQ.closePanel('reporters');
};

RQ.onReportersClick = function(btn) {
  let [board, pid] = RQ.getPostUID(btn);
   
  if (!board) {
    return;
  }
  
  RQ.showReporters(board, pid);
};

RQ.onReportersLoaded = function() {
  var resp;
  
  RQ.xhr.get = null;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    RQ.buildReporters(resp.data, this.board, this.pid);
  }
  else {
    RQ.showPanelError('reporters', resp.message);
  }
};

RQ.onReportersError = function() {
  $.id('repdetails-list').textContent = 'Error loading reporters.';
  RQ.xhr.get = null;
};

RQ.buildReporters = function(reporters, board, pid) {
  var cnt, i, rep, html;
  
  cnt = $.id('reporters-list');
  
  if (!cnt) {
    return;
  }
  
  html = '<table class="panel-list">';
  
  for (i = 0; rep = reporters[i]; ++i) {
    html += '<tr data-pid="' + pid + '" data-board="' + board + '" data-rid="'
      + rep.id + '"' + (rep.weight >= 500 ? ' class="report-cat-prio"' : '') + '>'
      + '<td data-cmd="repdetails"><span class="pierce">' + rep.ipStr + '</span><span class="pierce user-country">(' + rep.country + ')'
        + (rep.has_pass ? ' <span data-tip="4chan Pass" class="lbl-xs bg-green">PASS</span>' : '') + '</td>'
      + '<td class="cell-rep-ago" data-cmd="repdetails">' + $.agoShort(rep.time) + '</td>'
      + '<td class="cell-rep-reason" data-cmd="repdetails"><div class="pierce no-overflow">'
        + (rep.cat || ('N/A (' + rep.weight + ')'))
      + '</div></td>'
      + '<td class="align-right" data-cmd="repdetails">'
        + '<span class="button btn-xs" data-single="1" data-tip="Ban" data-cmd="ban-reporters">B</span>'
        + '<span class="button btn-xs" data-single="1" data-tip="Warn" data-warn="1" data-cmd="ban-reporters">W</span>'
      + '</td>'
    + '</tr>';
  }
  
  html += '</table>';
  
  cnt.innerHTML = html;
};

/**
 * Reporter details
 */
RQ.showRepDetails = function(rid, ipStr) {
  var query, title, html, attr;
  
  RQ.closeRepDetails();
  
  if (!ipStr) {
    ipStr = $.escapeHTML(rid);
    attr = 'data-ip="' + ipStr + '"';
    query = '?action=get_rep_details&ip=' + rid;
  }
  else {
    attr = 'data-rid="' + rid + '"';
    query = '?action=get_rep_details&rid=' + rid;
  }
  
  title = 'Reports from <span id="js-rep-ip" class="as-iblk">' + ipStr
    + '</span><span class="user-country" id="js-rep-c"></span>'
    + '<span id="js-rep-pass" data-tip="4chan Pass" class="hidden-i lbl-xs bg-green">PASS</span></span>'
    + '<span class="hdr-xs"><a data-tip="Posts by this IP" class="btn-xs btn-gray" target=_blank" '
      + 'href="https://team.4chan.org/search#{%22ip%22:%22' + ipStr
      + '%22}">posts</a><a data-tip="Bans for this IP" class="btn-xs btn-gray" target=_blank" '
      + 'href="https://team.4chan.org/bans?action=search&amp;ip=' + ipStr
    + '">bans</a></span>';
  
  html = '<div class="panel-content" tabindex="-1" id="repdetails-list">'
      + '<div class="spinner"></div>'
    + '</div>'
    + '<div ' + attr + ' class="panel-footer">'
      + '<span data-cmd="clear-reporter" class="button">Clear All</span>'
    + '</div>';
  
  RQ.showPanel('repdetails', html, title, { 'class': 'panel-m' });
  
  RQ.xhr.get = $.xhr('GET', query, {
    onload: RQ.onRepDetailsLoaded,
    onerror: RQ.onRepDetailsError
  });
};

RQ.closeRepDetails = function() {
  if (RQ.xhr.get) {
    RQ.xhr.get.abort();
    RQ.xhr.get = null;
  }
  
  RQ.closePanel('repdetails');
};

RQ.onRepDetailsClick = function(button) {
  var rid, ipStr;
  
  button = button.parentNode;
  rid = button.getAttribute('data-rid');
  ipStr = button.firstElementChild.firstElementChild.textContent;
  
  RQ.showRepDetails(rid, ipStr);
};

RQ.onRepDetailsLoaded = function() {
  var resp;
  
  RQ.xhr.get = null;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    RQ.buildRepDetails(resp.data);
  }
  else {
    RQ.showPanelError('repdetails', resp.message);
  }
};

RQ.onRepDetailsError = function() {
  $.id('repdetails-list').textContent = 'Error loading reporter details.';
  RQ.xhr.get = null;
};

RQ.buildRepDetails = function(data) {
  var cnt, i, report, post, html, tripcode, hasFile, thumb, reports, rowTip, rowCls;
  
  reports = data.reports;
  
  cnt = $.id('repdetails-list');
  
  if (!cnt) {
    return;
  }
  
  if (data.country) {
    $.id('js-rep-c').textContent = '(' + data.country + ')';
  }
  
  html = '<table class="panel-list">';
  
  for (i = 0; report = reports[i]; ++i) {
    post = JSON.parse(report.post_json);
    
    hasFile = false;
    
    if (post.trip) {
      tripcode = '<span class="post-trip">' + post.trip + '</span>';
    }
    else {
      tripcode = '';
    }
    
    if (post.ext && report.board !== 'f') { // fixme
      if (post.filedeleted) {
        thumb = '<div><img class="post-thumb" src="'
          + this.protocol + this.fileDeleted + '" alt="File deleted"></div>';
      }
      else {
        hasFile = true;
        thumb = '<a class="post-thumb-link" target="_blank" href="'
          + this.linkToImage(report.board, post.tim, post.ext) + '">'
          + '<img class="post-thumb" src="'
            + this.linkToThumb(report.board, post.tim) + '" alt="">'
          + '</a>';
      }
    }
    else {
      thumb = '';
    }
    
    rowCls = '';
    rowTip = '';
    
    if (report.cleared === '1' || report.weight < 1) {
      rowCls += 'disabled ';
      
      if (report.cleared === '1') {
        rowTip = '<span data-tip="Cleared" class="lbl-xs bg-green">CLR</span>';
      }
      else if (report.weight < 1) {
        rowTip = '<span data-tip="Ignored" class="lbl-xs bg-blue">IGN</span>';
      }
    }
    
    if (report.weight >= 500) {
      rowCls += 'report-cat-prio';
    }
    
    html += '<tr data-pid="' + report.no + '" data-board="' + report.board
      + '" data-rid="' + report.id + '">'
      + '<td>' + rowTip + '</td>'
      + '<td class="' + rowCls + '" data-cmd="report-preview">/' + report.board + '/' + post.no + '</td>'
      + '<td class="cell-rep-ip ' + rowCls + '" data-cmd="report-preview">' + report.ip + '<span class="user-country">(' + report.country + ')</span></td>'
      + '<td class="cell-rep-ago ' + rowCls + '" data-cmd="report-preview">' + $.agoShort(report.time) + '</td>'
      + '<td class="cell-rep-reason ' + rowCls + '" data-cmd="report-preview"><div class="pierce no-overflow">'
        + (report.cat || ('N/A (' + report.weight + ')'))
      + '</span></td>'
      + '<td class="align-right">'
        + '<span class="button btn-xs" data-single="1" data-tip="Ban" data-cmd="ban-reporters">B</span>'
        + '<span class="button btn-xs" data-single="1" data-tip="Warn" data-warn="1" data-cmd="ban-reporters">W</span>'
      + '</td>'
    + '</tr>'
    + '<tr class="hidden unwrapped-row">'
      + '<td colspan="6"><div class="report-preview">'
        + '<div class="post-author"><span class="post-name">'
        + (post.name || '') + '</span>' + tripcode
        + '<a class="post-link-xs" target="_blank" href="'
          + this.linkToPost(report.board, post.no, post.resto) + '">/'
          + report.board + '/' + post.no + '</a></div>'
        + '<div class="post-subject">' + (post.sub || '') + '</div>'
        + '<div class="post-content">' + thumb + (post.com || '') + '</div>'
      + '</div></td>'
    + '</tr>';
  }
  
  html += '</table>';
  
  cnt.innerHTML = html;
  
  if (data.has_pass) {
    cnt = $.id('js-rep-pass');
    $.removeClass(cnt, 'hidden');
  }
};

RQ.onReportPreviewClick = function(button) {
  var preview = button.parentNode.nextElementSibling;
  
  if (preview.style.display == 'table-row') {
    preview.style.display = 'none';
    return;
  }
  
  preview.style.display = 'table-row';
};

/**
 * Clear reporter
 */
RQ.onClearReporterClick = function(button) {
  var params, id;
  
  button = button.parentNode;
  
  params = {
    action: 'clear_reporter',
    '_tkn': $.getToken()
  };
  
  if (id = button.getAttribute('data-rid')) {
    params.rid = id;
  }
  else if (id = button.getAttribute('data-ip')) {
    params.ip = id;
  }
  else {
    return false;
  }
  
  $.id('repdetails-list').innerHTML = '<div class="spinner"></div>';
  
  $.xhr('POST', '', {
    onload: RQ.onClearReporterLoaded,
    onerror: RQ.onXhrError
  }, params);
};

RQ.onClearReporterLoaded = function() {
  var resp;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    RQ.notify('Cleared ' + resp.data.affected + ' report'
      + (resp.data.affected !== 1 ? 's' : ''));
    
    RQ.closePanel('repdetails');
  }
  else {
    RQ.error(resp.message);
  }
};

/**
 * Ban all reporters of a post
 */
RQ.onBanReportersClick = function(button) {
  var board, pid, params, cnt;
  
  cnt = button.parentNode;
  
  params = {
    action: 'ban_reporters',
    board: board,
    pid: pid,
    '_tkn': $.getToken()
  };
  
  if (button.hasAttribute('data-single')) {
    cnt = cnt.parentNode;
    
    params.rid = cnt.getAttribute('data-rid');
  }
  else {
    if (!confirm('Are you sure?')) {
      return;
    }
  }
  
  params.board = cnt.getAttribute('data-board');
  params.pid = cnt.getAttribute('data-pid');
  
  if (button.hasAttribute('data-warn')) {
    params.warn = 1;
  }
  
  RQ.showMessage('Processing…', 'notify');
  
  $.xhr('POST', '', {
    onload: RQ.onBanReportersLoaded,
    onerror: RQ.onXhrError
  }, params);
};

RQ.onBanReportersLoaded = function() {
  var resp;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    RQ.notify(resp.data);
  }
  else {
    RQ.error(resp.message);
  }
};

/**
 * Ban
 */
RQ.onBanClick = function(button) {
  var report, uid;
  
  report = button.parentNode.parentNode;
  uid = RQ.getItemUID(report);
  
  if (!uid) {
    return;
  }
  
  RQ.ban(uid.board, uid.pid, button.hasAttribute('data-is-context'));
};

RQ.banFocused = function() {
  var uid, focused;
  
  focused = RQ.getFocusedItem();
  
  if (!focused) {
    return;
  }
  
  uid = RQ.getItemUID(focused);
  
  RQ.ban(uid.board, uid.pid);
};

RQ.ban = function(board, pid) {
  var title, html, panelId, attrs;
  
  title = 'Ban No.' + pid + ' on /' + board + '/';
  
  panelId = board + '-' + pid;
  
  html = '<div class="panel-content">'
      + '<iframe src="https://sys.4chan.org/'
      + board + '/admin?mode=admin&admin=ban&id=' + pid
    + '&noheader=true" frameborder="0"></iframe>'
    + '</div>';
  
  attrs = { 'class': 'ban-panel' };
  
  if (RQ.mode == RQ.MODE_CONTEXT) {
    attrs['data-is-context'] = 1;
  }
  
  RQ.showPanel('ban-' + panelId, html, title, attrs, RQ.onCreateIframe);
};

RQ.onBanDone = function(id) {
  var panel = RQ.getPanel('ban-' + id);
  
  RQ.closeBan(id);
  RQ.setDisabled(id, panel.hasAttribute('data-is-context'));
};

RQ.onBanStart = function(id) {
  var uid, focused, panel = RQ.getPanel('ban-' + id);
  
  RQ.setProcessing(id, panel.hasAttribute('data-is-context'));
  
  focused = RQ.getFocusedItem();
  
  if (focused) {
    uid = RQ.getItemUID(focused);
    
    if (id == (uid.board + '-' + uid.pid)) {
      RQ.focusNext();
    }
  }
  
  RQ.panelStack.focus();
  
  RQ.hideBan(id);
};

RQ.hideBan = function(id) {
  RQ.hidePanel('ban-' + id);
};

RQ.closeBan = function(id) {
  RQ.closePanel('ban-' + id);
};

RQ.onToggleIgnoredClick = function(button) {
  RQ.cleared_only = false;
  $.removeClass($.id('cleared-btn'), 'active');
  
  if ($.hasClass(button, 'active')) {
    if (RQ.board) {
      location.hash = '/' + RQ.board;
    }
    else {
      location.hash = '';
    }
    RQ.extraFetch = null;
    RQ.showReports(RQ.board, false);
    $.removeClass(button, 'active');
  }
  else {
    location.hash = '/' + (RQ.board || '') + '-ignored';
    RQ.extraFetch = 'ignored';
    RQ.showReports(RQ.board, false, RQ.extraFetch);
    $.addClass(button, 'active');
  }
};

/**
 * Remove stale reports
 */
RQ.cleanup = function() {
  var params;
  
  params = {
    action: 'clear_stale_reports',
    board: RQ.board,
    '_tkn': $.getToken()
  };
  
  RQ.showMessage('Loading&hellip;', 'notify');
  
  $.xhr('POST', '', {
    onload: RQ.onCleanupLoaded,
    onerror: RQ.onXhrError
  }, params);
};

RQ.onCleanupLoaded = function() {
  var resp;
  
  resp = RQ.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    if (resp.data) {
      RQ.notify('Removed ' + resp.data.count + ' report(s)');
    }
    else {
      RQ.notify('Nothing to do');
    }
  }
  else {
    RQ.error(resp.message);
  }
};

/**
 * Thread Options
 */
RQ.onThreadOptsClick = function(btn) {
  let [board, pid] = RQ.getPostUID(btn);
   
  if (!board) {
    return;
  }
  
  window.open(
    'https://sys.4chan.org/' + board
      + '/admin?mode=admin&admin=opt&id=' + pid,
    '_blank', 'width=400,height=275'
  );
};

RQ.onHlUidClick = function(btn) {
  var el, uid, nodes;
  
  if (btn.classList.contains('cb-a')) {
    RQ.clearHighlight();
    return;
  }
  
  RQ.clearHighlight();
  
  uid = btn.parentNode.parentNode.parentNode.getAttribute('data-uid');
  
  if (!uid) {
    return;
  }
  
  nodes = $.qsa('.report[data-uid="' + uid + '"]');
  
  for (el of nodes) {
    el.classList.add('report-hl', 'report-hl-uid');
    $.cls('cb-hl-uid', el)[0].classList.add('cb-hl', 'cb-a');
  }
};

/**
 * Extra tools
 */
RQ.clearExtraToolsNode = function() {
  let dl = $.id('js-et-l');
  
  if (dl) {
    $.off(document, 'click', RQ.clearExtraToolsNode);
    dl.parentNode.removeChild(dl);
  }
};

RQ.onExtraToolsClick = function(btn) {
  RQ.clearExtraToolsNode();
  
  let el = $.parentByCls(btn, 'report');
  
  if (!el) {
    return;
  }
  
  let board = el.getAttribute('data-board');
  
  if (!board) {
    return;
  }
  
  let pid = el.getAttribute('data-pid');
  let is_op = !el.hasAttribute('data-tid');
  let is_archived = el.hasAttribute('data-archived');
  
  let dl = RQ.buildExtraToolsNode(board, pid, is_op, is_archived);
  
  dl.id = 'js-et-l';
  dl.className = 'dl-panel';
  $.on(document, 'click', RQ.clearExtraToolsNode);
  
  document.body.appendChild(dl);
  
  let rect = btn.getBoundingClientRect();
  let left = rect.left - (dl.offsetWidth - rect.width) / 2;
  let top = rect.top - dl.offsetHeight - 4;
  
  dl.style.top = (top + window.pageYOffset) + 'px';
  dl.style.left = (left + window.pageXOffset) + 'px';
};

RQ.buildExtraToolsNode = function(board, post_id, is_op, is_archived) {
  let dl = $.el('div');
  
  let tools = [
    ['Show reporters', 'reporters'],
  ];
  
  if (!is_archived) {
    if (is_op) {
      tools.unshift(['Perma-sage', 'set-permasage']);
      tools.unshift(['Thread options', 'thread-opts']);
    }
  }
  
  for (let t of tools) {
    let div = $.el('div');
    
    div.setAttribute('data-cmd', t[1]);
    div.setAttribute('data-board', board);
    div.setAttribute('data-pid', post_id);
    div.innerHTML = t[0];
    
    dl.appendChild(div);
  }
  
  return dl;
};

/**
 * Perma-sage
 */
RQ.onSetPermaSageClick = function(btn) {
  let board = btn.getAttribute('data-board');
  let pid = btn.getAttribute('data-pid');
  
  RQ.setPermaSage(board, pid);
};

RQ.setPermaSage = function(board, pid) {
  let params = {
    submit: '1',
    permasage: '1',
    _tkn: $.getToken()
  };
  
  RQ.setProcessing(`${board}-${pid}`);
  
  $.xhr('POST', 'https://sys.4chan.org/'
    + board + '/admin?mode=admin&admin=opt&id=' + pid + '&noheader=true',
    {
      onload: RQ.onSetPermaSageDone,
      onerror: RQ.onXhrError,
      withCredentials: true,
      board: board,
      pid: pid,
    },
    params
  );
};

RQ.onSetPermaSageDone = function() {
  RQ.unsetProcessing(`${this.board}-${this.pid}`);
  
  if (/Perma-sage &check;/.test(this.responseText)) {
    RQ.notify(`Perma-sage enabled`);
  }
  else if (/not found/.test(this.responseText)) {
    RQ.notify(`Thread not found`);
  }
  else {
    RQ.error();
  }
};

/**
 * Commands
 */
RQ.addCommands({
  'show-ban-requests': RQ.onShowBanRequestsClick,
  'thread-opts': RQ.onThreadOptsClick,
  'reporters' : RQ.onReportersClick,
  'repdetails' : RQ.onRepDetailsClick,
  'report-preview' : RQ.onReportPreviewClick,
  'clear-reporter': RQ.onClearReporterClick,
  'ban-reporters': RQ.onBanReportersClick,
  'ban': RQ.onBanClick,
  'multi': RQ.onMultiClick,
  'cleanup': RQ.cleanup,
  'toggle-ignored': RQ.onToggleIgnoredClick,
  'hl-uid': RQ.onHlUidClick,
  'show-xtools': RQ.onExtraToolsClick,
  'set-permasage': RQ.onSetPermaSageClick
});

/**
 * Keybinds
 */
Keybinds.add({
  66: [ RQ.banFocused ],
  77: [ RQ.multiFocused, [ 'M', 'Search by IP' ] ]
});

// ---

RQ.quickBanMode = 'ban';
RQ.quickBanHeader = 'Quick Ban &amp; Delete';

RQ.getReportControls = function(board, pid) {
  let str;
  
  str = `<span data-cmd="ban" class="button btn-ls">Ban</span>
<span data-cmd="show-q-ban" class="button btn-rs btn-hmg"><span>&hellip;</span></span>
<a href="${RQ.pidSearchLink(board, pid)}" target="_blank" data-tip="Search by IP" class="button">M</a>
<span data-cmd="show-xtools" class="button btn-hmg"><span>&hellip;</span></span>`;
  
  return str;
};

RQ.reportControlsArc = '<span data-cmd="show-xtools" class="button btn-hmg"><span>&hellip;</span></span>';

RQ.contextControls = '<span data-cmd="ban" class="button right" \
data-is-context="1">Ban</span>';

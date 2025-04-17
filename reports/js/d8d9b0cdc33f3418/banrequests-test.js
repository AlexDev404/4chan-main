var BR = {
  TPL_EVASION_ID: 135,
  TPL_EVASION_SHORT_ID: 207
};

BR.init = function() {
  this.settings = this.getSettings();
  
  this.applyCustomCSS();
  
  this.board = null;
  
  this.xhr = {};
  
  this.threadCache = {};
  
  this.quoteTimeout = null;
  this.quoteDetachTimeout = null;
  this.activeQuoteLink = null;
  this.activeQuoteLeaf = null;
  
  this.protocol = this.settings.useSSL ? 'https://' : 'http://';
  this.thumbServer = 'i.4cdn.org';
  this.imageServer = 'i.4cdn.org';
  this.flashServer = 'i.4cdn.org';
  
  this.fileDeleted = 's.4cdn.org/image/filedeleted-res.gif';
  
  this.focusedRequest = null;
  
  this.isMod = false;
  
  this.isMobileLayout = false;
  
  this.clickCommands = {
    'switch-board': BR.onBoardLinkClick,
    'toggle-checked': BR.onCheckboxClick,
    'refresh': BR.refreshRequests,
    'show-settings': BR.showSettings,
    'save-settings': BR.onSaveSettingsClick,
    'reset-menu': BR.resetCustomMenu,
    'accept': BR.onAcceptClick,
    'deny': BR.onDenyClick,
    'amend': BR.onAmendClick,
    'confirm-amend': BR.onConfirmAmendClick,
    'focus': BR.onFocusClick,
    'shift-panel': BR.shiftPanel,
    'show-report-queue': BR.onShowReportQueueClick,
    'toggle-dt': BR.onToggleDTClick
  };
  
  this.delayedJob = null;
  this.panelStack = null;
  
  this.currentCount = 0;
  
  this.templateCache = {};
  
  Keybinds.init(this);
  ImageHover.init(this);
  
  this.resolveQuery();
  
  Tip.init();
  
  $.on(document, 'click', BR.onClick);
  $.on(document, 'mouseover', BR.onMouseOver);
  $.on(document, 'mouseout', BR.onMouseOut);
  $.on(document, 'DOMContentLoaded', BR.run);
  $.on(window, 'beforeunload', BR.onBeforeUnload);
};

BR.run = function() {
  $.off(document, 'DOMContentLoaded', BR.run);
  
  BR.isMobileLayout = (window.matchMedia
    && window.matchMedia('(max-device-width: 640px)').matches);
  
  BR.initMobileBoardSelector();
  
  $.on($.id('filter-form'), 'submit', BR.onFilterSubmit);
  $.on($.id('filter-form'), 'reset', BR.onFilterReset);
  
  if (localStorage.getItem('dark-theme')) {
    $.addClass($.docEl, 'dark-theme');
    $.id('cfg-cb-dt').checked = true;
  }
  
  if (BR.settings.enableKeybinds) {
    if (BR.settings.keyRemap) {
      Keybinds.remap(BR.settings.keyRemap);
    }
    Keybinds.enable();
  }
  
  BR.panelStack = $.id('panel-stack');
  
  BR.applyCustomMenu(BR.settings.customMenu);
  
  if (BR.settings.hideThumbnails) {
    $.addClass(document.body, 'no-thumbnails');
  }
  
  BR.showRequests(BR.board);
};

BR.applyCustomCSS = function(css) {
  var nonce, el;
  
  if (el = $.id('js-custom-css')) {
    el.parentNode.removeChild(el);
  }
  
  if (css === undefined) {
    css = BR.settings.customCSS;
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

BR.initMobileBoardSelector = function() {
  var ul, boards, el, sel, node, i, wrap, idx;
  
  if (!BR.isMobileLayout) {
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
    
    if (BR.board === el.value) {
      idx = i;
    }
    
    sel.appendChild(el);
  }
  
  $.on(sel, 'change', BR.onMobileBoardSelectorChanged);
  
  wrap = $.el('div');
  wrap.id = 'board-sel-wrap';
  wrap.textContent = 'Board: ';
  wrap.appendChild(sel);
  
  ul.parentNode.insertBefore(wrap, ul);
  
  sel.selectedIndex = idx;
};

BR.onMobileBoardSelectorChanged = function() {
  var el = $.id('board-menu-mobile');
  BR.onBoardLinkClick(el.options[el.selectedIndex]);
};

BR.updateMenuCounts = function(data) {
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
BR.clearQuotePreviews = function() {
  clearTimeout(BR.quoteTimeout);
  clearTimeout(BR.quoteDetachTimeout);
  BR.activeQuoteLeaf = null;
  BR.triggerQuoteDetach();
};

BR.onQuoteLinkMouseOver = function(el) {
  clearTimeout(BR.quoteTimeout);
  BR.triggerQuoteDetach();
  
  if (el.classList.contains('deleted')) {
    return;
  }
  
  BR.quoteTimeout = setTimeout(BR.triggerQuotePreview, 300, el);
};

BR.onQuoteLinkMouseOut = function() {
  clearTimeout(BR.quoteTimeout);
  BR.activeQuoteLink = null;
  BR.quoteDetachTimeout = setTimeout(BR.triggerQuoteDetach, 150);
};

BR.onQuoteLeafMouseOver = function() {
  BR.activeQuoteLeaf = this;
};

BR.onQuoteLeafMouseOut = function() {
  BR.activeQuoteLeaf = null;
  BR.quoteDetachTimeout = setTimeout(BR.triggerQuoteDetach, 150);
};

BR.triggerQuoteDetach = function() {
  var el = BR.activeQuoteLeaf;
  
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

BR.triggerQuotePreview = function(el) {
  var uid, pid, board, tid;
  
  pid = +el.textContent.replace(/[^0-9]+/g, '');
  
  uid = el.parentNode.parentNode;
  board = uid.getAttribute('data-board');
  tid = uid.getAttribute('data-tid');
  
  BR.activeQuoteLink = el;
  
  if (BR.threadCache[board + tid]) {
    BR.showQuotePreview(board, tid, pid, el);
  }
  else {
    BR.fetchQuotePreview(board, tid, pid, el);
  }
};

BR.fetchQuotePreview = function(board, tid, pid, el) {
  if (BR.threadCache[board + tid] === true) {
    return;
  }
  
  BR.threadCache[board + tid] = true;
  
  $.xhr('GET', '//api.4chan.org/' + board + '/thread/' + tid + '.json', {
    onload: BR.onQuotePreviewLoaded,
    onerror: BR.onQuotePreviewError,
    board: board,
    tid: tid,
    pid: pid,
    el: el
  });
};

BR.disableQuoteLink = function(el) {
  el.classList.add('deleted');
};

BR.onQuotePreviewLoaded = function() {
  var resp, el;
  
  el = this.el;
  
  this.el = null;
  
  if (this.status == 404) {
    BR.disableQuoteLink(el);
    BR.threadCache[this.board + this.tid] = false;
    return;
  }
  
  resp = BR.parseResponse(this.responseText);
  
  BR.threadCache[this.board + this.tid] = resp.posts;
  
  BR.showQuotePreview(this.board, this.tid, this.pid, el);
};

BR.showQuotePreview = function(board, tid, pid, tgt) {
  var cnt, el, thread, p, post;
  
  if (BR.activeQuoteLink !== tgt) {
    return;
  }
  
  thread = BR.threadCache[board + tid];
  
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
    BR.disableQuoteLink(tgt);
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
  el.innerHTML = BR.buildQuotePreviewHTML(board, post);
  
  $.on(el, 'mouseover', BR.onQuoteLeafMouseOver);
  $.on(el, 'mouseout', BR.onQuoteLeafMouseOut);
  
  cnt.appendChild(el);
  
  BR.alignQuotePreview(el, tgt);
};

BR.buildQuotePreviewHTML = function(board, post) {
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
        + '<img data-tip data-tip-cb="BR.showFileTip" data-meta="'
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

BR.alignQuotePreview = function(el, tgt) {
  var elAABB, tgtAABB, pad, left, top;
  
  pad = 4;
  
  elAABB = el.getBoundingClientRect();
  tgtAABB = tgt.getBoundingClientRect();
  
  left = tgtAABB.right + pad;
  top = tgtAABB.top + window.pageYOffset;
  
  if (left + elAABB.width + pad > $.docEl.clientWidth) {
    left = tgtAABB.left - pad - elAABB.width;
  }
  
  el.style.left = left + 'px';
  el.style.top = top + 'px';
};

BR.onQuotePreviewError = function() {
  var el = this.el;
  
  this.el = null;
  
  BR.disableQuoteLink(el);
  BR.threadCache[this.board + this.tid] = false;
};

// ---

BR.onToggleDTClick = function() {
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

BR.parseResponse = function(data) {
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

BR.resolveQuery = function() {
  var hash;
  
  if (location.hash) {
    hash = location.hash.split('/');
    BR.board = hash[1];
  }
  else {
    BR.board = null;
  }
};

BR.derefer = function(url) {
  return 'https://www.4chan.org/derefer?url=' + encodeURIComponent(url);
};

BR.getRequestNode = function(id) {
  return $.id('ban-request-' + id);
};

BR.getRequestId = function(el) {
  var uid;
  
  if (!el) {
    return null;
  }
  
  if ((uid = el.id.split('-'))[1] == 'request') {
    return uid[2];
  }
  
  return null;
};

/**
 * Event handlers
 */
BR.onFilterSubmit = function(e) {
  e.preventDefault();
  e.stopPropagation();
  
  BR.applySearch($.id('search-box').value);
};

BR.onFilterReset = function() {
  BR.clearSearch();
};

BR.onBeforeUnload = function() {
  if (BR.delayedJob) {
    BR.runDelayedJob();
  }
};

BR.onClick = function(e) {
  var t, cmd;
  
  if (e.which != 1 || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) {
    return;
  }
  
  if ((t = e.target) == document) {
    return;
  }
  
  if ((cmd = t.getAttribute('data-cmd')) && (cmd = BR.clickCommands[cmd])) {
    e.stopPropagation();
    cmd(t, e);
  }
  
  if (t.classList.contains('quotelink')) {
    e.stopPropagation();
    e.preventDefault();
  }
};

BR.onMouseOver = function(e) {
  var target = e.target;
  
  if (BR.settings.imageHover && $.hasClass(target, 'post-thumb')) {
    ImageHover.show(target);
  }
  else if (target.classList.contains('quotelink')) {
    BR.onQuoteLinkMouseOver(target);
  }
};

BR.onMouseOut = function(e) {
  var target = e.target;
  
  if (BR.settings.imageHover && $.hasClass(target, 'post-thumb')) {
    ImageHover.hide();
  }
  else if (target.classList.contains('quotelink')) {
    BR.onQuoteLinkMouseOut();
  }
};

BR.onBoardLinkClick = function(button) {
  var board = button.textContent;
  
  if (board == 'All') {
    BR.board = null;
    location.hash = '';
  }
  else {
    BR.board = board;
    location.hash = '/' + board;
  }
  
  BR.applyCustomMenu(BR.settings.customMenu);
  
  BR.showRequests(BR.board);
};

BR.onShowReportQueueClick = function() {
  window.location.search = '';
};

BR.onCheckboxClick = function(button) {
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

BR.applySearch = function(str) {
  if (str === undefined || str === '') {
    BR.clearSearch();
    return;
  }
  
  str = str.toLowerCase();
  
  $.id('reset-btn').classList.remove('hidden');
  
  let fields = ['post-name', 'post-content', 'ban-request-template'];
  
  let nodes = $.cls('ban-request');
  
  nodeloop: for (let br of nodes) {
    br.classList.remove('hidden');
    
    for (let f of fields) {
      let el = $.cls(f, br)[0];
      
      if (!el) {
        continue;
      }
      
      if (el.textContent.toLowerCase().indexOf(str) !== -1) {
        continue nodeloop;
      }
    }
    
    br.classList.add('hidden');
  }
};

BR.clearSearch = function() {
  let nodes = $.cls('ban-request');
  
  $.id('reset-btn').classList.add('hidden');
  
  for (let br of nodes) {
    br.classList.remove('hidden');
  }
};

BR.refreshRequests = function() {
  if ($.id('image-hover')) {
    ImageHover.hide();
  }
  else if ($.id('swf-preview')) {
    ImageHover.hideSWF();
  }
  
  if (BR.delayedJob) {
    BR.runDelayedJob();
  }
  
  BR.threadCache = {};
  
  BR.clearQuotePreviews();
  
  BR.applyCustomMenu(BR.settings.customMenu);
  BR.focusedRequest = null;
  BR.showRequests(BR.board);
};

BR.setPageTitle = function(board, count) {
  var title;
  
  if (!board) {
    title = 'All';
  }
  else {
    title = '/' + board + '/';
  }
  
  document.title = 'Ban Requests (' + count + ') - ' + title;
  $.id('title').textContent = 'Ban Requests - ' + title;
};

BR.toggleThumbnails = function() {
  if (BR.settings.hideThumbnails) {
    BR.settings.hideThumbnails = false;
    $.removeClass(document.body, 'no-thumbnails');
  }
  else {
    BR.settings.hideThumbnails = true;
    $.addClass(document.body, 'no-thumbnails');
  }
  
  $.setItem('rq-settings', JSON.stringify(BR.settings));
};

BR.linkToImage = function(board, file, ext) {
  return '//' + this.imageServer + '/' + board + '/' + file + ext;
};

BR.linkToRetainedImage = function(board, file, ext) {
  return '//' + this.imageServer + '/bans/src/' + board + '/' + file + ext;
};

BR.linkToFlash = function(fileName) {
  return this.protocol + this.flashServer + '/f/' + fileName + '.swf';
};

BR.linkToThumb = function(board, file) {
  return '//' + this.thumbServer + '/' + board + '/' + file + 's.jpg';
};

BR.linkToRetainedThumb = function(board, file) {
  return '//' + this.imageServer + '/bans/thumb/' + board + '/' + file + 's.jpg';
};

BR.linkToPost = function(board, pid, tid) {
  return this.protocol + 'boards.' + $L.d(board) + '/' + board + '/thread/'
    + (+tid !== 0 ? (tid + '#p' + pid) : pid);
};

/**
 * UI Panels
 */
BR.createPanel = function(id, html, attributes) {
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
  
  return panel;
};

BR.showPanel = function(id, html, title, attributes) {
  var previous, panel, content;
  
  ImageHover.hide();
  
  if (previous = BR.panelStack.lastElementChild) {
    previous.style.display = 'none';
  }
  
  if (BR.panelStack.children.length === 0) {
    $.addClass(BR.panelStack, 'backdrop');
  }
  
  if (title) {
    html = '<div class="panel-header">'
      + '<span data-cmd="shift-panel" class="button clickbox">&times;</span>'
      + '<h3>' + title + '</h3>'
    + '</div>' + (html || '');
  }
  
  panel = BR.createPanel(id, html, attributes);
  BR.panelStack.appendChild(panel);
  
  if (content = $.cls('panel-content', panel)[0]) {
    content.focus();
    content.style.maxHeight =
      ($.docEl.clientHeight - content.getBoundingClientRect().top * 2) + 'px';
  }
};

BR.closePanel = function(id) {
  var previous, panel;
  
  if (panel = BR.getPanel(id)) {
    if (previous = panel.previousElementSibling) {
      previous.style.display = 'block';
    }
    BR.panelStack.removeChild(panel);
    
    if (BR.panelStack.children.length === 0) {
      $.removeClass(BR.panelStack, 'backdrop');
    }
  }
};

BR.shiftPanel = function() {
  var cb, panel = BR.panelStack.lastElementChild;
  
  if (!panel) {
    return;
  }
  
  if (cb = panel.getAttribute('data-close-cb')) {
    BR['close' + cb]();
  }
  else {
    BR.closePanel(BR.getPanelId(panel));
  }
};

BR.getPanelId = function(el) {
  return el.id.split('-').slice(0, -1).join('-');
};

BR.getPanel = function(id) {
  return $.id(id + '-panel');
};

BR.showPanelError = function(id, msg) {
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
BR.messageTimeout = null;

BR.showMessage = function(msg, type, timeout) {
  var el;
  
  BR.hideMessage();
  
  el = document.createElement('div');
  el.id = 'feedback';
  el.title = 'Dismiss';
  el.innerHTML = '<span class="feedback-' + type + '">' + msg + '</span>';
  
  $.on(el, 'click', BR.hideMessage);
  
  document.body.appendChild(el);
  
  if (timeout) {
    BR.messageTimeout = setTimeout(BR.hideMessage, timeout);
  }
};

BR.hideMessage = function() {
  var el = $.id('feedback');
  
  if (el) {
    if (BR.messageTimeout) {
      clearTimeout(BR.messageTimeout);
      BR.messageTimeout = null;
    }
    
    $.off(el, 'click', BR.hideMessage);
    
    document.body.removeChild(el);
  }
};

BR.error = function(msg) {
  BR.showMessage(msg || 'Something went wrong', 'error', 5000);
};

BR.notify = function(msg) {
  BR.showMessage(msg, 'notify', 3000);
};

BR.showLoadError = function(msg) {
  $.id('items').innerHTML = '<div class="load-error">' + msg + '</div>';
};

BR.showLoadEmpty = function() {
  $.id('items').innerHTML = '<div class="load-empty">Nothing found</div>';
};

BR.showLoadSpinner = function() {
  $.id('items').innerHTML = '<div class="load-loading">Loading&hellip;</div>';
};

/**
 * Settings
 */
BR.settingsList = {
  useSSL: [ "Use HTTPS when linking to posts.", true ],
  imageHover: [ "Expand thumbnails on hover.", true ],
  enableKeybinds: [ 'Enable keyboard shortcuts.', true ],
  hideThumbnails: [ 'Hide thumbnails.', false ]
};

BR.getSettings = function() {
  var key, settings, keyRemap;
  
  if (settings = $.getItem('rq-settings')) {
    settings = JSON.parse(settings);
    
  }
  else {
    settings = {};
  }
  
  for (key in BR.settingsList) {
    if (settings[key] === undefined) {
      settings[key] = BR.settingsList[key][1];
    }
  }
  
  if (keyRemap = $.getItem('br-keyremap')) {
    settings.keyRemap = JSON.parse(keyRemap);
  }
  
  return settings;
};

BR.saveSettings = function(settings, keyMap) {
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
    $.setItem('br-keyremap', json);
  }
  else {
    $.removeItem('br-keyremap');
  }
};

BR.onSaveSettingsClick = function() {
  var i, el, settings, panel, opts, menu, needRefresh, nodes, keyRemap, keyMap,
    fromCode, toCode, css;
  
  settings = {};
  
  panel = BR.getPanel('settings');
  
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
  
  if (settings.useSSL != BR.settings.useSSL) {
    BR.protocol = settings.useSSL ? 'https://' : 'http://';
    needRefresh = true;
  }
  
  if (settings.customMenu != BR.settings.customMenu) {
    BR.resetCustomMenu();
    BR.applyCustomMenu(settings.customMenu);
  }
  
  if (settings.customCSS != BR.settings.customCSS) {
    BR.applyCustomCSS(css);
  }
  
  if (settings.hideThumbnails != BR.settings.hideThumbnails) {
    $[settings.hideThumbnails ? 'addClass' : 'removeClass'](
      document.body, 'no-thumbnails'
    );
  }
  
  BR.settings = settings;
  
  BR.saveSettings(settings, keyMap);
  
  if (needRefresh) {
    BR.refreshRequests();
  }
  
  BR.closeSettings();
};

BR.resetCustomMenu = function() {
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

BR.applyCustomMenu = function(str) {
  var i, slug, el, nav, boards, more, nodes, hash;
  
  if (!str) {
    return;
  }
  
  boards = str.split(',');
  
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

BR.showSettings = function() {
  var label, key, html;
  
  if (BR.getPanel('settings')) {
    BR.closeSettings();
    return;
  }
  
  html = '<div class="panel-content"><h4>Options</h4>'
    + '<ul class="options-set">';
  
  for (key in BR.settingsList) {
    html += '<li><span data-cmd="toggle-checked"'
      + ' data-key="' + key + '"'
      + ' class="option-item button clickbox'
      + (BR.settings[key] ? ' checked">✔' : '">')
      + '</span> ' + BR.settingsList[key][0] + '</li>';
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
    + (BR.settings.customMenu ? BR.settings.customMenu : '')
    + '" placeholder="example: a,jp,tg" type="text"></li>'
    + '</ul>';
  
  html += '</ul><h4>Custom CSS</h4><textarea id="custom-css-field" class="options-set">'
    + (BR.settings.customCSS ? $.escapeHTML(BR.settings.customCSS) : '')
    + '</textarea><ul><li class="center"><a href="/login?action=do_logout" class="button btn-logout">Logout</a></li></ul>';
    
  html += '</div><div class="panel-footer">'
      + '<span data-cmd="save-settings" class="button">Save</span>'
    + '</div>';
  
  BR.showPanel('settings', html, 'Settings');
};

BR.closeSettings = function() {
  BR.closePanel('settings');
};

// ---

BR.appendEditLink = function(el, banId) {
  var cnt = $.cls('edit-link-cnt', el)[0];
  
  if (!cnt || !banId || $.cls('edit-link', cnt)[0]) {
    return;
  }
  
  el = $.el('a');
  el.href = '//team.4chan.org/bans?action=update&id=' + banId;
  el.target = '_blank';
  el.className = 'edit-link';
  el.textContent = 'Ban Details';
  
  cnt.appendChild(el);
};

BR.disableRequest = function(el, skipTitle) {
  if ($.hasClass(el, 'disabled')) {
    return;
  }
  
  $.addClass(el, 'disabled');
  BR.currentCount--;
  
  if (!skipTitle) {
    BR.setPageTitle(BR.board, BR.currentCount);
  }
};

/**
 * Focusing
 */
BR.onFocusClick = function(el) {
  BR.focusRequest(el.parentNode);
};

BR.focusNext = function() {
  var el, node;
  
  node = null;
  
  if (el = BR.focusedRequest) {
    while (el = el.nextElementSibling) {
      if (!$.hasClass(el, 'disabled')) {
        node = el;
        break;
      }
    }
  }
  
  BR.focusRequest(node || $.id('br-list').firstElementChild);
};

BR.focusPrevious = function() {
  var el, node;
  
  node = null;
  
  if (el = BR.focusedRequest) {
    while (el = el.previousElementSibling) {
      if (!$.hasClass(el, 'disabled')) {
        node = el;
        break;
      }
    }
    
    BR.focusRequest(node);
  }
};

BR.focusRequest = function(el) {
  var rect, focusMargin;
  
  focusMargin = 10;
  
  ImageHover.hide();
  
  if (BR.focusedRequest) {
    $.removeClass(BR.focusedRequest, 'focused');
  }
  
  if (!el) {
    BR.focusedRequest = null;
    return;
  }
  
  $.addClass(el, 'focused');
  
  rect = el.getBoundingClientRect();
  
  if (rect.top < 0 || rect.bottom > $.docEl.clientHeight) {
    window.scrollBy(0, rect.top - focusMargin);
  }
  
  BR.focusedRequest = el;
};

/**
 * Ban requests
 */
BR.showRequests = function(board) {
  var query;
  
  if (BR.xhr.get) {
    BR.xhr.get.abort();
    BR.xhr.get = null;
  }
  
  query = '?action=get_ban_requests';
  
  if (board) {
    query += '&board=' + board;
  }
  
  BR.showLoadSpinner();
  
  BR.xhr.get = $.xhr('GET', query, {
    onload: BR.onRequestsLoaded,
    onerror: BR.onXhrError,
    board: board
  });
};

BR.onRequestsLoaded = function() {
  var resp;
  
  resp = BR.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    BR.currentCount = resp.data.requests.length;
    BR.setPageTitle(this.board, BR.currentCount);
    BR.buildRequests(resp.data);
  }
  else {
    $.id('items').textContent = '';
    
    if (resp.status === 'error') {
      BR.showLoadError(resp.message);
    }
  }
};

BR.format_template_list = function(template_ids, template_data) {
  let lines = [];
  
  for (let id of template_ids) {
    if (template_data[id]) {
      lines.push(template_data[id]['name']);
    }
  }
  
  return lines.join("\n");
};

BR.buildRequests = function(data) {
  var requests, templates, template, i, request, post, html, tripcode, thumb,
    imgWidth, imgHeight, thumbSize, ratio, op, post_link, warn_req, can_warn,
    sub, ban_summary, is_warning, is_global, quick_amend_html, template_html,
    ban_history, live_link, com, tid;
  
  requests = data.requests;
  templates = data.templates;
  
  BR.updateMenuCounts(data.counts);
  
  if (!requests[0]) {
    return BR.showLoadEmpty();
  }
  
  thumbSize = 130;
  
  html = '<table id="ban-requests"><thead>\
<tr>\
  <th class="ban-request-board">Board</th>\
  <th class="ban-request-author">Author</th>\
  <th class="ban-request-post">Post</th>\
  <th class="ban-request-template">Template</th>\
  <th class="ban-request-controls"></th>\
</tr>\
</thead>\
<tbody id="br-list">';
  
  for (i = 0; request = requests[i]; ++i) {
    post = JSON.parse(request.post);
    
    if (post.trip) {
      tripcode = '<span class="post-trip">' + post.trip + '</span>';
    }
    else {
      tripcode = '';
    }
    
    if (!post.resto) {
      op = '<span class="post-no-op"> (OP)</span>';
      tid = post.no;
      live_link = '';
    }
    else {
      op = '';
      tid = post.resto;
      live_link = ' <a data-tip="Live Thread" target="_blank" href="' + this.linkToPost(request.board, post.resto, 0) + '">Live</a>';
    }
    
    if (post.thumb) {
      if (request.board === 'f') {
        thumb = '<a target="_blank" href="'
          + this.linkToFlash(post.filename) + '">'
          + '<div class="post-swf" title="' + post.filename + '.swf">'
          + post.filename + '</div></a>';
      }
      else {
        imgWidth = post.tn_w;
        imgHeight = post.tn_h;
        
        if (imgWidth > thumbSize) {
          ratio = thumbSize / imgWidth;
          imgWidth = thumbSize;
          imgHeight = imgHeight * ratio;
        }
        if (imgHeight > thumbSize) {
          ratio = thumbSize / imgHeight;
          imgHeight = thumbSize;
          imgWidth = imgWidth * ratio;
        }
        
        thumb = '<a class="post-thumb-link" target="_blank" href="'
          + this.linkToRetainedImage(request.board, post.thumb, post.ext) + '">'
          + '<img alt="Thumbnail" class="post-thumb" width="'
            + imgWidth + '" height="' + imgHeight + '" src="'
            + this.linkToRetainedThumb(request.board, post.thumb)
            + '" loading="lazy" data-width="' + post.w + '" alt="">'
          + '</a>';
      }
    }
    else if (post.raw_md5) {
      thumb = '<div><img class="post-thumb" src="//'
        + this.fileDeleted + '" alt="File deleted"></div>';
    }
    else {
      thumb = '';
    }
    
    if (template = templates[request.ban_template]) {
      if (request.warn_req === '1' && !template.is_warn) {
        warn_req = ' [Warn]';
      }
      else {
        warn_req = '';
      }
      
      template_html = '<p data-tip="Requested by '
        + request.janitor + '" class="br-tpl-name">'
        + template.name
        + warn_req + '</p><p class="br-tpl-reason">'
        + template.reason + '</p>';
    }
    else {
      warn_req = '';
      template_html = '';
    }
    
    if (template) {
      is_warning = request.warn_req === '1' || template.is_warn;
      is_global = template.is_global;
      can_warn = !template.global1 && !is_warning;
    }
    else {
      is_warning = is_global = can_warn = false;
    }
    
    if (request.link) {
      post_link = '<a target="_blank" href="' + BR.derefer(request.link) + '">Archive</a>';
    }
    else {
      post_link = '';
    }
    
    if (post.rel_sub) {
      sub = '<div class="post-subject post-rel-sub">' + post.rel_sub + '</div>';
    }
    else if (post.sub) {
      sub = '<div class="post-subject">' + post.sub + '</div>';
    }
    else {
      sub = '';
    }
    
    if (post.com !== undefined) {
      com = post.com.replace(/(&gt;&gt;[0-9]+)/g, '<a href="#" class="quotelink">$1</a>');
    }
    else {
      com = '';
    }
    
    if (request.ban_summary) {
      ban_summary = '<div class="ban-summary"><a data-tip data-tip-cb="BR.showBanTip" '
        + 'href="https://team.4chan.org/bans?action=search&amp;ip='
        + request.host + '" target="_blank">'
        + request.ban_summary.total + ' ban'
          + $.pluralise(request.ban_summary.total) + ' for this IP</a>'
          + '<script type="application/json">'
            + JSON.stringify(request.ban_summary) + '</script></div>';
    }
    else {
      ban_summary = '';
    }
    
    quick_amend_html = '';
    
    if (can_warn) {
      quick_amend_html += '<div class="button-tree-wrap"><span id="js-as-w-btn-'
        + request.id + '" data-cmd="accept" data-as-warning="'
        + request.ban_template
        + '" data-tip="Accept as a Warning" class="button br-accept-btn">Warn &check;</span></div>';
    }
    
    if (is_warning) {
      quick_amend_html += '<div class="button-tree-wrap"><span data-cmd="accept" data-as-ban="'
        + request.ban_template
        + '" data-tip="Accept as a 1 day Global Ban" class="button br-accept-btn btn-as-deny">Ban &check;</span></div>';
    }
    
    if (!is_global && !is_warning) {
      quick_amend_html += '<div class="button-tree-wrap"><span data-cmd="accept" data-as-global="'
        + request.ban_template + '" data-days="' + template.days
        + '" data-tip="Accept as a Global Ban" class="button br-accept-btn btn-as-deny">Global &check;</span></div>';
    }
    
    if (request.ban_template == BR.TPL_EVASION_ID) {
      quick_amend_html += '<div class="button-tree-wrap"><span data-cmd="accept" data-as-ban="'
      + BR.TPL_EVASION_SHORT_ID
      + '" data-tip="Accept as a Ban Evasion [Short] ban" class="button br-accept-btn btn-as-other">Short &check;</span></div>';
    }
    
    ban_history = request.ban_history;
    
    html += '<tr id="ban-request-' + request.id + '" class="ban-request">'
      + '<td data-cmd="focus" class="ban-request-board">/' + request.board + '/</td>'
      + '<td class="ban-request-author">'
        + '<span class="post-name">' + (post.name || '') + tripcode + '</span>'
        + (request.pass_user ? ('<div class="user-pass">4chan Pass User</div>') : '')
        + '<div class="user-net-cnt"><ul class="dotted-list">'
            + '<li><span class="user-host">' + request.host
              + '</span><span class="user-country">(' + ($.escapeHTML(request.geo_loc) || 'XX') + ')</span></li>'
            + (request.reverse !== request.host ? ('<li>' + request.reverse + '</li>') : '')
            + (request.asn_name ? ('<li>' + $.escapeHTML(request.asn_name) + '</li>') : '')
          + '</ul>'
          + '<div class="button-tree-wrap">Search posts by '
            + '<a target="_blank" href="https://team.4chan.org/search#{&quot;ip&quot;:&quot;' + request.host + '&quot;}">IP</a> '
            + '<a target="_blank" href="https://team.4chan.org/search#{&quot;password&quot;:&quot;' + request.pwd + '&quot;}">Pwd</a>'
            + (request.pass_user ? (' <a target="_blank" href="https://team.4chan.org/search#{&quot;pass_ref&quot;:&quot;!' + request.id + '&quot;}">4chan Pass</a>') : '')
          + '</div>'
        + '</div>'
        + (ban_history ? (
          '<div class="user-bans-cnt">'
          + '<div><span data-tip="All time bans or warnings: ' + ban_history.total + '">Past 12 months ban history</span></div>'
          + '<ul class="dotted-list">'
            + '<li><b>' + ban_history.recent_bans + '</b> ban' + $.pluralise(ban_history.recent_bans)
              + (ban_history.active_bans.length ? (' (<b data-tip="' + BR.format_template_list(ban_history.active_bans, templates) + '" class="wot str-deny">' + ban_history.active_bans.length + ' active</b>)') : '')
              + '</li>'
            + '<li><b>' + ban_history.recent_warns + '</b> warning' + $.pluralise(ban_history.recent_warns)
              + (ban_history.active_warns.length ? (' (<b data-tip="' + BR.format_template_list(ban_history.active_warns, templates) + '" class="wot str-deny">' + ban_history.active_warns.length + ' today</b>)') : '')
              + '</li>'
            + '<li><b>' + ban_history.recent_days + '</b> day' + $.pluralise(ban_history.recent_days) + ' spent banned</li>'
          + '</ul>'
          + '<div class="button-tree-wrap">Search bans by '
            + '<a target="_blank" href="https://team.4chan.org/bans?action=search&amp;ip=' + request.host + '">IP</a> '
            + (request.pass_user ? (' <a target="_blank" href="https://team.4chan.org/bans?action=search&amp;pass_ref=!' + request.id + '">4chan Pass</a>') : '')
          + '</div>'
        + '</div>'
        ) : '')
      + '</td>'
      + '<td data-board="' + request.board + '" data-tid="' + tid + '" class="ban-request-post"><span class="post-no"><span class="as-iblk">No.' + post.no + '</span>' + op + '</span>'
        + '<span class="post-links">' + post_link + live_link + '</span>'
        + sub
        + (post.ext ? ('<div class="post-filename"><a target="_blank" href="https://team.4chan.org/search#{&quot;md5&quot;:&quot;' + post.md5 + '&quot;}">Search MD5</a> <span class="sec-txt">&ndash;</span> <span>' + post.filename + post.ext + '</span></div>') : '')
        + '<div class="post-content">' + thumb + com + '</div>'
      + '</td>'
      + '<td class="ban-request-template" data-tpl="'
        + request.ban_template + '"' + (warn_req ? 'data-warn' : '') + '>'
        + template_html
      + '</td>'
      + '<td class="ban-request-controls">'
        + '<span data-cmd="accept" class="button br-accept-btn">Accept &check;</span>'
          + quick_amend_html
        + '<span data-cmd="deny" class="button br-deny-btn">Deny &cross;</span>'
        + '<span data-cmd="amend" class="button br-other-btn">Amend&hellip;</span>'
        + '<div class="edit-link-cnt"></div>'
      + '</td>'
      + '</tr>';
  }
  
  $.id('items').innerHTML = html + '</tbody></table>';
  
  let sb = $.id('search-box').value;
  
  if (sb !== '') {
    BR.applySearch(sb);
  }
  
  document.dispatchEvent(new CustomEvent('4chanBanRequestsReady'));
};

BR.onXhrError = function() {
  BR.error();
};

BR.pruneBanRequests = function(live_ids) {
  var rid, nodes;
  
  nodes = $.cls('ban-request');
  
  for (let el of nodes) {
    if (rid = BR.getRequestId(el)) {
      if (live_ids.indexOf(+rid) === -1) {
        BR.disableRequest(el, true);
      }
    }
  }
  
  BR.setPageTitle(BR.board, BR.currentCount);
};

/**
 * Amend
 */
BR.onAmendClick = function(button) {
  var el;
  
  if (el = button.parentNode.parentNode) {
    BR.showAmend(el);
  }
};

BR.amendFocused = function() {
  if (BR.focusedRequest) {
    ImageHover.hide();
    BR.showAmend(BR.focusedRequest);
  }
};

BR.showAmend = function(el) {
  var query, html, board, tplEl, tpl;
  
  BR.closeAmend();
  
  html = '<div class="panel-content" tabindex="-1" id="amend-br">'
      + '<div class="spinner"></div>'
    + '</div>';
  
  tplEl = $.cls('ban-request-template', el)[0];
  
  BR.showPanel('amend', html, 'Change Template', {
    'data-close-cb': 'Amend',
    'data-br-id': BR.getRequestId(el)
  });
  
  board = $.cls('ban-request-board', el)[0].textContent.slice(1, -1);
  
  tpl = tplEl.getAttribute('data-tpl');
  
  query = '?action=get_templates&board=' + board;
  
  BR.xhr.amend = $.xhr('GET', query, {
    onload: BR.onAmendLoaded,
    onerror: BR.onAmendError,
    tpl: tpl,
    warn_req: tplEl.hasAttribute('data-warn')
  });
};

BR.closeAmend = function() {
  var el;
  
  if (BR.xhr.amend) {
    BR.xhr.amend.abort();
    BR.xhr.amend = null;
  }
  
  if (el = $.id('amend-form')) {
    $.off(el, 'submit', BR.onAmendSubmit);
  }
  
  if (el = $.id('amend-selector')) {
    $.off(el, 'change', BR.onAmendChange);
  }
  
  if (el = $.id('amend-auto')) {
    $.off(el, 'keyup', BR.onAmendKeyUp);
  }
  
  if (el = $.id('amend-warn')) {
    $.off(el, 'change', BR.onAmendLengthPresetChange);
  }
  
  if (el = $.id('amend-perm')) {
    $.off(el, 'change', BR.onAmendLengthPresetChange);
  }
  
  BR.closePanel('amend');
};

BR.onAmendSubmit = function(e) {
  e.preventDefault();
  e.stopPropagation();
  BR.onConfirmAmendClick($.id('amend-accept'));
};

BR.onAmendChange = function() {
  var tpl;
  
  tpl = BR.templateCache[this.options[this.selectedIndex].value];
  
  $.id('amend-reason').textContent = tpl.publicreason;
  $.id('amend-length').value = tpl.days;
  
  $.id('amend-warn').checked = tpl.days === '0';
  $.id('amend-perm').checked = tpl.days === '-1';
  
  $.id('amend-length').disabled =
    $.id('amend-warn').checked || $.id('amend-perm').checked;
  
  $.id('amend-global').checked = tpl.bantype === 'global';
};

BR.onAmendLengthPresetChange = function() {
  var i, nodes, el, disabled;
  
  nodes = $.cls('length-lbl');
  
  disabled = this.checked;
  
  for (i = 0; el = nodes[i]; ++i) {
    if (el.firstElementChild !== this) {
      el.firstElementChild.checked = false;
    }
  }
  
  $.id('amend-length').disabled = disabled;
};

BR.onAmendKeyUp = function(e) {
  var esc, nodes, cnt, el, i, query, sel, w, words;
  
  if (e.keyCode == 27) {
    this.blur();
    return;
  }
  
  query = $.id('amend-auto').value;
  
  esc = ['/', '.', '*', '+', '?', '(', ')', '[', ']', '{', '}', '\\' ].join('|\\');
  esc = new RegExp('(\\' + esc + ')', 'g');
  
  words = query.split(/ +/);
  
  query = '';
  
  for (i = 0; w = words[i]; ++i) {
    query += '(?=[\\s\\S]*\\b' + w.replace(esc, '\\$1') + ')';
  }
  
  query = new RegExp(query, 'i');
  
  cnt = $.id('amend-selector');
  nodes = cnt.children;
  
  sel = false;
  
  for (i = 0; el = nodes[i]; ++i) {
    if (query.test(el.textContent)) {
      sel = i;
      break;
    }
  }
  
  if (sel !== false) {
    cnt.selectedIndex = sel;
    BR.onAmendChange.call(cnt);
  }
};

BR.formatBanLength = function(days) {
  days = +days;
  
  if (days > 1) {
    days = days + ' days';
  }
  else if (days < 0) {
    days = 'Permanent';
  }
  else if (days == 1) {
    days = '1 day';
  }
  else {
    days = 'Warning';
  }
  
  return days;
};

BR.onAmendLoaded = function() {
  var resp;
  
  BR.xhr.amend = null;
  
  resp = BR.parseResponse(this.responseText);
  
  if (resp.status === 'success') {
    BR.buildAmend(resp.data, this.tpl, this.warn_req);
  }
  else {
    BR.showPanelError('amend', resp.message);
  }
};

BR.onAmendError = function() {
  BR.showPanelError('amend', 'Error retrieving templates');
  BR.xhr.amend = null;
};

BR.onConfirmAmendClick = function(button) {
  var id, tpl, length, global, public_reason, private_reason;
  
  id = button.parentNode.parentNode.parentNode.getAttribute('data-br-id');
  
  tpl = $.id('amend-selector');
  tpl = tpl.options[tpl.selectedIndex].value;
  
  if ($.id('amend-warn').checked) {
    length = 0;
  }
  else if ($.id('amend-perm').checked) {
    length = -1;
  }
  else {
    length = +$.id('amend-length').value;
  }
  
  global = $.id('amend-global').checked;
  
  public_reason = $.id('amend-reason').value;
  
  private_reason = $.id('amend-reason-pv').value;
  
  BR.closePanel('amend');
  
  $.addClass($.id('ban-request-' + id), 'processing');
  BR.addDelayedJob(BR.acceptRequest, id, tpl, length, global, public_reason, private_reason);
};

BR.buildAmend = function(data, template_id, warn_req) {
  var i, t, html, tpl;
  
  if (!data.length) {
    BR.showPanelError('amend', 'No templates found.');
    return;
  }
  
  BR.templateCache = {};
  
  html = '<h4>Template:</h4>'
    + '<form id="amend-form" action="?"><button class="void" type="submit"></button>'
    + '<input tabindex="1" autofocus="autofocus" type="text" class="amend-txt-field" id="amend-auto" placeholder="Autocomplete...">'
    + '<select tabindex="2" id="amend-selector">';
  
  for (i = 0; t = data[i]; ++i) {
    BR.templateCache[t.no] = t;
    
    html += '<option '
      + (t.no == template_id ? 'selected="selected "' : '')
      + 'value="' + t.no + '">'
      + t.name
    + '</option>';
  }
  
  html += '</select>';
  
  tpl = BR.templateCache[template_id];
  
  html += '<h4>Public Reason:</h4><textarea id="amend-reason" name="public_reason">'
    + tpl.publicreason
  + '</textarea>';
  
  html += '<h4>Private Reason:</h4><input tabindex="3" class="amend-txt-field" id="amend-reason-pv" name="private_reason" type="text">';
  
  html += '<h4>Length:</h4><input type="text" id="amend-length" tabindex="4" value="'
    + tpl.days + '"> day(s)';
  
  html += '<label class="length-lbl"><input id="amend-warn" tabindex="5" type="checkbox" name="warn"'
    + (warn_req || tpl.days === '0' ? ' checked' : '')
    + '> Warn</label>';
  
  html += '<label class="length-lbl"><input id="amend-perm" tabindex="6" type="checkbox" name="perm"'
    + (!warn_req && tpl.days === '-1' ? ' checked' : '')
    + '> Permanent</label>';
  
  html += '<h4><label class="global-lbl">Global: <input id="amend-global" tabindex="7" type="checkbox" name="global"'
    + (tpl.bantype === 'global' ? ' checked' : '')
    + '></label></h4>';
  
  html += '</form><div class="panel-footer">'
    + '<span tabindex="8" id="amend-accept" class="button br-accept-btn" data-cmd="confirm-amend">Amend &amp; Accept &check;</span>'
    + '</div>';
  
  $.id('amend-br').innerHTML = html;
  
  $.id('amend-length').disabled =
    $.id('amend-warn').checked || $.id('amend-perm').checked;
  
  $.on($.id('amend-form'), 'submit', BR.onAmendSubmit);
  $.on($.id('amend-selector'), 'change', BR.onAmendChange);
  $.on($.id('amend-auto'), 'keyup', BR.onAmendKeyUp);
  $.on($.id('amend-warn'), 'change', BR.onAmendLengthPresetChange);
  $.on($.id('amend-perm'), 'change', BR.onAmendLengthPresetChange);
  $.id('amend-auto').focus();
};

/**
 * Search by IP
 */

BR.searchFocused = function() {
  if (BR.focusedRequest) {
    ImageHover.hide();
    window.open($.cls('post-host', BR.focusedRequest)[0].href);
  }
};

/**
 * Delayed jobs
 */
BR.delayedJobTimeout = 3000;

BR.addDelayedJob = function() {
  var args;
  
  if (BR.delayedJob) {
    BR.runDelayedJob();
  }
  
  args = Array.prototype.slice.call(arguments);
  
  BR.delayedJob = {
    fn: args.shift(),
    args: args,
    timeOut: setTimeout(BR.runDelayedJob, BR.delayedJobTimeout)
  };
};

BR.runDelayedJob = function() {
  var job = BR.delayedJob;
  
  clearTimeout(job.timeOut);
  
  job.fn.apply(this, job.args);
  
  BR.delayedJob = null;
};

BR.cancelDelayedJob = function() {
  var args, report;
  
  if (!BR.delayedJob) {
    return;
  }
  
  args = BR.delayedJob.args;
  
  clearTimeout(BR.delayedJob.timeOut);
  
  report = BR.getRequestNode(args[0], args[1]);
  
  $.removeClass(report, 'processing');
  
  if (BR.focusedRequest) {
    BR.focusRequest(report);
  }
  
  BR.delayedJob = null;
};

/**
 * Accept
 */
BR.acceptFocused = function() {
  var uid;
  
  if (BR.focusedRequest) {
    ImageHover.hide();
    
    uid = BR.getRequestId(BR.focusedRequest);
    
    $.addClass(BR.focusedRequest, 'processing');
    
    BR.addDelayedJob(BR.acceptRequest, uid);
    
    BR.focusNext();
  }
};

BR.acceptWarningFocused = function() {
  var uid, el, tpl_id;
  
  if (BR.focusedRequest) {
    ImageHover.hide();
    
    uid = BR.getRequestId(BR.focusedRequest);
    
    el = $.id('js-as-w-btn-' + uid);
    
    if (!el) {
      BR.error("Can't accept this as Warning");
      return;
    }
    
    if (tpl_id = el.getAttribute('data-as-warning')) {
      $.addClass(BR.focusedRequest, 'processing');
      BR.addDelayedJob(BR.acceptRequest, uid, tpl_id, 0);
      BR.focusNext();
    }
  }
};

BR.onAcceptClick = function(button) {
  var request, uid, tpl_id, days;
  
  request = $.parentByCls(button, 'ban-request');
  
  if (uid = BR.getRequestId(request)) {
    if (tpl_id = button.getAttribute('data-as-warning')) {
      BR.addDelayedJob(BR.acceptRequest, uid, tpl_id, 0);
    }
    else if (tpl_id = button.getAttribute('data-as-ban')) {
      BR.addDelayedJob(BR.acceptRequest, uid, tpl_id, 1, true);
    }
    else if (tpl_id = button.getAttribute('data-as-global')) {
      days = +button.getAttribute('data-days');
      
      if (days < 1) {
        console.log('Invalid ban length');
        return;
      }
      
      BR.addDelayedJob(BR.acceptRequest, uid, tpl_id, days, true);
    }
    else {
      BR.addDelayedJob(BR.acceptRequest, uid);
    }
    
    $.addClass(request, 'processing');
  }
};

BR.acceptRequest = function(id, template_id, length, global, public_reason, private_reason) {
  var data = {
    action: 'accept_ban_request',
    id: id,
    '_tkn': $.getToken()
  };
  
  if (template_id) {
    data.amend_tpl = template_id;
    
    if (length !== undefined && length !== null) {
      data.ban_length = length;
    }
    
    if (public_reason !== undefined && public_reason !== '') {
      data.public_reason = public_reason;
    }
    
    if (private_reason !== undefined && private_reason !== '') {
      data.private_reason = private_reason;
    }
    
    if (global !== undefined) {
      data.global = global ? 1 : 0;
    }
  }
  
  $.xhr('POST', '',
    {
      onload: BR.onRequestAccepted,
      onerror: BR.onXhrError,
      rid: id
    },
    data
  );
};

BR.onRequestAccepted = function() {
  var resp, el;
  
  resp = BR.parseResponse(this.responseText);
  
  el = BR.getRequestNode(this.rid);
  $.removeClass(el, 'processing');
  
  if (resp.status === 'success') {
    BR.appendEditLink(el, resp.data.ban_id);
    BR.disableRequest(el);
    
    if (resp.data.request_ids && resp.data.request_ids[0]) {
      BR.pruneBanRequests(resp.data.request_ids);
    }
  }
  else if (resp.code === 404) {
    BR.disableRequest(el);
    BR.error(resp.message);
  }
  else {
    BR.error(resp.message);
  }
};

/**
 * Deny
 */
BR.denyFocused = function() {
  var uid;
  
  if (BR.focusedRequest) {
    ImageHover.hide();
    
    uid = BR.getRequestId(BR.focusedRequest);
    
    $.addClass(BR.focusedRequest, 'processing');
    
    BR.addDelayedJob(BR.denyRequest, uid);
    
    BR.focusNext();
  }
};

BR.onDenyClick = function(button) {
  var request, uid;
  
  request = button.parentNode.parentNode;
  
  if (uid = BR.getRequestId(request)) {
    $.addClass(request, 'processing');
    BR.addDelayedJob(BR.denyRequest, uid);
  }
};

BR.denyRequest = function(id) {
  $.xhr('POST', '',
    {
      onload: BR.onRequestDenied,
      onerror: BR.onXhrError,
      rid: id
    },
    {
      action: 'deny_ban_request',
      id: id,
      '_tkn': $.getToken()
    }
  );
};

BR.onRequestDenied = function() {
  var resp, el;
  
  resp = BR.parseResponse(this.responseText);
  
  el = BR.getRequestNode(this.rid);
  $.removeClass(el, 'processing');
  
  if (resp.status === 'success') {
    BR.disableRequest(el);
    
    if (resp.data.request_ids && resp.data.request_ids[0]) {
      BR.pruneBanRequests(resp.data.request_ids);
    }
  }
  else if (resp.code === 404) {
    BR.disableRequest(el);
    BR.error(resp.message);
  }
  else {
    BR.error(resp.message);
  }
};

/**
 * Image expansion
 */
BR.toggleExpandFocused = function() {
  var thumb;
  
  if ($.id('image-hover')) {
    return ImageHover.hide();
  }
  else if ($.id('swf-preview')) {
    return ImageHover.hideSWF();
  }
  
  if (!BR.focusedRequest) {
    return;
  }
  
  if (thumb = $.cls('post-thumb', BR.focusedRequest)[0]) {
    ImageHover.show(thumb);
  }
  else if (thumb = $.cls('post-swf', BR.focusedRequest)[0]) {
    ImageHover.showSWF(thumb);
  }
};

/**
 * Keybinds
 */
Keybinds.map = {
  // Esc
  27: BR.shiftPanel,
  // Left arrow
  37: BR.focusPrevious,
  // Right arrow
  39: BR.focusNext,
  // A
  65: BR.acceptFocused,
  // D
  68: BR.denyFocused,
  // D
  87: BR.acceptWarningFocused,
  // I
  73: BR.toggleExpandFocused,
  // R
  82: BR.refreshRequests,
  // U
  85: BR.cancelDelayedJob,
  // T
  84: BR.toggleThumbnails,
  // S
  83: BR.searchFocused,
  // C
  67: BR.amendFocused
};

Keybinds.labels = {
  82: [ 'R', 'Refresh page.' ],
  39: [ '&#8594;', 'Focus next request.' ],
  37: [ '&#8592;', 'Focus previous request.' ],
  65: [ 'A', 'Accept request.' ],
  68: [ 'D', 'Deny request.' ],
  87: [ 'W', 'Accept as Warning.' ],
  85: [ 'U', 'Undo last action.' ],
  83: [ 'S', 'Search by IP.' ],
  67: [ 'C', 'Amend request.' ],
  73: [ 'I', 'Expand thumbnail.' ],
  84: [ 'T', 'Toggle thumbails.' ],
  27: [ 'Esc', 'Close panel.' ]
};

BR.closeKeyPrompt = function() {
  $.off(document, 'keydown', Keybinds.resolvePrompt);
  
  if (BR.settings.enableKeybinds) {
    Keybinds.enable();
  }
  
  BR.closePanel('key-prompt');
};

/**
 * Init
 */
BR.init();

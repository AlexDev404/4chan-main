var $L = {
  nws: {"aco":1,"b":1,"bant":1,"d":1,"e":1,"f":1,"gif":1,"h":1,"hc":1,"hm":1,"hr":1,"i":1,"ic":1,"pol":1,"r":1,"r9k":1,"s":1,"s4s":1,"soc":1,"t":1,"trash":1,"u":1,"wg":1,"y":1},
  blue: '4channel.org', red: '4chan.org',
  d: function(b) {
    return $L.nws[b] ? $L.red : $L.blue;
  }
};

/**
 * Keybinds
 */
var Keybinds = {};

Keybinds.init = function(main) {
  this.main = main;
  main.clickCommands['prompt-key'] = Keybinds.showPrompt;
};

Keybinds.add = function(map) {
  var label, code;
  
  for (code in map) {
    this.map[code] = map[code][0];
    if (label = map[code][1]) {
      this.labels[code] = label;
    }
  }
};

Keybinds.remap = function(list, validate) {
  var map, label, labels, funk, key, i, j, k, from, to, nodes;
  
  map = Keybinds.map;
  labels = Keybinds.labels;
  
  if (validate) {
    nodes = $.tag('kbd', Keybinds.main.getPanel('settings'));
  }
  
  for (i = 0; j = list[i]; ++i) {
    from = j[0];
    to = j[1];
    
    key = Keybinds.codeToKey(to);
    
    if (validate) {
      for (k = 0; j = nodes[k]; ++k) {
        if (+j.getAttribute('data-id') != from && j.innerHTML == key) {
          Keybinds.main.error('Fix your keybinds first');
          return false;
        }
      }
    }
    
    if (!key) {
      console.log('Invalid keybind: ' + from + ' -> ' + to);
      continue;
    }
    
    label = labels[from];
    
    if (!label) {
      continue;
    }
    
    if (label[2]) {
      from = label[2];
    }
    
    funk = map[from];
    map[to] = funk;
    delete map[from];
    
    label[0] = key;
    label[2] = to;
  }
  
  return true;
};

Keybinds.showPrompt = function(button) {
  var html;
  
  Keybinds.disable();
  
  html = '<div class="panel-content center">Valid keys are \
<kbd>Esc</kbd>, <kbd>&#8592;</kbd>, <kbd>&#8594;</kbd>, <kbd>A-Z</kbd>\
</div>';
  
  Keybinds.main.showPanel('key-prompt', html, 'Press a key',
    {
      'data-id': button.getAttribute('data-id'),
      'data-close-cb': 'KeyPrompt'
    }
  );
  
  $.on(document, 'keydown', Keybinds.resolvePrompt);
};

Keybinds.codeToKey = function(code) {
  var key = null;
  
  if (code == 8) {
    return null;
  }
  
  if (code == 27) {
    key = 'Esc';
  }
  else if (code == 37) {
    key = '&#8592;';
  }
  else if (code == 39) {
    key = '&#8594;';
  }
  else if (code >= 65 && code <= 90) {
    key = String.fromCharCode(code).toUpperCase();
  }
  
  return key;
};

Keybinds.resolvePrompt = function(e) {
  var key, panel, id, funk, map, labels, kbd;
  
  e.preventDefault();
  e.stopPropagation();
  
  key = Keybinds.codeToKey(e.keyCode);
  
  if (!key) {
    Keybinds.main.error('Invalid key');
    return;
  }
  
  Keybinds.main.hideMessage();
  
  panel = Keybinds.main.getPanel('key-prompt');
  id = +panel.getAttribute('data-id');
  
  kbd = $.qs('kbd[data-id="' + id + '"]', Keybinds.main.getPanel('settings'));
  kbd.innerHTML = key;
  kbd.setAttribute('data-remap', e.keyCode);
  
  $.off(document, 'keydown', Keybinds.resolvePrompt);
  
  Keybinds.enable();
  
  Keybinds.main.closePanel('key-prompt');
};

Keybinds.enable = function() {
  $.on(document, 'keydown', Keybinds.resolve);
};

Keybinds.disable = function() {
  $.off(document, 'keydown', Keybinds.resolve);
};

Keybinds.resolve = function(e) {
  var bind, el = e.target;
  
  if (el.nodeName == 'TEXTAREA' || el.nodeName == 'INPUT') {
    return;
  }
  
  bind = Keybinds.map[e.keyCode];
  
  if (bind && !e.altKey && !e.shiftKey && !e.ctrlKey && !e.metaKey) {
    e.preventDefault();
    e.stopPropagation();
    bind();
  }
};

/**
 * Tooltips
 */
var Tip = {
  node: null,
  timeout: null,
  delay: 300,
  
  init: function() {
    document.addEventListener('mouseover', this.onMouseOver, false);
    document.addEventListener('mouseout', this.onMouseOut, false);
  },
  
  onMouseOver: function(e) {
    var cb, data, t, delay;
    
    t = e.target;
    
    if (Tip.timeout) {
      clearTimeout(Tip.timeout);
      Tip.timeout = null;
    }
    
    Tip.hide();
    
    if (t.hasAttribute('data-tip')) {
      data = null;
      
      if (t.hasAttribute('data-tip-cb')) {
        cb = t.getAttribute('data-tip-cb');
        if (cb.indexOf('.') !== -1) {
          cb = cb.split('.');
          if (window[cb[0]] && (cb = window[cb[0]][cb[1]])) {
            data = cb(t);
          }
        }
        else if (window[cb]) {
          data = window[cb](t);
        }
        if (data === null) {
          return;
        }
      }
      
      if (delay = t.getAttribute('data-tip-delay')) {
        delay = +delay;
      }
      else {
        delay = Tip.delay;
      }
      
      Tip.timeout = setTimeout(Tip.show, delay, t, data);
    }
  },
  
  onMouseOut: function(e) {
    if (Tip.timeout) {
      clearTimeout(Tip.timeout);
      Tip.timeout = null;
    }
    
    Tip.hide();
  },
  
  show: function(t, data, pos) {
    var el, rect, style, left, top, hCls;
    
    rect = t.getBoundingClientRect();
    
    el = document.createElement('div');
    el.id = 'tooltip';
    
    if (data) {
      el.innerHTML = data;
    }
    else {
      el.textContent = t.getAttribute('data-tip');
    }
    
    el.className = 'tip-top';
    
    document.body.appendChild(el);
    
    left = rect.left - (el.offsetWidth - rect.width) / 2;
    
    if (left < 0) {
      left = 5;
      hCls = '-right';
    }
    else if (left + el.offsetWidth > document.documentElement.clientWidth) {
      left = rect.left - el.offsetWidth + rect.width / 2 + 5;
      hCls = '-left';
    }
    else {
      hCls = '';
    }
    
    top = rect.top - el.offsetHeight - 8;
    
    if (top < 0) {
      top = rect.bottom + 5;
      el.className = 'tip-bottom' + hCls;
    }
    else {
      el.className = 'tip-top' + hCls;
    }
    
    style = el.style;
    style.display = 'none';
    style.top = (top + window.pageYOffset) + 'px';
    style.left = left + window.pageXOffset + 'px';
    style.display = '';
    
    Tip.node = el;
  },
  
  showCustom: function(html, posX, posY) {
    var el, rect, style, left, top;
    
    el = document.createElement('div');
    el.className = 'tip-top';
    el.innerHTML = html;
    el.id = 'tooltip';
    document.body.appendChild(el);
    
    left = posX - el.offsetWidth / 2;
    
    if (left < 0) {
      left = posX - 5;
      el.className += '-right';
    }
    else if (left + el.offsetWidth > document.documentElement.clientWidth) {
      left = posX - el.offsetWidth + rect.width + 2;
      el.className += '-left';
    }
    
    top = posY - el.offsetHeight - 5;
    
    style = el.style;
    style.display = 'none';
    style.top = top + 'px';
    style.left = left + 'px';
    style.display = '';
    
    Tip.node = el;
    
    return el;
  },
  
  hide: function() {
    if (Tip.node) {
      document.body.removeChild(Tip.node);
      Tip.node = null;
    }
  }
};

var LazyLoader = {
  nodes: [],
  
  timeout: null,
  
  debounce: 100,
  
  threshold: 350,
  
  placeholder: '/image/pixel.gif',
  
  init: function() {
    window.addEventListener('scroll', this.onScroll, false);
    window.addEventListener('resize', this.onScroll, false);
  },
  
  refreshNodes: function() {
    var i, node, nodes;
    
    this.nodes = [];
    
    nodes = $.cls('post-thumb');
    
    for (i = 0; node = nodes[i]; ++i) {
      this.nodes.push(node);
    }
  },
  
  load: function() {
    var self, i, img, top, min, max, nodes, src;
    
    self = LazyLoader;
    
    nodes = [];
    min = window.pageYOffset - self.threshold;
    max = window.pageYOffset + self.threshold + $.docEl.clientHeight;
    
    for (i = 0; img = self.nodes[i]; ++i) {
      top = img.getBoundingClientRect().top + window.pageYOffset;
      if (top >= min && top <= max) {
        if (src = img.getAttribute('data-src')) {
          img.src = src;
        }
      }
      else {
        nodes.push(img);
      }
    }
    
    self.nodes = nodes;
  },
  
  onScroll: function() {
    clearTimeout(LazyLoader.timeout);
    LazyLoader.timeout = setTimeout(LazyLoader.load, 100);
  }
};

var ImageHover = {
  enabled: false,
  
  timeout: null,
  loadTimeout: null,
  delay: 150,
  margin: 10,
  
  init: function() {
    this.enabled = true;
    document.addEventListener('mouseover', this.onMouseOver, false);
    document.addEventListener('mouseout', this.onMouseOut, false);
  },
  
  disable: function() {
    this.enabled = false;
    document.removeEventListener('mouseover', this.onMouseOver, false);
    document.removeEventListener('mouseout', this.onMouseOut, false);
  },
  
  onMouseOver: function(e) {
    var target = e.target;
    
    if (ImageHover.timeout) {
      clearTimeout(ImageHover.timeout);
      ImageHover.timeout = null;
    }
    
    if ($.hasClass(target, 'post-thumb')) {
      ImageHover.timeout = setTimeout(ImageHover.show, ImageHover.delay, target);
    }
  },
  
  onMouseOut: function(e) {
    var target = e.target;
    
    if (ImageHover.timeout) {
      clearTimeout(ImageHover.timeout);
      ImageHover.timeout = null;
    }
    
    if ($.hasClass(target, 'post-thumb')) {
      ImageHover.hide();
    }
  },

  show: function(thumb) {
    var img, href, ext;
    
    href = thumb.parentNode.getAttribute('href');
    
    if (ext = href.match(/\.(?:webm|pdf)$/)) {
      if (ext[0] == '.webm') {
        ImageHover.showWebm(thumb);
      }
      return;
    }
    
    img = document.createElement('img');
    img.id = 'image-hover';
    img.alt = 'Image';
    img.className = 'fitToScreen';
    img.setAttribute('src', href);
    img.style.display = 'none';
    img.onerror = this.onError;
    
    document.body.appendChild(img);
    this.loadTimeout = ImageHover.checkLoadStart(img, thumb);
  },
  
  showWebm: function(thumb) {
    var el, bounds, limit, width, style;
    
    el = document.createElement('video');
    el.id = 'image-hover';
    el.src = thumb.parentNode.getAttribute('href');
    el.onerror = this.onError;
    el.className = 'fitToScreen';
    el.loop = true;
    
    bounds = thumb.getBoundingClientRect();
    limit = window.innerWidth - bounds.right;
    
    style = el.style;
    
    if (bounds.left >= limit) {
      limit = bounds.left;
      style.left = '0px';
    }
    else {
      style.right = '0px';
    }
    
    if (+thumb.getAttribute('data-width') > limit) {
      style.maxWidth = limit - this.margin + 'px';
    }
    
    document.body.appendChild(el);
    
    el.play();
    el.muted = true;
  },
  
  hide: function() {
    var img;
    
    clearTimeout(this.loadTimeout);
    
    if (img = $.id('image-hover')) {
      if (img.play) {
        img.pause();
      }
      document.body.removeChild(img);
    }
  },
  
  onError: function() {
    window.Feedback && Feedback.error("Couldn't get the image.");
    ImageHover.hide();
  },
  
  onLoadStart: function(img, thumb) {
    var bounds, limit, style;
    
    bounds = thumb.getBoundingClientRect();
    limit = $.docEl.clientWidth - bounds.right;
    
    style = img.style;
    
    if (bounds.left >= limit) {
      limit = bounds.left;
      style.left = '0px';
    }
    else {
      style.right = '0px';
    }
    
    if (img.naturalWidth > limit) {
      style.maxWidth = limit - this.margin + 'px';
    }
    
    style.display = '';
  },

  checkLoadStart: function(img, thumb) {
    if (img.naturalWidth) {
      ImageHover.onLoadStart(img, thumb);
    }
    else {
      return setTimeout(ImageHover.checkLoadStart, 15, img, thumb);
    }
  },

  hideSWF: function() {
    var el;
    
    if (el = $.id('swf-preview')) {
      document.body.removeChild(el);
    }
  },
  
  showSWF: function(thumb) {
    var iframe, href, bounds, limit, style;
    
    href = thumb.parentNode.getAttribute('href');
    
    iframe = document.createElement('iframe');
    iframe.id = 'swf-preview';
    iframe.className = 'fitToScreen';
    iframe.setAttribute('src', href);
    iframe.frameborder = 0;
    iframe.width = 640;
    iframe.height = 480;
    
    bounds = thumb.getBoundingClientRect();
    limit = $.docEl.clientWidth - bounds.right;
    
    style = iframe.style;
    
    if (bounds.left >= limit) {
      limit = bounds.left;
      style.left = '0px';
    }
    else {
      style.right = '0px';
    }
    
    if (iframe.width > limit) {
      style.maxWidth = limit - this.margin + 'px';
    }
    
    document.body.appendChild(iframe);
  }
};


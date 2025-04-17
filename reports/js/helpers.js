'use strict';

var $ = {};

$.id = function(id) {
  return document.getElementById(id);
};

$.cls = function(klass, root) {
  return (root || document).getElementsByClassName(klass);
};

$.byName = function(name) {
  return document.getElementsByName(name);
};

$.tag = function(tag, root) {
  return (root || document).getElementsByTagName(tag);
};

$.el = function(tag) {
  return document.createElement(tag);
};

$.frag = function() {
  return document.createDocumentFragment();
};

$.qs = function(selector, root) {
  return (root || document).querySelector(selector);
};

$.qsa = function(selector, root) {
  return (root || document).querySelectorAll(selector);
};

$.extend = function(destination, source) {
  for (var key in source) {
    destination[key] = source[key];
  }
};

$.escapeHTML = function(str) {
  if (!/["'&<>]/.test(str)) {
    return str;
  }
  
  return str.replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
};

$.parentByCls = function(el, klass) {
  var root = $.docEl, orig = el;
  
  while (el !== root) {
   if ($.hasClass(el, klass)) {
      break;
    }
    
    el = el.parentNode;
  }
  
  if (orig !== el) {
    return el;
  }
  
  return null;
};

if (!document.documentElement.classList) {
  $.hasClass = function(el, klass) {
    return (' ' + el.className + ' ').indexOf(' ' + klass + ' ') != -1;
  };
  
  $.addClass = function(el, klass) {
    el.className = (el.className === '') ? klass : el.className + ' ' + klass;
  };
  
  $.removeClass = function(el, klass) {
    el.className = (' ' + el.className + ' ').replace(' ' + klass + ' ', '');
  };
}
else {
  $.hasClass = function(el, klass) {
    return el.classList.contains(klass);
  };
  
  $.addClass = function(el, klass) {
    el.classList.add(klass);
  };
  
  $.removeClass = function(el, klass) {
    el.classList.remove(klass);
  };
}

$.on = function(n, e, h) {
  n.addEventListener(e, h, false);
};

$.off = function(n, e, h) {
  n.removeEventListener(e, h, false);
};

$.xhr = function(method, url, callbacks, data) {
  var key, xhr, form;
  
  xhr = new XMLHttpRequest();
  
  xhr.open(method, url, true);
  
  if (callbacks) {
    for (key in callbacks) {
      xhr[key] = callbacks[key];
    }
  }
  
  if (data) {
    if (typeof data === 'string') {
      xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    }
    else {
      form = new FormData();
      for (key in data) {
        form.append(key, data[key]);
      }
      data = form;
    }
  }
  else {
    data = null;
  }
  
  xhr.send(data);
  
  return xhr;
};

$.getItem = function(key) {
  return localStorage.getItem(key);
};

$.setItem = function(key, value) {
  return localStorage.setItem(key, value);
};

$.removeItem = function(key) {
  return localStorage.removeItem(key);
};

$.getCookie = function(name) {
  var i, c, ca, key;
  
  key = name + '=';
  ca = document.cookie.split(';');
  
  for (i = 0; c = ca[i]; ++i) {
    while (c.charAt(0) == ' ') {
      c = c.substring(1, c.length);
    }
    if (c.indexOf(key) === 0) {
      return decodeURIComponent(c.substring(key.length, c.length));
    }
  }
  
  return null;
};

$.getToken = function() {
  return document.body.getAttribute('data-tkn');
};

$.agoShort = function(timestamp) {
  var delta, count, count2, head, tail, ago;
  
  delta = Date.now() / 1000 - timestamp;
  
  if (delta < 60) {
    return 'just now';
  }
  
  if (delta < 3600) {
    count = 0 | (delta / 60);
    
    if (count > 1) {
      return count + 'm ago';
    }
    else {
      return '1m ago';
    }
  }
  
  if (delta < 86400) {
    count = 0 | (delta / 3600);
    
    count2 = 0 | (delta / 60 - count * 60);
    
    if (count2 > 1 && count2 > 30) {
      count++;
    }
    
    if (count > 1) {
      head = count + 'h';
    }
    else {
      head = '1h';
    }
    
    return head + ' ago';
  }
  
  count = 0 | (delta / 86400);
  
  if (count > 1) {
    head = count + 'd';
  }
  else {
    head = '1d';
  }
  
  count2 = 0 | (delta / 3600 - count * 24);
  
  if (count2 > 1 && count2 > 12) {
    count++;
  }
  
  return head + ' ago';
};

$.ago = function(timestamp, short) {
  var delta, count, count2, head, tail, ago;
  
  delta = Date.now() / 1000 - timestamp;
  
  if (delta < 1) {
    return 'moments ago';
  }
  
  if (delta < 60) {
    return (0 | delta) + ' seconds ago';
  }
  
  if (delta < 3600) {
    count = 0 | (delta / 60);
    
    if (count > 1) {
      return count + ' minutes ago';
    }
    else {
      return 'one minute ago';
    }
  }
  
  if (delta < 86400) {
    count = 0 | (delta / 3600);
    
    count2 = 0 | (delta / 60 - count * 60);
    
    tail = '';
    
    if (count2 > 1) {
      if (short) {
        if (count2 > 30) {
          count++;
        }
      }
      else {
        tail = ' and ' + count2 + ' minutes';
      }
    }
    
    if (count > 1) {
      head = count + ' hours';
    }
    else {
      head = 'one hour';
    }
    
    return head + tail + ' ago';
  }
  
  count = 0 | (delta / 86400);
  
  if (count > 1) {
    head = count + ' days';
  }
  else {
    head = 'one day';
  }
  
  count2 = 0 | (delta / 3600 - count * 24);
  
  tail = '';
  
  if (count2 > 1) {
    if (short) {
      if (count2 > 12) {
        count++;
      }
    }
    else {
      tail = ' and ' + count2 + ' hours';
    }
  }
  
  return head + tail + ' ago';
};

$.capitalise = function(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
};

$.pluralise = function(count, s, p) {
  return count === 1 ? (s || '') : (p || 's');
};

$.prettyBytes = function(bytes) {
  var size;
  
  if (bytes >= 1048576) {
    size = ((0 | (bytes / 1048576 * 100 + 0.5)) / 100) + ' MB';
  }
  else if (bytes > 1024) {
    size = (0 | (bytes / 1024 + 0.5)) + ' KB';
  }
  else {
    size = bytes + ' B';
  }
  
  return size;
};

$.hidden = 'hidden';
$.visibilitychange = 'visibilitychange';

if (typeof document.hidden === 'undefined') {
  if ('mozHidden' in document) {
    $.hidden = 'mozHidden';
    $.visibilitychange = 'mozvisibilitychange';
  }
  else if ('webkitHidden' in document) {
    $.hidden = 'webkitHidden';
    $.visibilitychange = 'webkitvisibilitychange';
  }
  else if ('msHidden' in document) {
    $.hidden = 'msHidden';
    $.visibilitychange = 'msvisibilitychange';
  }
}

$.docEl = document.documentElement;

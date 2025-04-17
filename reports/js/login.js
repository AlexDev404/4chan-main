var APP = {
  init: function() {
    this.xhr = null;
    this.xhr2 = null;
    
    this.authedChan = false;
    this.authedChannel = false;
    
    this.channelId = 1;
    
    this.isChannelFrame = false;
    
    this.channelFrame = null;
    
    this.channelFrameReady = false;
    
    APP.isChannelPage = $.docEl.hasAttribute('data-channelpage');
    APP.isChannelFrame = $.docEl.hasAttribute('data-channelframe');
    
    if (APP.isChannelFrame) {
      $.on(window, 'message', APP.onChanMessage);
    }
    else {
      $.on(window, 'message', APP.onChannelMessage);
    }
    
    $.on(document, 'DOMContentLoaded', APP.run);
  },
  
  run: function() {
    var el;
    
    $.off(document, 'DOMContentLoaded', APP.run);
    
    if (APP.isChannelFrame) {
      window.parent.postMessage(
        { ready: !!$.getCookie('tptkn') },
        'https://reports.4chan.org'
      );
    }
    else {
      APP.channelFrame = $.id('js-channel-frame');
      
      if (el = $.id('login-form')) {
        $.on(el, 'submit', APP.onLoginSubmit);
      }
      
      if (el = $.id('js-logout-btn')) {
        $.on(el, 'click', APP.onLogoutClick);
      }
      
      if (APP.isChannelPage && location.hash === '#fb') {
        Feedback.notify('Please login again on <b>4channel.org</b>', 0);
      }
    }
    
    $.removeClass(document.body, 'has-backdrop');
  },
  
  // From 4chan to 4channel
  onChanMessage: function(e) {
    if (e.origin !== 'https://reports.4chan.org') {
      return;
    }
    
    if (e.data.user) {
      APP.auth(e.data.user, e.data.pwd, e.data.otp, e.data.cid);
    }
  },
  
  // From 4channel to 4chan
  onChannelMessage: function(e) {
    if (e.origin !== 'https://reports.4channel.org') {
      return;
    }
    
    if (e.data.error) {
      if (e.data.cid != APP.channelID) {
        return;
      }
      
      APP.clearState();
      Feedback.error(e.data.error);
    }
    else if (e.data.ready !== undefined) {
      APP.channelFrameReady = e.data.ready;
    }
    else if (e.data.success) {
      APP.authedChannel = true;
      APP.checkAuthSuccess();
    }
  },
  
  auth: function(user, pwd, otp, cid) {
    var params, host;
    
    params = {
      userlogin: user,
      passlogin: pwd,
      otp: otp,
      csrf: document.body.getAttribute('data-tkn'),
      xhr: 1,
      action: 'do_login'
    };
    
    if (APP.isChannelPage || APP.isChannelFrame) {
      host = '4channel.org';
    }
    else {
      host = '4chan.org';
    }
    
    APP.xhr = $.xhr('POST', 'https://reports.' + host + '/login',
      {
        onload: APP.onLoginLoad,
        onerror: APP.onLoginError,
        withCredentials: true,
        cid: cid
      },
      params
    );
  },
  
  onLogoutClick: function(e) {
    var cbs = {
      onload: APP.onLogoutLoad,
      onerror: APP.onLogoutError,
      withCredentials: true,
    };
    
    e.preventDefault();
    
    $.addClass(document.body, 'has-backdrop');
    Feedback.showMessage('Please wait…', 'notify', false);
    
    if (!APP.isChannelPage) {
      cbs._d = 'chan';
      APP.xhr = $.xhr('GET', 'https://reports.4chan.org/login?action=do_logout', cbs);
    }
    
    cbs._d = 'channel';
    APP.xhr2 = $.xhr('GET', 'https://reports.4channel.org/login?action=do_logout', cbs);
  },
  
  onLogoutLoad: function(e) {
    if (this._d === 'chan') {
      APP.xhr = null;
    }
    else {
      APP.xhr2 = null;
    }
    
    if (APP.xhr === null && APP.xhr2 === null) {
      window.location = window.location;
    }
  },
  
  onLogoutError: function(e) {
    APP.xhr.abort();
    APP.xhr2.abort();
    APP.xhr = null;
    APP.xhr2 = null;
    
    $.removeClass(document.body, 'has-backdrop');
    
    Feedback.error('Something went wrong.');
  },
  
  onLoginSubmit: function(e) {
    var user, pwd, otp, params;
    
    e.preventDefault();
    /*
    if (!APP.isChannelPage && !APP.channelFrameReady) {
      Feedback.error(
        'Please unblock frames, cookies, scripts and XHR on both '
        + 'the 4channel.org and 4chan.org domains.'
      , 0);
      
      return;
    }
    */
    $.id('login-form').disabled = true;
    
    $.addClass(document.body, 'has-backdrop');
    
    Feedback.showMessage('Please wait…', 'notify', false);
    
    APP.authedChan = false;
    APP.authedChannel = false;
    
    user = $.id('js-user').value;
    pwd = $.id('js-pwd').value;
    otp = $.id('js-otp').value;
    
    params = {
      xhr: 1,
      action: 'do_login'
    };
    
    APP.auth(user, pwd, otp);
    
    if (!APP.channelFrame || !APP.channelFrameReady) {
      return;
    }
    
    APP.channelFrame.contentWindow.postMessage(
      {
        user: user,
        pwd: pwd,
        otp: otp,
        cid: APP.channelId
      },
      
      'https://reports.4channel.org'
    );
  },
  
  onLoginError: function() {
    if (APP.isChannelFrame) {
      APP.clearState();
      
      window.parent.postMessage(
        { error: 'Something went wrong.' },
        'https://reports.4chan.org'
      );
    }
    else {
      APP.clearState();
      Feedback.error('Something went wrong.');
    }
  },
  
  onLoginLoad: function() {
    var resp;
    
    resp = APP.parseResponse(this.responseText);
    
    if (resp.status === 'success') {
      if (APP.isChannelFrame) {
        APP.clearState();
        
        window.parent.postMessage(
          { success: true },
          'https://reports.4chan.org'
        );
      }
      else {
        if (APP.isChannelPage || APP.channelFrameReady === false) {
          APP.authedChannel = true;
        }
        APP.authedChan = true;
        APP.checkAuthSuccess();
      }
    }
    else {
      if (APP.isChannelFrame) {
        APP.clearState();
        
        window.parent.postMessage(
          { error: resp.message },
          'https://reports.4chan.org'
        );
      }
      else {
        APP.clearState();
        Feedback.error(resp.message);
      }
    }
  },
  
  clearState: function() {
    APP.xhr.abort();
    APP.xhr = null;
    
    if (!APP.isChannelFrame) {
      APP.authedChan = false;
      APP.authedChannel = false;
      APP.channelId += 1;
      $.id('login-form').disabled = false;
      $.removeClass(document.body, 'has-backdrop');
    }
  },
  
  checkAuthSuccess: function() {
    if (APP.authedChan && APP.authedChannel) {
      if (!APP.isChannelPage && APP.channelFrameReady === false) {
        window.location = 'https://reports.4channel.org/login#fb';
      }
      else {
        window.location = 'https://reports.4chan.org/login';
      }
    }
  },
  
  parseResponse: function(data) {
    try {
      return JSON.parse(data);
    }
    catch (e) {
      return {
        status: 'error',
        message: 'Something went wrong.'
      };
    }
  }
};

var Feedback = {
  messageTimeout: null,
  
  showMessage: function(msg, type, timeout, onClick) {
    var el;
    
    Feedback.hideMessage();
    
    el = document.createElement('div');
    el.id = 'feedback';
    el.title = 'Dismiss';
    el.innerHTML = '<span class="feedback feedback-' + type + '">' + msg + '</span>';
    
    $.on(el, 'click', onClick || Feedback.hideMessage);
    
    document.body.appendChild(el);
    
    if (timeout) {
      Feedback.messageTimeout = setTimeout(Feedback.hideMessage, timeout);
    }
  },
  
  replaceMessage: function(msg) {
    var el = $.id('feedback');
    
    if (el) {
      el.firstElementChild.innerHTML = msg;
    }
  },
  
  hideMessage: function() {
    var el = $.id('feedback');
    
    if (el) {
      if (Feedback.messageTimeout) {
        clearTimeout(Feedback.messageTimeout);
        Feedback.messageTimeout = null;
      }
      
      $.off(el, 'click', Feedback.hideMessage);
      
      document.body.removeChild(el);
    }
  },
  
  error: function(msg, timeout, onClick) {
    if (timeout === undefined) {
      timeout = 5000;
    }
    
    Feedback.showMessage(msg, 'error', timeout, onClick);
  },
  
  notify: function(msg, timeout, onClick) {
    if (timeout === undefined) {
      timeout = 3000;
    }
    
    Feedback.showMessage(msg, 'notify', timeout, onClick);
  }
};

APP.init();

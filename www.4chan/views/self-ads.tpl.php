<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Banner Contest - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/banner_contest.css?25">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="https://checkout.stripe.com/checkout.js"></script>
  <script type="text/javascript" src="//s.4cdn.org/js/donate.min.js?5"></script>
  <script type="text/javascript">
var $ = {};

$.id = function(id) {
  return document.getElementById(id);
};

$.el = function(tag) {
  return document.createElement(tag);
};

$.on = function(n, e, h) {
  n.addEventListener(e, h, false);
};

$.off = function(n, e, h) {
  n.removeEventListener(e, h, false);
};
var APP = {
  init: function(cfg) {
    cfg.token = APP.onToken;
    
    this.handler = StripeCheckout.configure(cfg);
    
    $.on(document, 'DOMContentLoaded', APP.run);
  },
  
  run: function() {
    var el;
    
    $.off(document, 'DOMContentLoaded', APP.run);
    
    if (el = $.id('self-form')) {
      $.on(el, 'submit', APP.onSubmit);
    }
    
    if (el = $.id('submit-btn')) {
      el.disabled = false;
    }
  },
  
  onToken: function(token) {
    var cnt, el;
    
    cnt = $.id('self-form');
    
    $.off(cnt, 'submit', APP.onSubmit);
    
    if (el = $.id('field-token')) {
      el.parentNode.removeChild(el);
    }
    
    if (el = $.id('field-token-type')) {
      el.parentNode.removeChild(el);
    }
    
    el = $.el('input');
    el.id = 'field-token';
    el.type = 'hidden';
    el.name = 'stripeToken';
    el.value = token.id;
    cnt.appendChild(el);
    
    el = $.el('input');
    el.id = 'field-token-type';
    el.type = 'hidden';
    el.name = 'stripeTokenType';
    el.value = token.type;
    cnt.appendChild(el);
    
    cnt.submit();
  },
  
  onSubmit: function(e) {
    var el, el2, amount, email, email2;
    
    e.preventDefault();
    e.stopPropagation();
    
    el = $.id('field-email');
    
    email = el.value;
    
    el = $.id('field-price');
    
    if (!/^[0-9]+$/.test(el.value)) {
      alert('Invalid price.');
      return;
    }
    
    amount = (+el.value) * 100;
    
    if (amount < (+el.getAttribute('min'))) {
      alert('Invalid amount.');
      return;
    }
    
    // ---
    
    APP.handler.open({
      amount: amount,
      email: email
    });
  }
};
    APP.init({
      key: '<?php echo "pk_test_bjaQzTvxxmDK13dB0lTA2NNj" ?>',
      currency: 'usd',
      name: '4chan',
      description: 'Self-Serve Ads',
      image: '//s.4cdn.org/image/apple-touch-icon-iphone-retina.png',
      bitcoin: true,
      allowRememberMe: false
    });
  </script>
</head>
<body>
<header><h1 id="title">4chan Banner Contest</h1></header>
<div id="content">
<div id="intro">

</div>
<div id="form">
  <h2>Submit Banner</h2>
  <form id="self-form" action="" method="POST" enctype="multipart/form-data">
    <div>Returning customer</div>
    <label for="field-email">E-Mail</label>
    <input type="text" maxlength="150" name="email" id="field-login">
    <label for="field-pwd">Password</label>
    <input type="password" maxlength="255" name="pwd" id="field-pwd">
    <div>New customer</div>
    <label for="field-name">Name</label>
    <input type="text" maxlength="150" name="name" id="field-name">
    <label for="field-email">E-Mail</label>
    <input required type="text" maxlength="150" name="email" id="field-email">
    <label for="field-email2">Confirm E-Mail</label>
    <input required type="text" maxlength="150" name="email2" id="field-email2">
    <label>Placement</label>
    <select required name="placement"><?php foreach ($this->_placements as $val => $p): ?>
<option value="<?php echo $val ?>"><?php echo $p['label'] ?> - <?php echo $p['width'] ?>&times;<?php echo $p['height'] ?></option>
<?php endforeach ?></select>
    <label>Boards</label>
    <select name="keywords"><option value=""> </option><?php foreach ($this->keywords as $kw => $title): ?>
<option value="<?php echo $kw ?>"><?php echo $title ?></option>
<?php endforeach ?></select>
    <label>Geo targets</label>
    <select name="geo"><option value=""> </option><?php foreach ($this->countries as $c): ?>
<option value="<?php echo $c['Code'] ?>"><?php echo $c['Name'] ?></option>
<?php endforeach ?></select>
    <label>Frequency cap</label>
    Display this ad at most <input id="field-freq" type="text" value="" name="freq_cap"> times per user per day.
    <label for="field-start-date">Start Date</label>
    <input type="text" maxlength="150" name="starts_on" id="field-start-date">
    <label for="field-end-date">End Date (optional)</label>
    <input type="text" maxlength="150" name="ends_on" id="field-end-date">
    <label>Price (USD)</label>
    <input required id="field-price" step="1" min="<?php echo self::MIN_USD_AMOUNT ?>" type="number" value="<?php echo self::MIN_USD_AMOUNT ?>" name="amount_usd">
    <label for="field-url">URL</label>
    <input type="text" maxlength="255" name="click_url" id="field-url">
    <label for="field-file">File</label>
    <span class="sub-lbl">PNGs, JPGs and static GIFs only. Maximum allowed file size is <?php echo round(self::IMG_MAX_FILESIZE / 1024) ?>KB.</span>
    <input type="file" name="file" id="field-file"><input type="hidden" name="action" value="submit">
    <button id="submit-btn" type="submit">Submit</button>
  </form>
</div>
</div>
<footer>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

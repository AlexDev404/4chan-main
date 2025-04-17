<?php if (!defined('IN_APP')) die();
include '/www/4chan.org/web/www/frontpage_footer.php';
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Donate - 4chan</title>
  <link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/donate.css?11">
  <link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" type="image/x-icon">
  <script type="text/javascript" src="https://checkout.stripe.com/checkout.js"></script>
  <script type="text/javascript" src="//s.4cdn.org/js/donate.min.js?5"></script>
  <script type="text/javascript">
    APP.init({
      key: '<?php echo STRIPE_API_KEY_PUBLIC ?>',
      currency: 'usd',
      name: 'Donate',
      description: 'Donate to 4chan',
      image: '//s.4cdn.org/image/apple-touch-icon-iphone-retina.png',
      bitcoin: true,
      allowRememberMe: false
    });
  </script>
</head>
<body>
<header><h1 id="title">Donate to 4chan</h1></header>
<div id="content">
<div id="intro">
<p>For supporting 4chan.<br>
Your gifts to 4chan. Gifts are non-refundable.</p>
<p>You are not lending money.You are not purchasing equity. You cannot
expect interest. You do not get any creative control over 4chan.</p>
</div>
<form id="donate-form" autocomplete="off" action="https://www.4chan.org/donate" method="POST">
  <input type="hidden" name="action" value="donate">
  <label for="field-email">E-Mail</label>
  <input required type="text" maxlength="<?php echo self::FIELD_MAX_LEN ?>" name="email" id="field-email"> <span title="Required">*</span>
  <label for="field-email2">Confirm E-Mail</label>
  <input required type="text" maxlength="<?php echo self::FIELD_MAX_LEN ?>" name="email2" id="field-email2"> <span title="Required">*</span>
  <label for="field-amount">Amount (USD)</label>
  <input required type="number" data-min="<?php echo self::MIN_AMOUNT ?>" step="1" min="<?php echo round(self::MIN_AMOUNT / 100) ?>" value="<?php echo round(self::MIN_AMOUNT / 100) ?>" name="amount" id="field-amount"> <span title="Required">*</span>
  <label for="field-name">Name</label>
  <input type="text" maxlength="<?php echo self::FIELD_MAX_LEN ?>" name="name" id="field-name">
  <label for="field-message">Message (<?php echo self::FIELD_MAX_LEN ?> characters)</label>
  <textarea maxlength="<?php echo self::FIELD_MAX_LEN ?>" name="message" id="field-message"></textarea>
  <label for="field-public"><input type="checkbox" name="public" value="1" id="field-public"> Display my name and message publicly</label>
  <button id="donate-btn" type="submit">Donate</button>
</form>
</div>
<footer>
<div id="copyright"><a href="/faq#what4chan">About</a> &bull; <a href="/feedback">Feedback</a> &bull; <a href="/legal">Legal</a> &bull; <a href="/contact">Contact</a><br>
Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.
</div>
</footer>
</body>
</html>

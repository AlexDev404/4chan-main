<?php if (!defined('IN_APP')) die(); ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Success - 4chan</title>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <style type="text/css">
    body {
      background: url('//s.4cdn.org/image/fade.png') repeat-x scroll center top #FFFFEE;
      font-family: 'Helvetica Neue', arial, sans-serif;
      margin: 5px 0 5px 0;
      padding: 0 5px;
      font-size: 13px;
      color: #800000;
    }
    #success {
      margin-top: 50px;
      font-size: 24px;
      font-weight: bold;
      text-align: center;
      color: green;
    }
    .success-sub {
      text-align: center;
      margin-top: 25px;
    }
  </style>
</head>
<body>
<div id="success">Application submitted.</div>
<div class="success-sub">
<p>You can edit your application using the URL below. Make sure to keep it private.</p>
<p><a href="https://www.<?php echo WEB_DOMAIN.self::WEB_ROOT ?>?id=<?php echo $this->unique_id ?>">https://www.<?php echo WEB_DOMAIN.self::WEB_ROOT ?>?id=<?php echo $this->unique_id ?></a></p>
</div>
</body>
</html>

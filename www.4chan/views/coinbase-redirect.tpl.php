<?php if (!defined('IN_APP')) die(); ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Purchase 4chan Pass - 4chan</title>
  <meta http-equiv="Refresh" content="2; url=<?php echo htmlspecialchars($this->payment_url, ENT_QUOTES) ?>" />
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
      font-size: 18px;
      font-weight: bold;
      text-align: center;
    }
  </style>
</head>
<body>
<div id="success">You will be redirected to the Coinbase website in a few seconds.</div>
</body>
</html>

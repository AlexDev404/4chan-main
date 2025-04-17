<?php if (!defined('IN_APP')) die(); ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Denied</title>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <style type="text/css">
    body {
      font-family: 'Helvetica Neue', arial, sans-serif;
      color: #393836;
      background-color: #E7E7E7;
    }
    #error {
      margin-top: 25px;
      font-size: 24px;
      font-weight: bold;
      text-align: center;
      color: #C41E3A;
    }
  </style>
</head>
<body>
<div id="error"><?php if (!isset($_COOKIE['4chan_auser'])): ?>
You need to log in first.
<?php else: ?>
You do not have access to this tool.
<?php endif ?></div>
</body>
</html>

<?php if (!defined('IN_APP')) die(); ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Error - 4chan</title>
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
    #error {
      margin-top: 50px;
      font-size: 24px;
      font-weight: bold;
      text-align: center;
      color: red;
    }
  </style>
</head>
<body>
<div id="error"><?php echo $this->message ?></div>
</body>
</html>

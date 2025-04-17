<?php if (!defined('IN_APP')) die(); ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Success - 4chan</title>
  <?php if (isset($this->redirect) && $this->redirect): ?><meta http-equiv="Refresh" content="1; url=<?php echo $this->redirect ?>" /><?php endif ?>
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
  </style>
</head>
<body>
<div id="success"><?php echo (isset($this->message) && $this->message) ? $this->message : 'Done.' ?></div>
</body>
</html>

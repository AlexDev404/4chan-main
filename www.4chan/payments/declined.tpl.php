<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="keywords" content="imageboard,image board,anime,manga,ecchi,hentai,video games,english,japan" />
  <meta name="robots" content="noarchive" />
  <meta http-equiv="pragma" content="no-cache" />
  <meta http-equiv="expires" content="-1" />

<title>4chan - Payment</title>
<link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/yui.css" />
<link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/global.css" />
<link rel="stylesheet" type="text/css" href="//s.4cdn.org/css/payments.css" />
<link rel="shortcut icon" href="//s.4cdn.org/image/favicon.ico" />
<style type="text/css">
#copyright { text-align: center; margin-top: 0em; margin-bottom:1em; }
</style>
</head>

<body>
  <div id="doc">
    <div id="hd">
      <div id="logo">
        <a href="//www.4chan.org/" title="Home"><img alt="4chan" src="//s.4cdn.org/image/fp/logo.png" width="300" height="120"></a>
      </div>
    </div>

    <div id="bd">
      <div class="box-outer top-box" >
        <div class="box-inner">
          <div class="boxbar">
            <h2><?php $t = isset($errBigTitle) ? $errBigTitle : 'Payment Failed'; echo $t ?></h2>
          </div>
          <div class="boxcontent" style="text-align: center;">
              <h2 style="margin-top:10px"><?php echo $errTitle; ?></h2><p><?php echo $errMsg; ?></p>
          </div>
        </div>
      </div>

    </div>
    <div id="ft">
      <br class="clear-bug" />
      <div id="copyright">Copyright &copy; 2003-<?php echo date('Y', $_SERVER['REQUEST_TIME']) ?> 4chan community support LLC. All rights reserved.</div>
    </div>
  </div>
</body>
</html>

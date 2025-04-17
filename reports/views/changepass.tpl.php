<?php if (!defined('IN_APP')) die() ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Change Password</title>
  <link rel="stylesheet" type="text/css" href="/css/login.css">
  <link rel="shortcut icon" href="/image/favicon-team.ico" type="image/x-icon">
</head>
<body>
<header>
  <h1 id="title">Change Password</h1>
</header>
<div id="content">
<?php if ($this->error): ?>
  <h3 class="auth-status auth-error"><?php echo $this->error ?></h3>
  <a class="button btn-logout" href="/changepass">Return</a>
<?php elseif ($this->mode === 'prompt'): ?>
<form action="" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="action" value="change">
  <input type="hidden" name="csrf" value="<?php echo $this->csrf ?>">
  <input placeholder="Username" type="text" name="userlogin" required value="<?php if ($this->username) echo $this->username ?>">
  <input placeholder="Old password" type="password" name="passlogin" required>
  <input placeholder="New password" type="password" name="new_password" required>
  <input placeholder="Confirm new password" type="password" name="new_password2" required>
  <div class="label-protip">If using two-factor authentication:</div>
  <input placeholder="One-time code" type="text" maxlength="6" name="otp" autocomplete="off">
  <button class="button btn-login" type="submit">Change</button>
</form>
<?php elseif ($this->mode === 'success'): ?>
  <h3 class="auth-status auth-success"><?php echo self::S_OK ?></h3>
  <a class="button btn-login" href="/login">Login</a>
<?php endif ?>
</div>
</body>
</html>

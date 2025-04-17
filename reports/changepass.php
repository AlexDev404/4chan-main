<?php
require_once 'lib/db.php';
require_once 'lib/auth.php';
require_once 'csp.php';

/*
$mysql_suppress_err = false;
ini_set('display_errors', 1);
error_reporting(E_ALL);
*/

define('IN_APP', true);

class ChangePass {
  protected
    $error = null,
    // Routes
    $actions = array(
      'index',
      'change'/*,
      'force'*/
    );
  
  const
    MODS = '/www/global/htpasswd/moderators',
    JANITORS = '/www/global/htpasswd/janitors',
    JANITORS_NGINX = '/www/global/htpasswd/janitors_nginx',
    ADMINS = '/www/global/htpasswd/admins',
    MANAGERS = '/www/global/htpasswd/managers',
    DEVS = '/www/global/htpasswd/developers',
    DEVS_NGINX = '/www/global/htpasswd/developers_nginx'
  ;
  
  const
    S_BADAUTH = 'Username or old password incorrect.',
    S_MISMATCH = 'Your new passwords did not match.',
    S_TOOWEAK = 'You password must be at least 8 characters long and contain one letter and one digit.',
    S_TOOLONG = 'Your password is too long.',
    S_SAMEPASS = 'Your new password may not match your old one.',
    S_ERROR = 'Internal Server Error',
    S_OK = 'Password changed successfully.',
    S_BADOTP = 'Invalid or expired OTP.'
  ;
  
  /**
   * Renders HTML template
   */
  private function renderHTML($view) {
    include('views/' . $view . '.tpl.php');
  }
  
  private function error($msg) {
    $this->error = $msg;
    
    if ($msg === self::S_BADAUTH) {
      header('HTTP/1.0 403 Forbidden');
    }
    
    $this->renderHTML('changepass');
    die();
  }
  
  private function get_csrf_token() {
    return bin2hex(openssl_random_pseudo_bytes(16));
  }
  
  private function updateHtpasswd($file, $username, $password, $nginx = false) {
    if ($nginx) {
      $args = '-b';
    }
    else {
      $args = '-Bb -C 10';
    }
    
    system("/usr/local/www/bin/htpasswd $args "
      . escapeshellarg($file) . " "
      . escapeshellarg($username) . " "
      . escapeshellarg($password),
      $ret_status
    );
    
    if ($ret_status != 0) {
      $this->error(self::S_ERROR . ' (pwd' . (int)$ret_status . ')');
    }
  }
  
  /**
   * Login
   */
  public function change() {
    if (!isset($_POST['userlogin'])
      || !isset($_POST['passlogin'])
      || !isset($_POST['new_password'])
      || !isset($_POST['new_password2'])) {
      $this->error(self::S_BADAUTH);
    }
    
    if (!isset($_COOKIE['csrf']) || !isset($_POST['csrf'])
      || $_COOKIE['csrf'] === '' || $_POST['csrf'] === '') {
      $this->error(self::S_BADAUTH);
    }
    
    if ($_COOKIE['csrf'] !== $_POST['csrf']) {
      $this->error(self::S_BADAUTH);
    }
    
    $username = $_POST['userlogin'];
    $password = $_POST['passlogin'];
    $new_password = $_POST['new_password'];
    $new_password2 = $_POST['new_password2'];
    
    if ($username === ''
      || $password === ''
      || $new_password === ''
      || $new_password2 === '') {
      $this->error(self::S_BADAUTH);
    }
    
    if (strlen($new_password) > 100) {
      $this->error(self::S_TOOLONG);
    }
    
    if ($new_password !== $new_password2) {
      $this->error(self::S_MISMATCH);
    }
    /*
    if ($new_password === $password) {
      $this->error(self::S_SAMEPASS);
    }
    */
    if (strlen($new_password) < 8
      || !preg_match('/[0-9]/', $new_password)
      || !preg_match('/[a-z]/i', $new_password)) {
      $this->error(self::S_TOOWEAK);
    }
    
    mysql_global_connect();
    
    $query = "SELECT * FROM `mod_users` WHERE `username` = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, $username);
    
    if (!mysql_num_rows($res)) {
      $this->error(self::S_BADAUTH);
    }
    
    $user = mysql_fetch_assoc($res);
    
    if ($user['auth_secret']) {
      if (!isset($_POST['otp']) || !preg_match('/^[0-9]+$/', $_POST['otp'])) {
        $this->error(self::S_BADOTP);
      }
      
      require_once 'lib/GoogleAuthenticator.php';
      
      $ga = new PHPGangsta_GoogleAuthenticator();
      
      $dec_secret = auth_decrypt($user['auth_secret']);
      
      if ($dec_secret === false) {
        $this->error(self::S_ERROR);
      }
      
      if (!$ga->verifyCode($dec_secret, $_POST['otp'], 1)) {
        $this->error(self::S_BADOTP);
      }
    }
    
    if (!$user) {
      $this->error(self::S_BADAUTH);
    }
    
    if (!password_verify($password, $user['password'])) {
      $this->error(self::S_BADAUTH);
    }
    
    // Update table
    $query = "UPDATE `mod_users` SET password = '%s', password_expired = 0 WHERE username = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, password_hash($new_password, PASSWORD_DEFAULT), $username);
    
    if (mysql_affected_rows() !== 1) {
      $this->error(self::S_ERROR . ' (4)');
    }
    
    // htpasswd
    $isAdmin = $user['level'] === 'admin';
    $isManager = ($user['level'] === 'manager') || ($user['username'] === 'desuwa');
    $isMod = $user['level'] === 'mod';
    $isJanitor = $user['level'] === 'janitor';
    $isDev = strpos($user['flags'], 'developer') !== false;
    
    $this->updateHtpasswd(self::JANITORS, $username, $new_password);
    $this->updateHtpasswd(self::JANITORS_NGINX, $username, $new_password, true);
    
    if ($isAdmin || $isManager || $isMod) {
      $this->updateHtpasswd(self::MODS, $username, $new_password);
    }
    
    if ($isAdmin || $isManager) {
      $this->updateHtpasswd(self::MANAGERS, $username, $new_password);
    }
    
    if ($isAdmin/* || ($isDev && $username === 'desuwa')*/) {
      $this->updateHtpasswd(self::ADMINS, $username, $new_password);
    }
    
    if ($isDev) {
      $this->updateHtpasswd(self::DEVS, $username, $new_password);
      $this->updateHtpasswd(self::DEVS_NGINX, $username, $new_password, true);
    }
    
    // Delete cookies
    $cookie_ttl = $_SERVER['REQUEST_TIME'] - 3600;
    setcookie('4chan_auser', '', $cookie_ttl, '/', '.4chan.org', true, true);
    setcookie('4chan_apass', '', $cookie_ttl, '/', '.4chan.org', true, true);
    setcookie('apass', '', $cookie_ttl, '/', '.4chan.org', true, true);
    setcookie('csrf', '', $cookie_ttl, '/changepass', '.4chan.org', true, true);
    // Remove after migrating from 4chan_asession -> apass
    setcookie('4chan_asession', '', $cookie_ttl, '/', '.4chan.org', true, true);
    
    $this->mode = 'success';
    
    $this->renderHTML('changepass');
  }
  
  public function force() {
    die();
    
    $mysql_suppress_err = false;
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    require_once 'lib/admin.php';
    require_once 'lib/auth.php';
    
    auth_user();
    
    if (!has_flag('developer') || !isset($_GET['password']) || !isset($_GET['username'])) {
      $this->error('Bad request');
    }
    
    $username = $_GET['username'];
    $password = $_GET['password'];
    
    if (!$username || !$password) {
      $this->error('Bad request');
    }
    
    $query = "UPDATE `mod_users` SET password = '%s', password_expired = 0 WHERE username = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, password_hash($password, PASSWORD_DEFAULT), $username);
    
    if (mysql_affected_rows() !== 1) {
      $this->error(self::S_ERROR . ' (4)');
    }
    
    $this->updateHtpasswd(self::JANITORS, $username, $password);
    $this->updateHtpasswd(self::JANITORS_NGINX, $username, $password, true);
    //$this->updateHtpasswd(self::MODS, $username, $password);
  }
  
  /**
   * Default page
   */
  public function index() {
    $this->mode = 'prompt';
    
    $this->csrf = $this->get_csrf_token();
    
    if (isset($_COOKIE['4chan_auser'])) {
      $this->username = htmlspecialchars($_COOKIE['4chan_auser'], ENT_QUOTES);
    }
    else {
      $this->username = false;
    }
    
    setcookie('csrf', $this->csrf, 0, '/changepass', '.4chan.org', true, true);
    
    $this->renderHTML('changepass');
  }
  
  /**
   * Main
   */
  public function run() {
    if ($_SERVER['HTTP_HOST'] !== 'reports.4chan.org') {
      $this->error('Bad request');
    }
    
    $method = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    if (isset($method['action'])) {
      $action = $method['action'];
    }
    else {
      $action = 'index';
    }
    
    if (in_array($action, $this->actions)) {
      $this->$action();
    }
    else {
      $this->error('Bad request');
    }
  }
}

$ctrl = new ChangePass();
$ctrl->run();

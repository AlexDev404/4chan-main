<?php
die();
require_once 'lib/auth.php';
require_once 'lib/db.php';

define('IN_APP', true);

class Login {
  protected
    $error = null,
    // Routes
    $actions = array(
      'index',
      'tfa',
      'do_login',
      'do_logout',
      'channel_frame'
    ),
    
    $has2FA = false
    ;
  
  const
    ADMIN_SALT_PATH = '/www/keys/2014_admin.salt',
    ADMIN_JS_PATH = '9a9f422e4fc10c549b9e2c6519433d76',
    JANITOR_JS_PATH = 'dbb7da08ac5a75139c3cd257870ae466'
  ;
  
  const ROOT_URL = 'reports.4chan.org/login';
  
  const
    S_BADAUTH = 'Incorrect username or password.',
    S_BADCSRF = 'Invalid CSRF token.',
    S_OKAUTH = 'You are logged in.',
    S_OKLOGOUT = 'You are logged out.',
    S_PASSEXPIRED = 'Your password has expired; check IRC for instructions on changing it.',
    S_ERROR = 'Internal Server Error',
    S_TFA_ON = 'Two-factor authentication is already enabled.',
    S_FORCE_TFA = 'Two-factor authentication is required for this account.',
    S_BADOTP = 'Invalid or expired OTP.'
  ;
  
  private function setCookie($name, $value, $ttl = 0, $path = '', $domain = '', $secure = false, $http_only = false) {
    $name = rawurlencode($name);
    $value = rawurlencode($value);
    
    $flags = array();
    
    if ($secure) {
      $flags[] = 'Secure';
    }
    
    if ($http_only) {
      $flags[] = 'HttpOnly';
    }
    
    if (!empty($flags)) {
      $flags = '; ' . implode('; ', $flags);
    }
    else {
      $flags = '';
    }
    
    if ($ttl !== 0) {
      $max_age = " Max-Age=$ttl;";
    }
    else {
      $max_age = '';
    }
    
    header("Set-Cookie: $name=$value; Path=$path;$max_age Domain=$domain; SameSite=None$flags");
  }
  
  /**
   * Renders HTML template
   */
  private function renderHTML($view) {
    include('views/' . $view . '.tpl.php');
  }
  
  private function renderJSON($data, $add_length = false) {
    header('Content-Type: application/json');
    echo json_encode($data);
  }
  
  private function successJSON($data = null) {
    $this->renderJSON(array('status' => 'success', 'data' => $data));
    die();
  }
  
  private function errorJSON($message = null, $fatal = false) {
    $payload = array('status' => 'error', 'message' => $message);
    
    if ($fatal === true) {
      $payload['fatal'] = true;
    }
    
    $this->renderJSON($payload);
    die();
  }
  
  private function error($msg) {
    if (isset($_POST['xhr'])) {
      $this->errorJSON($msg);
    }
    else {
      $this->error = $msg;
      
      if ($msg === self::S_BADAUTH) {
        header('HTTP/1.0 403 Forbidden');
      }
      
      $this->renderHTML('login');
      die();
    }
  }
  
  private function get_csrf_token() {
    return bin2hex(openssl_random_pseudo_bytes(16));
  }
  
  private function validate_user() {
    if (!isset($_POST['userlogin']) || !isset($_POST['passlogin'])) {
      $this->error(self::S_BADAUTH);
    }
    
    if (!isset($_COOKIE['csrf']) || !isset($_POST['csrf'])
      || $_COOKIE['csrf'] === '' || $_POST['csrf'] === '') {
      $this->error(self::S_BADCSRF);
    }
    
    if ($_COOKIE['csrf'] !== $_POST['csrf']) {
      $this->error(self::S_BADCSRF);
    }
    
    $username = $_POST['userlogin'];
    $password = $_POST['passlogin'];
    
    if ($username === '' || $password === '') {
      $this->error(self::S_BADAUTH);
    }
    
    mysql_global_connect();
    
    $query = "SELECT * FROM `mod_users` WHERE `username` = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, $username);
    
    if (!mysql_num_rows($res)) {
      $this->error(self::S_BADAUTH);
    }
    
    $user = mysql_fetch_assoc($res);
    
    if (!$user) {
      $this->error(self::S_BADAUTH);
    }
    
    if ($user['password_expired'] == 1) {
      $this->error(self::S_PASSEXPIRED);
    }
    
    if (!password_verify($password, $user['password'])) {
      $this->error(self::S_BADAUTH);
    }
    
    return $user;
  }
  
  private function should_force_tfa($user) {
    if (strpos($user['flags'], 'developer') !== false) {
      return true;
    }
    
    if ($user['level'] == 'admin' || $user['level'] == 'manager') {
      return true;
    }
  }
  
  private function is_tfa_enabled() {
    if (!isset($_COOKIE['4chan_auser']) || !isset($_COOKIE['apass'])) {
      return false;
    }
    
    $query = "SELECT username, password, auth_secret FROM mod_users WHERE username = '%s'";
    
    $res = mysql_global_call($query, $_COOKIE['4chan_auser']);
    
    if (!$res || mysql_num_rows($res) < 1) {
      return false;
    }
    
    $user = mysql_fetch_assoc($res);
    
    $admin_salt = file_get_contents(self::ADMIN_SALT_PATH);
    
    if (!$admin_salt) {
      return false;
    }
    
    $hashed_admin_password = hash('sha256', $user['username'] . $user['password'] . $admin_salt);
    
    if ($_COOKIE['apass'] !== $hashed_admin_password) {
      return false;
    }
    
    return $user['auth_secret'] !== '';
  }
  
  /**
   * Login
   */
  public function do_login() {
    $user = $this->validate_user();
    
    $ips_array = json_decode($user['ips'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->error(self::S_ERROR . ' (j1)');
    }
    
    $ips_array[$_SERVER['REMOTE_ADDR']] = $_SERVER['REQUEST_TIME'];
    
    if (count($ips_array) > 512) {
      asort($ips_array);
      array_shift($ips_array);
    }
    
    $ips_array = json_encode($ips_array);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->error(self::S_ERROR . ' (j2)');
    }
    
    $admin_salt = file_get_contents(self::ADMIN_SALT_PATH);
    
    if (!$admin_salt) {
      $this->error(self::S_ERROR . ' (as1)');
    }
    
    if ($user['auth_secret']) {
      if (!isset($_POST['otp']) || !preg_match('/^[0-9]+$/', $_POST['otp'])) {
        $this->error(self::S_BADOTP);
      }
      
      require_once 'lib/GoogleAuthenticator.php';
      
      $ga = new PHPGangsta_GoogleAuthenticator();
      
      $dec_secret = auth_decrypt($user['auth_secret']);
      
      if ($dec_secret === false) {
        $this->error(self::S_ERROR . ' (as2)');
      }
      
      if (!$ga->verifyCode($dec_secret, $_POST['otp'], 3)) {
        $this->error(self::S_BADOTP);
      }
    }
    else if ($this->should_force_tfa($user)) {
      $this->error(self::S_FORCE_TFA);
    }
    
    $query = "UPDATE `mod_users` SET ips = '%s', last_login = NOW() WHERE id = %d LIMIT 1";
    
    $res = mysql_global_call($query, $ips_array, $user['id']);
    
    if (mysql_affected_rows() !== 1) {
      $this->error(self::S_ERROR . ' (ip2)');
    }
    
    if ($_SERVER['HTTP_HOST'] === 'reports.4channel.org') {
      $domain = '.4channel.org';
    }
    else {
      $domain = '.4chan.org';
    }
    
    // Cookies
    //$cookie_ttl = $_SERVER['REQUEST_TIME'] + 30 * 24 * 3600;
    $cookie_ttl = 30 * 24 * 3600;
    
    $this->setCookie('4chan_auser', $user['username'], $cookie_ttl, '/', $domain, true, true);
    //$this->setCookie('4chan_apass', $user['password'], $cookie_ttl, '/', $domain, true, true);
    
    $hashed_admin_password = hash('sha256', $user['username'] . $user['password'] . $admin_salt);
    $this->setCookie('apass', $hashed_admin_password, $cookie_ttl, '/', $domain, true, true);
    
    // Flags
    $user_flags = explode(',', $user['flags']);
    $js_flags = array();
  
    if ($user['level'] === 'admin' && $user['username'] === 'moot') {
      $js_flags[] = 'forcedanonname';
    }
    
    if (in_array('html', $user_flags)) {
      $js_flags[] = 'html';
    }
    
    if ($js_flags) {
      $this->setCookie('4chan_aflags', implode(',', $js_flags), $cookie_ttl, '/', $domain, true);
    }
    
    // Mod/Janitor JS extension
    if ($user['level'] === 'admin' || $user['level'] === 'mod' || $user['level'] === 'manager') {
      $js_path = self::ADMIN_JS_PATH;
    }
    else if ($user['level'] === 'janitor') {
      $js_path = self::JANITOR_JS_PATH;
    }
    else {
      $js_path = null;
    }
    
    $this->setCookie('extra_path', $js_path, $cookie_ttl, '/', $domain);
    
    // Delete CSRF cookie
    $this->setCookie('csrf', '', -3600, '/login', $domain, true, true);
    
    // Set csrf token
    $this->setCookie('_tkn', $this->get_csrf_token(), $cookie_ttl, '/', $domain, true, true);
    
    $this->mode = 'success';
    
    $this->hasTFA = !!$user['auth_secret'];
    
    if (isset($_POST['xhr'])) {
      $this->successJSON();
    }
    else {
      $this->renderHTML('login');
    }
  }
  
  /**
   * Enable two-factor auth
   */
  public function tfa() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $user = $this->validate_user();
      
      if ($user['auth_secret']) {
        $this->error(self::S_TFA_ON);
      }
      
      require_once 'lib/GoogleAuthenticator.php';
      
      $ga = new PHPGangsta_GoogleAuthenticator();
      
      $this->secret = $ga->createSecret();
      
      $this->qrCode = $ga->getQRCodeGoogleUrl(
        str_replace(' ', '%20', $user['username']),
        $this->secret) . '%26issuer%3D4chan';
      
      $enc_secret = auth_encrypt($this->secret);
      
      if ($enc_secret === false) {
        $this->error(self::S_ERROR . ' (tfa1)');
      }
      
      $enc_esc = mysql_real_escape_string($enc_secret);
      
      $query = "UPDATE mod_users SET auth_secret = '$enc_esc' WHERE username = '%s' LIMIT 1";
      
      $res = mysql_global_call($query, $user['username']);
      
      if (!$res) {
        $this->error(self::S_ERROR . ' (tfa2)');
      }
      
      if ($_SERVER['HTTP_HOST'] === 'reports.4channel.org') {
        $domain = '.4channel.org';
      }
      else {
        $domain = '.4chan.org';
      }
      
      $cookie_ttl = -3600;
      
      // Remove username and password cookies
      $this->setCookie('4chan_auser', '', $cookie_ttl, '/', '.4chan.org', true, true);
      $this->setCookie('apass', '', $cookie_ttl, '/', '.4chan.org', true, true);
      
      // Remove csrf cookies
      $this->setCookie('csrf', '', $cookie_ttl, '/login', '.4chan.org', true, true);
      $this->setCookie('_tkn', '', $cookie_ttl, '/', '.4chan.org', true, true);
    }
    else {
      $this->secret = null;
      $this->csrf = $this->get_csrf_token();
      $this->setCookie('csrf', $this->csrf, 0, '/login', '.4chan.org', true, true);
    }
    
    $this->mode = 'tfa';
    
    $this->renderHTML('login');
  }
  
  /**
   * Logout
   */
  public function do_logout() {
    $this->mode = 'logout';
    
    $cookie_ttl = 3600;
    
    if ($_SERVER['HTTP_HOST'] === 'reports.4channel.org') {
      $domain = '.4channel.org';
    }
    else {
      $domain = '.4chan.org';
    }
    
    // Remove username and password cookies
    $this->setCookie('4chan_auser', '', $cookie_ttl, '/', $domain, true, true);
    $this->setCookie('apass', '', $cookie_ttl, '/', $domain, true, true);
    
    // Remove flags cookie
    if (isset($_COOKIE['4chan_aflags'])) {
      $this->setCookie('4chan_aflags', '', $cookie_ttl, '/', $domain, true);
    }
    
    // Remove mod/janitor extension JS cookie
    $this->setCookie('extra_path', '', $cookie_ttl, '/', $domain);
    
    // Remove csrf cookies
    $this->setCookie('csrf', '', $cookie_ttl, '/login', $domain, true, true);
    $this->setCookie('_tkn', '', $cookie_ttl, '/', $domain, true, true);
    
    if (isset($_POST['xhr'])) {
      $this->successJSON();
    }
    else {
      $this->renderHTML('login');
    }
  }
  
  /**
   * For cookie transfer
   */
  public function channel_frame() {
    if ($_SERVER['HTTP_HOST'] !== 'reports.4channel.org') {
      $this->error('Bad request');
    }
    
    $this->csrf = $this->get_csrf_token();
    $this->setCookie('csrf', $this->csrf, 0, '/login', '.4channel.org', true, true);
    
    $this->renderHTML('login-frame');
  }
  
  /**
   * Default page
   */
  public function index() {
    $domain = '.4chan.org';
    
    $this->mode = 'prompt';
    
    if (isset($_COOKIE['apass'])) {
      $this->isLogged = true;
      $this->has2FA = $this->is_tfa_enabled();
    }
    else {
      $this->isLogged = false;
      $this->csrf = $this->get_csrf_token();
      $this->setCookie('csrf', $this->csrf, 0, '/login', $domain, true, true);
    }
    
    $this->renderHTML('login');
  }
  
  /**
   * Main
   */
  public function run() {
    if (strpos($_SERVER['REQUEST_URI'], '/login.php') === 0) {
      $this->error('Use <a href="//' . self::ROOT_URL . '">/login</a> instead');
    }
    
    $method = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    if (isset($method['action'])) {
      $action = $method['action'];
    }
    else {
      $action = 'index';
    }
    
    if ($_SERVER['HTTP_HOST'] !== 'reports.4chan.org') {
      if ($action === 'tfa' || $action === 'index') {
        $this->error("You can't do this here.");
      }
    }
    
    if (in_array($action, $this->actions)) {
      $this->$action();
    }
    else {
      $this->error('Bad request');
    }
  }
}

$ctrl = new Login();
$ctrl->run();

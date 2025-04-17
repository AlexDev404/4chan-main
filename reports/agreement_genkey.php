<?php
die();

require_once 'lib/admin.php';
require_once 'lib/auth.php';

//require_once 'csp.php';

auth_user(true);

if (!has_level('janitor')) {
  APP::denied();
}

define('IN_APP', true);

if (has_flag('developer')) {
  $mysql_suppress_err = false;
  ini_set('display_errors', '1');
  error_reporting(E_ALL);
}

class APP {
  protected
    // Routes
    $actions = array(
      'index'
    );
  
  const MOD_USERS_TABLE = 'mod_users';
  
  const SALT_FILE = '/www/keys/2014_admin.salt';
  
  const
    HTPASSWD_CMD = '/usr/local/www/bin/htpasswd -b %s %s %s',
    HTPASSWD_TMP =  '/www/global/htpasswd/temp_agreement_nginx'
  ;
  
  static public function denied() {
    require_once('views/denied.tpl.php');
    die();
  }
  
  final protected function success($redirect = null, $no_die = false) {
    $this->redirect = $redirect;
    $this->renderHTML('success');
    if (!$no_die) {
      die();
    }
  }
  
  final protected function error($msg) {
    $this->message = $msg;
    $this->renderHTML('error');
    die();
  }
  
  private function send_email($email, $values = null) {
    $subject = 'Volunteer Moderator Agreement is ready to be signed';
    
    $message = <<<TXT
The new Volunteer Moderator Agreement is ready to be signed. While I strongly suggest you read it for yourself, it appears to be very similar to the previous agreement you all had with moot (4chan, LLC), except that the company name has changed to Hiroyuki's company (4chan Community Support LLC), and that the 'Identity Verification' section has been dropped.

To facilitate the signing of this agreement, your user account has been disabled. In order to re-activate your account, you must first agree to and sign the 4chan Volunteer Moderator Agreement.

You can review the Volunteer Moderator Agreement by following this link (use the temporary credentials below to access):https://reports.4chan.org/agreement?key={{KEY}}

Your temporary Username is: {{LOGIN}}
Your temporary Password is: {{PWD}}


Once your agreement has been counter-signed, you'll be able to log in and access the janitor tools once again. During this process you will be kicked from #janiteam, and the channel key will be changed. Once your agreement has been counter-signed and your account re-activated, just go to /j/ to get the new channel key and re-join the channel.

If you run into any trouble during this process, please email me at grapeape@4chan.org
If you have any questions or concerns about the agreement itself, please email Hiroyuki at hiro@4chan.org


--RapeApe
TXT;
    
    if ($values) {
      $message = str_replace(array_keys($values), array_values($values), $message);
    }
    
    $headers = "From: Team 4chan <janitorapps@4chan.org>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $opts = '-f janitorapps@4chan.org';
    
    return mail($email, $subject, $message, $headers, $opts);
  }
  
  /**
   * Renders HTML template
   */
  private function renderHTML($view) {
    include('views/' . $view . '.tpl.php');
  }
  
  private function getRandomHexBytes($length = 10) {
    $bytes = openssl_random_pseudo_bytes($length);
    return bin2hex($bytes);
  }
  
  public function index() {
    $mod_tbl = self::MOD_USERS_TABLE;
    
    $query = "SELECT * FROM $mod_tbl WHERE username = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, $_COOKIE['4chan_auser']);
    
    if (!$res) {
      $this->error('Database error (0)');
    }
    
    $user = mysql_fetch_assoc($res);
    
    if (!$user) {
      $this->error('You need to log in first.');
    }
    
    if ($user['signed_agreement']) {
      $this->error('You have already signed the agreement.');
    }
    
    if ($user['agreement_key']) {
      $this->error('You have already requested your agreement key.');
    }
    
    $auth_key = $this->getRandomHexBytes(32);
    $temp_http_passwd = $this->getRandomHexBytes(32);
    $admin_salt = file_get_contents(self::SALT_FILE);
    
    if (!$auth_key || ! $temp_http_passwd || !$admin_salt) {
      $this->error('Internal Server Error (1)');
    }
    
    $hashed_auth_key = hash('sha256', $auth_key . $admin_salt);
    
    // Check if the auth key is already used somewhere else
    $query = "SELECT * FROM janitor_apps WHERE agreement_key = '%s' LIMIT 1";
    $res = mysql_global_call($query, $hashed_auth_key);
    
    if (mysql_num_rows($res) !== 0) {
      die('Database error (d1)');
    }
    
    $query = "SELECT * FROM signed_agreements WHERE agreement_key = '%s' LIMIT 1";
    $res = mysql_global_call($query, $hashed_auth_key);
    
    if (mysql_num_rows($res) !== 0) {
      die('Database error (d2)');
    }
    
    $query = "SELECT * FROM $mod_tbl WHERE agreement_key = '%s' LIMIT 1";
    $res = mysql_global_call($query, $hashed_auth_key);
    
    if (mysql_num_rows($res) !== 0) {
      die('Database error (d3)');
    }
    
    // Update user
    $query = "UPDATE $mod_tbl SET agreement_key = '%s' WHERE id = %d LIMIT 1";
    
    $res = mysql_global_call($query, $hashed_auth_key, $user['id']);
    
    if (!$res) {
      $this->error('Database error (3)');
    }
    
    if (mysql_affected_rows() < 1) {
      $this->error('Database error (4)');
    }
    
    $cmd = sprintf(self::HTPASSWD_CMD,
      self::HTPASSWD_TMP,
      escapeshellarg($hashed_auth_key),
      escapeshellarg($temp_http_passwd)
    );
    
    if (system($cmd) === false) {
      $this->error('Internal Server Error (2).');
    }
    
    $values = array(
      '{{LOGIN}}' => $hashed_auth_key,
      '{{PWD}}' => $temp_http_passwd,
      '{{KEY}}' => $auth_key
    );
    
    $clean_email = htmlspecialchars($user['email']);
    
    $this->success_msg = "Please check your email ($clean_email) " . 
      "for instructions for signing the Volunteer Moderator Agreement.";
    
    $this->success(null, true);
    
    fastcgi_finish_request();
    
    $this->send_email($user['email'], $values);
  }
  
  /**
   * Main
   */
  public function run() {
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

$ctrl = new APP();
$ctrl->run();

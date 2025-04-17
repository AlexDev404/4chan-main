<?php
die();
require_once 'lib/admin.php';
require_once 'lib/auth.php';

//require_once 'csp.php';

define('IN_APP', true);

class Agreement {
  protected
    // Routes
    $actions = array(
      'index',
      'sign',
      'review',
      'reject',
      'counter_sign'
      /*
      'debug',
      'truncate_table',
      'create_table'*/
    );
  
  const AGREEMENTS_TABLE = 'signed_agreements';
  const JANITOR_APPS_TABLE = 'janitor_apps';
  const MOD_USERS_TABLE = 'mod_users';
  
  // Corresponds to the ACCEPTED and SIGNED constants in janitorapps.php
  const APP_VALID_STATUS = 2;
  const APP_SIGNED_STATUS = 4;
  
  // Expected number of entries in a base htpasswd file
  const HTPASSWD_BASE_SIZE = 3;
  // Expected number of entries in a base working directory
  const WORKDIR_BASE_SIZE = 3;
  
  const
    ADMIN_KEY_FILE = '/www/keys/agreement_key.ini',
    ADMIN_SALT_PATH = '/www/keys/2014_admin.salt';

  const
    HTPASSWD_CMD_NGINX = '/usr/local/www/bin/htpasswd -b %s %s %s',
    HTPASSWD_RM_CMD_NGINX = '/usr/local/www/bin/htpasswd -D %s %s',
    JANITOR_HTPASSWD_TMP = '/www/global/htpasswd/temp_agreement_nginx',
    JANITOR_HTPASSWD_NGINX = '/www/global/htpasswd/janitors_nginx';
  
  const KEY_TTL = 864000; // Temp auth keys expires after 10 days
  
  const
    // Working dir, with trailing slash (ID images, temporary pdf files, etc...)
    WORKING_DIR = '/www/perhost/team/',
    // Template file name
    TPL_FILE = 'agreement_template.pdf',
    // pdftk command line (template, xfdf fields, output)
    CMD_PDFTK = '/usr/local/bin/pdftk "%s" fill_form "%s" output "%s" flatten 2>&1',
    // imagemagick command line (no longer used)
    CMD_CONVERT = '/usr/local/bin/convert "%s" -quality 100 "%s" 2>&1',
    // ghostscript command line (output, filled template, ID pdf) (no longer used)
    CMD_GS = '/usr/local/bin/gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile="%s" "%s" "%s" 2>&1'
  ;
  
  const
    // jpeg ID image file
    ID_JPG_FILE = 'id_file.jpg',
    // converted to pdf ID
    ID_PDF_FILE = 'id_file.pdf',
    // User provided data
    XFDF_FILE = 'fields.xfdf',
    // PDF with user provided data filled in
    FILLED_PDF_FILE = 'filled.pdf',
    // Final PDF (filled pdf + appended ID pdf)
    FINAL_PDF_FILE = 'final.pdf'
  ;
  
  const
    IMG_MAX_FILESIZE = 2097152,
    IMG_MAX_DIM = 2000
  ;
  
  public function debug() {
    //copy('/usr/www/4chan.org/web/team/data/agreement_template.pdf', '/www/perhost/team/agreement_template.pdf');
  }
  
  final protected function success($redirect = null) {
    $this->redirect = $redirect;
    $this->renderHTML('success');
    die();
  }
  
  final protected function error($msg) {
    $this->message = $msg;
    $this->renderHTML('error');
    die();
  }
  
  private function isValidUID($uid) {
    return preg_match('/^[a-f0-9]+$/', $uid);
  }
  
  private function deleteUserData($uid) {
    if (!$this->isValidUID($uid)) {
      $this->error('Internal Server Error (dud)');
    }
    
    $cwd = self::WORKING_DIR . $uid . '/';
    
    if (!is_dir($cwd)) {
      return true;
    }
    
    $files = array(
      $cwd . self::ID_JPG_FILE,
      $cwd . self::ID_PDF_FILE,
      $cwd . self::XFDF_FILE,
      $cwd . self::FILLED_PDF_FILE,
      $cwd . self::FINAL_PDF_FILE
    );
    
    foreach ($files as $file) {
      if (file_exists($file)) {
        unlink($file);
      }
    }
    
    return rmdir($cwd);
  }
  
  private function notify_counter_signature($mod_name, $mod_email) {
    // Email
    $subject = '[Team 4chan] New Volunteer Moderator Agreement Counter-Signed';
    
    $message = "The janitor application from \"$mod_name\" <$mod_email> is ready to proceed to Orientation (Step 3).";

    // From:
    $headers = "From: Team 4chan <janitorapps@4chan.org>\r\n";
    
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail('janitorapps@4chan.org', $subject, $message, $headers, '-f janitorapps@4chan.org');
  }
  
  private function notify($mod_name, $mod_email) {
    // Email
    $subject = '[Team 4chan] New Volunteer Moderator Agreement Awaiting Counter-Signature';
    
    $message = "\"$mod_name\" <$mod_email> has submitted their agreement for review and counter-signature.";

    // From:
    $headers = "From: Team 4chan <janitorapps@4chan.org>\r\n";
    
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail('janitorapps@4chan.org', $subject, $message, $headers, '-f janitorapps@4chan.org');
  }
  
  private function sendFinalPDF($email, $uid, $first_name, $last_name, $mod_name) {
    if (!$this->isValidUID($uid)) {
      $this->error('Internal Server Error (sfp1)');
    }
    
    $filepath = self::WORKING_DIR . $uid . '/' . self::FINAL_PDF_FILE;
    
    if (!file_exists($filepath)) {
      $this->error('PDF file not found.');
    }
    
    $data = file_get_contents($filepath);
    
    if ($data === false) {
      $this->error('Couldn\'t read the PDF.');
    }
    
    $data = chunk_split(base64_encode($data));
    
    $boundary = 'forfree' . md5(mt_rand() . microtime());
    
    // Subject
    $subject = '[Team 4chan] Executed 4chan Volunteer Moderator Agreement';
    
    // Message
    $msg_body = <<<TXT
Dear $first_name,

Thank you for signing the 4chan Volunteer Moderator Agreement, and your service as a volunteer janitor with 4chan. Please find attached a copy of the fully executed agreement for your records.

We strongly recommend you save and/or print this copy of the agreement and store it safely for your own personal records, and delete it from both your Inbox and Trash folders. We also highly recommend using two-factor authentication and a strong password on the e-mail account associated with your 4chan login.

Please do not hesitate to contact us should you have any further questions.

Best Regards,

Team 4chan
TXT;
    
    // From:
    $headers = "From: Team 4chan <janitorapps@4chan.org>\r\n";
    // Bcc:
    $headers .= "Bcc: 4chan Legal <legal@4chan.org>\r\n";
    
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; ";
    $headers .= 'boundary="' . $boundary . '"';
    
    $message = '--' . $boundary . "\r\n"
      . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
      . 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n"
      . $msg_body . "\r\n\r\n"
      . '--' . $boundary . "\r\n"
      . 'Content-Type: application/pdf; '
      . "name=\"SIGNED - 4chan - Volunteer Moderator Agreement - $first_name $last_name, aka $mod_name.pdf\"" . "\r\n"
      . "Content-Disposition: attachment; "
      . "filename=\"SIGNED - 4chan - Volunteer Moderator Agreement - $first_name $last_name, aka $mod_name.pdf\"" . "\r\n"
      . "Content-Transfer-Encoding: base64\r\n\r\n"
      . $data . "\r\n\r\n"
      . '--' . $boundary . "\r\n";
    
    // Envelope
    return mail($email, $subject, $message, $headers, '-f janitorapps@4chan.org');
  }
  
  private function send_noapp_account_email($email, $values = null) {
    $subject = 'Your 4chan Account Details';
    
    $message = <<<TXT
Your 4chan moderation account has been successfully created.

Please find your account credentials below, and be sure to follow the steps in order:

Username: {{USERNAME}}
Temporary Password: {{PASSWORD}}

1. You must first choose a new, user-defined Password in order to log in. You can do so here, using your Temporary Password: https://reports.4chan.org/changepass
2. *After* changing your password, you may then log in at: https://reports.4chan.org/login
3. Optional: We highly recommend enabling two-factor authentication: https://reports.4chan.org/login?action=tfa

It is very important that you protect these credentials! Be sure to exercise best security practices, such as using a strong password and/or password manager, two-factor authentication for your e-mail account, etc. If your account username and/or password may have been compromised, report it to us immediately.

--Team 4chan
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
  
  private function finalizeNoAppAccount($email, $username, $hashed_agreement_key) {
    // Password
    /*
    $plain_password = bin2hex(openssl_random_pseudo_bytes(8));
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    */
    /*
    $query = "UPDATE `%s` SET signed_agreement = 1, password_expired = 1, " .
      "agreement_key = '', password = '%s' WHERE agreement_key = '%s' LIMIT 1";
    */
    $query = "UPDATE `%s` SET signed_agreement = 1, " .
      "agreement_key = '' WHERE agreement_key = '%s' LIMIT 1";
    
    $res = mysql_global_call($query,
      self::MOD_USERS_TABLE,
      $hashed_agreement_key
    );
    /*
    $res = mysql_global_call($query,
      self::MOD_USERS_TABLE,
      $hashed_password,
      $hashed_agreement_key
    );
    */
    if (mysql_affected_rows() !== 1) {
      $this->error('User not found');
    }
    /*
    $cmd = sprintf(self::HTPASSWD_CMD_NGINX,
      self::JANITOR_HTPASSWD_NGINX,
      escapeshellarg($username),
      escapeshellarg($plain_password)
    );
    
    if (system($cmd) === false) {
      $this->error('Could not update htpasswd file (fa1).');
    }
    
    // Email
    $values = array(
      '{{USERNAME}}' => $username,
      '{{PASSWORD}}' => $plain_password
    );
    
    if (!$this->send_noapp_account_email($email, $values)) {
      $this->error('Email not accepted for delivery.');
    }
    
    if (!$res || mysql_affected_rows() !== 1) {
      $this->error('Database error.');
    }
    */
    return true;
  }
  
  private function checkAdmin($tfa = false) {
    $key = file_get_contents(self::ADMIN_KEY_FILE);
    
    if (!$key || !isset($_GET['key']) || $_GET['key'] !== trim($key)) {
      header('HTTP/1.0 403 Forbidden');
      $this->error('Bad request');
    }
    
    if ($tfa) {
      if (!isset($_GET['otp']) || !$_GET['otp']) {
        header('HTTP/1.0 403 Forbidden');
        $this->error('Bad OTP.');
      }
      
      $query = "SELECT auth_secret FROM mod_users WHERE username = 'hiro'";
      
      $res = mysql_global_call($query);
      
      if (!$res) {
        $this->error('Database error (cA)');
      }
      
      $user = mysql_fetch_assoc($res);
      
      if (!$user || !$user['auth_secret']) {
        header('HTTP/1.0 403 Forbidden');
        $this->error('Bad OTP.');
      }
      
      require_once 'lib/GoogleAuthenticator.php';
      
      $ga = new PHPGangsta_GoogleAuthenticator();
      
      $dec_secret = auth_decrypt($user['auth_secret']);
      
      if ($dec_secret === false) {
        $this->error('Internal Server Error (cA).');
      }
      
      if (!$ga->verifyCode($dec_secret, $_GET['otp'], 1)) {
        header('HTTP/1.0 403 Forbidden');
        $this->error('Bad OTP.');
      }
    }
  }
  
  private function validate_janitor_auth_key() {
      $query = "SELECT * FROM `%s` WHERE username = 'desuwa' LIMIT 1";
      $res = mysql_global_call($query, self::MOD_USERS_TABLE);
      
      
      $user = mysql_fetch_assoc($res);

return $user;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $auth_key = isset($_POST['key']) ? $_POST['key'] : null;
    }
    else {
      $auth_key = isset($_GET['key']) ? $_GET['key'] : null;
    }
    
    if (!$auth_key) {
      header('HTTP/1.0 403 Forbidden');
      echo('File not found.');
      die();
    }
    
    $admin_salt = file_get_contents(self::ADMIN_SALT_PATH);
    
    if (!$admin_salt) {
      $this->error('Internal Server Error (au0)');
    }
    
    $hashed_auth_key = hash('sha256', $auth_key . $admin_salt);
    
    $query = "SELECT id FROM signed_agreements WHERE agreement_key = '%s'";
    $res = mysql_global_call($query, $hashed_auth_key);
    
    if (!$res) {
      $this->error('Internal Server Error (au1)');
    }
    
    if (mysql_num_rows($res) > 0) {
      $this->error('Your agreement has been submitted successfully and is awaiting counter-signature.');
    }
    
    $query = "SELECT * FROM `%s` WHERE agreement_key = '%s' AND closed = %d LIMIT 1";
    $res = mysql_global_call($query, self::JANITOR_APPS_TABLE, $hashed_auth_key, self::APP_VALID_STATUS);
    
    if (!$res) {
      header('HTTP/1.0 403 Forbidden');
      echo('Forbidden.');
      die();
    }
    
    if (mysql_num_rows($res) !== 1) {
      $query = "SELECT * FROM `%s` WHERE agreement_key = '%s' LIMIT 1";
      $res = mysql_global_call($query, self::MOD_USERS_TABLE, $hashed_auth_key);
      
      if (!$res || mysql_num_rows($res) !== 1) {
        header('HTTP/1.0 403 Forbidden');
        $this->removeTempAccount($hashed_auth_key);
        echo('Forbidden.');
        die();
      }
      
      $user = mysql_fetch_assoc($res);
      
      if (!$user) {
        $this->error('Database Error (au1)');
      }
      
      $user['handle'] = $user['username'];
      $user['no_app'] = 1;
    }
    else {
      $user = mysql_fetch_assoc($res);
      
      if (!$user) {
        $this->error('Database Error (au1)');
      }
      
      $now = time();
      
      if ((int)$user['key_created_on'] + self::KEY_TTL <= $now) {
        header('HTTP/1.0 403 Forbidden');
        $this->removeTempAccount($hashed_auth_key);
        echo('Forbidden.');
        die();
      }
      
      $user['no_app'] = 0;
    }
    
    return $user;
  }
  
  private function removeTempAccount($hashed_auth_key) {
    $cmd = sprintf(self::HTPASSWD_RM_CMD_NGINX,
      self::JANITOR_HTPASSWD_TMP,
      escapeshellarg($hashed_auth_key)
    );
    
    if (system($cmd) === false) {
      $this->error('Internal Server Error (rta2).');
    }
    
    $query = "UPDATE `%s` SET agreement_key = '' WHERE agreement_key = '%s' LIMIT 1";
    $res = mysql_global_call($query, self::JANITOR_APPS_TABLE, $hashed_auth_key);
    
    if (!$res) {
      $this->error('Database error (rta)');
    }
    
    return true;
  }
  
  /**
   * Renders HTML template
   */
  private function renderHTML($view) {
    include('views/' . $view . '.tpl.php');
  }
  
  public function truncate_table() {
    $sql = "TRUNCATE TABLE `" . self::AGREEMENTS_TABLE . "`";
    mysql_global_call($sql);
  }
  
  public function create_table() {
    $tableName = self::AGREEMENTS_TABLE;
    
    //$sql = "DROP TABLE `$tableName`";
    //mysql_global_call($sql);
    
    $sql =<<<SQL
CREATE TABLE IF NOT EXISTS `$tableName` (
  `id` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `signature` varchar(255) NOT NULL,
  `sign_date` varchar(255) NOT NULL,
  `created_on` int(10) unsigned NOT NULL,
  `uid` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `agreement_key` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
SQL;
    
    mysql_global_call($sql);
  }
  
  // data should already be html escaped
  private function generateXFDF($data, $uid) {
    $xfdf = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve">
<fields>

XML;
    
    foreach ($data as $field => $value) {
      $xfdf .= '<field name="' . $field . '">
        <value>' . $value . '</value>
      </field>';
    }
    
    $xfdf .= '
</fields>
<ids original="' . $uid . '" modified="' . time() . '" />
<f href="' . $uid . '" />
</xfdf>';
    
    return $xfdf;
  }
  
  public function reject() {
    auth_user();
    
    if (!has_level('admin') || $_COOKIE['4chan_auser'] !== 'hiro') {
      $this->error('Bad request');
    }
    
    $this->checkAdmin();
    
    $id = (int)$_GET['id'];
    
    if (!$id) {
      $this->error('Document not found.');
    }
    
    $tableName = self::AGREEMENTS_TABLE;
    
    $query = "SELECT * FROM $tableName WHERE id = $id LIMIT 1";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error('Database error (1)');
    }
    
    if (mysql_num_rows($res) !== 1) {
      $this->error('Document not found.');
    }
    
    $doc = mysql_fetch_assoc($res);
    
    if (!$doc) {
      $this->error('Database error (2)');
    }
    
    if (!$this->deleteUserData($doc['uid'])) {
      $this->error("Couldn't delete user data.");
    }
    
    $query = "DELETE FROM $tableName WHERE id = %d LIMIT 1";
    
    $res = mysql_global_call($query, $doc['id']);
    
    if (!$res) {
      $this->error("Couldn't delete database entry (The documents were deleted).");
    }
    
    $first_name = $doc['first_name'];
    $custom_msg = $_GET['msg'];
    
    // Email
    $subject = '[Team 4chan] Rejected 4chan Volunteer Moderator Agreement';
    
    $message = <<<TXT
Dear $first_name,

Unfortunately your signed 4chan Volunteer Moderator Agreement has been rejected for the following reason:

"$custom_msg"

Please complete the agreement again.

Best Regards,

Team 4chan
TXT;

    // From:
    $headers = "From: Team 4chan <janitorapps@4chan.org>\r\n";
    // Bcc:
    $headers .= "Bcc: 4chan Legal <legal@4chan.org>\r\n";
    
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    if (mail($doc['email'], $subject, $message, $headers, '-f janitorapps@4chan.org')) {
      $this->success();
    }
    else {
      $this->error('Email rejected');
    }
  }
  
  public function counter_sign() {
    auth_user();
    
    if (!has_level('admin') || $_COOKIE['4chan_auser'] !== 'hiro') {
      $this->error('Bad request');
    }
    
    $this->checkAdmin(true);
    
    if (!isset($_GET['id'])) {
      $this->error('Bad Request');
    }
    
    $id = (int)$_GET['id'];
    
    if (!$id) {
      $this->error('Document not found.');
    }
    
    $tableName = self::AGREEMENTS_TABLE;
    
    $query = "SELECT * FROM $tableName WHERE id = $id LIMIT 1";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error('Database error (1)');
    }
    
    if (mysql_num_rows($res) !== 1) {
      $this->error('Document not found.');
    }
    
    $doc = mysql_fetch_assoc($res);
    
    if (!$doc) {
      $this->error('Database error (2)');
    }
    
    if (!$this->isValidUID($doc['uid'])) {
      $this->error('Internal Server Error (uid0)');
    }
    
    $cwd = self::WORKING_DIR . $doc['uid'] . '/';
    
    $tpl_file = self::WORKING_DIR . self::TPL_FILE;
    $id_jpg_file = $cwd . self::ID_JPG_FILE;
    $id_pdf_file = $cwd . self::ID_PDF_FILE;
    $xfdf_file = $cwd . self::XFDF_FILE;
    $filled_pdf_file = $cwd . self::FILLED_PDF_FILE;
    $final_pdf_file = $cwd . self::FINAL_PDF_FILE;
    
    $form_data = array(
      'fullname' => $doc['first_name'] . ' ' . $doc['last_name'],
      'fullname2' => $doc['first_name'] . ' ' . $doc['last_name'],
      'firstname' => $doc['first_name'],
      'email' => $doc['email'],
      'address' => $doc['address'],
      'signature' => $doc['signature'],
      'signdate' => $doc['sign_date'],
      'docdate' => date('F j, Y', (int)$doc['created_on'])
    );
    
    $xfdf = $this->generateXFDF($form_data, $doc['uid']);
    
    if (file_put_contents($xfdf_file, $xfdf) === false) {
      $this->error('Failed to output XFDF file');
    }
    
    //$cmd = sprintf(self::CMD_PDFTK, $tpl_file, $xfdf_file, $filled_pdf_file);
    $cmd = sprintf(self::CMD_PDFTK, $tpl_file, $xfdf_file, $final_pdf_file);
    $ret = shell_exec($cmd);
    
    if ($ret != '') {
      $this->error('pdftk failed.');
    }
    // This is for image uploads. No longer used.
    /*
    $cmd = sprintf(self::CMD_CONVERT, $id_jpg_file, $id_pdf_file);
    shell_exec($cmd);
    
    if ($ret != '') {
      $this->error('convert failed.');
    }
    
    $cmd = sprintf(self::CMD_GS, $final_pdf_file, $filled_pdf_file, $id_pdf_file);
    shell_exec($cmd);
    
    if ($ret != '') {
      $this->error('gs failed.' . $ret);
    }
    */
    if (isset($_GET['preview'])) {
      header('Content-Type: application/pdf');
      echo file_get_contents($final_pdf_file);
    }
    else {
      $res = $this->sendFinalPDF(
        $doc['email'],
        $doc['uid'],
        $doc['first_name'],
        $doc['last_name'],
        $doc['user_name']
      );
      
      if ($res) {
        if (!$this->deleteUserData($doc['uid'])) {
          $this->error("Couldn't delete user data (The email was accepted for delivery).");
        }
        
        $query = "DELETE FROM $tableName WHERE id = %d LIMIT 1";
        mysql_global_call($query, $doc['id']);
        
        if ($doc['no_app']) {
          $this->finalizeNoAppAccount($doc['email'], $doc['user_name'], $doc['agreement_key']);
        }
        else {
          $query = "UPDATE `%s` SET closed = %d WHERE agreement_key = '%s' LIMIT 1";
          mysql_global_call($query, self::JANITOR_APPS_TABLE, self::APP_SIGNED_STATUS, $doc['agreement_key']);
        }
        
        $this->removeTempAccount($doc['agreement_key']);
        
        $this->notify_counter_signature(htmlspecialchars($doc['user_name']), htmlspecialchars($doc['email']));
        
        $this->success();
      }
      else {
        $this->error('Email rejected.');
      }
    }
  }
  
  public function sign() {
    $janitorapp = $this->validate_janitor_auth_key();
    /*
    if (!isset($_FILES['id_file'])) {
      $this->error('You forgot to upload your ID');
    }
    
    $up_meta = $_FILES['id_file'];  
    
    if ($up_meta['error'] !== UPLOAD_ERR_OK) {
      if ($up_meta['error'] === UPLOAD_ERR_INI_SIZE) {
        $this->error('The uploaded file is too big.');
      }
      else if ($up_meta['error'] === UPLOAD_ERR_NO_FILE) {
        $this->error('You forgot to upload your ID');
      }
      else {
        $this->error('Internal Server Error (1)');
      }
    }
    
    if (!is_uploaded_file($up_meta['tmp_name'])) {
      $this->error('Internal Server Error (2)');
    }
    
    if (filesize($up_meta['tmp_name']) > self::IMG_MAX_FILESIZE) {
      $this->error('The uploaded file is too big.');
    }
    
    $id_filemeta = getimagesize($up_meta['tmp_name']);
    
    if (!is_array($id_filemeta)) {
      $this->error('Please make sure the uploaded file is a valid JPEG image.');
    }
    
    // Check the filetype
    if ($id_filemeta[2] !== IMAGETYPE_JPEG) {
      $this->error('Please make sure the uploaded file is a valid JPEG image.');
    }
    
    // Check image dimensions
    if ($id_filemeta[0] > self::IMG_MAX_DIM || $id_filemeta[1] > self::IMG_MAX_DIM) {
      $this->error('The uploaded image is too large.');
    }
    */
    // Validate fields
    $post_fields = array('first_name', 'last_name', 'statement_certification',
      'statement_terms', 'address', 'date', 'signature' );
    
    foreach ($post_fields as $field) {
      if (!isset($_POST[$field]) || !$_POST[$field]) {
        $this->error('Please make sure you have filled in all the required fields.');
      }
    }
    
    $created_on = time();
    
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date'])) {
      $this->error('Invalid date (1)');
    }
    
    $ts = explode('/', $_POST['date']);
    $ts = mktime(0, 0, 0, (int)$ts[0], (int)$ts[1], (int)$ts[2]);
    $tnow = mktime(0, 0, 0);
    
    if ($ts === false || $tnow === false) {
      $this->error('Invalid date (2)');
    }
    
    $td = abs($ts - $tnow);
    if ($td > (24 * 60 * 60)) {
      $this->error('Invalid date (3)');
    }
    
    $date = htmlentities($_POST['date'], ENT_COMPAT);
    $first_name = htmlentities($_POST['first_name'], ENT_COMPAT);
    $last_name = htmlentities($_POST['last_name'], ENT_COMPAT);
    $address = htmlentities($_POST['address'], ENT_COMPAT);
    
    if (!preg_match('/^\/s\/ /', $_POST['signature'])) {
      $this->error('The signature must start with a /s/ symbol.');
    }
    
    $signature = htmlentities($_POST['signature'], ENT_COMPAT);
    
    $uid = bin2hex(openssl_random_pseudo_bytes(32));
    
    $cwd = self::WORKING_DIR . $uid . '/';
    
    if (is_dir($cwd) || !mkdir($cwd)) {
      $this->error('Internal Server Error (3)');
    }
    /*
    if (move_uploaded_file($up_meta['tmp_name'], $cwd . self::ID_JPG_FILE) === false) {
      $this->error('Internal Server Error (4)');
    }
    */
    $tableName = self::AGREEMENTS_TABLE;
    
    $query =<<<SQL
INSERT INTO `$tableName`(
  `user_name`, `email`, `address`, `first_name`, `last_name`, `signature`,
  `sign_date`, `created_on`, `uid`, `agreement_key`, `no_app`
) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', %d)
SQL;
    
    $res = mysql_global_call($query,
      $janitorapp['handle'],
      $janitorapp['email'],
      $address,
      $first_name,
      $last_name,
      $signature,
      $date,
      $created_on,
      $uid,
      $janitorapp['agreement_key'],
      $janitorapp['no_app']
    );
    
    if (!$res || mysql_affected_rows() !== 1) {
      $this->error('Internal Server Error (5)');
    }
    
    $this->notify(htmlspecialchars($janitorapp['handle']), htmlspecialchars($janitorapp['email']));
    
    $this->renderHTML('agreement-sent');
  }
  
  public function review() {
    auth_user();
    
    if (!has_level('admin') || $_COOKIE['4chan_auser'] !== 'hiro') {
      $this->error('Bad request');
    }
    
    $this->checkAdmin();
    
    $query = 'SELECT * FROM `' . self::AGREEMENTS_TABLE . '`';
    
    $res = mysql_global_call($query);
    
    $this->documents = array();
    
    while ($doc = mysql_fetch_assoc($res)) {
      $this->documents[] = $doc;
    }
    
    $this->htpasswd_over_lines = false;
    $this->workdir_over_size = false;
    
    $htpasswd_lines = count(file(self::JANITOR_HTPASSWD_TMP));
    $workdir_size = count(scandir(self::WORKING_DIR));
    $doc_size = count($this->documents);
    
    if ($doc_size + self::WORKDIR_BASE_SIZE < $workdir_size) {
      $this->workdir_over_size = true;
    }
    
    $query = "SELECT COUNT(*) FROM " . self::JANITOR_APPS_TABLE . " WHERE closed = " . self::APP_VALID_STATUS;
    $res = mysql_global_call($query);
    $pending_size = (int)mysql_fetch_row($res)[0];
    
    $query = "SELECT COUNT(*) FROM " . self::MOD_USERS_TABLE . " WHERE agreement_key != ''";
    $res = mysql_global_call($query);
    $pending_size += (int)mysql_fetch_row($res)[0];
    
    if ($pending_size + self::HTPASSWD_BASE_SIZE < $htpasswd_lines) {
      $this->htpasswd_over_lines = true;
    }
    
    $this->renderHTML('agreement-review');
  }
  
  /**
   * Default page
   */
  public function index() {
    $this->janitorapp = $this->validate_janitor_auth_key();
     
    $this->auth_key = htmlspecialchars($_GET['key'], ENT_QUOTES);
    
    $this->renderHTML('agreement-test');
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

$ctrl = new Agreement();
$ctrl->run();

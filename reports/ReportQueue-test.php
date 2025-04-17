<?php

require_once 'lib/geoip2.php';

class ReportQueue {
  protected
    $actions = array(
      'reports',
      'ban_requests',
      'get_reports',
      'clear_report',
      'get_reporters',
      'get_rep_details',
      'ban_reporters',
      'clear_reporter',
      'get_ban_requests',
      'accept_ban_request',
      'deny_ban_request',
      'get_ip',
      'count_reports',
      'get_report_ids',
      'get_templates',
      'clear_stale_reports',
      'staffmessages'
    ),
    $isCountReports,
    $access;
  
  protected $report_abuse_tpl = null;
  
  protected $cached_board_coefs = null;
  
  const DATE_FORMAT ='m/d/y H:i';
  
  const REP_ABUSE_TPL = 190;
  
  const PAGE_SIZE = 25;
  
  const GLOBAL_THRES = 1500; // Weight after which the report is globally unlocked to all janitors
  
  const HIGHLIGHT_THRES = 500;
  
  const THREAD_WEIGHT_BOOST = 1.25; // weight multiplier for threads, rounded up
  
  const STATS_PRUNE_MONTHS = 6; // limit in month for pruning janitor stats
  
  const STATS_ACTION_DENIED = 0;
  
  const STATS_ACTION_ACCEPTED = 1;
  
  const MAX_REPORTS_PER_PAGE = 3000;
  
  // after X local bans on different boards, a local ban will be turned into a global one
  const LOCAL_TO_GLOBAL_THRES = 1;
  
  // time interval in days to consider when deciding to upgrade report abuse warnings to bans
  const ABUSE_WARN_UPGRADE_INTERVAL = 30;
  
  // when to prune the cleared log, in days
  const CLEAR_LOG_AGE = 5;
  
  // Automatic warnings/bans for abuse based on the number of cleared reports
  const ABUSE_CLEAR_DAYS = 3;
  const ABUSE_CLEAR_COUNT = 50;
  const ABUSE_CLEAR_BAN_INTERVAL = 5; // Minimum number of days between auto-bans for abuse
  
  const WS_ANY = 0;
  const WS_ONLY = 1;
  const WS_NOT = 2;
  
  static public function denied() {
    require_once('views/denied.tpl.php');
    die();
  }
    
  function __construct($my_access, $isCountReports = false) {
    if (!$my_access || !is_array($my_access)) {
      ReportQueue::denied();
    }
    
    $this->access = $my_access;
    
    $this->isCountReports = $isCountReports;
    
    $this->isMod = $this->access['ban'] === 1;
    $this->isAdmin = isset($this->access['is_admin']) && $this->access['is_admin'];
    $this->isManager = isset($this->access['is_manager']) && $this->access['is_manager'];
    $this->isDeveloper = isset($this->access['is_developer']) && $this->access['is_developer'];
    /*
    if ($this->isDeveloper) {
      $mysql_suppress_err = false;
      ini_set('display_errors', 1);
      error_reporting(E_ALL);
    }
    */
    if ($this->isAdmin) {
      $this->userLevel = 'admin';
    }
    else if ($this->isManager) {
      $this->userLevel = 'manager';
    }
    else if ($this->isMod) {
      $this->userLevel = 'mod';
    }
    else {
      $this->userLevel = 'janitor';
    }
  }
  
  private function success($data = null, $add_length = false) {
    $this->renderJSON(array('status' => 'success', 'data' => $data), $add_length);
  }
  
  private function fail($data = null) {
    $this->renderJSON(array('status' => 'fail', 'data' => $data));
    die();
  }
  
  private function error($message, $code = null, $data = null) {
    $payload = array('status' => 'error', 'message' => $message);
    
    if ($code) {
      $payload['code'] = $code;
    }
    
    if ($data) {
      $payload['data'] = $data;
    }
    
    $this->renderJSON($payload);
    
    die();
  }
  
  private function renderJSON($data, $add_length = false) {
    header('Content-Type: application/json');
    
    $data = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
    
    echo $data;
  }
  
  private function renderHTML($view) {
    include('views/' . $view . '.tpl.php');
  }
  
  private function validate_csrf($value='') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      if (!isset($_COOKIE['_tkn']) || !isset($_POST['_tkn'])
        || $_COOKIE['_tkn'] == '' || $_POST['_tkn'] == ''
        || $_COOKIE['_tkn'] !== $_POST['_tkn']) {
        $this->error('Bad Request');
      }
    }
    else {
      if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != ''
        && !preg_match('/^https?:\/\/([_a-z0-9]+)\.(4chan|4channel)\.org(\/|$)/', $_SERVER['HTTP_REFERER'])) {
        $this->error('Bad Request');
      }
    }
  }
  
  private function prune_clear_log() {
    $days = (int)self::CLEAR_LOG_AGE;
    
    $sql = "DELETE FROM report_clear_log WHERE created_on < DATE_SUB(NOW(), INTERVAL $days DAY)";
    
    return mysql_global_call($sql);
  }
  
  private function write_to_event_log($event, $ip, $args = []) {
    $sql = <<<SQL
INSERT INTO event_log(`type`, ip, board, thread_id, post_id, arg_num,
arg_str, pwd, req_sig, ua_sig, meta)
VALUES('%s', '%s', '%s', '%d', '%d', '%d',
'%s', '%s', '%s', '%s', '%s')
SQL;
    
    return mysql_global_call($sql, $event, $ip,
      $args['board'], $args['thread_id'], $args['post_id'], $args['arg_num'],
      $args['arg_str'], $args['pwd'], $args['req_sig'], $args['ua_sig'], $args['meta']
    );
  }
  
  private function get_report_abuse_template() {
    if ($this->report_abuse_tpl) {
      return $this->report_abuse_tpl;
    }
    
    $rep_abuse_tpl = (int)self::REP_ABUSE_TPL;
    
    $result = mysql_global_call("SELECT * FROM ban_templates WHERE no = $rep_abuse_tpl");
    
    $template = mysql_fetch_assoc($result);
    
    if (!$template) {
      return null;
    }
    
    $this->report_abuse_tpl = $template;
    
    return $template;
  }
  
  // Logs cleared reports to a separate table
  private function log_cleared_reporter($long_ip, $pwd, $pass_id, $cat_id, $weight) {
    $sql = <<<SQL
INSERT INTO report_clear_log(long_ip, pwd, pass_id, category, weight)
VALUES(%d, '%s', '%s', %d, %F)
SQL;
    
    return !!mysql_global_call($sql, $long_ip, $pwd, $pass_id, $cat_id, $weight);
  }
  
  // Checks for abuse and issues warnings/bans
  private function enforce_cleared_abuse($long_ip, $pwd, $pass_id) {
    $cleared_days_lim = (int)self::ABUSE_CLEAR_DAYS;
    $cleared_count_lim = (int)self::ABUSE_CLEAR_COUNT;
    $ban_days_lim = (int)self::ABUSE_CLEAR_BAN_INTERVAL;
    
    $rep_abuse_tpl = (int)self::REP_ABUSE_TPL;
    
    $ip = long2ip($long_ip);
    
    if (!$ip) {
      return false;
    }
    
    // Double ban query because indexes all cover 'active'
    // 4chan Pass
    if ($pass_id) {
      $pass_id_sql = mysql_real_escape_string($pass_id);
      
      $ban_clauses[] = "active = 0 AND 4pass_id = '$pass_id_sql'";
      $ban_clauses[] = "active = 1 AND 4pass_id = '$pass_id_sql'";
      
      $rep_clauses[] = "pass_id = '$pass_id_sql'";
    }
    // IP
    else {
      $ban_clauses[] = "host = '" . mysql_real_escape_string($ip) . "'";
      $rep_clauses[] = "long_ip = " . (int)$long_ip;  
    }
    
    // Password
    if ($pwd) {
      $pwd_sql = mysql_real_escape_string($pwd);
      
      $ban_clauses[] = "active = 0 AND password = '$pwd_sql'";
      $ban_clauses[] = "active = 1 AND password = '$pwd_sql'";
      
      $rep_clauses[] = "pwd = '$pwd_sql'";
    }
    
    // Check cleared reports
    $clause = implode(' OR ', $rep_clauses);
    
    $query = <<<SQL
SELECT SQL_NO_CACHE COUNT(*) FROM report_clear_log
WHERE created_on > DATE_SUB(NOW(), INTERVAL $cleared_days_lim DAY)
AND ($clause)
SQL;
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      return false;
    }
    
    $clear_count = (int)mysql_fetch_row($res)[0];
    
    if ($clear_count < $cleared_count_lim) {
      return false;
    }
    
    // Cleared threshold reached, check if a warn/ban is needed
    foreach ($ban_clauses as $clause) {
      $query = <<<SQL
SELECT 1 FROM banned_users
WHERE $clause AND template_id = $rep_abuse_tpl
AND now > DATE_SUB(NOW(), INTERVAL $ban_days_lim DAY)
SQL;
      
      $res = mysql_global_call($query);
      
      if (!$res) {
        return false;
      }
      
      if (mysql_num_rows($res) > 0) {
        return false;
      }
    }
    
    // Issue warning 
    $template = $this->get_report_abuse_template();
    
    if (!$template) {
      return false;
    }
    
    //$this->write_to_event_log('clear_lim_warn', $ip);
    $reason = $template['publicreason'] . '<>Clear limit reached';
    
    $query =<<<SQL
INSERT INTO banned_users (
  board, global, zonly, name, host, reverse, xff, reason, length,
  admin, md5, post_num, rule, post_time, template_id, password, 4pass_id,
  post_json, admin_ip
) 
VALUES ('', %d, %d, 'Anonymous', '%s', '%s', '', '%s', '%s',
  '%s', '', 0, '%s', '', %d, '%s', '%s',
  '', '')
SQL;
    
    $length = date('Y-m-d H:i:s', time());
    
    $result = mysql_global_call($query,
      1, 0, $ip, $ip, $reason, $length, 
      'Auto-ban', $template['rule'], $rep_abuse_tpl, $pwd, $pass_id
    );
    
    if (!$result) {
      return false;
    }
    
    return true;
  }
  
  // parameters should already be validated and escaped for SQL
  private function is_report_for_own_post($board, $no) {
    $sql = "SELECT host FROM `$board` WHERE no = $no LIMIT 1";
    
    $res = mysql_board_call($sql);
    
    if (!$res) {
      return false;
    }
    
    $ip = mysql_fetch_row($res);
    
    if (!$ip) {
      return false;
    }
    
    return $ip[0] === $_SERVER['REMOTE_ADDR'];
  }
  
  private function get_request_ids() {
    $query = "SELECT id FROM ban_requests";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      return null;
    }
    
    $data = array();
    
    while ($row = mysql_fetch_row($res)) {
      $data[] = (int)$row[0];
    }
    
    return $data;
  }
  
  private function log_self_clear($board, $post_id, $post_json) {
    $sql = <<<SQL
INSERT INTO event_log(`type`, ip, board, post_id, arg_str, pwd, meta)
VALUES('staff_self_clear', '%s', '%s', '%d', '%s', '%s', '%s')
SQL;

    return mysql_global_call($sql, $_SERVER['REMOTE_ADDR'], $board, $post_id,
      $_COOKIE['4chan_auser'], $_COOKIE['4chan_pass'], $post_json
    );
  }
  
  private function update_janitor_stats($username, $action, $board, $post_id, $requested_tpl, $accepted_tpl, $mod_username) {
    $prune_lim = (int)self::STATS_PRUNE_MONTHS;
    
    $query = "SELECT id FROM mod_users WHERE username = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, $username);
    
    if (!$res) {
      return false;
    }
    
    $user_id = (int)mysql_fetch_row($res)[0];
    
    if (!$user_id) {
      return false;
    }
    
    $query = "SELECT id FROM mod_users WHERE username = '%s' LIMIT 1";
    
    $res = mysql_global_call($query, $mod_username);
    
    if (!$res) {
      return false;
    }
    
    $mod_user_id = (int)mysql_fetch_row($res)[0];
    
    if (!$mod_user_id) {
      return false;
    }
    
    $query = <<<SQL
INSERT INTO janitor_stats(user_id, action_type, board, post_id, requested_tpl, accepted_tpl, created_by_id)
VALUES(%d, %d, '%s', %d, %d, %d, %d)
SQL;
    
    $res = mysql_global_call($query, $user_id, $action, $board, $post_id, $requested_tpl, $accepted_tpl, $mod_user_id);
    
    if (!$res) {
      return false;
    }
    
    $query = "DELETE FROM janitor_stats WHERE created_on < DATE_SUB(NOW(), INTERVAL $prune_lim MONTH)";
    
    mysql_global_call($query);
    
    return true;
  }
  
  // Counts ban requests for each board
  private function count_board_brs() {
    $query = "SELECT COUNT(id) as cnt, board FROM ban_requests GROUP BY board";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      return array();
    }
    
    $data = array();
    
    while ($row = mysql_fetch_row($res)) {
      $data[$row[1]] = $row[0];
    }
    
    return $data;
  }
  
  // Counts reports for each board
  private function count_board_reports() {
    $data = array();
    
    if (isset($this->access['board'])) {
      $board_clause = array();
      
      foreach($this->access['board'] as $b) {
        if (!preg_match('/^[a-z0-9]+$/', $b)) {
          return $data;
        }
        
        $board_clause[] = $b;
      }
      
      if (empty($board_clause)) {
        $board_clause = '';
      }
      else {
        $board_clause = "AND board IN('" . implode("','", $board_clause) . "')";
      }
    }
    else {
      $board_clause = '';
    }
    
    $query = <<<SQL
SELECT SUM(weight) as total_weight, board, no, resto
FROM `reports`
WHERE cleared = 0 $board_clause
GROUP BY board, no
SQL;
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      return $data;
    }
    
    $board_coefs = $this->get_board_coefficients();
    
    while ($report = mysql_fetch_assoc($res)) {
      $total_weight = (float)$report['total_weight'];
      
      if (isset($board_coefs[$report['board']])) {
        $total_weight = $total_weight * $board_coefs[$report['board']];
      }
      
      if (!$report['resto']) {
        $total_weight = $total_weight * self::THREAD_WEIGHT_BOOST;
      }
      
      if ($total_weight < 1.00) {
        continue;
      }
      
      if (!isset($data[$report['board']])) {
        $data[$report['board']] = 0;
      }
      
      $data[$report['board']]++;
    }
    
    return $data;
  }
  
  private function is_board_valid($board) {
    if ($board === 'test') {
      return true;
    }
    
    $query = "SELECT dir FROM boardlist WHERE dir = '%s' LIMIT 1";
    
    $result = mysql_global_call($query, $board);
    
    return $result && mysql_num_rows($result) === 1;
  }
  
  private function get_bans_summary($ip, $pass = null) {
    $base_query = <<<SQL
SELECT SQL_NO_CACHE UNIX_TIMESTAMP(`now`) as created_on,
UNIX_TIMESTAMP(`length`) as expires_on,
UNIX_TIMESTAMP(`unbannedon`) as unbanned_on,
template_id
FROM banned_users
SQL;
    
    $query = $base_query . " WHERE host = '%s'";
    
    $res = mysql_global_call($query, $ip);
    
    if (!$res) {
      return array();
    }
    
    $bans = array();
    
    $template_ids = [];
    
    while ($row = mysql_fetch_assoc($res)) {
      $bans[] = $row;
    }
    
    if ($pass) {
      $query = $base_query . " WHERE (active = 0 OR active = 1) AND 4pass_id = '%s' AND host != '%s'";
      
      $res = mysql_global_call($query, $pass, $ip);
      
      if ($res) {
        while ($row = mysql_fetch_assoc($res)) {
          $bans[] = $row;
        }
      }
    }
    
    $now = $_SERVER['REQUEST_TIME'];
    
    $limit = $now - 31536000; // 1 year
    $warn_limit_low = $now - 86400; // 1 day
    $warn_limit_high = $now - 3600; // 1 hour
    
    $total_count = count($bans);
    $active_bans = [];
    $active_warns = [];
    $recent_ban_count = 0;
    $recent_warn_count = 0;
    $recent_duration = 0; // in days
    
    foreach ($bans as $ban) {
      if (!$ban['expires_on']) {
        continue;
      }
      
      if ($ban['created_on'] < $limit) {
        continue;
      }
      
      $ban_len = $ban['expires_on'] - $ban['created_on'];
      
      if ($ban_len <= 10) {
        ++$recent_warn_count;
        
        if ($ban['created_on'] >= $warn_limit_low && $ban['created_on'] <= $warn_limit_high) {
          $active_warns[] = $ban['template_id'];
        }
      }
      else {
        if ($ban['unbanned_on']) {
          $spent_len = $ban['unbanned_on'] - $ban['created_on'];
          
          if ($spent_len > $ban_len) {
            $spent_len = $ban_len;
          }
        }
        else if ($ban['expires_on'] > $now) {
          $spent_len = $now - $ban['created_on'];
          $active_bans[] = $ban['template_id'];
        }
        else {
          $spent_len = $ban_len;
        }
        
        $recent_duration += $spent_len;
        ++$recent_ban_count;
      }
    }
    
    if ($recent_duration) {
      $recent_duration = ceil($recent_duration / 86400.0);
    }
    
    $ret = array(
      'total' => $total_count,
      'recent_bans' => $recent_ban_count,
      'recent_warns' => $recent_warn_count,
      'recent_days' => $recent_duration,
      'active_bans' => $active_bans,
      'active_warns' => $active_warns
    );
    
    return $ret;
  }
  
  private function format_staff_message($msg) {
    return preg_replace('@https://i.4cdn.org/j/[a-z0-9]+/[0-9]+\.(?:jpg|png|gif)@', '<img alt="" src="\\0">', $msg);
  }
  
  private function get_abuse_bans_summary($ip, $pass_id = null) {
    $interval_min = 86400; // 1 day
    $interval_max = self::ABUSE_WARN_UPGRADE_INTERVAL * 86400;
    
    $abuse_tpl = (int)self::REP_ABUSE_TPL;
    
    $base_query = <<<SQL
SELECT SQL_NO_CACHE UNIX_TIMESTAMP(`now`) as created_on,
UNIX_TIMESTAMP(`length`) as expires_on
FROM banned_users
WHERE active = 0 AND template_id = $abuse_tpl
SQL;
    
    $warn_count = 0;
    $ban_count = 0;
    
    if ($pass_id) {
      $query = $base_query . " AND 4pass_id = '%s'";
      $res = mysql_global_call($query, $pass_id);
    }
    else {
      $query = $base_query . " AND host = '%s'";
      $res = mysql_global_call($query, $ip);
    }
    
    if ($res) {
      $now = $_SERVER['REQUEST_TIME'];
      
      $limit_min = $now - $interval_min;
      $limit_max = $now - $interval_max;
      
      while ($ban = mysql_fetch_assoc($res)) {
        if ($ban['created_on'] < $limit_max || $ban['created_on'] > $limit_min) {
          continue;
        }
        
        if ($ban['expires_on'] && ($ban['expires_on'] - $ban['created_on'] <= 10)) {
          $warn_count++;
        }
        else {
          $ban_count++;
        }
      }
    }
    
    return [$warn_count, $ban_count];
  }
  
  private function get_board_list() {
    $query = "SELECT dir FROM boardlist";
    
    $result = mysql_global_call($query);
    
    $boards = array();
    
    if (mysql_num_rows($result) > 0) {
      while ($board = mysql_fetch_array($result)) {
        $boards[] = $board[0];
      }
    }
    
    return $boards;
  }
  
  // weight multipliers for boards
  private function get_board_coefficients() {
    if ($this->cached_board_coefs !== null) {
      return $this->cached_board_coefs;
    }
    
    $query = "SELECT boards, coef FROM report_settings";
    
    $res = mysql_global_call($query);
    
    $data = array();
    
    if (!$res) {
      return $data;
    }
    
    while ($row = mysql_fetch_assoc($res)) {
      $boards = explode(',', $row['boards']);
      
      foreach ($boards as $b) {
        $data[$b] = (float)$row['coef'];
      }
    }
    
    $this->cached_board_coefs = $data;
    
    return $data;
  }
  
  private function can_access_board($board) {
    if (isset($this->access['board'])) {
      return in_array($board, $this->access['board']);
    }
    return true;
  }
  
  private function is_report_unlocked($board, $pid) {
    // Arguments should already be escaped for SQL
    $weight_boost = self::THREAD_WEIGHT_BOOST;
    
    $query = <<<SQL
SELECT CEIL(SUM(IF(resto > 0, weight, weight * $weight_boost))) as total_weight
FROM reports
WHERE board = '$board' AND no = $pid
SQL;
    
    $result = mysql_global_call($query);
    
    if (!$result) {
      return false;
    }
    
    $total_weight = (int)mysql_fetch_row($result)[0];
    
    if (!$total_weight) {
      return false;
    }
    
    return $total_weight >= self::GLOBAL_THRES;
  }
  
  private function delete_by_ip($board, $ip) {
    include_once 'lib/rpc.php';
    
    $url = 'admin.php';
    
    $data = array(
      'admin' => 'delallbyip',
      'ip' => $ip
    );
    
    rpc_start_request("https://sys.int/$board/admin.php", $data, $_COOKIE, true);
  }
  
  private function fetch_reports($board, $cleared_only = false, $ws_mode = self::WS_ANY) {
    /* board */
    $board_clause = null;
    
    if (isset($this->access['board'])) {
      if ($board === false) {
        $board_clause = array();
        
        foreach($this->access['board'] as $b) {
          $board_clause[] = "reports.board = '" . mysql_real_escape_string($b) . "'";
        }
        
        $board_clause = '(' . implode(' OR ', $board_clause) . ')';
      }
      elseif (!in_array($board, $this->access['board'])) {
        $this->error("You can't view reports for this board.");
      }
    }
    
    $cleared_only = (int)$cleared_only;
    
    $where = "WHERE cleared = $cleared_only";
    
    $prio = 'total_weight >= ' . self::GLOBAL_THRES;
    
    if ($board) {
      $board = mysql_real_escape_string($board);
      $having = "HAVING (reports.board = '$board' OR $prio)";
    }
    else if ($ws_mode === self::WS_ONLY) {
      $having = "HAVING (reports.ws = 1 OR $prio)";
    }
    else if ($ws_mode === self::WS_NOT) {
      $having = "HAVING (reports.ws = 0 OR $prio)";
    }
    else if ($board_clause) {
      $having = "HAVING ($board_clause OR $prio)";
    }
    
    $weight_boost = self::THREAD_WEIGHT_BOOST;
    
    $query = <<<SQL
SELECT *, SUM(weight) as total_weight, COUNT(reports.no) as cnt,
GROUP_CONCAT(DISTINCT report_category) as cats,
UNIX_TIMESTAMP(ts) as `time`
FROM reports
$where
GROUP BY reports.no, reports.board
$having
SQL;
    
    return mysql_global_call($query);
  }
  
  private function fetch_report_counts() {
    $board_clause = null;
    
    if (isset($this->access['board'])) {
      $board_clause = array();
      
      foreach($this->access['board'] as $b) {
        $board_clause[] = "reports.board = '" . mysql_real_escape_string($b) . "'";
      }
      
      $board_clause = '(' . implode(' OR ', $board_clause) . ')';
    }
    
    $having = "HAVING total_weight >= 0";
    
    if ($board_clause) {
      $prio = 'total_weight >= ' . self::GLOBAL_THRES;
      $having .= " AND ($board_clause OR $prio)";
    }
    
    $query = <<<SQL
SELECT SUM(weight) as total_weight, resto, reports.board
FROM reports
WHERE cleared = 0
GROUP BY reports.no, reports.board
$having
SQL;
    
    return mysql_global_call($query);
  }
  
  private function build_report($report) {
    $data = array(
      'id'          => (int)$report['id'],
      'board'       => $report['board'],
      'no'          => (int)$report['no'],
      'count'       => (int)$report['cnt'],
      'weight'      => (float)$report['total_weight'],
      'ts'          => (int)$report['time'],
      'post'        => $report['post_json']
    );
    
    return $data;
  }
  
  private function clear_ban_request($id, $salt, $has_approved = false) {
    $id = (int)$id;
    
    $result = mysql_global_call("SELECT * FROM ban_requests WHERE id = $id");
    
    if (!mysql_num_rows($result)) {
      return false;
    }
    
    $request = mysql_fetch_array($result);
    
    $result = mysql_global_call("DELETE FROM ban_requests WHERE id = $id");
    
    if (!$result) {
      $this->error('Database error (cbr0)');
    }
    
    $post = json_decode($request['post_json']);
    $hash = sha1($request['board'] . $post->no . $salt);
    
    $ban_thumb_dir = '/www/4chan.org/web/images/bans/thumb/';
    $ban_image_dir = '/www/4chan.org/web/images/bans/src/';
    
    @unlink($ban_image_dir . "{$request['board']}/$hash{$post->ext}");
    if (!$has_approved) {
      @unlink($ban_thumb_dir . "{$request['board']}/{$hash}s.jpg");
    }
    
    // Janitor stats
    if (!$has_approved) {
      $query = <<<SQL
INSERT INTO ban_request_stats(janitor, approved, denied)
VALUES('%s', 0, 1) ON DUPLICATE KEY UPDATE denied = denied + 1
SQL;
      $result = mysql_global_call($query, $request['janitor']);
      
      if (!$result) {
        $this->error('Database error (cbr1)');
      }
      
      // Janitor stats (2)
      $this->update_janitor_stats(
        $request['janitor'],
        self::STATS_ACTION_DENIED,
        $request['board'],
        $post->no,
        $request['ban_template'],
        0,
        $_COOKIE['4chan_auser']
      );
    }
  }
  
  private function hard_clear_report($board, $no) {
    // Arguments should already be escaped for SQL
    $query = "DELETE FROM reports WHERE board = '$board' AND no = $no";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error("Failed to delete reports for /$board/$no");
    }
    
    $query = "DELETE FROM reports_for_posts WHERE board = '$board' AND postid = $no";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error("Failed to delete reports for /$board/$no");
    }
    
    return true;
  }
  
  private function should_ban_global($ip) {
    $thres = (int)self::LOCAL_TO_GLOBAL_THRES;
    
    if ($thres <= 0) {
      return false;
    }
    
    $query =<<<SQL
SELECT SQL_NO_CACHE board FROM banned_users
WHERE active = 1 AND host = '%s' AND length > NOW() AND global = 0
GROUP BY board
SQL;

    $res = mysql_global_call($query, $ip);
    
    if (!$res) {
      return false;
    }
    
    if (mysql_num_rows($res) >= $thres) {
      return true;
    }
    
    return false;
  }
  
  private function clear_orphaned_reports() {
    $query =<<<SQL
SELECT reports_for_posts.board, reports_for_posts.postid FROM reports_for_posts
LEFT JOIN reports
ON reports.no = reports_for_posts.postid
AND reports.board = reports_for_posts.board
WHERE reports.no IS null
SQL;
    
    $res = mysql_global_call($query);
    
    while ($row = mysql_fetch_assoc($res)) {
      $board = $row['board'];
      $postid = $row['postid'];
      mysql_global_call("DELETE FROM reports_for_posts WHERE board = '$board' AND postid = $postid");
    }
    
    return true;
  }
  
  public function clear_stale_reports() {
    if (isset($_GET['board'])) {
      $board = mysql_real_escape_string($_GET['board']);
    }
    else {
      $this->error('Missing board');
    }
    
    if (!in_array($board, $this->get_board_list())) {
      $this->error('Invalid board');
    }
    
    $post_ids = array();
    $report_ids = array();
    
    // fetch reports
    $query = "SELECT postid FROM reports_for_posts WHERE board = '$board'";
    $res = mysql_global_call($query);
    
    while ($row = mysql_fetch_assoc($res)) {
      $report_ids[] = $row['postid'];
    }
    
    if (empty($report_ids)) {
      $this->success();
      return;
    }
    
    // fetch posts
    $query = "SELECT no FROM `$board` WHERE no IN(" . implode(',', $report_ids) . ")";
    $res = mysql_board_call($query);
    
    if (mysql_num_rows($res) == count($report_ids)) {
      $this->success();
      return;
    }
    
    // delete reports
    while ($row = mysql_fetch_assoc($res)) {
      $post_ids[] = $row['no'];
    }
    
    $delete_ids = array_diff($report_ids, $post_ids);
    
    $delete_ids = implode(',', $delete_ids);
    
    $query = "DELETE FROM reports WHERE board = '$board' AND no IN($delete_ids)";
    mysql_global_call($query);
    
    $query = "DELETE FROM reports_for_posts WHERE board = '$board' AND postid IN($delete_ids)";
    mysql_global_call($query);
    
    $del_count = mysql_affected_rows();
    
    $this->success(array('count' => $del_count));
  }
  
  // $ids should already be SQL-safe
  private function get_report_categories_by_ids($ids) {
    $ids = implode(',', $ids);
    
    $sql = "SELECT id, title FROM report_categories WHERE id IN($ids) ORDER BY id ASC";
    
    $res = mysql_global_call($sql);
    
    $data = [];
    
    if (!$res) {
      return $data;
    }
    
    while ($row = mysql_fetch_assoc($res)) {
      $data[$row['id']] = $row['title'];
    }
    
    return $data;
  }
  
  public function get_templates() {
    if (isset($_GET['board'])) {
      $board = '/' . $_GET['board'] . '/';
    }
    else {
      $this->error('Missing board');
    }
    
    $level_map = get_level_map($this->userLevel);
    
    $query = 'SELECT no, name, rule, bantype, publicreason, days, level, can_warn FROM ban_templates ORDER BY length(rule), rule ASC';
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error('Database error');
    }
    
    $templates = array();
    
    while ($tpl = mysql_fetch_assoc($res)) {
      if (!isset($level_map[$tpl['level']]) || $level_map[$tpl['level']] !== true) {
        continue;
      }
      
      if (preg_match('#^(global|' . $_GET['board'] . ')[0-9]+$#', $tpl['rule'])) {
        unset($tpl['level']);
        unset($tpl['rule']);
        $templates[] = $tpl;
      }
    }
    
    $this->success($templates);
  }
  
  // Fetch board specific templates and a selection of global templates
  // for use in the quick ban/br function
  private function get_quickable_templates($boards) {
    $boards_regex = implode('|', $boards);
    
    // no => [boards to skip]
    $global_ids = [
      6   => false,       // Global 5 - NWS on Worksafe Board
      222 => ['s4s'],     // Global 3 - Troll posts
      223 => ['pol'],     // Global 3 - Racism
      135 => false,       // Global 9 - Ban Evasion [Temp]
    ];
    
    $file_only_tpl = 6;
    $ws_only_tpl = 6;
    
    $level_map = get_level_map($this->userLevel);
    
    $query = 'SELECT no, name, rule, level, postban, postban_arg FROM ban_templates ORDER BY length(rule), rule ASC';
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error('Database error');
    }
    
    $templates = [];
    
    while ($tpl = mysql_fetch_assoc($res)) {
      if (!isset($level_map[$tpl['level']]) || $level_map[$tpl['level']] !== true) {
        continue;
      }
      
      if ($tpl['postban'] === 'move' && !$tpl['postban_arg']) {
        continue;
      }
      
      if (isset($global_ids[$tpl['no']]) || preg_match("#^($boards_regex)[0-9]+$#", $tpl['rule'])) {
        $board = preg_replace('/^([a-z]+|3)+[0-9]+x?$/', '$1', $tpl['rule']);
        
        $tpl_data = [
          'no' => (int)$tpl['no'],
          'name' => $tpl['name']
        ];
        
        if ($tpl['no'] == $file_only_tpl) {
          $tpl_data['file_only'] = 1;
        }
        
        if ($tpl['no'] == $ws_only_tpl) {
          $tpl_data['ws_only'] = 1;
        }
        
        $skip_boards = $global_ids[$tpl['no']];
        
        if ($skip_boards) {
          $tpl_data['skip'] = $skip_boards;
        }
        
        if (!isset($templates[$board])) {
          $templates[$board] = [];
        }
        
        $templates[$board][] = $tpl_data;
      }
    }
    
    return $templates;
  }
  
  public function count_reports() {
    // Access check
    if ($this->access['clear'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    $data = array();
    
    if ($this->access['ban']) {
      // User is a mod
      // Ban requests
      $result = mysql_global_call('SELECT COUNT(*) FROM ban_requests');
      $data['banreqs'] = (int)mysql_fetch_row($result)[0];
      
      // Priority ban requests
      $in_tpl = array();
      $result = mysql_global_call("SELECT no FROM ban_templates WHERE rule = 'global1'");
      while ($tid = mysql_fetch_row($result)) {
        $in_tpl[] = $tid[0];
      }
      $in_tpl = implode(',', $in_tpl);
      $result = mysql_global_call("SELECT COUNT(*) FROM ban_requests WHERE ban_template IN ($in_tpl)");
      $data['illegal_banreqs'] = (int)mysql_fetch_row($result)[0];
      
      // Appeals
      $query = <<<SQL
SELECT ban.4pass_id FROM banned_users ban
LEFT OUTER JOIN appeals ON appeals.no = ban.no
WHERE ban.active = 1
AND appeals.closed = 0
AND appeals.updated != 0
AND (ban.length = 0 or ban.length >= NOW())
SQL;
      $result = mysql_global_call($query);
      
      $data['appeals'] = mysql_num_rows($result);
      $prio_appeal_count = 0;
      
      while ($row = mysql_fetch_row($result)) {
        if ($row[0] !== '') {
          $prio_appeal_count += 1;
        }
      }
      
      $data['prio_appeals'] = $prio_appeal_count;
    }
    
    // Reports
    $result = $this->fetch_report_counts();
    
    if (!$result) {
      $this->error('Error while fetching the report count');
    }
    
    $report_count = 0;
    $report_count_illegal = 0;
    
    $board_coefs = $this->get_board_coefficients();
    
    $hl_thres = $this->isMod ? self::HIGHLIGHT_THRES : self::GLOBAL_THRES;
    
    while ($report = mysql_fetch_assoc($result)) {
      $total_weight = (float)$report['total_weight'];
      
      if (isset($board_coefs[$report['board']])) {
        $total_weight = $total_weight * $board_coefs[$report['board']];
      }
      
      if (!$report['resto']) {
        $total_weight = $total_weight * self::THREAD_WEIGHT_BOOST;
      }
      
      if ($total_weight >= 1.00) {
        $report_count++;
      }
      
      if ($total_weight >= $hl_thres) {
        $report_count_illegal++;
      }
    }
    
    $data['total'] = $report_count;
    $data['illegal'] = $report_count_illegal;
    
    // Thread flood detection
    if ($this->isDeveloper) {
      $flood_stats = $this->get_flood_stats();
      
      if ($flood_stats) {
        $data['flood'] = $flood_stats;
      }
    }
    
    $unread_msg = $this->staffmessages();
    
    if ($unread_msg) {
      $data['msg'] = $unread_msg;
    }
    
    $this->success($data);
  }
  
  public function get_ip() {
    // Access check
    if ($this->access['ban'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if (isset($_GET['board'])) {
      $board = mysql_real_escape_string($_GET['board']);
    }
    else {
      $this->error('Missing board');
    }
    
    if (isset($_GET['no'])) {
      $no = (int)$_GET['no'];
    }
    else {
      $this->error('Missing post ID');
    }
    
    $post = mysql_board_get_post($board, $no);
    
    if (!$post) {
      $this->error("This post doesn't exist anymore");
    }
    
    $this->success($post['host']);
  }
  
  public function get_ban_requests() {
    if ($this->access['ban'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if (isset($_GET['board'])) {
      $board = "WHERE board = '" . mysql_real_escape_string($_GET['board']) . "'";
      $order = "ban_template ASC, ts ASC";
    }
    else {
      $board = "WHERE board != 'test'";
      $order = "ts ASC";
    }
    
    // Getting templates
    $all_templates = array();
    
    $result = mysql_global_call('SELECT * FROM ban_templates');
    
    if (!mysql_num_rows($result)) {
      $this->error("Couldn't get ban templates");
    }
    
    while ($tpl = mysql_fetch_assoc($result)) {
      $all_templates[$tpl['no']] = $tpl;
    }
    
    // Getting requests
    $query = <<<SQL
SELECT SQL_NO_CACHE id, host, reverse, ban_template, board, janitor, post_json as post, spost,
warn_req, UNIX_TIMESTAMP(ts) as time
FROM ban_requests $board
ORDER BY $order
SQL;
    
    $result = mysql_global_call($query);
    
    $templates = array();
    $requests = array();
    
    $cached_ban_history = array();
    
    while ($req = mysql_fetch_assoc($result)) {
      if (!isset($templates[$req['ban_template']])) {
        $templates[$req['ban_template']] = true;
      }
      
      $post = unserialize($req['spost']);
      
      unset($req['spost']);
      
      $link = return_archive_link($req['board'], $post['no'], false, true);
      
      if ($link !== false) {
        $req['link'] = $link;
      }
      
      // Get ban history
      if (isset($cached_ban_history[$req['host']])) {
        $ban_history = $cached_ban_history[$req['host']];
      }
      else {
        $ban_history = $this->get_bans_summary($req['host'], $post['4pass_id']);
        $cached_ban_history[$req['host']] = $ban_history;
        
        foreach($ban_history['active_bans'] as $_tpl_id) {
          $templates[$_tpl_id] = true;
        }
        
        foreach($ban_history['active_warns'] as $_tpl_id) {
          $templates[$_tpl_id] = true;
        }
      }
      
      if ($ban_history['total'] > 0) {
        $req['ban_history'] = $ban_history;
      }
      
      if ($post['4pass_id']) {
        $req['pass_user'] = true;
      }
      
      $req['pwd'] = $post['pwd'];
      
      // Geo location
      $geoinfo = GeoIP2::get_country($req['host']);
      
      if ($geoinfo && isset($geoinfo['country_code'])) {
        $geo_loc = array();
        
        if (isset($geoinfo['city_name'])) {
          $geo_loc[] = $geoinfo['city_name'];
        }
        
        if (isset($geoinfo['state_code'])) {
          $geo_loc[] = $geoinfo['state_code'];
        }
        
        $geo_loc[] = $geoinfo['country_name'];
        
        $req['geo_loc'] = implode(', ', $geo_loc);
      }
      
      // ASN
      $asninfo = GeoIP2::get_asn($req['host']);
      
      if ($asninfo) {
        $req['asn_name'] = $asninfo['aso'];
      }
      
      // Put priority requests on top
      if (isset($all_templates[$req['ban_template']])) {
        $tpl_rule = $all_templates[$req['ban_template']]['rule'];
      }
      else {
        $tpl_rule = null;
      }
      
      if ($tpl_rule === 'global1' || $tpl_rule === 'hm6' || $tpl_rule === 's6') {
        array_unshift($requests, $req);
      }
      else {
        $requests[] = $req;
      }
    }
    
    // TODO: Sort requests by template priority
    
    // Only keep relevant templates
    foreach ($templates as $id => &$tpl) {
      $tpl = array(
        'name' => $all_templates[$id]['name'],
        'reason' => $all_templates[$id]['publicreason'],
        'is_warn' => !$all_templates[$id]['days'] && !$all_templates[$id]['banlen'],
        'is_global' => $all_templates[$id]['bantype'] === 'global'
      );
      
      if (!$tpl['is_warn']) {
        $tpl['days'] = $all_templates[$id]['days'];
      }
      
      if ($all_templates[$id]['rule'] === 'global1') {
        $tpl['global1'] = true;
      }
    }
    
    $counts = $this->count_board_brs();
    
    $this->success(array(
      'templates' => $templates,
      'requests' => $requests,
      'counts' => $counts
    ));
  }
  
  public function reports() {
    // Access check
    if ($this->access['clear'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if ($this->isMod) {
      $this->disAccess = false;
      $this->boardlist = $this->get_board_list();
    }
    else {
      $this->disAccess = false;
      
      if (!isset($this->access['board'])) {
        $this->disAccess = false;
        $this->boardlist = $this->get_board_list();
      }
      else {
        if (in_array('dis', $this->access['board'])) {
          $this->disAccess = false;
        }
        $this->boardlist = $this->access['board'];
      }
    }
    
    $this->renderHTML('reportqueue-test');
  }
  
  public function ban_requests() {
    // Access check
    if ($this->access['ban'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    $this->boardlist = $this->get_board_list();
    
    $this->renderHTML('banrequests-test');
  }
  
  public function get_reporters() {
    // Access check
    if ($this->access['ban_reporter'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if (isset($_GET['no'])) {
      $no = (int)$_GET['no'];
    }
    else {
      $this->error('Missing no');
    }
    
    if (isset($_GET['board'])) {
      $board = mysql_real_escape_string($_GET['board']);
    }
    else {
      $this->error('Missing board');
    }
    
    $query = <<<SQL
SELECT SQL_NO_CACHE reports.id, cat, ip, reports.weight, title as cat, UNIX_TIMESTAMP(ts) as time
FROM reports
LEFT JOIN report_categories ON report_categories.id = report_category
WHERE reports.board = '$board'
AND no = $no
ORDER BY ts DESC
SQL;
    
    $result = mysql_global_call($query);
    
    if (mysql_num_rows($result) > 0) {
      $data = array();
      
      while ($user = mysql_fetch_assoc($result)) {
        $user['ipStr'] = long2ip($user['ip']);
        
        $geoinfo = GeoIP2::get_country($user['ipStr']);
        
        if ($geoinfo && isset($geoinfo['country_code'])) {
          $user['country'] = $geoinfo['country_code'];
        }
        
        $data[] = $user;
      }
    }
    else {
      $this->error('Nothing found');
    }
    
    $this->success($data);
  }
  
  public function get_rep_details() {
    // Access check
    if ($this->access['ban_reporter'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    $has_pass = false;
    
    if (isset($_GET['rid'])) {
      $rid = (int)$_GET['rid'];
      
      $query = "SELECT SQL_NO_CACHE ip, 4pass_id, pwd FROM reports WHERE id = $rid LIMIT 1";
      
      $result = mysql_global_call($query);
      
      if (!$result) {
        $this->error('Database Error');
      }
      
      $report = mysql_fetch_assoc($result);
      
      if (!$report) {
        $this->error('Report not found');
      }
      
      $ip_str = long2ip($report['ip']);
      
      if (!$ip_str) {
        $this->error('Internal Server Error (1)');
      }
      
      $clauses = array(
        'ip = ' . (int)$report['ip']
      );
      
      $ban_clauses = array(
        "host = '$ip_str'"
      );
      
      if ($report['4pass_id']) {
        $pass_id_sql = mysql_real_escape_string($report['4pass_id']);
        
        if (!$pass_id_sql) {
          $this->error('Internal Server Error (2)');
        }
        
        $clauses[] = "4pass_id = '$pass_id_sql'";
        $ban_clauses[] = "4pass_id = '$pass_id_sql'";
        
        $has_pass = true;
      }
      
      if ($report['pwd']) {
        $pwd_sql = mysql_real_escape_string($report['pwd']);
        
        if (!$pwd_sql) {
          $this->error('Internal Server Error (3)');
        }
        
        $clauses[] = "pwd = '$pwd_sql'";
        $ban_clauses[] = "password = '$pwd_sql'";
      }
    }
    else if (isset($_GET['ip'])) {
      $ip = ip2long($_GET['ip']);
      
      if (!$ip) {
        $this->error('Invalid IP');
      }
      
      $clauses = array(
        'ip = ' . $ip
      );
    }
    else {
      $this->error('Missing parameters');
    }
    
    $clauses = implode(' OR ', $clauses);
    
    $query = <<<SQL
SELECT SQL_NO_CACHE reports.id, reports.board, reports.weight, title as cat, post_json,
UNIX_TIMESTAMP(ts) as time, cleared, no, ip
FROM reports
LEFT JOIN report_categories ON report_categories.id = report_category
WHERE ($clauses)
ORDER BY ts DESC
SQL;
    
    $result = mysql_global_call($query);
    
    if (!$result) {
      $this->error('Database Error');
    }
    
    $reports = array();
    
    $country_cache = [];
    
    while ($report = mysql_fetch_assoc($result)) {
      $report['ip'] = long2ip($report['ip']);
      
      $rep_ip = $report['ip'];
      
      if (isset($country_cache[$rep_ip])) {
        $report['country'] = $country_cache[$rep_ip];
      }
      else {
        $geoinfo = GeoIP2::get_country($rep_ip);
        
        if ($geoinfo && isset($geoinfo['country_code'])) {
          $report['country'] = $geoinfo['country_code'];
          $country_cache[$rep_ip] = $geoinfo['country_code'];
        }
      }
      
      unset($report['4pass_id']);
      unset($report['pwd']);
      
      $reports[] = $report;
    }
    
    if (empty($reports)) {
      $this->error('Nothing found');
    }
    
    $data = array('reports' => $reports);
    
    if (isset($country_cache[$ip_str])) {
      $data['country'] = $country_cache[$ip_str];
    }
    
    if ($has_pass) {
      $data['has_pass'] = true;
    }
    
    $this->success($data);
  }
  
  public function ban_reporters() {
    // Access check
    if ($this->access['ban_reporter'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    // Single reporter ban
    if (isset($_POST['rid'])) {
      $rid = (int)$_POST['rid'];
      
      if (!$rid) {
        $this->error('Bad Request');
      }
    }
    else {
      $rid = null;
    }
    
    if (isset($_POST['board']) && isset($_POST['pid'])) {
      $board = $_POST['board'];
      $pid = (int)$_POST['pid'];
      
      if ($board === '' || !$pid) {
        $this->error('Bad Request');
      }
      
      if (!$this->is_board_valid($board)) {
        $this->error('Invalid board');
      }
    }
    else {
      $this->error('Missing parameters');
    }
    
    // Fetch reported post
    $query = "SELECT SQL_NO_CACHE * FROM `%s` WHERE no = $pid LIMIT 1";
    $result = mysql_board_call($query, $board);
    
    if (!$result) {
      $this->error('Database error (1)');
    }
    
    $post = mysql_fetch_assoc($result);
    
    if (!$post) {
      $this->error("Couldn't find reported post");
    }
    
    if ($post['ext'] !== '') {
      $post['filedeleted'] = 1;
    }
    
    // Prepare ban/warn template
    $tpl_id = (int)self::REP_ABUSE_TPL;
    
    $result = mysql_global_call("SELECT * FROM ban_templates WHERE no = $tpl_id");
    
    if (!mysql_num_rows($result)) {
      $this->error("Couldn't get the ban template");
    }
    
    $template = mysql_fetch_assoc($result);
    
    $tpl_days = $template['days'];
    
    // Fetch reporters
    $query = "SELECT SQL_NO_CACHE ip, pwd, 4pass_id, weight, report_category FROM reports WHERE board = '%s' AND no = $pid";
    
    if ($rid) {
      $query .= " AND id = $rid";
    }
    
    $result = mysql_global_call($query, $board);
    
    if (!$result) {
      $this->error('Database error (2)');
    }
    
    $ip_count = mysql_num_rows($result);
    
    if (!$ip_count) {
      $this->error("Nothing to do");
    }
    
    $is_warn = isset($_POST['warn']) && $_POST['warn'];
    
    $reporters = array();
    
    while ($row = mysql_fetch_assoc($result)) {
      $ipStr = long2ip((int)$row['ip']);
      
      if (!$ipStr) {
        continue;
      }
      
      if ($ip_count < 15) {
        $reverse = gethostbyaddr($ipStr);
      }
      else {
        $reverse = $ipStr;
      }
      
      $warn_upgraded = false;
      
      if ($is_warn) {
        list($warn_count, $ban_count) = $this->get_abuse_bans_summary($ipStr, $row['4pass_id']);
        
        if ($ban_count >= 2) {
          $days = $tpl_days;
          $warn_upgraded = true;
        }
        else if ($ban_count > 0 || $warn_count >= 2) {
          $days = 1;
          $warn_upgraded = true;
        }
        else {
          $days = 0;
        }
      }
      else {
        $days = $tpl_days;
      }
      
      $reporters[] = array(
        'ip' => $ipStr,
        'reverse' => $reverse,
        'report_cat' => (int)$row['report_category'],
        'report_weight' => (int)$row['weight'],
        'pwd' => $row['pwd'],
        'pass_id' => $row['4pass_id'],
        'days' => $days,
        'warn_upgraded' => $warn_upgraded
      );
    }
    
    $rule = $template['rule'];
    
    // Insert bans
    $query =<<<SQL
INSERT INTO banned_users (
  board, global, zonly, name, host, reverse, xff, reason, length,
  admin, md5, post_num, rule, post_time, template_id, password, 4pass_id,
  post_json, admin_ip
) 
VALUES ('%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s',
  '%s', '', %d, '%s', '', %d, '%s', '%s',
  '%s', '%s')
SQL;
    
    $total = 0;
    
    foreach ($reporters as $rep) {
      $length = date('Y-m-d H:i:s', time() + ($rep['days'] * (24 * 60 * 60)));
      
      $ipStr = $rep['ip'];
      $reverse = $rep['reverse'];
      
      $post['report_weight'] = $rep['report_weight'];
      $post['report_cat'] = $rep['report_cat'];
      
      $pass_id = $rep['pass_id'];
      
      $post_json = json_encode($post, JSON_PARTIAL_OUTPUT_ON_ERROR);
      
      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->error('Internal Server Error');
      }
      
      $private_reason = [];
      
      if ($template['privatereason']) {
        $private_reason[] = $template['privatereason'];
      }
      
      if ($rep['warn_upgraded']) {
        $private_reason[] = 'auto-upgraded from a warning';
      }
      
      $reason = $template['publicreason'] . '<>' . implode(', ', $private_reason);
      
      $result = mysql_global_call($query,
        $board, 1, 0, 'Anonymous', $ipStr, $reverse, '', $reason, $length, 
        $_COOKIE['4chan_auser'], $pid, $rule, $tpl_id, $rep['pwd'], $pass_id,
        $post_json, $_SERVER['REMOTE_ADDR']
      );
      
      if (!$result) {
        $this->error('Database error (i)');
      }
      
      $total++;
    }
    
    if ($total > 0) {
      $this->success("Done. $total IP(s) affected");
    }
    else {
      $this->error('Nothing to do');
    }
  }
  
  public function clear_reporter() {
    // Access check
    if ($this->access['clear_reporter'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if (isset($_POST['rid'])) {
      $rid = (int)$_POST['rid'];
      
      $res = mysql_global_call("SELECT SQL_NO_CACHE ip, pwd, 4pass_id FROM reports WHERE id = $rid LIMIT 1");
      
      if (!$res) {
        $this->error('Database error');
      }
      
      $report = mysql_fetch_assoc($res);
      
      if (!$report) {
        $this->error('Report not found');
      }
    }
    else if (isset($_POST['ip'])) {
      $long_ip = ip2long($_POST['ip']);
      
      if (!$long_ip) {
        $this->error('Invalid IP');
      }
      
      $report = array(
        'ip' => $long_ip,
        'pwd' => '',
        '4pass_id' => ''
      );
    }
    else {
      $this->error('Missing report ID');
    }
    
    // ---
    
    $clauses = array(
      'ip = ' . (int)$report['ip']
    );
    
    if ($report['4pass_id']) {
      $param = mysql_real_escape_string($report['4pass_id']);
      
      if ($param) {
        $clauses[] = "4pass_id = '$param'";
      }
    }
    
    if ($report['pwd']) {
      $param = mysql_real_escape_string($report['pwd']);
      
      if ($param) {
        $clauses[] = "pwd = '$param'";
      }
    }
    
    $clauses = implode(' OR ', $clauses);
    
    mysql_global_call("DELETE FROM reports WHERE $clauses");
    
    $affected = mysql_affected_rows();
    
    if ($affected === -1) {
      $this->error('Database error');
    }
    else {
      $this->clear_orphaned_reports();
      /*
      $this->log_cleared_reporter(
        $report['ip'],
        $report['pwd'],
        $report['4pass_id'],
        0, 1.0
      );
      */
      $this->success(array('affected' => $affected));
    }
  }
  
  public function accept_ban_request() {
    // Access check
    if ($this->access['ban'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    // $this->error('Disabled');
    
    if (isset($_POST['id'])) {
      $id = (int)$_POST['id'];
    }
    else {
      $this->error('Missing ID');
    }
    
    // Fetching the request
    $query = "SELECT * FROM ban_requests WHERE id = {$id}";
    $queries[] = $query;
    
    $result = mysql_global_call($query);
    
    if (!mysql_num_rows($result)) {
      $this->error("This request doesn't exist anymore", 404);
    }
    
    $request = mysql_fetch_assoc($result);
    
    $amend_length = false;
    
    // Fetching ban template
    if (isset($_POST['amend_tpl'])) {
      $req_amend = true;
      $template_id = (int)$_POST['amend_tpl'];
      
      if (isset($_POST['ban_length'])) {
        $amend_length = (int)$_POST['ban_length'];
        
        if ($amend_length < -1 || $amend_length > 1024) {
          $this->error('Invalid length');
        }
      }
    }
    else {
      $req_amend = false;
      $template_id = (int)$request['ban_template'];
    }
    
    if ($amend_length !== false && $amend_length === 0) {
      $warn = true;
    }
    else if (!$req_amend && $request['warn_req']) {
      $warn = true;
    }
    else {
      $warn = false;
    }
    
    // Don't allow pass revoke templates
    if (!$template_id || in_array($template_id, array(130, 131))) {
      $this->error('Invalid template');
    }
    
    $query = "SELECT * FROM ban_templates WHERE no = $template_id";
    $result = mysql_global_call($query);
    
    if (!mysql_num_rows($result)) {
      $this->error("Couldn't get the ban template");
    }
    
    $template = mysql_fetch_assoc($result);
    
    $level_map = get_level_map($this->userLevel);
    
    if ($level_map[$template['level']] !== true) {
      $this->error('You cannot use this template');
    }
    
    // Inserting the ban
    $post = json_decode($request['post_json']);
    
    $spost = unserialize($request['spost']);
    
    $pass_id = $spost['4pass_id'];
    
    $board = $request['board'];
    
    if ($warn) {
      $length = date( "Y-m-d H:i:s", time());
    }
    else if ($amend_length !== false) {
      if ($amend_length < 0) {
        $length = '0000-00-00 00:00:00';
      }
      else {
        $length = date( "Y-m-d H:i:s", time() + ($amend_length * (24 * 60 * 60)));
      }
    }
    else if ($template['banlen'] == 'indefinite') {
      $length = '0000-00-00 00:00:00';
    }
    else {
      if ($req_amend && $template['days'] === '0' && !$warn) {
        $tpl_days = 1;
      }
      else {
        $tpl_days = $template['days'];
      }
      $length = date( "Y-m-d H:i:s", time() + ($tpl_days * (24 * 60 * 60)));
    }
    
    if ($req_amend && !$warn && isset($_POST['global'])) {
      $global = $_POST['global'] === '1';
    }
    else {
      $global = $template['bantype'] == 'global';
    }
    
    // Delete the file if the request was amended to a template with save_json = json_only
    if ($req_amend && $post->ext && $template['save_post'] == 'json_only') {
      $salt = file_get_contents('/www/keys/legacy.salt');
      
      $hash = sha1($request['board'] . $post->no . $salt);
      
      $ban_thumb_dir = '/www/4chan.org/web/images/bans/thumb/';
      $ban_image_dir = '/www/4chan.org/web/images/bans/src/';
      
      unlink($ban_image_dir . "{$request['board']}/$hash{$post->ext}");
      
      if (!$has_approved) {
        unlink($ban_thumb_dir . "{$request['board']}/{$hash}s.jpg");
      }
      
      $post->raw_md5 = $spost['md5'];
    }
    
    if ($req_amend && isset($_POST['public_reason']) && $_POST['public_reason'] !== '') {
      $public_reason = nl2br(htmlspecialchars($_POST['public_reason']), false);
    }
    else {
      $public_reason = nl2br($template['publicreason'], false);
    }
    
    if ($template['privatereason']) {
      $reason = "{$public_reason}<>{$template['privatereason']}, ban requested by {$request['janitor']}";
    }
    else {
      $reason = "{$public_reason}<>ban requested by {$request['janitor']}";
    }
    
    if ($req_amend && isset($_POST['private_reason']) && $_POST['private_reason'] !== '') {
      $reason .= '. ' . htmlspecialchars($_POST['private_reason'], ENT_QUOTES);
    }
    
    $rule = $template['rule'];
    
    $name = $spost['name'];
    $tripcode = '';
    
    $name_bits = explode('</span> <span class="postertrip">!', $name);
    
    if ($name_bits[1]) {
      $tripcode = preg_replace('/<[^>]+>/', '', $name_bits[1]); // fixme: why do we do that?
    }
    else {
      $tripcode = '';
    }
    
    $name = str_replace('</span> <span class="postertrip">!', ' #', $name);
    $name = preg_replace('/<[^>]+>/', '', $name);
    
    if ($request['host'] != '') {
      // Check if ban should be changed to global
      if (!$req_amend && !$warn && !$global) {
        if ($this->should_ban_global($request['host'])) {
          $global = true;
        }
      }
      
      // FIXME: email field
      if (isset($spost['email'])) {
        $spost['ua'] = $spost['email'];
        unset($spost['email']);
      }
      
      $post_json = json_encode($spost);
      
      $result = mysql_global_call(
        "INSERT INTO " . SQLLOGBAN . " (
          board, global, zonly, name, host, reverse, xff, reason, length, admin,
          md5, post_num, rule, post_time, template_id, post_json, 4pass_id, admin_ip,
          tripcode, password
        ) 
        VALUES ('%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d,
          '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s');",
        $board, $global, 0, $name, $request['host'], $request['reverse'], $request['xff'], $reason,
        $length, $_COOKIE['4chan_auser'], $spost['md5'], $post->no, $rule, $post->time, $template_id,
        $post_json, $pass_id, $_SERVER['REMOTE_ADDR'], $tripcode, $spost['pwd']
      );
      
      if (!$result) {
        $this->error('Database error (abr0)');
      }
      
      $ban_id = mysql_global_insert_id();
      
      $ban_string = $global ? 'global' : $board;
      
      // Delete all by ip
      if (!$warn && $template['postban'] == 'delall') {
        //$this->delete_by_ip($request['board'], $request['host']);
      }
    }
    else {
      $ban_id = 0;
    }
    
    // Blacklisting
    if (!$warn && ($template['blacklist'] == 'image' || $template['blacklist'] == 'rejectimage') && $post->raw_md5) {
      global $gcon;
      
      $inserted_ban_id = mysql_insert_id($gcon);
      
      $bl_ban = $template['blacklist'] == 'image' ? '1' : '0';
      
      $query = <<<SQL
INSERT INTO blacklist(field, contents, description, addedby, ban, banlength, banreason)
VALUES('md5','%s','%s','%s','$bl_ban','-1','%s')
SQL;
      
      $result = mysql_global_call($query,
        $post->raw_md5,
        "{$template['publicreason']} (via ban template, ban requested by {$request['janitor']}, ban id: $inserted_ban_id)",
        $_COOKIE['4chan_auser'],
        $template['publicreason']
      );
      
      if (!$result) {
        $this->error('Database error (abr1)');
      }
    }
    
    // Janitor stats
    $query = <<<SQL
INSERT INTO ban_request_stats(janitor, approved, denied)
VALUES('%s', 1, 0) ON DUPLICATE KEY UPDATE approved = approved + 1
SQL;
    
    $result = mysql_global_call($query, $request['janitor']);
    
    if (!$result) {
      $this->error('Database error (abr2)');
    }
    
    // Clearing
    $salt = file_get_contents('/www/keys/legacy.salt');
    
    $this->clear_ban_request($request['id'], $salt, true);
    
    // Remove duplicate ban requests
    if ($template['save_post'] != 'json_only') {
      if ($warn) {
        $warn_clause = 'AND warn_req = 1';
      }
      else {
        $warn_clause = '';
      }
      
      $query = "SELECT id FROM ban_requests WHERE host = '%s' AND ban_template = %d $warn_clause";
      
      $result = mysql_global_call($query, $request['host'], $template_id);
      
      while ($row = mysql_fetch_assoc($result)) {
        $this->clear_ban_request($row['id'], $salt, true);
      }
    }
    
    // Janitor stats (2)
    $this->update_janitor_stats(
      $request['janitor'],
      self::STATS_ACTION_ACCEPTED,
      $request['board'],
      $post->no,
      $request['ban_template'],
      $template_id,
      $_COOKIE['4chan_auser']
    );
    
    $request_ids = $this->get_request_ids();
    
    $this->success(array('ban_id' => $ban_id, 'request_ids' => $request_ids));
  }
  
  public function deny_ban_request() {
    // Access check
    if ($this->access['ban'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    // $this->error('Disabled');
    
    if (isset($_POST['id'])) {
      $id = (int)$_POST['id'];
    }
    else {
      $this->error('Missing ID');
    }
    
    $salt = file_get_contents('/www/keys/legacy.salt');
    
    $result = $this->clear_ban_request($id, $salt);
    
    $request_ids = $this->get_request_ids();
    
    $this->success(array('request_ids' => $request_ids));
  }
  
  public function get_reports() {
    // Access check
    if ($this->access['clear'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    $board = false;
    
    $ws_mode = self::WS_ANY;
    
    if (isset($_GET['board'])) {
      if ($_GET['board'] === '_ws_') {
        $ws_mode = self::WS_ONLY;
      }
      else if ($_GET['board'] === '_nws_') {
        $ws_mode = self::WS_NOT;
      }
      else {
        $board = $_GET['board'];
      }
      
      if ($board == 'test' && !$this->isDeveloper) {
        $this->error("Can't let you do that");
      }
    }
    
    $cleared_only = isset($_GET['cleared_only']);
    
    $ignored_only = isset($_GET['ignored']) && $this->access['ban'] === 1;
    
    if ($board === false && $cleared_only) {
      $this->error('Cleared Queue is disabled for this set of filters');
    }
    
    $reports = array();
    
    $needs_cleared_by = $cleared_only && $this->isMod;
    
    $board_coefs = $this->get_board_coefficients();
    
    $result = $this->fetch_reports($board, $cleared_only, $ws_mode);
    
    $count = 0;
    
    $uid_idx = 1;
    $uid_map = array();
    
    $need_rep_cats = $this->isMod;
    
    if ($need_rep_cats) {
      $rep_cat_ids = [];
    }
    
    $reported_boards = [];
    
    if (mysql_num_rows($result) > 0) {
      while ($report = mysql_fetch_assoc($result)) {
        if ($r = $this->build_report($report)) {
          if ($count >= self::MAX_REPORTS_PER_PAGE) {
            break;
          }
          
          if ($needs_cleared_by) {
            $r['cleared_by'] = $report['cleared_by'];
          }
          
          if (isset($board_coefs[$r['board']]) && !$report['num_illegal']) {
            $r['weight'] = $r['weight'] * $board_coefs[$r['board']];
          }
          
          if (!$report['resto']) {
            $r['weight'] = $r['weight'] * self::THREAD_WEIGHT_BOOST;
          }
          
          if ($r['weight'] < 1.00) {
            if ($ignored_only) {
              $r['weight'] = 0;
              $count++;
              $reports[] = $r;
            }
            continue;
          }
          else if ($ignored_only) {
            continue;
          }
          
          $r['weight'] = ceil($r['weight']);
          
          // Mods only data
          if ($this->isMod) {
            $long_ip = (int)$report['post_ip'];
            
            if ($long_ip) {
              $uid = $uid_map[$long_ip];
              
              if ($uid) {
                $r['uid'] = $uid;
              }
              else {
                $uid_map[$long_ip] = $uid_idx;
                $r['uid'] = $uid_idx;
                $uid_idx++;
              }
            }
            
            // Report categories
            $r['cats'] = $report['cats'];
            
            if ($need_rep_cats) {
              $rep_cat_ids[] = $report['cats'];
            }
          }
          
          $reported_boards[$r['board']] = true;
          
          $reports[] = $r;
          
          $count++;
        }
      }
    }
    
    if ($count > 0) {
      $ban_templates = $this->get_quickable_templates(array_keys($reported_boards));
    }
    else {
      $ban_templates = null;
    }
    
    usort($reports, array($this, 'sort_reports_func'));
    
    if (!$this->isMod) {
      foreach ($reports as &$r) {
        if ($r['weight'] >= self::HIGHLIGHT_THRES) {
          $r['hl'] = 1;
        }
        
        unset($r['weight']);
        unset($r['count']);
      }
    }
    
    if (!$board && $ws_mode == self::WS_ANY) {
      $counts = [];
      
      foreach ($reports as &$r) {
        if (!isset($counts[$r['board']])) {
          $counts[$r['board']] = 1;
          continue;
        }
        $counts[$r['board']]++;
      }
    }
    else {
      $counts = $this->count_board_reports();
    }
    
    $data = array(
      'reports' => $reports,
      'counts' => $counts,
      'templates' => $ban_templates
    );
    
    if ($need_rep_cats && !empty($rep_cat_ids)) {
      $rep_cat_ids = implode(',', $rep_cat_ids);
      $rep_cat_ids = explode(',', $rep_cat_ids);
      $rep_cat_ids = array_unique($rep_cat_ids);
      
      $rep_cats = $this->get_report_categories_by_ids($rep_cat_ids);
      $data['rep_cats'] = $rep_cats;
    }
    
    $this->success($data, true);
  }
  
  private function sort_reports_func($a, $b) {
    if ($a['weight'] == $b['weight']) {
      if ($a['ts'] == $b['ts']) {
        return 0;
      }
      return ($a['ts'] > $b['ts']) ? -1 : 1;
    }
    
    return ($a['weight'] > $b['weight']) ? -1 : 1;
  }
  
  public function get_report_ids() {
    // Access check
    if ($this->access['clear'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    $ws_mode = self::WS_ANY;
    
    $board = false;
    
    if (isset($_GET['board'])) {
      if ($_GET['board'] === '_ws_') {
        $ws_mode = self::WS_ONLY;
      }
      else if ($_GET['board'] === '_nws_') {
        $ws_mode = self::WS_NOT;
      }
      else {
        $board = $_GET['board'];
      }
    }
    
    $board_clause = '';
    
    // local janitor
    if (isset($this->access['board'])) {
      if (!$board) {
        $board_clause = array();
        
        foreach($this->access['board'] as $b) {
          $board_clause[] = mysql_real_escape_string($b);
        }
        
        $board_clause = " WHERE board IN('" . implode("','", $board_clause) . "')";
      }
      else if ($this->can_access_board($board)) {
        $board_clause = " WHERE board = '" . mysql_real_escape_string($board) . "'";
      }
      else {
        $this->error("You can't view reports for this board.");
      }
    }
    // mod or global janitor
    else {
      if ($board) {
        $board_clause = " WHERE board = '" . mysql_real_escape_string($board) . "'";
      }
    }
    
    if ($ws_mode !== self::WS_ANY) {
      if ($board_clause) {
        $board_clause .= ' AND';
      }
      else {
        $board_clause .= ' WHERE';
      }
      
      if ($ws_mode === self::WS_ONLY) {
        $board_clause .= ' ws = 1';
      }
      else if ($ws_mode === self::WS_NOT) {
        $board_clause .= ' ws = 0';
      }
    }
    
    $reports = array();
    
    $query = "SELECT board, no FROM reports$board_clause GROUP BY board, no";
    
    $result = mysql_global_call($query);
    
    if (mysql_num_rows($result) > 0) {
      while ($report = mysql_fetch_row($result)) {
        $reports[] = $report;
      }
    }
    
    $counts = $this->count_board_reports();
    
    $data = array(
      'reports' => $reports,
      'counts' => $counts
    );
    
    $this->success($data);
  }
  
  public function clear_report() {
    // Access check
    if ($this->access['clear'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if (isset($_POST['no'])) {
      $no = (int)$_POST['no'];
    }
    else {
      $this->error('Missing post number');
    }
    
    if (isset($_POST['board'])) {
      $board = mysql_real_escape_string($_POST['board']);
      if (!$this->can_access_board($_POST['board'])) {
        // User can't access this board
        // Let's see if the post has enough illegal reports
        if ($this->is_report_unlocked($board, $no) === false) {
          // Nope
          $this->error("You don't have access to this board.");
        }
      }
    }
    else {
      $this->error('Missing board');
    }
    
    // Fetching report
    $query = "SELECT SQL_NO_CACHE id, post_json, cleared, SUM(weight) as weight FROM reports WHERE board = '$board' AND no = $no";
    
    $res = mysql_global_call($query);
    
    if (!$res || mysql_num_rows($res) !== 1) {
      $this->error("Couldn't retrieve report for /$board/$no");
    }
    
    $report = mysql_fetch_assoc($res);
    
    if ($report['weight'] == 0) {
      $this->error('You cannot clear this report');
    }
    
    if ($report['cleared'] === '1') {
      // Allow clearing of orphaned reports
      $query = "SELECT no FROM `$board` WHERE no = $no";

      $res = mysql_board_call($query);
      
      if (!mysql_num_rows($res)) {
        $this->hard_clear_report($board, $no);
        $this->success('Orphaned report cleared');
        return;
      }
      else {
        $this->error('This report is already cleared');
      }
    }
    
    // Janitors can't clear reports for their own posts
    if (!$this->access['ban'] && $this->is_report_for_own_post($board, $no)) {
      //$this->error('You cannot clear this report');
      //$this->success();
      //return;
      $this->log_self_clear($board, $no, $report['post_json']);
    }
    
    // Fetch all reporters for logging
    $all_reporters = array();
    
    $query = "SELECT SQL_NO_CACHE ip, pwd, 4pass_id, report_category, weight FROM reports WHERE board = '$board' AND no = $no AND cleared = 0";
    
    $res = mysql_global_call($query);
    
    if ($res) {
      while ($row = mysql_fetch_assoc($res)) {
        $all_reporters[] = $row;
      }
    }
    
    $user = mysql_real_escape_string($_COOKIE['4chan_auser']);
    
    if ($report['post_json']) {
      $post_json = json_decode($report['post_json'], true);
      
      $has_img = $post_json['fsize'] > 0 ? 1 : 0;
      
      if ($post_json['trip']) {
        $name = $post_json['name'] . '#' . substr($post_json['trip'], 1);
      }
      else {
        $name = $post_json['name'];
      }
      
      $name = mysql_real_escape_string($name);
      $post_json['com'] = mysql_real_escape_string($post_json['com']);
      $post_json['sub'] = mysql_real_escape_string($post_json['sub']);
      $post_json['filename'] = mysql_real_escape_string($post_json['filename']);
      
      $post_resto = (int)$post_json['resto'];
      
      $query = <<<SQL
INSERT INTO del_log (postno, board, name, sub, com, img, filename, admin, admin_ip, cleared, resto)
VALUES(
$no,
'$board',
'$name',
'{$post_json['sub']}',
'{$post_json['com']}',
'$has_img',
'{$post_json['filename']}{$post_json['ext']}',
'$user',
'{$_SERVER['REMOTE_ADDR']}',
1,
$post_resto
)
SQL;
    }
    else {
      $query = <<<SQL
INSERT INTO del_log (postno, board, admin, admin_ip, cleared)
VALUES($no, '$board', '$user', '{$_SERVER['REMOTE_ADDR']}', 1)
SQL;
    }
    
    // Logging
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error("Failed to clear reports for /$board/$no (1)");
    }
    
    // Updating reports table
    $query = "UPDATE reports SET cleared = 1, cleared_by = '$user' WHERE board = '$board' AND no = $no";
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error("Failed to clear reports for /$board/$no (2)");
    }
    
    // Updating reports_for_posts table
    $query = <<<SQL
UPDATE reports_for_posts
SET cleared = 1, clearedby = '$user'
WHERE board = '$board' AND postid = $no
SQL;
    
    $res = mysql_global_call($query);
    
    if (!$res) {
      $this->error("Failed to clear reports for /$board/$no (3)");
    }
    
    $this->success();
    
    fastcgi_finish_request();
    
    // Logging reporters and checking for abuse
    foreach ($all_reporters as $reporter) {
      $this->log_cleared_reporter(
        $reporter['ip'], $reporter['pwd'], $reporter['4pass_id'],
        $reporter['report_category'], $reporter['weight']
      );
      
      $this->enforce_cleared_abuse(
        $reporter['ip'], $reporter['pwd'], $reporter['4pass_id']
      );
    }
    
    $this->prune_clear_log();
  }
  
  /**
   * Renders the staff messages page or returns the number of unread messages
   * if called from the count_reports action
   */
  public function staffmessages() {
    if ($this->access['clear'] !== 1) {
      $this->error("Can't let you do that");
    }
    
    if ($this->isCountReports) {
      $count_mode = true;
      
      $msg_count = 0;
      
      if (isset($_COOKIE['smts'])) {
        $ts = (int)$_COOKIE['smts'];
      }
      else {
        $ts = 0;
      }
      
      $sql = "SELECT boards FROM staff_messages WHERE created_on > $ts";
    }
    else {
      $count_mode = false;
      
      $lim = self::PAGE_SIZE + 1;
      
      if (isset($_GET['offset'])) {
        $offset = (int)$_GET['offset'];
      }
      else {
        $offset = 0;
      }
      
      $sql = "SELECT * FROM staff_messages ORDER BY id DESC LIMIT $offset,$lim";
      
      $this->messages = array();
    }
    
    $res = mysql_global_call($sql);
    
    if (!$res) {
      $this->error('Database error.');
    }
    
    if (!$count_mode) {
      $this->offset = $offset;
      
      $this->previous_offset = $offset - self::PAGE_SIZE;
      
      if ($this->previous_offset < 0) {
        $this->previous_offset = 0;
      }
      
      if (mysql_num_rows($res) === $lim) {
        $this->next_offset = $offset + self::PAGE_SIZE;
      }
      else {
        $this->next_offset = 0;
      }
    }
    
    while ($row = mysql_fetch_assoc($res)) {
      $flag = false;
      // No board restrictions or user is a mod
      if ($row['boards'] === '' || $this->isMod) {
        $flag = true;
      }
      // Multiple boards
      else if (strpos($row['boards'], ',') !== false) {
        $boards = explode(',', $row['boards']);
        
        foreach ($boards as $b) {
          if ($this->can_access_board($b)) {
            $flag = true;
            break;
          }
        }
      }
      // Single board
      else if ($this->can_access_board($row['boards'])) {
        $flag = true;
      }
      
      if ($flag) {
        if ($count_mode) {
          $msg_count++;
        }
        else {
          $this->messages[] = $row;
        }
      }
    }
    
    if ($count_mode) {
      return $msg_count;
    }
    
    if ($this->next_offset) {
      array_pop($this->messages);
    }
    
    if (isset($_COOKIE['smts'])) {
      $this->ts = (int)$_COOKIE['smts'];
    }
    else {
      $this->ts = false;
    }
    
    setcookie('smts',
      $_SERVER['REQUEST_TIME'],
      $_SERVER['REQUEST_TIME'] + 365 * 24 * 3600,
      '/',
      'reports.4chan.org',
      false,
      true
    );
    
    $this->renderHTML('messages');
  }
  
  // Checks the event log for thread flood alerts to show inside mod tools.
  private function get_flood_stats() {
    $sql =<<<SQL
SELECT board, COUNT(*) as cnt FROM `event_log`
WHERE type = 'block_flood_check' AND thread_id = 0
AND created_on > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
AND board NOT IN('pol', 'biz', 'v', 'sp')
GROUP BY board
SQL;

    $res = mysql_global_call($sql);
    
    if (!$res) {
      return false;
    }
    
    $data = [];
    
    while ($row = mysql_fetch_row($res)) {
      if ($row[1] >= 3) {
        $data[] = $row[0];
      }
    }
    
    if (empty($data)) {
      return null;
    }
    
    return $data;
  }
  
  public function run() {
    $this->validate_csrf();
    
    if ($_SERVER['HTTP_HOST'] !== 'reports.4chan.org') {
      $this->error('Bad request');
    }
    
    if ($this->isCountReports) {
      $action = 'count_reports';
    }
    else if (isset($_REQUEST['action'])) {
      $action = $_REQUEST['action'];
    }
    else {
      $action = 'reports';
    }
    
    if (in_array($action, $this->actions)) {
      $this->$action();
    }
    else {
      $this->error('Bad request');
    }
  }
}

?>

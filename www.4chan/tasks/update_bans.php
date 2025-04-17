<?php

if(!isset( $_SERVER["argv"])) {
	die(); // don't run from httpd
}

require_once 'lib/db.php';

define('IN_APP', true);

class StaffLog {
  private
    // NSFW boards for preview styling
    $nsfwBoards = array(
      'b' => true,
      'aco' => true,
      'd' => true,
      'e' => true,
      'gif' => true,
      'h' => true,
      'hc' => true,
      'hm' => true,
      'hr' => true,
      'pol' => true,
      'r' => true,
      'r9k' => true,
      's4s' => true,
      's' => true,
      'soc' => true,
      't' => true,
      'u' => true,
      'wg' => true,
      'y' => true,
      'i' => true,
      'ic' => true,
      'f' => true
    ),
    
    // Forced anon boards
    $forcedAnonBoards = array(
      'b' => true,
      'soc' => true,
    ),
    
    // Maximum number of entries to display
    $limit = 25,
    
    // Just in case
    $hard_limit = 250,
    
    // Fetch bans from X hours ago
    $interval = 3,
    
    // Where to output the file
    $outpath = '/www/4chan.org/web/www/bans.html',
    
    // HTML template root
    $template_root = '/www/4chan.org/web/www/views/';
  
  /**
   * Salt for thumbnail hashing
   */
  private function getSalt() {
    return file_get_contents('/www/keys/legacy.salt');
  }
  
  /**
   * "Days/Hours/Minutes ago"
   */
  private function getPreciseDuration($delta) {
    if ($delta < 1) {
      return 'moments';
    }
    
    if ($delta < 60) {
      return $delta . ' seconds';
    }
    
    if ($delta < 3600) {
      $count = floor($delta / 60);
      
      if ($count > 1) {
        return $count . ' minutes';
      }
      else {
        return 'one minute';
      }
    }
    
    if ($delta < 86400) {
      $count = floor($delta / 3600);
      
      if ($count > 1) {
        $head = $count . ' hours';
      }
      else {
        $head = 'one hour';
      }
      
      $tail = floor($delta / 60 - $count * 60);
      
      if ($tail > 1) {
        $head .= ' and ' . $tail . ' minutes';
      }
      
      return $head;
    }
    
    $count = floor($delta / 86400);
    
    if ($count > 1) {
      $head = $count . ' days';
    }
    else {
      $head = 'one day';
    }
    
    $tail = floor($delta / 3600 - $count * 24);
    
    if ($tail > 1) {
      $head .= ' and ' . $tail . ' hours';
    }
    
    return $head;
  }
  
  /**
   * Show unstyled error message and exit
   */
  private function error($message) {
    die($message);
  }
  
  /**
   * Render HTML
   */
  private function renderHTML($view) {
    ob_start();
    include($this->template_root . $view . '.tpl.php');
    file_put_contents($this->outpath, ob_get_contents());
    ob_end_flush();
  }
  
  /**
   * Fetches all public templates from the database (is_public = 1)
   */
  private function getTemplates() {
    $all_templates = array();
    
    $result = mysql_global_call('SELECT SQL_NO_CACHE no, name, days FROM ban_templates WHERE is_public = 1');
    
    if (!mysql_num_rows($result)) {
      $this->error("Couldn't get ban templates");
    }
    
    while ($tpl = mysql_fetch_assoc($result)) {
      $all_templates[$tpl['no']] = array(
        'name' => preg_replace('/ \[[^\]]+\]$/i', '', $tpl['name'])
      );
      
      $days = (int)$tpl['days'];
      
      if ($days > 1) {
        $length = $tpl['days'] . ' days';
      }
      else if ($days < 0) {
        $length = 'Permanent';
      }
      else if ($days == 1) {
        $length = $tpl['days'] . ' day';
      }
      else {
        $length = 'n/a';
      }
      
      $all_templates[$tpl['no']]['length'] = $length;
    }
    
    return $all_templates;
  }
  
  /**
   * Main
   */
  public function run() {
    mysql_global_connect();
    
    $this->entries = array();
    $this->previews = array();
    
    // Get templates
    $this->templates = $this->getTemplates();
    
    //print_r($this->templates);
    
    $tpl_list = implode(',', array_keys($this->templates));
    
    $salt = $this->getSalt();
    
    // Get bans
    $query = <<<SQL
SELECT SQL_NO_CACHE no, global, template_id, board, post_num, post_json as post,
template_id as ban_template, reason,
UNIX_TIMESTAMP(now) as time,
UNIX_TIMESTAMP(length) as ban_end
FROM banned_users
WHERE template_id IN($tpl_list)
AND active = 1
AND post_json != ''
AND board != 'test'
AND board != 'j'
AND length != '0'
AND now >= DATE_SUB(NOW(), INTERVAL {$this->interval} HOUR)
ORDER BY now DESC
LIMIT {$this->hard_limit}
SQL;
    
    $result = mysql_global_call($query);
    
    $entries = array();
    
    while ($entry = mysql_fetch_assoc($result)) {
      $entries[] = $entry;
    }
    
    if (count($entries) < $this->limit) {
      $keys = array_keys($entries);
    }
    else {
      $keys = array_rand($entries, $this->limit);
    }
    
    $id = 0;
    
    foreach ($keys as $key) {
      $entry = $entries[$key];
      
      $entry['id'] = $id;
      $entry['time'] = (int)$entry['time'];
      
      $reasons = explode('<>', $entry['reason']);
      $entry['public_reason'] = $reasons[0];
      
      if ($entry['ban_end'] && ((int)$entry['ban_end'] - (int)$entry['time'] < 1)) {
        $entry['type'] = 'Warn';
      }
      else {
        $entry['type'] = 'Ban';
      }
      
      $post = json_decode($entry['post'], true);
      
      $entry['is_op'] = !$post['resto'];
      
      $entry['preview_id'] = $entry['board'] . '-' . $id;
      
      $preview = array(
        'board' => $entry['board'],
        'now' => $post['now'],
        'name' => $names[0],
        'trip' => $names[1],
        'com' => $post['com'],
        'time' => $post['time']
      );
      
      if ($this->forcedAnonBoards[$entry['board']]) {
        $preview['name'] = 'Anonymous';
        unset($preview['trip']);
      }
      else {
        $names = explode('</span> <span class="postertrip">', $post['name']);
          
        $preview['name'] = $names[0];
        
        if ($names[1]) {
          $preview['trip'] = $names[1];
        }
      }
      
      if (strpos($post['sub'], 'SPOILER<>') === 0) {
        $preview['sub'] = substr($post['sub'], 9);
        $preview['spoiler'] = 1;
      } else {
        if ($entry['board'] == 'f') {
          $preview['sub'] = preg_replace('/^\d+\|/', '', $post['sub']);
        }
        else {
          $preview['sub'] = $post['sub'];
        }
      }
      
      if (isset($this->nsfwBoards[$entry['board']])) {
        $preview['nsfw'] = true;
      }
      
      if ($post['ext']) {
        $preview['thumb'] = sha1($entry['board'] . $post['no'] . $salt);
        $preview['ext'] = $post['ext'];
        $preview['w'] = $post['w'];
        $preview['h'] = $post['h'];
        $preview['tn_w'] = $post['tn_w'];
        $preview['tn_h'] = $post['tn_h'];
        $preview['md5'] = $post['md5'];
        $preview['fsize'] = $post['fsize'];
        $preview['filename'] = $post['filename'];
        $preview['md5'] = $post['md5'];
        $preview['tim'] = $post['tim'];
      }
      else if ($post['filedeleted']) {
        $preview['filedeleted'] = 1;
      }
      
      $this->previews[$entry['preview_id']] = $preview;
      
      $this->entries[] = $entry;
      
      ++$id;
    }
    
    $this->previews = json_encode($this->previews);
    
    $this->renderHTML('bans');
  }
}

$ctrl = new StaffLog();
$ctrl->run();

<?php
/**
 * Periodically saves the maximum post ID for boards.
 */

if (!isset( $_SERVER["argv"])) {
  die();
}

require_once 'lib/db.php';

class App {
  const
    TABLE_NAME = 'board_stats'
  ;
  
  private function get_boards() {
    $query = 'SELECT dir FROM boardlist';
    
    $result = mysql_global_call($query);
    
    $boards = array();
    
    if (!$result) {
      return false;
    }
    
    while ($board = mysql_fetch_assoc($result)) {
      $boards[] = $board['dir'];
    }
    
    return $boards;
  }
  
  /**
   * Main
   */
  public function run() {
    mysql_global_connect();
    
    $boards = $this->get_boards();
    
    if (!$boards) {
      return;
    }
    
    $tbl = self::TABLE_NAME;
    
    foreach ($boards as $board) {
      $board = mysql_real_escape_string($board);
      
      $query = "SELECT MAX(no) FROM `$board`";
      
      $res = mysql_board_call($query);
      
      if (!$res) {
        return;
      }
      
      $post_no = (int)mysql_fetch_row($res)[0];
      
      if (!$post_no) {
        continue;
      }
      
      $query = <<<SQL
INSERT INTO `$tbl` (board, created_on, post_count)
VALUES ('$board', NOW(), $post_no)
SQL;
      
      mysql_global_call($query);
    }
  }
}

$ctrl = new App();
$ctrl->run();

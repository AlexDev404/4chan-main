<?php
if(!isset( $_SERVER["argv"])) {
	die(); // don't run from httpd
}
include 'lib/db.php';

function local_error_log($err) {
	global $errfd;

	if (!$errfd) {
		$errfd = fopen("/www/perhost/fpcontent.log", "a");
		flock($errfd, LOCK_EX);
	}

	fwrite($errfd, $err."\n");
}

mysql_board_connect("a");

$boardq = mysql_global_call("SELECT dir from boardlist");

$recent_images = array();
$latest_posts = array();
$popular_threads = array();
$stats = array('post_total' => 0, 'size_total' => 0, 'ips_total' => 0);

function check_post($post) {
	$q = mysql_global_call("SELECT COUNT(*) from reports where board='{$post['board']}' and no='{$post['no']}'");
	list($reports) = mysql_fetch_array($q);
	if($reports) return false;
	if(strpos($post['sub'],"SPOILER<>")===0) return false;
	if(strpos($post['com'],"[spoiler]")!==false) return false;
	if(strpos($post['filename'], ".pdf") !== false || strpos($post['filename'], ".webm") !== false) return false;
	return true;
}

function adjust_doubles($board, $no) {
	$skipped_at = array('b' => 381720221, 'v' => 129889805, 'vg' => 93794);
	
	if (!isset($skipped_at[$board])) return $no;
	
	return $no - .1*($no - $skipped_at[$board]);
}

while(list($board) = mysql_fetch_array($boardq)) {
	$i = 3;
	do {
	$q = mysql_board_call("SELECT MAX(no),SUM(fsize),COUNT(DISTINCT host) from `$board`");
	list($maxno,$sumfsize,$ips) = mysql_fetch_array($q);
	if (!$maxno) local_error_log("Failed select from board $board (try $i)");
	$i--;
	} while (!$maxno && $i);
	if (!$maxno) {
		local_error_log("Ran out of tries for $board, abandoning fp update");
		exit(1);
	}
	$stats['post_total'] += adjust_doubles($board,$maxno);
	$stats['size_total'] += $sumfsize;
	$stats['ips_total'] += $ips;
	if($board == 'b' || $board == 'f' || $board == 'qa') continue;

	$q = mysql_board_call("SELECT SQL_NO_CACHE * from `$board` WHERE time < UNIX_TIMESTAMP() - 10*60 ORDER BY time desc LIMIT 5");
	while($row = mysql_fetch_array($q)) {
		$row['board'] = $board;
		if(!check_post($row)) continue;
		unset($row['host']);
		unset($row['4pass_id']);
		$latest_posts[] = $row;
	}

	$q = mysql_board_call("SELECT SQL_NO_CACHE * from `$board` WHERE time < UNIX_TIMESTAMP() - 10*60 AND fsize>0 ORDER BY time desc LIMIT 5");
	while($row = mysql_fetch_array($q)) {
		$row['board'] = $board;
		if(!check_post($row)) continue;
		unset($row['host']);
		unset($row['4pass_id']);
		$recent_images[] = $row;
	}

	$q = mysql_board_call("SELECT SQL_NO_CACHE * from `$board` WHERE time < UNIX_TIMESTAMP() - 10*60 AND root > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
	while($row = mysql_fetch_array($q)) {
		$q2 = mysql_board_call("SELECT count(*) from `$board` WHERE resto={$row['no']}");
		list($replycount) = mysql_fetch_array($q2);
		if($replycount < 15) continue;
		
		$q2 = mysql_board_call("SELECT count(*) from `$board` WHERE resto={$row['no']} AND fsize > 0 AND filedeleted = 0");
		list($imagecount) = mysql_fetch_array($q2);
		
		$row['board'] = $board;
		
		if(!check_post($row)) continue;
		
		$row['replies'] = $replycount;
		$row['images'] = $imagecount;
		
		unset($row['host']);
		unset($row['4pass_id']);
		
		$popular_threads[] = $row;
	}
}

if (!$stats['post_total']) {
	local_error_log("Failed select from boardlist");
	exit(1);
}

function safe_put_contents($fn, $data)
{
$n = tempnam(dirname($fn), "cgi");
file_put_contents($n, $data);
rename($n, $fn);
}

safe_put_contents("/www/4chan.org/web/www/data/.recent_images.cgi", serialize($recent_images));
safe_put_contents("/www/4chan.org/web/www/data/.latest_posts.cgi", serialize($latest_posts));
safe_put_contents("/www/4chan.org/web/www/data/.popular_threads.cgi", serialize($popular_threads));
safe_put_contents("/www/4chan.org/web/www/data/.stats.cgi", serialize($stats));

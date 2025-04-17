-- SQL dump based on analysis of the provided PHP code for the yotsuba project.
-- Note: Some details like exact lengths, specific indexes beyond primary/unique keys,
--       and character sets/collations are inferred. Adjust as needed.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Core Tables
-- --------------------------------------------------------

-- Stores the list of boards and their configurations
CREATE TABLE IF NOT EXISTS `boardlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dir` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `db` int(11) DEFAULT NULL, -- Assumed, based on db.php logic
  `domain` varchar(100) DEFAULT NULL, -- Assumed, based on report.php logic
  `sql_cache` varchar(100) DEFAULT NULL, -- Based on admin.php boardlist query
  PRIMARY KEY (`id`),
  UNIQUE KEY `dir` (`dir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generic Post Table Structure (Replace [board_name] with actual board dirs like 'b', 'v', 'vg', etc.)
-- CREATE TABLE IF NOT EXISTS `[board_name]` (
--  `no` int(10) unsigned NOT NULL AUTO_INCREMENT,
--  `resto` int(10) unsigned NOT NULL DEFAULT 0,
--  `sticky` tinyint(1) NOT NULL DEFAULT 0,
--  `closed` tinyint(1) NOT NULL DEFAULT 0,
--  `archived` tinyint(1) NOT NULL DEFAULT 0,
--  `now` varchar(50) NOT NULL,
--  `time` int(10) unsigned NOT NULL,
--  `last_modified` int(10) unsigned NOT NULL DEFAULT 0,
--  `root` timestamp NULL DEFAULT NULL, -- Used for thread ordering and maybe archiving
--  `name` varchar(100) DEFAULT NULL,
--  `tripcode` varchar(20) DEFAULT NULL,
--  `capcode` varchar(50) NOT NULL DEFAULT 'none',
--  `country` varchar(2) DEFAULT NULL,
--  `board_flag` varchar(10) DEFAULT NULL,
--  `id` varchar(10) DEFAULT NULL,
--  `sub` varchar(100) DEFAULT NULL,
--  `com` text,
--  `email` varchar(255) DEFAULT NULL, -- Stores user meta data like browser ID, status, etc.
--  `host` varchar(45) DEFAULT NULL, -- Stores IP address
--  `pwd` varchar(64) DEFAULT NULL, -- Stores UserPwd password hash
--  `4pass_id` varchar(10) DEFAULT NULL, -- Stores 4chan Pass ID
--  `since4pass` int(11) DEFAULT 0, -- Stores year 4chan Pass was purchased or event flags
--  `filename` varchar(255) DEFAULT NULL,
--  `ext` varchar(10) DEFAULT NULL,
--  `fsize` int(10) unsigned DEFAULT 0,
--  `md5` varchar(32) DEFAULT NULL,
--  `w` int(10) unsigned DEFAULT 0,
--  `h` int(10) unsigned DEFAULT 0,
--  `tn_w` int(10) unsigned DEFAULT 0,
--  `tn_h` int(10) unsigned DEFAULT 0,
--  `tim` varchar(20) DEFAULT NULL, -- Stores filename timestamp component
--  `filedeleted` tinyint(1) NOT NULL DEFAULT 0,
--  `tmd5` varchar(16) DEFAULT NULL, -- Thumbnail/perceptual hash
--  `permasage` tinyint(1) NOT NULL DEFAULT 0,
--  `permaage` tinyint(1) NOT NULL DEFAULT 0, -- Only used by managers/devs?
--  `undead` tinyint(1) NOT NULL DEFAULT 0, -- Only used by managers/devs?
--  `m_img` tinyint(1) DEFAULT 0, -- Flag for mobile resized image
--  PRIMARY KEY (`no`),
--  KEY `resto` (`resto`),
--  KEY `time` (`time`),
--  KEY `host` (`host`),
--  KEY `pwd` (`pwd`(8)), -- Index prefix for potentially long PWD
--  KEY `4pass_id` (`4pass_id`),
--  KEY `archived` (`archived`),
--  KEY `root` (`root`),
--  KEY `tim` (`tim`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores MD5 hashes for uploaded files on /f/ (Upload Board)
CREATE TABLE IF NOT EXISTS `f_md5` (
  `filename` varchar(255) NOT NULL,
  `md5` varchar(32) NOT NULL,
  `now` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`filename`),
  KEY `md5` (`md5`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------
-- Moderation & Authentication Tables
-- --------------------------------------------------------

-- Stores moderator/admin user accounts
CREATE TABLE IF NOT EXISTS `mod_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL, -- Stores password hash (bcrypt?) or older plain password
  `allow` text, -- Comma-separated list of allowed boards/flags
  `deny` text, -- Comma-separated list of denied boards/flags
  `flags` text, -- Comma-separated list of special flags (developer, html, etc.)
  `level` enum('janitor','mod','manager','admin') NOT NULL,
  `password_expired` tinyint(1) NOT NULL DEFAULT 0,
  `signed_agreement` tinyint(1) NOT NULL DEFAULT 0,
  `auth_secret` varchar(255) DEFAULT NULL, -- Encrypted 2FA secret
  `ips` text, -- JSON array/object storing recent IPs?
  `last_ua` varchar(128) DEFAULT NULL, -- Last User Agent
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores banned users and ban details
CREATE TABLE IF NOT EXISTS `banned_users` (
  `no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board` varchar(10) DEFAULT NULL,
  `global` tinyint(1) NOT NULL DEFAULT 0,
  `zonly` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(100) DEFAULT NULL,
  `tripcode` varchar(20) DEFAULT NULL,
  `host` varchar(45) NOT NULL,
  `reverse` varchar(255) DEFAULT NULL,
  `xff` varchar(255) DEFAULT NULL,
  `reason` text,
  `length` datetime DEFAULT NULL, -- End date/time of ban, NULL or 0000... for permanent
  `admin` varchar(50) NOT NULL,
  `md5` varchar(32) DEFAULT NULL,
  `4pass_id` varchar(10) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL, -- UserPwd hash associated with the ban
  `post_num` int(10) unsigned DEFAULT NULL,
  `rule` varchar(50) DEFAULT NULL, -- Rule code from ban_templates?
  `post_time` datetime DEFAULT NULL,
  `post_json` text, -- JSON representation of the banned post
  `template_id` int(11) DEFAULT NULL, -- Foreign key to ban_templates
  `admin_ip` varchar(45) DEFAULT NULL, -- IP of the admin who issued the ban
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `now` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unbannedon` timestamp NULL DEFAULT NULL,
  `unbannedby` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`no`),
  KEY `host` (`host`),
  KEY `active` (`active`),
  KEY `md5` (`md5`),
  KEY `4pass_id` (`4pass_id`),
  KEY `password` (`password`(8)) -- Index prefix for potentially long PWD
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores ban templates used by staff
CREATE TABLE IF NOT EXISTS `ban_templates` (
  `no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rule` varchar(50) DEFAULT NULL, -- e.g., 'global1', 'b1'
  `name` varchar(255) NOT NULL, -- Display name of the template
  `publicreason` text, -- Reason shown to the banned user
  `privatereason` text, -- Internal reason/notes for staff
  `days` int(11) DEFAULT NULL, -- Default ban length in days (0 for warn)
  `banlen` varchar(20) DEFAULT NULL, -- 'indefinite' or empty
  `publicban` tinyint(1) DEFAULT 0, -- Show "USER WAS BANNED" message?
  `bantype` enum('local','global') DEFAULT 'local',
  `postban` varchar(20) DEFAULT NULL, -- Action after ban (delpost, delfile, move, etc.)
  `postban_arg` varchar(10) DEFAULT NULL, -- Argument for postban action (e.g., board to move to)
  `save_post` varchar(20) DEFAULT NULL, -- 'everything', 'image_only', etc.
  `can_warn` tinyint(1) DEFAULT 1, -- Can this template be used for warnings?
  `level` enum('janitor','mod','manager','admin') DEFAULT 'mod', -- Minimum level required to use
  `special_action` varchar(50) DEFAULT NULL, -- e.g., 'quarantine', 'revokepass_spam'
  `blacklist` varchar(20) DEFAULT NULL, -- 'image', 'rejectimage', etc.
  PRIMARY KEY (`no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores ban requests made by janitors/mods
CREATE TABLE IF NOT EXISTS `ban_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(45) NOT NULL,
  `reverse` varchar(255) DEFAULT NULL,
  `pwd` varchar(64) DEFAULT NULL,
  `xff` varchar(255) DEFAULT NULL,
  `reason` text, -- Seems unused based on code, tpl_name is used instead
  `global` tinyint(1) DEFAULT 0,
  `tpl_name` varchar(255) DEFAULT NULL, -- Template name
  `ban_template` int(11) DEFAULT NULL, -- Foreign key to ban_templates
  `board` varchar(10) NOT NULL,
  `janitor` varchar(50) NOT NULL, -- User who made the request
  `spost` blob, -- Serialized post data (PHP serialization)
  `post_json` text, -- JSON representation of the post
  `warn_req` tinyint(1) DEFAULT 0, -- Is this a warning request?
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `host` (`host`),
  KEY `board` (`board`),
  KEY `ts` (`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores 4chan Pass user data
CREATE TABLE IF NOT EXISTS `pass_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_hash` varchar(10) NOT NULL, -- The 10-character Pass token
  `session_id` varchar(64) DEFAULT NULL, -- Random session ID for cookie auth
  `pin` varchar(255) NOT NULL, -- Hashed PIN (PHP crypt format?)
  `email` varchar(255) DEFAULT NULL, -- Original purchase email
  `gift_email` varchar(255) DEFAULT NULL, -- Email if it was a gift
  `purchase_date` datetime DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0, -- 0: active, 1: expired, 2: refunded, 3: disputed, 4: revoked-spam, 5: revoked-illegal, 6: new, 7: delayed
  `last_status` tinyint(4) DEFAULT NULL, -- Previous status before change
  `email_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `email_expired_sent` tinyint(1) NOT NULL DEFAULT 0,
  `last_ip` varchar(45) DEFAULT NULL,
  `last_used` timestamp NULL DEFAULT NULL,
  `last_country` varchar(2) DEFAULT NULL,
  `pending_id` varchar(50) DEFAULT NULL, -- ID used for renewal links?
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_hash` (`user_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores VIP capcodes (manually assigned)
CREATE TABLE IF NOT EXISTS `vip_capcodes` (
  `user_id` varchar(50) NOT NULL, -- Unique identifier for the user
  `user_key` varchar(255) NOT NULL, -- Hashed key/password for the capcode
  `name` varchar(100) NOT NULL, -- Name displayed with the capcode
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used` int(10) unsigned DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Reporting & Logging Tables
-- --------------------------------------------------------

-- Stores individual post reports
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` int(10) unsigned NOT NULL,
  `pwd` varchar(64) DEFAULT NULL,
  `4pass_id` varchar(10) DEFAULT NULL,
  `req_sig` varchar(8) DEFAULT NULL, -- Request signature hash
  `board` varchar(10) NOT NULL,
  `no` int(10) unsigned NOT NULL,
  `resto` int(10) unsigned NOT NULL DEFAULT 0,
  `cat` tinyint(4) DEFAULT NULL, -- Old category (1: rule, 2: illegal)
  `weight` float DEFAULT 1, -- Report weight/priority
  `report_category` int(11) DEFAULT NULL, -- New category ID from report_categories
  `ws` tinyint(1) DEFAULT 0, -- Is the board worksafe?
  `post_ip` int(10) unsigned DEFAULT NULL, -- IP of the reported post's author
  `post_json` text, -- JSON representation of the reported post
  `cleared` tinyint(1) NOT NULL DEFAULT 0,
  `cleared_by` varchar(50) DEFAULT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `board` (`board`,`no`),
  KEY `ts` (`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores summary of reports for each post
CREATE TABLE IF NOT EXISTS `reports_for_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board` varchar(10) NOT NULL,
  `postid` int(10) unsigned NOT NULL,
  `threadid` int(10) unsigned NOT NULL DEFAULT 0,
  `num_rule` int(11) NOT NULL DEFAULT 0, -- Count of rule reports
  `num_illegal` int(11) NOT NULL DEFAULT 0, -- Count of illegal reports
  `max_cat` tinyint(4) DEFAULT NULL, -- Category with the highest number of reports (1 or 2)
  `cleared` tinyint(1) NOT NULL DEFAULT 0,
  `clearedby` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `board_postid` (`board`,`postid`),
  KEY `postid` (`postid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores report categories shown in the report window
CREATE TABLE IF NOT EXISTS `report_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board` varchar(10) DEFAULT NULL, -- Specific board or _ws_/_nws_
  `op_only` tinyint(1) DEFAULT 0,
  `reply_only` tinyint(1) DEFAULT 0,
  `image_only` tinyint(1) DEFAULT 0,
  `exclude_boards` text, -- Comma-separated list of boards to exclude this category from
  `title` varchar(255) NOT NULL,
  `weight` float NOT NULL DEFAULT 1,
  `filtered` int(11) DEFAULT 0, -- Threshold for filtering reports from this user?
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log when a staff member clears a report that was made by an abusive user
CREATE TABLE IF NOT EXISTS `report_clear_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `long_ip` int(10) unsigned NOT NULL,
  `pwd` varchar(64) DEFAULT NULL,
  `pass_id` varchar(10) DEFAULT NULL,
  `category` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `long_ip` (`long_ip`),
  KEY `pwd` (`pwd`),
  KEY `pass_id` (`pass_id`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Log of post deletions
CREATE TABLE IF NOT EXISTS `del_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `imgonly` tinyint(1) NOT NULL DEFAULT 0,
  `postno` int(10) unsigned NOT NULL,
  `resto` int(10) unsigned DEFAULT 0,
  `board` varchar(10) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sub` varchar(100) DEFAULT NULL,
  `com` text,
  `img` tinyint(1) DEFAULT 0,
  `filename` varchar(255) DEFAULT NULL,
  `admin` varchar(50) DEFAULT NULL,
  `admin_ip` varchar(45) DEFAULT NULL,
  `template_id` int(11) DEFAULT 0,
  `tool` varchar(20) DEFAULT NULL, -- Tool used for deletion (e.g., 'ban-req')
  `email` varchar(255) DEFAULT NULL, -- Stores user meta? Unlikely user email.
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `board` (`board`),
  KEY `timestamp` (`timestamp`),
  KEY `admin` (`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log of staff actions (modifying posts, issuing bans, etc.)
CREATE TABLE IF NOT EXISTS `actions_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oldmask` int(11) DEFAULT NULL, -- Bitmask for old thread options?
  `newmask` int(11) DEFAULT NULL, -- Bitmask for new thread options?
  `postno` int(10) unsigned NOT NULL,
  `board` varchar(10) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sub` varchar(100) DEFAULT NULL,
  `com` text,
  `filename` varchar(255) DEFAULT NULL,
  `admin` varchar(50) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `board` (`board`),
  KEY `admin` (`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- General event log for various actions (captcha failures, rangeban hits, etc.)
CREATE TABLE IF NOT EXISTS `event_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `board` varchar(10) DEFAULT NULL,
  `thread_id` int(10) unsigned DEFAULT NULL,
  `post_id` int(10) unsigned DEFAULT NULL,
  `arg_num` int(11) DEFAULT NULL,
  `arg_str` varchar(50) DEFAULT NULL,
  `pwd` varchar(64) DEFAULT NULL,
  `req_sig` varchar(8) DEFAULT NULL,
  `ua_sig` varchar(9) DEFAULT NULL,
  `meta` text,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `ip` (`ip`),
  KEY `pwd` (`pwd`(8)),
  KEY `ua_sig` (`ua_sig`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Spam & Filter Tables
-- --------------------------------------------------------

-- Stores IP range bans
CREATE TABLE IF NOT EXISTS `iprangebans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `range_start` int(10) unsigned NOT NULL,
  `range_end` int(10) unsigned NOT NULL,
  `asn` int(10) unsigned DEFAULT NULL,
  `reason` text,
  `creator` varchar(50) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` int(10) unsigned DEFAULT NULL,
  `expires_on` int(10) unsigned DEFAULT 0, -- 0 for permanent
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `boards` text, -- Comma-separated list of boards, or empty for global
  `ops_only` tinyint(1) DEFAULT 0,
  `img_only` tinyint(1) DEFAULT 0,
  `lenient` tinyint(4) DEFAULT 0, -- 1: bypass if known/verified, 2: bypass if known, 3: bypass if verified
  `report_only` tinyint(1) DEFAULT 0,
  `ua_ids` text, -- Comma-separated list of UserPwd ua_sig/req_sig values?
  PRIMARY KEY (`id`),
  KEY `range_start` (`range_start`),
  KEY `range_end` (`range_end`),
  KEY `asn` (`asn`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Stores post content filters (text or regex)
CREATE TABLE IF NOT EXISTS `postfilter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pattern` text NOT NULL,
  `autosage` tinyint(1) NOT NULL DEFAULT 0,
  `log` tinyint(1) NOT NULL DEFAULT 0,
  `regex` tinyint(1) NOT NULL DEFAULT 0,
  `quiet` tinyint(1) NOT NULL DEFAULT 0,
  `lenient` tinyint(1) NOT NULL DEFAULT 0, -- Bypassable by known users
  `ops_only` tinyint(1) NOT NULL DEFAULT 0,
  `min_count` int(11) DEFAULT 1, -- Minimum number of matches to trigger
  `board` varchar(10) NOT NULL DEFAULT '', -- Empty for global
  `ban_days` int(11) DEFAULT 0, -- 0 means reject without ban
  `created_on` int(10) unsigned DEFAULT NULL,
  `updated_on` int(10) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `active` (`active`,`board`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log of post filter hits
CREATE TABLE IF NOT EXISTS `postfilter_hits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filter_id` int(10) unsigned NOT NULL,
  `board` varchar(10) DEFAULT NULL,
  `long_ip` int(10) unsigned NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `filter_id` (`filter_id`,`long_ip`,`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Stores blacklisted content (MD5, text strings, IPs, etc.)
CREATE TABLE IF NOT EXISTS `blacklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field` varchar(20) NOT NULL, -- 'md5', 'host', 'comment', etc.
  `contents` varchar(255) NOT NULL,
  `description` text,
  `addedby` varchar(50) DEFAULT NULL,
  `ban` tinyint(4) DEFAULT 0, -- 0: reject, 1: auto-ban, 2: DMCA error
  `banlength` int(11) DEFAULT 0,
  `banreason` text,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `boardrestrict` varchar(255) DEFAULT '', -- Comma-separated boards or _ws_/_nws_
  PRIMARY KEY (`id`),
  KEY `active` (`active`,`field`,`contents`(191)), -- Index prefix for contents
  KEY `boardrestrict` (`boardrestrict`(191)) -- Index prefix
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log of flood check triggers
CREATE TABLE IF NOT EXISTS `flood_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board` varchar(10) NOT NULL,
  `thread_id` int(10) unsigned NOT NULL,
  `ip` varchar(45) NOT NULL,
  `phash` varchar(16) DEFAULT NULL,
  `ua_sig` varchar(9) DEFAULT NULL,
  `req_sig` varchar(8) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------
-- Email Verification Tables
-- --------------------------------------------------------

-- Stores pending email verification requests
CREATE TABLE IF NOT EXISTS `email_signins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(32) NOT NULL,
  `hashed_email` varchar(32) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `ua` varchar(255) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `bot_score` int(11) DEFAULT 0,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` int(11) NOT NULL DEFAULT 0, -- Usage count
  `failed_challenges` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `hashed_email` (`hashed_email`),
  KEY `ip` (`ip`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Queue for the signin_mailer task
CREATE TABLE IF NOT EXISTS `email_signins_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(32) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores blacklisted email addresses
CREATE TABLE IF NOT EXISTS `email_signins_blacklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------
-- Robot9000 Tables (for /r9k/)
-- --------------------------------------------------------

-- Stores unique text hashes for /r9k/
CREATE TABLE IF NOT EXISTS `r9k_posts` (
  `text` varchar(32) NOT NULL, -- MD5 hash of normalized comment
  `image` varchar(32) DEFAULT NULL, -- MD5 hash of image (unused?)
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`text`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Stores muted users for /r9k/
CREATE TABLE IF NOT EXISTS `r9k_mutes` (
  `ip` int(10) unsigned NOT NULL,
  `timeout_power` int(11) NOT NULL DEFAULT 0,
  `mute_until` timestamp NULL DEFAULT NULL,
  `next_expire` timestamp NULL DEFAULT NULL, -- When to next reduce timeout_power
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------
-- Seasonal/Event Tables
-- --------------------------------------------------------

-- For April 2019 "Like" system
CREATE TABLE IF NOT EXISTS `like_user_scores` (
  `user_id` varchar(32) NOT NULL, -- IP address or 4chan Pass ID
  `user_score` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Log for April 2019 "Like" system
CREATE TABLE IF NOT EXISTS `like_user_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL,
  `target_user_id` varchar(32) NOT NULL,
  `suspicious` tinyint(1) DEFAULT 0,
  `board` varchar(10) NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_user_id` (`target_user_id`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- For April 2022 Emotes system
CREATE TABLE IF NOT EXISTS `april_emotes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(40) NOT NULL, -- SHA1 hash of IP?
  `ip` varchar(45) DEFAULT NULL,
  `data` text, -- JSON encoded data (balance, owned emotes, start_ts)
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- For April 2024 Stock Market event
CREATE TABLE IF NOT EXISTS `april_stock_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL, -- UserPwd hex
  `stock` varchar(10) NOT NULL, -- Stock ticker or '_' for cash balance
  `amount` int(11) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `stock` (`stock`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- For April 2024 Stock Market event
CREATE TABLE IF NOT EXISTS `april_stock_prices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock` varchar(10) NOT NULL,
  `price` int(11) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock` (`stock`,`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- For Halloween 2017 event
CREATE TABLE IF NOT EXISTS `halloween_tricks` (
  `user_hash` varchar(10) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- For Halloween 2017 event
CREATE TABLE IF NOT EXISTS `halloween_votes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `long_ip` int(10) unsigned NOT NULL,
  `board` varchar(10) NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `long_ip` (`long_ip`),
  KEY `board` (`board`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- --------------------------------------------------------
-- Other Tables
-- --------------------------------------------------------

-- Stores blotter messages (news updates)
CREATE TABLE IF NOT EXISTS `blotter_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores contest banner information
CREATE TABLE IF NOT EXISTS `contest_banners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file_id` varchar(50) NOT NULL,
  `file_ext` varchar(4) NOT NULL,
  `board` varchar(10) NOT NULL,
  `is_live` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Stores tensorchan log entries
CREATE TABLE IF NOT EXISTS `tensor_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board` varchar(10) NOT NULL,
  `thread_id` int(10) unsigned NOT NULL DEFAULT 0,
  `post_id` int(10) unsigned NOT NULL,
  `file_id` varchar(20) DEFAULT NULL,
  `file_ext` varchar(4) DEFAULT NULL,
  `nsfw` float DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `board_post_id` (`board`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

-- Table for Rapidshare link logging (implied by postfilter.php)
CREATE TABLE IF NOT EXISTS `rapidsearch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board` varchar(10) NOT NULL,
  `postno` int(10) unsigned NOT NULL,
  `body` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
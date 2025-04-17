# Yotsuba Source

quick rundown:

setup php5.6 (end of support 2018) and mysql, then change db_config.php file to

<?php
	define( 'SQLHOST_GLOBAL', 'localhost' );
	define( 'SQLUSER_GLOBAL', 'root' );
	define( 'SQLPASS_GLOBAL', 'passwd' );
	define( 'SQLDB_GLOBAL', 'chan' );
	define( 'SQLHOST', 'localhost' );
	define( 'SQLUSER', 'root' );
	define( 'SQLPASS', 'passwd' );
	define( 'SQLDB', 'chan' );

after setting up php5.6, download phpmyadmin (you need the old 4.9.11 version for this php) and make sure you can login to your mysql

make all the tables that OP posted before (their pic is missing a bunch tho), then make a table for the board itself, just named as "j" or "test" or whatever

check the json.php post_json_force_type func for some of the columns you need to add to the board table, that tells you if they should be int or varchar too
not all columns are listed there though, but if you have display_errors enabled in php.ini it should give you error about which columns are missing

edit lib/auth.php and change is_local_auth to always return true which skips captcha/security shit, also have to edit lib/postfilter.php and lib/geoip2.php and make them just return otherwise it'll error about missing libs (display_errors should tell you the lines that are broke)

harder part is getting directories and virtual hosts setup, afaik there has to be a global copy of yotsuba code at /www/global/yotsuba, but each board is also meant to have its own copy of it too
idk where those copies are meant to go but i just put them at /www/[board]/yotsuba

each board also has files at /www/4chan.org/web/boards/[board]/thread/, /www/4chan.org/web/images/[board]/, /www/4chan.org/web/thumbs/[board]/, that's where the .html gets written to when you make a thread/reply, make sure to create those folders (and also create a /www/perhost/ folder too)

you're meant to point boards.XXX hostname to the 4chan.org/web/ folder, and think sys.XXX points to a folder with symlinks to all the /www/[board]/yotsuba dirs

then you need to fuck with the JS/CSS to point them to the right path, change some urls in the global configs files, probably some other shit i forgot, also change SELF_PATH_POST in global_config.ini to {{PHP_SERVER}}/imgboard.php?mode=post

after all that you can insert a row into your table db, make sure "resto" is 0 and "root" is 1, then you can call /imgboard.php?mode=rebuildall for it to create the thread/index/catalog/etc html files
once you got JS shit fixed up you should be able to make new posts from there like normal

(the files it creates are .html.gz, https://www.solemnwarning.net/post/apache-static-compressed shows how to serve those with apache)
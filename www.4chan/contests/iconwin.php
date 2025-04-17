<?php
function title() {
 echo "Contests - Icon Contest Winner - 4chan";
}

$top_box_count = 1;
function top_box_title_0() {
?>Icon Contest Winner<?
}

function top_box_content_0() {
?>
<p>Thanks to all that submitted. The descision was a tough choice but I'm
            happy with the one I picked. I originally intended to put up a submissions
            page however I will not be doing that at this time, here is the winner:</p>
            <p><img src="//s.4cdn.org/image/favicon.ico" alt="shut" /><br />
            shut</p>
<?
}

$left_box_count = 0;

$right_box_count = 0;

include '../frontpage_template.php';

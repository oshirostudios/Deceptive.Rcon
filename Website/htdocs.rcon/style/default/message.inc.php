<?php
$message = $_SESSION['module'] . '_message';

if (isset($_SESSION[$message]))
{
	echo "<div class=\"page_center\" style=\"padding-bottom: 10px;\">\n";
	echo "<span style=\"color: red; font-weight: bold; font-size: 14px;\">\n";
	echo $_SESSION[$message];
	echo "</span>\n";
	echo "</div>\n";
	
	unset($_SESSION[$message]);
}
?>

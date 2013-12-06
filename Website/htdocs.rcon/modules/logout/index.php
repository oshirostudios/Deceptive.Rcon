<?php
require_once('modules/index.php');

if (isset($_SESSION['{$adb->prefix}user']))
{
	$user = $_SESSION['{$adb->prefix}user'];
	$user->Logout();
	
	unset($_SESSION['{$adb->prefix}user']);
}

header("Location: /index.php");
?>

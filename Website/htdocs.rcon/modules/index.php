<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if ($_SESSION['secure_module'] == true || $_SESSION['secure_action'] == true)
	require_once('modules/secure.php');

if (isset($_REQUEST['server-id']))
	$_SESSION['server-id'] = $_REQUEST['server-id'];

function is_dir_empty($dir)
{
	return ((is_dir($dir)) && ($files = @scandir($dir)) && count($files) <= 2);
}
?>

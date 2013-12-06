<?php
if ($_SERVER['PHP_SELF'] != '/index.php')
	die();

if (!isset($_SESSION['{$adb->prefix}user']))
	die();
	
$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server, MODE_ACCESS))
	die();

$userid = $_REQUEST['user'];
$target = $_REQUEST['target'];
$access = substr($target, 7);
$update = $_REQUEST['value'];

if ($access != -1)
{	
	$query = "SELECT * FROM {$adb->prefix}server_users WHERE server_id = {$server} AND user_id = {$userid}";
	$result = $adb->query($query, false);
	
	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		$permissions = $adb->query_result($result, 0, 'server_user_permissions');
		$permissions[$access] = $update;

		$query = "UPDATE {$adb->prefix}server_users SET server_user_permissions = '{$permissions}' WHERE server_id = {$server} AND user_id = {$userid}";
		$adb->query($query, true);
	}
}

?>

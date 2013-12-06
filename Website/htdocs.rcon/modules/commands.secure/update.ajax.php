<?php
if ($_SERVER['PHP_SELF'] != '/index.php')
	die();

if (!isset($_SESSION['{$adb->prefix}user']))
	die();
	
$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server, MODE_COMMANDS))
	die();

$group = $_REQUEST['group'];
$target = $_REQUEST['target'];
$command = $command = substr($target, 8);
$update = $_REQUEST['value'];

if ($command != -1)
{	
	$query = "SELECT * FROM {$adb->prefix}server_commands WHERE server_id = {$server} AND group_id = {$group} AND command_id = {$command}";
	$result = $adb->query($query, false);

	if (!empty($result) && $adb->num_rows($result) > 0)
		$query = "UPDATE {$adb->prefix}server_commands SET server_command_access = {$update} WHERE server_id = {$server} AND group_id = {$group} AND command_id = {$command}";
	else
		$query = "INSERT INTO {$adb->prefix}server_commands (server_id, group_id, command_id, server_command_access) VALUES ({$server}, {$group}, {$command}, {$update})";

	$adb->query($query, true);
}

?>

<?php
if ($_SERVER['PHP_SELF'] != '/index.php')
	die();

if (!isset($_SESSION['{$adb->prefix}user']))
	die();
	
$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server, MODE_COMMANDS))
	die();

$command = $_REQUEST['command'];
$format = $_REQUEST['format'];
$rcon = $_REQUEST['rcon'];

$continue = true;

$query = "SELECT * FROM {$adb->prefix}commands WHERE (server_id = 0 OR server_id = {$server}) AND server_type_id = 1 AND command_name = '{$command}'";
$result = $adb->query($query, false);

if (!empty($result) && $adb->num_rows($result) > 0)
{
	if ($adb->query_result($result, 0, 'server_id') == 0)
		$continue = false;
	else
		$query = "UPDATE {$adb->prefix}commands SET command_format = '{$format}', command_rcon_command = '{$rcon}' WHERE server_type_id = 1 AND server_id = {$server} AND command_name = '{$command}'";
}
else
{
	$query = "INSERT INTO {$adb->prefix}commands (server_type_id, server_id, command_name, command_format, command_rcon_command) VALUES (1, {$server}, '{$command}', '{$format}', '{$rcon}')";
}

if ($continue)
{
	$adb->query($query, true);
	header("Location: /?module=commands");
}
else
{
	header("Location: /?module=commands&error=ERROR: You cannot edit standard commands");
}
?>

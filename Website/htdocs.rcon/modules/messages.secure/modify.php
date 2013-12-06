<?php
if ($_SERVER['PHP_SELF'] != '/index.php' || !isset($_SESSION['{$adb->prefix}user'])) {
	header("Location: /index.php");
}

$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server, MODE_MESSAGES))
	header("Location: /index.php");

$mode = $_REQUEST['mode'];

$id = $_REQUEST['id'];
$type = $_REQUEST['type'];
$order = $_REQUEST['order'];

//$query = "SELECT * FROM {$adb->prefix}server_messages WHERE server_id = {$server} AND server_message_type = {$message_type} ORDER BY server_message_order";

switch ($mode)
{
	case 'up':
	{
		if ($order > 1)
		{
			$query = "UPDATE {$adb->prefix}server_messages SET server_message_order = server_message_order + 1 WHERE server_id = {$server} AND server_message_type = {$type} AND server_message_order = ({$order} - 1)";
			$adb->query($query, true);
		
			$query = "UPDATE {$adb->prefix}server_messages SET server_message_order = server_message_order - 1 WHERE server_message_id = {$id}";
			$adb->query($query, true);
		}
	} break;

	case 'down':
	{
		$query = "UPDATE {$adb->prefix}server_messages SET server_message_order = server_message_order - 1 WHERE server_id = {$server} AND server_message_type = {$type} AND server_message_order = ({$order} + 1)";
		$adb->query($query, true);
	
		$query = "UPDATE {$adb->prefix}server_messages SET server_message_order = server_message_order + 1 WHERE server_message_id = {$id}";
		$adb->query($query, true);
	} break;
	
	case 'remove':
	{
		$query = "UPDATE {$adb->prefix}server_messages SET server_message_order = server_message_order - 1 WHERE server_id = {$server} AND server_message_type = {$type} AND server_message_order > {$order}";
		$adb->query($query, true);
	
		$query = "DELETE FROM {$adb->prefix}server_messages WHERE server_message_id = {$id}";
		$adb->query($query, true);
	} break;
	
	case 'create':
	{
		if ($type == 3)
			$message = "{$_REQUEST['shorthand']}={$_REQUEST['message']}";
		else
			$message = $_REQUEST['message'];
		
		$query = "INSERT INTO {$adb->prefix}server_messages (server_id, server_message_type, server_message_order, server_message_detail) VALUES ({$server}, {$type}, {$order}, '{$message}')";
		$adb->query($query, true);
	}
}

header("Location: /?module=messages&type={$type}");

?>

<?php
if ($_SERVER['PHP_SELF'] != '/index.php' || !isset($_SESSION['{$adb->prefix}user'])) {
	header("Location: /index.php");
}

require_once '/usr/share/pear/Mail.php';

$type = $_REQUEST['type'];

$server_id = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

switch ($type)
{
	case 'start':
	{
		if (!$user->HasAccess($server_id, MODE_SERVERS))
			header("Location: /?module=servers");

		$query = "UPDATE {$adb->prefix}servers SET server_monitor = 1 WHERE server_id = {$server_id}";
		$adb->query($query, true);
	} break;
	
	case 'stop':
	{
		if (!$user->HasAccess($server_id, MODE_SERVERS))
			header("Location: /?module=servers");

		$query = "UPDATE {$adb->prefix}servers SET server_monitor = 0 WHERE server_id = {$server_id}";
		$adb->query($query, true);
	} break;
	
	case 'create':
	{
		$activation = uniqid();
		$name = trim($_REQUEST['name']);
		
		if (strlen($name) > 0)
		{
			$query = "INSERT INTO {$adb->prefix}servers (user_id, server_name, server_type_id, server_activation) VALUES ({$user->ID()}, '{$name}', 1, '{$activation}')";
			$adb->query($query, true);
		
			$_SESSION['server-id'] = 0;
		}
	} break;
	
	case 'modify':
	{
		$server = new Server($server_id);
		
		if (!$user->HasAccess($server_id, MODE_SERVERS) && $user->ID() == $server->Creator())
			header("Location: /?module=servers");

		$name = trim($_REQUEST['name']);
		$ip = trim($_REQUEST['ip']);
		$port = trim($_REQUEST['port']);
		$url = trim($_REQUEST['url']);
		$description = trim($_REQUEST['description']);
		$ranked = (isset($_REQUEST['ranked']) && $_REQUEST['ranked'] == "1") ? 1 : 0;
		$rcon = trim($_REQUEST['rcon']);
		$warnings = trim($_REQUEST['warnings']);
		$show_restrictions = (isset($_REQUEST['show_restrictions']) && $_REQUEST['show_restrictions'] == "1") ? 1 : 0;
		$max_ping = (isset($_REQUEST['max_ping'])) ? trim($_REQUEST['max_ping']) : 0;
		
		$query = "UPDATE {$adb->prefix}servers SET server_name = '{$name}', server_ip = '{$ip}', server_port = '{$port}', server_log_url = '{$url}', server_description = '{$description}', server_rcon_password = '{$rcon}', server_ranked = {$ranked}, server_warnings = {$warnings}, server_show_restrictions = {$show_restrictions}, server_max_ping = {$max_ping} WHERE server_id = {$server_id}";
		$adb->query($query, true);
	} break;
	
	case 'delete':
	{
		$server = new Server($server_id);
		
		if (!$user->HasAccess($server_id, MODE_SERVERS) && $user->ID() == $server->Creator())
			header("Location: /?module=servers");

		if (isset($_REQUEST['activation']))
		{
			if ($server->Activation() == $_REQUEST['activation'])
			{
				$query = "DELETE FROM {$adb->prefix}servers WHERE server_id = {$server_id}";
				$adb->query($query, true);
				
				$_SESSION['servers_message'] = "Server '{$server->Name()}' deleted successfully";
			}
			else
			{
				$_SESSION['servers_message'] = "Server deletion link was invalid, please try again";
			}
		}
		else
		{
			$_SESSION['servers_message'] = "To confirm deletion of '{$server->Name()}', press the Delete button again";
			$_SESSION['servers_delete_confirm'] = $server->Activation();
		}
	} break;
}

header("Location: /servers");
?>

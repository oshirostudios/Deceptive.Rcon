<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_CONSOLE))
		die();
	
	// include Rcon system
	require_once("include/Rcon.php");
	
	$query = "SELECT * FROM {$adb->prefix}servers WHERE server_id = {$server}";
	$result = $adb->query($query, false);

	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		$command = $_REQUEST['command'];
		
		if (strpos($command, '!') === 0)
		{
			// insert command into the process queue
			$timestamp = time();
			$data = "CONSOLE;0;CONSOLE; {$command}";
			
			$insert = "INSERT INTO {$adb->prefix}server_log (server_id, server_log_timestamp, server_log_command, server_log_data) VALUES ({$server}, '{$timestamp}', 'G', '{$data}')";
			$adb->query($insert, true);
			
			echo "Command added to the system queue<br />";
		}
		else
		{
			$server_ip = $adb->query_result($result, 0, 'server_ip');
			$server_port = $adb->query_result($result, 0, 'server_port');
			$rcon_password = $adb->query_result($result, 0, 'server_rcon_password');
			
			Rcon::Setup($server_ip, $server_port, $rcon_password);
			
			$response = Rcon::Command($command);
			
			if ($response != '')
			{
				if (strlen($response) > 11)
				{
					$response = str_replace("\xFF\xFF\xFF\xFF\x01print\n", "", $response);
					//$response = str_replace("\x3F\x3F\x3F\x3F\x01print\n", "", $response);
					
					$response = str_replace("\n", "<br/>", $response);
					$response = str_replace(" ", "&nbsp;", $response);
					$response = str_replace("^7", "", $response);
				}
				else
				{
					$response = "Command '{$command}' processed successfully<br/>";
				}
			}
			else
			{
				$response = "Response timed out<br/>";
			}
			
			echo $response;
		}
	}
}
else
{
	echo 'Your session is invalid' . "\n";
}

?>

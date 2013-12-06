<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_ACCESS))
		die();
	
	$task = $_REQUEST['task'];
	$username = $_REQUEST['username'];
	
	if ($task == 'add')
	{
		$query = "SELECT user_id FROM {$adb->prefix}users WHERE user_name = '{$username}'";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$userid = $adb->query_result($result, 0, 'user_id');
			
			if ($userid != $user->ID())
			{
				$query = "SELECT * FROM {$adb->prefix}server_users WHERE server_id = {$server} AND user_id = {$userid}";
				$result = $adb->query($query, false);
				
				if (empty($result) || $adb->num_rows($result) == 0)
				{
					$insert = "INSERT INTO {$adb->prefix}server_users (server_id, user_id, server_user_permissions) VALUES ({$server}, {$userid}, '00000000')";
					$adb->query($insert, true);
				}
			}
			else
			{
				echo "You already have full access\n";
			}
		}
		else
		{
			echo "Unknown username '{$username}'\n";
		}
	}
}
else
{
	echo "Your session is invalid\n";
}

?>

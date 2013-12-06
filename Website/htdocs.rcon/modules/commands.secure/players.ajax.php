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

if (isset($_REQUEST['player']))
{
	$player = $_REQUEST['player'];
	$value = $_REQUEST['value'];
	
	$group = $group + $value;
	
	if ($group >= 1 && $group <= 6)
	{
		// update group here
		$query = "UPDATE {$adb->prefix}player_groups SET group_id = {$group} WHERE player_id = {$player} AND server_id = {$server}";
		$adb->query($query, true);
		
		echo "success";
	}
	else
	{
		echo "failure";
	}
}
else
{
	$query = "SELECT * FROM {$adb->prefix}players INNER JOIN {$adb->prefix}player_groups ON {$adb->prefix}players.player_id = {$adb->prefix}player_groups.player_id WHERE server_id = {$server} AND group_id = {$group} ORDER BY player_name";
	$result = $adb->query($query, false);
	
	if (!empty($result) && $adb->num_rows($result) > 0)
	{
		for ($count = 0; $count < $adb->num_rows($result); $count++)
		{
			$player = $adb->query_result($result, $count, 'player_id');
			$name = trim($adb->query_result($result, $count, 'player_name'));
			
			if (strlen($name) > 0)
				echo "<option value=\"{$player}\">{$name}</option>\n";
		}
	}	
}

?>

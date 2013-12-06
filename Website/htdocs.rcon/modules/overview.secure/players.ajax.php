<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];

	if (!$user->HasAccess($server, MODE_OVERVIEW))
		die();

	$query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = $server ORDER BY server_player_score DESC";
	$result = $adb->query($query, false);
	
	$total_players = 0;
	
	if (!empty($result))
	{
		$total_players = $adb->num_rows($result);
		
		for ($count = 0; $count < $total_players; $count++)
		{
			$server_slot = $adb->query_result($result, $count, 'server_slot');
			$player_guid = $adb->query_result($result, $count, 'server_player_guid');
			$player_name = $adb->query_result($result, $count, 'server_player_name');
			$player_score = $adb->query_result($result, $count, 'server_player_score');
			$player_ip = $adb->query_result($result, $count, 'server_player_ip');
			$player_ping = $adb->query_result($result, $count, 'server_player_ping');
			
			echo "<tr>\n";
			echo "<td class=\"left\">{$server_slot}</td>\n";
			echo "<td>{$player_name}</td>\n";
			echo "<td>{$player_guid}</td>\n";
			echo "<td>{$player_score}</td>\n";
			echo "<td>{$player_ip}</td>\n";
			echo "<td class=\"right\">{$player_ping}</td>\n";
			echo "</tr>\n";
		}
	}
	
	if ($total_players == 0)
	{
		echo '<tr><td class="left" colspan="5">No players online</td><td class="right"> </td></tr>' . "\n";
	}
}
else
{
	echo '<tr><td class="left" colspan="5">Your session has been logged out</td><td class="right"> </td></tr>' . "\n";
}

?>

<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

function GetPlayer($player_id)
{
	global $adb;
	
	$query = "SELECT * FROM {$adb->prefix}players WHERE player_id = {$player_id}";
	$result = $adb->query($query, false);
	
	if ($adb->num_rows($result) == 1)

	return "Unknown player";
}

header("content-type: text/csv");
header("content-disposition: attachment; filename=history.csv");
header("pragma: no-cache");
header("expires: 0");

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_HISTORY))
		die();

	$log_query = "SELECT * FROM {$adb->prefix}player_history INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_history.player_id = {$adb->prefix}players.player_id WHERE server_id = {$server}";

	if (isset($_REQUEST['player']))
	{
		if (is_numeric($_REQUEST['player']))
			$log_query .= " AND player_guid = '{$_REQUEST['player']}'";
		else
			$log_query .= " AND {$adb->prefix}players.player_name LIKE '%{$_REQUEST['player']}%'";
	}

	if (isset($_REQUEST['type']))
	{
		if ($_REQUEST['type'] == '8')
			$log_query .= " AND player_history_action = 4 AND (player_history_detail LIKE '!warn %' OR player_history_detail LIKE '!kick %' OR player_history_detail LIKE '!ban %' OR player_history_detail LIKE '!tempban %' OR player_history_detail LIKE '!timedban %')";
		else
			$log_query .= " AND player_history_action = {$_REQUEST['type']}";
	}
		
	if (isset($_REQUEST['start_date']))
		$log_query .= " AND player_history_timestamp >= " . (strtotime("{$_REQUEST['start_date']} 00:00:00") - 36000);
		
	if (isset($_REQUEST['end_date']))
		$log_query .= " AND player_history_timestamp <= " . (strtotime("{$_REQUEST['end_date']} 23:59:59") - 36000);
		
	$log_query .= " ORDER BY player_history_timestamp DESC";
	$log_result = $adb->query($log_query, false);
	
	if (!empty($log_result) && $adb->num_rows($log_result) > 0)
	{
		echo "Player,Timestamp,Reason,Detail\n";
		
		$total_history = $adb->num_rows($log_result);
		
		for ($count = 0; $count < $total_history && $count < 4000; $count++)
		{
			$player_name = $adb->query_result($log_result, $count, 'player_name');
			$player_guid = $adb->query_result($log_result, $count, 'player_guid');
			$timestamp = $adb->query_result($log_result, $count, 'player_history_timestamp');
			$reason = $adb->query_result($log_result, $count, 'player_history_reason');
			$detail = $adb->query_result($log_result, $count, 'player_history_detail');
			
			$datetime = date("Y-m-d H:i:s", $timestamp + 36000);
			
			echo "{$player_name} ({$player_guid}),{$datetime},{$reason},{$detail}\n";
		}
		
		echo "\nTotal {$total_history} available, {$count} shown\n";
	}
}
else
{
	echo "Your session is invalid\n";
}
?>

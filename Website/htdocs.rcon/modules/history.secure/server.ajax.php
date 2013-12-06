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
		return $adb->query_result($result, 0, 'player_name') . " (" . $adb->query_result($result, 0, 'player_guid') . ")";

	return "Unknown player";
}

function BuildURL()
{
	$url = "/?module=history&action=download";
	
	if (isset($_REQUEST['player']))
		$url .= "&player={$_REQUEST['player']}";
	
	if (isset($_REQUEST['type']))
		$url .= "&type={$_REQUEST['type']}";
	
	if (isset($_REQUEST['start_date']))
		$url .= "&start_date={$_REQUEST['start_date']}";
	
	if (isset($_REQUEST['end_date']))
		$url .= "&end_date={$_REQUEST['end_date']}";
	
	return $url;
}

$page_size = 15;

if (isset($_SESSION['{$adb->prefix}user']))
{
	$server = $_SESSION['server-id'];
	$user = $_SESSION['{$adb->prefix}user'];
	
	if (!$user->HasAccess($server, MODE_HISTORY))
		die();
	
	$offset = 0;
	
	if (isset($_REQUEST['offset']))
		$offset = $_REQUEST['offset'];
		
	$previous = $offset - $page_size;
	$next = $offset + $page_size;
	
	if ($previous < 0)
		$previous = 0;
		
	if (isset($_REQUEST['player']))
	{
		$player = $_REQUEST['player'];
			
		// include Rcon system
		require_once("include/Rcon.php");
		
		if (is_numeric($player))
			$player_query = "SELECT * FROM {$adb->prefix}players WHERE player_guid = '{$player}'";
		else
			$player_query = "SELECT * FROM {$adb->prefix}players WHERE player_name LIKE '%{$player}%'";
			
		$player_result = $adb->query($player_query, false);
		
		if (!empty($player_result))
		{
			if ($adb->num_rows($player_result) == 1)
			{
				$player_name = $adb->query_result($player_result, 0, 'player_name');
				$player_guid = $adb->query_result($player_result, 0, 'player_guid');
				$player_id = $adb->query_result($player_result, 0, 'player_id');
				
				$log_query = "SELECT * FROM {$adb->prefix}player_history WHERE player_id = {$player_id} AND server_id = {$server}";
	
				if (isset($_REQUEST['type']))
				{
					if ($_REQUEST['type'] == '8')
						$log_query .= " AND player_history_action = 4 AND (player_history_detail LIKE '!warn %' OR player_history_detail LIKE '!kick %' OR player_history_detail LIKE '!ban %' OR player_history_detail LIKE '!tempban %' OR player_history_detail LIKE '!timedban %')";
					else
						$log_query .= " AND player_history_action = {$_REQUEST['type']}";
				}
					
				if (isset($_REQUEST['start_date']))
					$log_query = $log_query . " AND player_history_timestamp >= " . (strtotime("{$_REQUEST['start_date']} 00:00:00") - 36000);
					
				if (isset($_REQUEST['end_date']))
					$log_query = $log_query . " AND player_history_timestamp <= " . (strtotime("{$_REQUEST['end_date']} 23:59:59") - 36000);
					
				$log_query = $log_query . " ORDER BY player_history_timestamp DESC";
				$log_result = $adb->query($log_query, false);
				
				if (!empty($log_result) && $adb->num_rows($log_result) > 0)
				{
					echo "<table class=\"standard\" style=\"width: 820px\">\n";
					echo "<thead>\n";
					echo "<tr>\n";
					echo "<th class=\"border-top left\">";
					
					if ($offset > 0)
						echo "<a class=\"th-link\" href=\"javascript:UpdateHistory(0)\">&lt;&lt;</a> | <a class=\"th-link\" href=\"javascript:UpdateHistory(" . ($previous) . ")\">&lt;</a>";
					
					echo "</th>\n";
					echo "<th colspan=\"3\" class=\"border-top center\"><b>History for {$player_name} ({$player_guid})";
					
					if (isset($_REQUEST['start_date']))
						echo " from {$_REQUEST['start_date']}";
						
					if (isset($_REQUEST['end_date']))
						echo " until {$_REQUEST['end_date']}";
					
					echo "</b></th>\n";
					echo "<th class=\"border-top right\" style=\"text-align: right\">";
					
					if ($adb->num_rows($log_result) > ($offset + $page_size))
						echo "<a class=\"th-link\" href=\"javascript:UpdateHistory(" . ($next) . ")\">&gt;</a> | <a class=\"th-link\" href=\"javascript:UpdateHistory(" . ($adb->num_rows($log_result) - $page_size) . ")\">&gt;&gt;</a>";
						
					echo "</th>\n";
					echo "</tr>\n";
					echo "<tr>\n";
					echo "<th class=\"left\" width=\"120\">Timestamp</th>\n";
					echo "<th width=\"100\">Reason</th>\n";
					echo "<th>Detail</th>\n";
					echo "<th> </th>\n";
					echo "<th class=\"right\"> </th>\n";
					echo "</tr>\n";
					echo "</thead>\n";
					
					echo "<tfoot>\n";
					echo "<tr>\n";
					echo "<td colspan=\"4\" class=\"border-bottom left\"></td>\n";
					echo "<td class=\"border-bottom right\" style=\"text-align: right\"><a href=\"" . BuildURL() . "\"><img src=\"/style/default/images/icons/save.png\" style=\"margin-top: 10px\" border=\"0\"></a></td>\n";
					echo "</tr>\n";
					echo "</tfoot>\n";
					
					$total_history = $adb->num_rows($log_result);
					
					for ($count = $offset; $count < $offset + $page_size && $count < $total_history; $count++)
					{
						$timestamp = $adb->query_result($log_result, $count, 'player_history_timestamp');
						$reason = $adb->query_result($log_result, $count, 'player_history_reason');
						$detail = $adb->query_result($log_result, $count, 'player_history_detail');
						
						$datetime = date("Y-m-d H:i:s", $timestamp + 36000);
						
						echo "<tr><td>{$datetime}</td><td>{$reason}</td><td colspan=\"3\">{$detail}</td>\n";
					}	
					
					echo "</tbody>\n</table>\n";
				}
				else
				{
					echo "There is no history for '{$player}'";
				}
			}
			else if ($adb->num_rows($player_result) > 1)
			{
				echo "<table class=\"standard\" style=\"width: 820px\">\n";
				echo "<thead>\n";
				echo "<tr>\n";
				echo "<th class=\"border-top center\"><b>Players Matching '{$player}'</b></th>\n";
				echo "</tr>\n";
				echo "</thead>\n";
				
				echo "<tfoot>\n";
				echo "<tr>\n";
				echo "<td class=\"border-bottom left right\"></td>\n";
				echo "</tr>\n";
				echo "</tfoot>\n";
				
				$total_names = $adb->num_rows($player_result);
				
				for ($count = 0; $count < $total_names; $count++)
				{
					$player_name = $adb->query_result($player_result, $count, 'player_name');
					$player_guid = $adb->query_result($player_result, $count, 'player_guid');
					$player_id = $adb->query_result($player_result, $count, 'player_id');
					
					echo "<tr><td>{$player_name} ({$player_guid})</td></tr>\n";
				}	
				
				echo "</tbody>\n</table>\n";
			}
			else
			{
				echo "Unknown player";
			}
		}
		else
		{
			echo "Unknown player";
		}
	}
	else
	{
		$log_query = "SELECT * FROM {$adb->prefix}player_history WHERE server_id = {$server}";

		if (isset($_REQUEST['type']))
		{
			if ($_REQUEST['type'] == '8')
				$log_query .= " AND player_history_action = 4 AND (player_history_detail LIKE '!warn %' OR player_history_detail LIKE '!kick %' OR player_history_detail LIKE '!ban %' OR player_history_detail LIKE '!tempban %' OR player_history_detail LIKE '!timedban %')";
			else
				$log_query .= " AND player_history_action = {$_REQUEST['type']}";
		}
			
		if (isset($_REQUEST['start_date']))
			$log_query = $log_query . " AND player_history_timestamp >= " . (strtotime("{$_REQUEST['start_date']} 00:00:00") - 36000);
			
		if (isset($_REQUEST['end_date']))
			$log_query = $log_query . " AND player_history_timestamp <= " . (strtotime("{$_REQUEST['end_date']} 23:59:59") - 36000);
			
		$log_query = $log_query . " ORDER BY player_history_timestamp DESC";
		$log_result = $adb->query($log_query, false);
		
		if (!empty($log_result) && $adb->num_rows($log_result) > 0)
		{
			echo "<table class=\"standard\" style=\"width: 820px\">\n";
			echo "<thead>\n";
			echo "<tr>\n";
			echo "<th class=\"border-top left\">";
			
			if ($offset > 0)
				echo "<a class=\"th-link\" href=\"javascript:UpdateHistory(0)\">&lt;&lt;</a> | <a class=\"th-link\" href=\"javascript:UpdateHistory(" . ($previous) . ")\">&lt;</a>";
			
			echo "</th>\n";
			echo "<th colspan=\"4\" class=\"border-top center\"><b>History";
			
			if (isset($_REQUEST['start_date']))
				echo " from {$_REQUEST['start_date']}";
				
			if (isset($_REQUEST['end_date']))
				echo " until {$_REQUEST['end_date']}";
			
			echo "</b></th>\n";
			echo "<th class=\"border-top right\" style=\"text-align: right\">";
			
			if ($adb->num_rows($log_result) > ($offset + $page_size))
				echo "<a class=\"th-link\" href=\"javascript:UpdateHistory(" . ($next) . ")\">&gt;</a> | <a class=\"th-link\" href=\"javascript:UpdateHistory(" . ($adb->num_rows($log_result) - $page_size) . ")\">&gt;&gt;</a>";
				
			echo "</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<th class=\"left\" width=\"120\">Player</th>\n";
			echo "<th width=\"120\">Timestamp</th>\n";
			echo "<th width=\"100\">Reason</th>\n";
			echo "<th>Detail</th>\n";
			echo "<th> </th>\n";
			echo "<th class=\"right\"> </th>\n";
			echo "</tr>\n";
			echo "</thead>\n";
			
			echo "<tfoot>\n";
			echo "<tr>\n";
			echo "<td colspan=\"5\" class=\"border-bottom left\"></td>\n";
			echo "<td class=\"border-bottom right\" style=\"text-align: right\"><a href=\"" . BuildURL() . "\"><img src=\"/style/default/images/icons/save.png\" style=\"margin-top: 10px\" border=\"0\"></a></td>\n";
			echo "</tr>\n";
			echo "</tfoot>\n";
			
			$total_history = $adb->num_rows($log_result);
			
			for ($count = $offset; $count < $offset + $page_size && $count < $total_history; $count++)
			{
				$player_name = GetPlayer($adb->query_result($log_result, $count, 'player_id'));
				$timestamp = $adb->query_result($log_result, $count, 'player_history_timestamp');
				$reason = $adb->query_result($log_result, $count, 'player_history_reason');
				$detail = $adb->query_result($log_result, $count, 'player_history_detail');
				
				$datetime = date("Y-m-d H:i:s", $timestamp + 36000);
				
				echo "<tr><td>{$player_name}</td><td>{$datetime}</td><td>{$reason}</td><td colspan=\"3\">{$detail}</td>\n";
			}	
			
			echo "</tbody>\n</table>\n";
		}
		else
		{
			echo "There is no history for '{$player}'";
		}
	}
}
else
{
	echo "Your session is invalid\n";
}
?>

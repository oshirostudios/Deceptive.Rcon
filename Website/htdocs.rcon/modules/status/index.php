<?php
require_once('modules/index.php');

if (!isset($_REQUEST['server-id']))
	die();

$server = $_REQUEST['server-id'];

$map = false;
$online = false;
$topkills = false;
$topcaps = false;
$topkdr = false;

$horizontal = false;

if (isset($_REQUEST['map']))
	$map = true;

if (isset($_REQUEST['online']))
	$online = true;

if (isset($_REQUEST['kills']))
	$topkills = true;
else if (isset($_REQUEST['caps']))
	$topcaps = true;
else if (isset($_REQUEST['kdr']))
	$topkdr = true;

if (isset($_REQUEST['horizontal']))
	$horizontal = true;

if (!$map && !$online && !($topkills || $topcaps || $topkdr))
{
	$map = true;
	$online = true;
	$topkills = true;
}

$server_query = "SELECT * FROM {$adb->prefix}servers WHERE server_id = {$server}";
$server_result = $adb->query($server_query, false);

$status_query = "SELECT * FROM {$adb->prefix}server_status WHERE server_id = {$server} AND server_status_id = 1";
$status_result = $adb->query($status_query, false);

if (!empty($status_result) && $adb->num_rows($status_result) > 0)
{
	$server_name = $adb->query_result($server_result, 0, 'server_name');
	
	$map_id = $adb->query_result($status_result, 0, 'map_id');
	$mode_type_id = $adb->query_result($status_result, 0, 'mode_type_id');
	
	$map_query = "SELECT map_name FROM {$adb->prefix}maps WHERE map_id = {$map_id}";
	$map_result = $adb->query($map_query, false);
	$map_name = $adb->query_result($map_result, 0, 'map_name');
	
	$game_query = "SELECT mode_name, type_name, mode_type_players FROM (({$adb->prefix}mode_types INNER JOIN {$adb->prefix}modes ON {$adb->prefix}mode_types.mode_id = {$adb->prefix}modes.mode_id) INNER JOIN {$adb->prefix}types ON {$adb->prefix}mode_types.type_id = {$adb->prefix}types.type_id) WHERE mode_type_id = {$mode_type_id}";
	$game_result = $adb->query($game_query, false);
	
	$mode_name = $adb->query_result($game_result, 0, 'mode_name');
	$type_name = $adb->query_result($game_result, 0, 'type_name');
	$players = $adb->query_result($game_result, 0, 'mode_type_players');
	
	if ($mode_name != "Regular")
		$type_name = "{$mode_name} {$type_name}";
	
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n";
	echo "	\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n";
	echo "<title>{$server_name} Status</title>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />\n";
	echo "<link rel=\"stylesheet\" href=\"/style/default/status.css\" />\n";
	echo "<link rel=\"stylesheet\" href=\"/images/maps.css.php\" />\n";
	echo "</head>\n<body>\n";

	if ($horizontal)
		echo "<div>\n";
	
	if ($map)
	{
		if ($horizontal)
			echo "<div style=\"float: left;\">\n";
		
		echo "<div class=\"header_footer\" style=\"font-size: 16px; font-weight: 200;\">\n";
		echo "<div style=\"float: left; text-align: left; width: 200px; height: 21px; overflow: hidden;\">{$server_name}</div>\n";
		echo "<div style=\"clear: both;\"></div>\n";
		echo "</div>\n";
	
		echo "<div class=\"serverstate map-{$map_id}\" style=\"width: 190px; height: 129px;\">{$type_name}<br />{$map_name}</div>\n";
		echo "<div style=\"clear: both;\"></div>\n";
		
		if ($horizontal)
			echo "</div>\n";
	}

	if ($online)
	{
		if ($horizontal)
			echo "<div style=\"float: left;\">\n";
		
		$player_query = "SELECT * FROM {$adb->prefix}server_players WHERE server_id = {$server} ORDER BY server_player_score DESC";
		$player_result = $adb->query($player_query, false);
		
		echo "<div class=\"header_footer\">\n";
		echo "<div style=\"float: left\">Online Players</div>\n";
		echo "<div style=\"float: right\">" . $adb->num_rows($player_result) . " / {$players}</div>\n";
		echo "<div style=\"clear: both;\"></div>\n";
		echo "</div>\n";
		
		echo "<div class=\"playerlist\">\n";
		
		if (!empty($player_result) && $adb->num_rows($player_result) > 0)
		{
			for ($count = 0; $count < $adb->num_rows($player_result); $count++)
			{
				$name = $adb->query_result($player_result, $count, 'server_player_name');
				$score = $adb->query_result($player_result, $count, 'server_player_score');
				
				echo "<div class=\"player\">\n";
				echo "<div class=\"slot\">" . ($count + 1) . "</div>\n";
				echo "<div class=\"name\">{$name}</div>\n";
				echo "<div class=\"score\">{$score}</div>\n";
				echo "<div style=\"clear: both;\"></div>\n";
				echo "</div>\n";
			}
		}
		else
		{
			echo "<div class=\"player\">No players online</div>\n";
		}
		
		echo "</div>\n";
		
		if ($horizontal)
			echo "</div>\n";
	}
	
	if ($topkills || $topcaps || $topkdr)
	{
		if ($horizontal)
			echo "<div style=\"float: left;\">\n";
		
		echo "<div class=\"header_footer\">\n";
		
		if ($topkills)
			echo "<div style=\"float: left\">Top 10 Players - Kills</div>\n";
		else if ($topcaps)
			echo "<div style=\"float: left\">Top 10 Players - Captures</div>\n";
		else // if ($topkdr)
			echo "<div style=\"float: left\">Top 10 Players - KDR</div>\n";
		
		echo "<div style=\"clear: both;\"></div>\n";
		echo "</div>\n";
		
		echo "<div class=\"playerlist\">\n";
		
		if ($topkills)
			$stats_query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$server} ORDER BY player_stats_kills DESC LIMIT 0, 10";
		else if ($topcaps)
			$stats_query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$server} ORDER BY player_stats_captures DESC LIMIT 0, 10";
		else // if ($topkdr)
			$stats_query = "SELECT * FROM {$adb->prefix}player_stats INNER JOIN {$adb->prefix}players ON {$adb->prefix}player_stats.player_id = {$adb->prefix}players.player_id WHERE server_id = {$server} ORDER BY (player_stats_kills / player_stats_deaths) DESC LIMIT 0, 10";
				
		$stats_result = $adb->query($stats_query, false);
		
		if (!empty($stats_result) && $adb->num_rows($stats_result) > 0)
		{
			for ($count = 0; $count < $adb->num_rows($stats_result); $count++)
			{
				$name = $adb->query_result($stats_result, $count, 'player_name');
				$kills = $adb->query_result($stats_result, $count, 'player_stats_kills');
				$caps = $adb->query_result($stats_result, $count, 'player_stats_captures');
				$deaths = $adb->query_result($stats_result, $count, 'player_stats_deaths');
				
				$kdr = sprintf("%01.2f", ($kills / $deaths));
				
				if ($topkills)
					$score = $kills;
				else if ($topcaps)
					$score = $caps;
				else // if ($topkdr)
					$score = $kdr;
				
				echo "<div class=\"player\">\n";
				echo "<div class=\"slot\">" . ($count + 1) . "</div>\n";
				echo "<div class=\"name\">{$name}</div>\n";
				echo "<div class=\"score\">{$score}</div>\n";
				echo "<div style=\"clear: both;\"></div>\n";
				echo "</div>\n";
			}
		}
		else
		{
			echo "<div class=\"player\">No statistics available</div>\n";
		}
	
		echo "</div>\n";
		
		if ($horizontal)
			echo "</div>\n";
	}
	
	if ($horizontal)
		echo "</div>\n";
	
	echo "</body>\n</html>\n";
}

?>

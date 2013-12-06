<?php
if ($_SERVER['PHP_SELF'] != '/index.php' || !isset($_SESSION['{$adb->prefix}user'])) {
	header("Location: /index.php");
}

$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server))
	header("Location: /index.php");

$group = $_REQUEST['group'];

$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE rotation_group_id = {$batch} AND server_id = {$server}";
$result = $adb->query($query, false);

if (empty($result) || $adb->num_rows($result) == 0)
	header("Location: /index.php?module=rotation");

$type = $_REQUEST['type'];

$id = $_REQUEST['id'];
$order = $_REQUEST['order'];

$game_id = -1;
$playlist_id = -1;

switch ($type)
{
	case 'up':
	{
		if ($order > 1)
		{
			$query = "UPDATE {$adb->prefix}rotation SET rotation_sort = rotation_sort + 1 WHERE rotation_group_id = {$group} AND rotation_sort = ({$order} - 1)";
			$adb->query($query, true);
		
			$query = "UPDATE {$adb->prefix}rotation SET rotation_sort = rotation_sort - 1 WHERE server_rotation_id = {$id}";
			$adb->query($query, true);
		}
	} break;

	case 'down':
	{
		$query = "UPDATE {$adb->prefix}rotation SET rotation_sort = rotation_sort - 1 WHERE rotation_group_id = {$group} AND rotation_sort = ({$order} + 1)";
		$adb->query($query, true);
	
		$query = "UPDATE {$adb->prefix}rotation SET rotation_sort = rotation_sort + 1 WHERE server_rotation_id = {$id}";
		$adb->query($query, true);
	} break;
	
	case 'remove':
	{
		$query = "UPDATE {$adb->prefix}rotation SET rotation_sort = rotation_sort - 1 WHERE rotation_group_id = {$group} AND rotation_sort > {$order}";
		$adb->query($query, true);
	
		$query = "DELETE FROM {$adb->prefix}rotation WHERE server_rotation_id = {$id}";
		$adb->query($query, true);
	} break;
	
	case 'add':
	{
		$map = $_REQUEST['map'];
		$playlist = $_REQUEST['playlist'];
		
		if (substr($playlist, 0, 4) == 'play')
		{
			$playlist_id = substr($playlist, 5);
			
			$query = "INSERT INTO {$adb->prefix}rotation (rotation_group_id, rotation_sort, map_id, mode_type_id) VALUES ({$group}, {$order}, {$map}, {$playlist_id})";
			$adb->query($query, true);
		}
		else if (substr($playlist, 0, 4) == 'game')
		{
			$game_id = substr($playlist, 5);
			
			$query = "INSERT INTO {$adb->prefix}rotation (rotation_group_id, rotation_sort, map_id, mode_type_id, game_id) VALUES ({$group}, {$order}, {$map}, -1, {$game_id})";
			$adb->query($query, true);
		}
	}
}

header("Location: /?module=rotation&group={$group}&map={$map}&playlist={$playlist_id}&game={$game_id}");

?>

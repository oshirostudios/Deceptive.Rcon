<?php
if ($_SERVER['PHP_SELF'] != '/index.php' || !isset($_SESSION['{$adb->prefix}user'])) {
	header("Location: /index.php");
}

$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server, MODE_ROTATION))
	header("Location: /index.php");

$type = $_REQUEST['type'];

$group = $_REQUEST['group'];
$name = trim($_REQUEST['name']);

switch ($type)
{
	case 'create':
	{
		if (strlen($name) > 0)
		{
			$query = "INSERT INTO {$adb->prefix}rotation_group (rotation_group_name, server_id) VALUES ('{$name}', {$server})";
			$adb->query($query, true);
		}
	} break;

	case 'remove':
	{
		$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE rotation_group_id = {$group} AND server_id = {$server} AND rotation_group_active = 0";
		$result = $adb->query($query, false);
		
		// ensure this group is for this server
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$query = "DELETE FROM {$adb->prefix}rotation_group WHERE rotation_group_id = {$group} AND server_id = {$server}";
			$adb->query($query, true);
	
			$query = "DELETE FROM {$adb->prefix}rotation WHERE rotation_group_id = {$group}";
			$adb->query($query, true);
		}
	} break;
	
	case 'activate':
	{
		$query = "SELECT * FROM {$adb->prefix}rotation_group WHERE rotation_group_id = {$group} AND server_id = {$server}";
		$result = $adb->query($query, false);
		
		// ensure this group is for this server
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$query = "UPDATE {$adb->prefix}rotation_group SET rotation_group_active = 0 WHERE server_id = {$server}";
			$adb->query($query, true);
		
			$query = "UPDATE {$adb->prefix}rotation_group SET rotation_group_active = 1 WHERE server_id = {$server} AND rotation_group_id = {$group}";
			$adb->query($query, true);
		}
	} break;
	
	case 'random':
	{
		$query = "SELECT * FROM {$adb->prefix}rotation WHERE rotation_group_id = {$group} ORDER BY rotation_sort";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			$sort = array();
			$total_maps = $adb->num_rows($result);
			
			for ($count = 0; $count < $total_maps; $count++)
			{
				do
				{
					$position = rand(1, $total_maps);
				} while (array_key_exists($position, $sort));
				
				$sort[$position] = $adb->query_result($result, $count, 'server_rotation_id');
			}
			
			foreach ($sort as $position => $item)
			{
				$query = "UPDATE {$adb->prefix}rotation SET rotation_sort = {$position} WHERE server_rotation_id = {$item}";
				$adb->query($query, true);
			}
		}
	} break;
}

header("Location: /?module=rotation&group={$group}");

?>

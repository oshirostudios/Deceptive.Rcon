<?php
if ($_SERVER['PHP_SELF'] != '/index.php')
	die();

if (!isset($_SESSION['{$adb->prefix}user']))
	die();
	
$server = $_SESSION['server-id'];
$user = $_SESSION['{$adb->prefix}user'];

if (!$user->HasAccess($server, MODE_RESTRICTIONS))
	die();

$item = $_REQUEST['item'];
$update = $_REQUEST['value'];

$item_id = substr($item, 5);

if ($update == '1')
{
	$query = "SELECT * FROM {$adb->prefix}server_restrictions WHERE server_id = {$server} AND item_id = {$item_id}";
	$result = $adb->query($query, false);

	if (!empty($result) && $adb->num_rows($result) > 0)
		die();
		
	$query = "INSERT INTO {$adb->prefix}server_restrictions (server_id, item_id) VALUES ({$server}, {$item_id})";
}
else
{
	$query = "DELETE FROM {$adb->prefix}server_restrictions WHERE server_id = {$server} AND item_id = {$item_id}";
}

$adb->query($query, true);

?>

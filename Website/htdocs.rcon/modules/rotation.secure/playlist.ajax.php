<?php
if ($_SERVER['PHP_SELF'] != '/index.php') {
	header("Location: /index.php");
}

$user = $_SESSION['{$adb->prefix}user'];
$server = $user->Server($_SESSION['server-id']);

$group = $server->RotationGroup();
$active_group = $group;

$map = -1;
$playlist = -1;
$game = -1;

if (isset($_REQUEST['group']))
	$group = $_REQUEST['group'];

if (isset($_REQUEST['map']))
	$map = $_REQUEST['map'];

if (isset($_REQUEST['playlist']))
	$playlist = $_REQUEST['playlist'];
	
if (isset($_REQUEST['game']))
	$game = $_REQUEST['game'];
	
$group_detail = $server->GroupDetail($group);

echo "<thead>\n";
echo "<tr>\n";

if (isset($group_detail['previous']))
	echo "<th class=\"border-top left\"><a class=\"th-link\" href=\"javascript:LoadRotation({$group_detail['previous']})\">&lt;&lt;</a></th>\n";
else
	echo "<th class=\"border-top left\"> </th>\n";

if ($group == $active_group)
	echo "<th colspan=\"4\" class=\"border-top center\">{$group_detail['name']}<br/><a class=\"th-link\" href=\"/?module=rotation&action=group&type=random&group={$group}\">Randomise</a></th>\n";
else
	echo "<th colspan=\"4\" class=\"border-top center\">{$group_detail['name']}<br/><a class=\"th-link\" href=\"/?module=rotation&action=group&type=activate&group={$group}\">Activate</a> | <a class=\"th-link\" href=\"/?module=rotation&action=group&type=random&group={$group}\">Randomise</a> | <a class=\"th-link\" href=\"/?module=rotation&action=group&type=remove&group={$group}\">Remove</a></th>\n";

if (isset($group_detail['next']))
	echo "<th class=\"border-top right\" style=\"text-align: right\"><a class=\"th-link\" href=\"javascript:LoadRotation({$group_detail['next']})\">&gt;&gt;</a></th>\n";
else
	echo "<th class=\"border-top right\"> </th>\n";

echo "</tr>\n";
echo "<tr>\n";
echo "<th class=\"left\">Order</th>\n";
echo "<th>Map</th>\n";
echo "<th>Mode</th>\n";
echo "<th>Type</th>\n";
echo "<th>Players</th>\n";
echo "<th class=\"right\">Actions</th>\n";
echo "</tr>\n";
echo "</thead>\n";
echo "<tfoot>\n";
echo "<tr>\n";
echo "<td colspan=\"5\" class=\"border-bottom left\"></td>\n";
echo "<td class=\"border-bottom right\">&nbsp;</td>\n";
echo "</tr>\n";
echo "</tfoot>\n";
echo "<tbody>\n";

if ($_REQUEST['group'] != -1)
{
	$rotation = $server->Rotation($group);
	$next_rotation = 1;
	
	if ($rotation !== false)
	{
		for ($count = 0; $count < count($rotation); $count++)
		{
			echo "<tr>\n";
			echo "<td class=\"left\">{$rotation[$count]['order']}</td>\n";
			echo "<td>{$rotation[$count]['map']->Name()}</td>\n";
			
			if (isset($rotation[$count]['playlist']))
			{
				echo "<td>{$rotation[$count]['playlist']->ModeName()}</td>\n";
				echo "<td>{$rotation[$count]['playlist']->TypeName()}</td>\n";
				echo "<td>{$rotation[$count]['playlist']->Players()}</td>\n";
			}
			else
			{
				echo "<td colspan=\"3\">{$rotation[$count]['game']->Name()}</td>\n";
			}
			
			echo "<td class=\"right\">";
			
			if ($count == 0)
				echo "<img src=\"/style/" . $CONFIG['style'] . "/images/icons/blank.png\">";
			else
				echo "<a href=\"/?module=rotation&action=modify&type=up&group={$group}&id={$rotation[$count]['id']}&order={$rotation[$count]['order']}\"><img alt=\"Promote\" src=\"/style/" . $CONFIG['style'] . "/images/icons/up.png\"></a>";
				
			if ($count == (count($rotation) - 1))
				echo "<img src=\"/style/" . $CONFIG['style'] . "/images/icons/blank.png\">";
			else
				echo "<a href=\"/?module=rotation&action=modify&type=down&group={$group}&id={$rotation[$count]['id']}&order={$rotation[$count]['order']}\"><img alt=\"Demote\" src=\"/style/" . $CONFIG['style'] . "/images/icons/down.png\"></a>";

			echo "<a href=\"/?module=rotation&action=modify&type=remove&group={$group}&id={$rotation[$count]['id']}&order={$rotation[$count]['order']}\"><img alt=\"Remove\" src=\"/style/" . $CONFIG['style'] . "/images/icons/remove.png\"></a>";
			echo "</td>\n";
			echo "</tr>\n";
			
			$next_rotation++;
		}
	}
	else
	{
		echo "<tr><td colspan=\"6\">No rotation currently setup</td></tr>\n";
	}
	
	echo "<tr>\n";
	echo "<form method=\"post\">\n";
	echo "<input type=\"hidden\" name=\"module\" value=\"rotation\">\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"modify\">\n";
	echo "<input type=\"hidden\" name=\"type\" value=\"add\">\n";
	echo "<input type=\"hidden\" name=\"group\" value=\"{$group}\">\n";
	echo "<input type=\"hidden\" name=\"order\" value=\"{$next_rotation}\">\n";
	echo "<td>Add</td>\n";
	echo "<td colspan=\"2\">\n";
	echo "<select name=\"map\">\n";
	
	$blackops = new Blackops();
	$maps = $blackops->Maps();
	
	for ($count = 0; $count < count($maps); $count++)
	{
		if ($map == $maps[$count]->ID())
			echo "<option selected value=\"{$maps[$count]->ID()}\">{$maps[$count]->Name()}</option>\n";
		else
			echo "<option value=\"{$maps[$count]->ID()}\">{$maps[$count]->Name()}</option>\n";
	}
	
	echo "</select>\n";
	echo "</td>\n";
	echo "<td colspan=\"2\">\n";
	echo "<select name=\"playlist\">\n";

	$playlists = $blackops->Playlists();
	
	for ($count = 0; $count < count($playlists); $count++)
	{
		if ($playlist == $playlists[$count]->ID())
			echo "<option selected value=\"play-{$playlists[$count]->ID()}\">{$playlists[$count]->ModeName()} {$playlists[$count]->TypeName()} ({$playlists[$count]->Players()} slot)</option>\n";
		else
			echo "<option value=\"play-{$playlists[$count]->ID()}\">{$playlists[$count]->ModeName()} {$playlists[$count]->TypeName()} ({$playlists[$count]->Players()} slot)</option>\n";
	}
	
	if (!$server->Ranked())
	{
		$games = $blackops->Games();
		
		for ($count = 0; $count < count($games); $count++)
		{
			if ($game == $games[$count]->ID())
				echo "<option selected value=\"game-{$games[$count]->ID()}\">{$games[$count]->Name()}</option>\n";
			else
				echo "<option value=\"game-{$games[$count]->ID()}\">{$games[$count]->Name()}</option>\n";
		}
	}
	
	echo "</select>\n";
	echo "</td>\n";
	echo "<td>\n";
	echo "<input class=\"small\" type=\"image\" src=\"/style/" . $CONFIG['style'] . "/images/icons/add.png\" height=\"16\" width=\"16\">\n";
	echo "</td>\n";
	echo "</form>\n";
	echo "</tr>\n";
}

echo "<tr>\n";
echo "<form method=\"post\">\n";
echo "<input type=\"hidden\" name=\"module\" value=\"rotation\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"group\">\n";
echo "<input type=\"hidden\" name=\"type\" value=\"create\">\n";
echo "<td colspan=\"2\">Create Group</td>\n";
echo "<td colspan=\"3\"><input class=\"basic\" type=\"text\" name=\"name\" size=\"50\" maxlength=\"100\" /></td>\n";
echo "<td>\n";
echo "<input class=\"small\" type=\"image\" src=\"/style/" . $CONFIG['style'] . "/images/icons/add.png\" height=\"16\" width=\"16\">\n";
echo "</td>\n";
echo "</form>\n";
echo "</tr>\n";
echo "</tbody>\n";

?>

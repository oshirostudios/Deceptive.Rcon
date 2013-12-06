<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');

$message_type_names = array(1 => 'Timed Messages', 2 => 'Rules', 3 => 'Shorthand');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Messages</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_MESSAGES);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_MESSAGES))
		{
			$server = $_SESSION['server-id'];
			$message_type = 1;
			
			if (isset($_REQUEST['type']))
				$message_type = $_REQUEST['type'];
			
			$prev_type = $message_type - 1;
			$next_type = $message_type + 1;
			
			if ($prev_type < 1)
				$prev_type = 3;
			
			if ($next_type > 3)
				$next_type = 1;
			?>
			<div>
			<table class="standard" style="width: 820px;">
				<thead>
					<tr>
						<?php
						echo "<th class=\"border-top left\" width=\"60\"><a class=\"th-link\" href=\"/?module=messages&type={$prev_type}\">&lt;&lt;</a></th>\n";
						echo "<th colspan=\"4\" class=\"border-top center\" width=\"70\"><font size=\"+1\"><b>{$message_type_names[$message_type]}</b></font></th>\n";
						echo "<th class=\"border-top right\" style=\"text-align: right\" width=\"60\"><a class=\"th-link\" href=\"/?module=messages&type={$next_type}\">&gt;&gt;</a></th>\n";
						?>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="5" class="border-bottom left"></td>
						<td class="border-bottom right">&nbsp;</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
					$query = "SELECT * FROM {$adb->prefix}server_messages WHERE server_id = {$server} AND server_message_type = {$message_type} ORDER BY server_message_order";
					$result = $adb->query($query, false);
					
					$next_rotation = 1;
					
					if (!empty($result) && $adb->num_rows($result) > 0)
					{
						for ($count = 0; $count < $adb->num_rows($result); $count++)
						{
							$id = $adb->query_result($result, $count, 'server_message_id');
							$message = $adb->query_result($result, $count, 'server_message_detail');
							$order = $adb->query_result($result, $count, 'server_message_order');
							
							echo "<tr>\n";
							echo "<td class=\"left\" colspan=\"5\">\n";
							
							if ($message_type == 3)
							{
								$split_message = explode('=', $message);
								echo "<b>{$split_message[0]}</b> - {$split_message[1]}";
							}
							else
							{
								echo $message;
							}
							
							echo "</td>\n";
							echo "<td class=\"right\" style=\"text-align: right\">";
							
							if ($count == 0)
								echo "<img src=\"/style/" . $CONFIG['style'] . "/images/icons/blank.png\">";
							else
								echo "<a href=\"/?module=messages&action=modify&mode=up&id={$id}&order={$order}&type={$message_type}\"><img alt=\"Promote\" src=\"/style/" . $CONFIG['style'] . "/images/icons/up.png\"></a>";
								
							if ($count == ($adb->num_rows($result) - 1))
								echo "<img src=\"/style/" . $CONFIG['style'] . "/images/icons/blank.png\">";
							else
								echo "<a href=\"/?module=messages&action=modify&mode=down&id={$id}&order={$order}&type={$message_type}\"><img alt=\"Demote\" src=\"/style/" . $CONFIG['style'] . "/images/icons/down.png\"></a>";
	
							echo "<a href=\"/?module=messages&action=modify&mode=remove&id={$id}&order={$order}&type={$message_type}\"><img alt=\"Remove\" src=\"/style/" . $CONFIG['style'] . "/images/icons/remove.png\"></a>";
							echo "</td>\n";
							echo "</tr>\n";
							
							$next_rotation++;
						}
					}
					
					echo "<tr>\n";
					echo "<form method=\"post\">\n";
					echo "<input type=\"hidden\" name=\"module\" value=\"messages\">\n";
					echo "<input type=\"hidden\" name=\"action\" value=\"modify\">\n";
					echo "<input type=\"hidden\" name=\"mode\" value=\"create\">\n";
					echo "<input type=\"hidden\" name=\"type\" value=\"{$message_type}\">\n";
					echo "<input type=\"hidden\" name=\"order\" value=\"{$next_rotation}\">\n";
					echo "<td class=\"left\" style=\"text-align: right\">Create</td>\n";
					echo "<td colspan=\"4\">";
					
					if ($message_type == 3)
						echo "<input class=\"basic\" type=\"text\" name=\"shorthand\" size=\"20\" maxlength=\"20\" /> <input class=\"basic\" type=\"text\" name=\"message\" size=\"80\" maxlength=\"80\" />";
					else
						echo "<input class=\"basic\" type=\"text\" name=\"message\" size=\"100\" maxlength=\"100\" />";
					
					echo "</td>\n";
					echo "<td class=\"right\" style=\"text-align: right\">\n";
					echo "<input class=\"small\" type=\"image\" src=\"/style/" . $CONFIG['style'] . "/images/icons/add.png\" height=\"16\" width=\"16\">\n";
					echo "</td>\n";
					echo "</form>\n";
					echo "</tr>\n";
					?>
				</tbody>
			</table>
			</div>
			<?php
		}
		?>
		
		</div>
		</div>
		
	</div>
</section>

<?php
require_once('style/' . $CONFIG['style'] . '/footer.inc.php');
?>

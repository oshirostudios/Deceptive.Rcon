<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Commands</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<script type="text/javascript">
			var group = -1;
			$(document).ready(function()
			{
				$('#player-promote').click(function () {
					if ($('#player-select').html() != '')
					{
						if ($('#player-select').val() != null)
						{
							$.ajax({
									url: '/index.php',
									type: 'POST',
									data: 
									{ 
										'module': 'commands', 
										'action': 'players.ajax', 
										'player': $('#player-select').val(), 
										'group': $('#group-select').val(), 
										'value': -1
									}
								}).done(function (result) {
									if (result == 'success')
										$('#player-select option:selected').remove();
								});
						}
					}
				});

				$('#player-demote').click(function () {
					if ($('#player-select').html() != '')
					{
						if ($('#player-select').val() != null)
						{
							$.ajax({
									url: '/index.php',
									type: 'POST',
									data: 
									{ 
										'module': 'commands', 
										'action': 'players.ajax', 
										'player': $('#player-select').val(), 
										'group': $('#group-select').val(), 
										'value': 1
									}
								}).done(function (result) {
									if (result == 'success')
										$('#player-select option:selected').remove();
								});
						}
					}
				});

				if ($('#group-select').val() == null)
				{
					SetState($('#player-promote'), false);
					SetState($('#player-demote'), false);
				}
				
				$('#group-select').change(function()
				{
					if ($('#group-select').val() == 1)
						SetState($('#player-promote'), false);
					else
						SetState($('#player-promote'), true);
						
					if ($('#group-select').val() == 6)
						SetState($('#player-demote'), false);
					else
						SetState($('#player-demote'), true);
					
					$('#player-select').empty();
					$('#player-select').load('/?module=commands&action=players.ajax&group=' + $('#group-select').val());
					
					$('#commands').empty();
					$('#commands').load('/?module=commands&action=list.ajax&group=' + $('#group-select').val(), function()
					{
						$('input:checkbox').change(function()
						{ 
							if ($(this).is(":checked"))
							{
								$.ajax({
									url: '/index.php',
									type: 'POST',
									data: 
									{ 
										'module': 'commands', 
										'action': 'update.ajax', 
										'group': $('#group-select').val(), 
										'target': $(this).attr('name'),
										'value': '1'
									}
								});
							} 
							else 
							{
								$.ajax({
									url: '/index.php',
									type: 'POST',
									data: 
									{ 
										'module': 'commands', 
										'action': 'update.ajax', 
										'group': $('#group-select').val(), 
										'target': $(this).attr('name'),
										'value': '0'
									}
								});
							}
 						}); 
 					});
				});
			});
			
			function SetState(target, enabled)
			{
				if (enabled)
				{
					target.removeAttr('disabled');
					target.css('background-color','#fafafa');
					target.css('color','#000');
				}
				else
				{
					target.attr('disabled','disabled');
					target.css('background-color','#ccc');
					target.css('color','#bbb');
				}
			}
		</script>
		
		<div class="view">
		<div class="pages">
		
		<?php
		$server_id = 0;
		
		if (isset($_REQUEST['server-id']))
			$server_id = $_REQUEST['server-id'];
		
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_COMMANDS, $server_id);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_OVERVIEW))
		{
		?>
		<div>
		<br/>
		<div id="overview">
		<select id="group-select" style="width: 200px; padding: 5px;" size="6">
		<?php
		$query = "SELECT * FROM {$adb->prefix}groups ORDER BY group_id";
		$result = $adb->query($query, false);
		
		if (!empty($result) && $adb->num_rows($result) > 0)
		{
			for ($count = 0; $count < $adb->num_rows($result); $count++)
			{
				$group_id = $adb->query_result($result, $count, 'group_id');
				$group_name = $adb->query_result($result, $count, 'group_name');
				
				echo "<option value=\"{$group_id}\">{$group_name}</option>\n";
			}
		}
		?>
		</select>
		<br />
		<br />
		<select id="player-select" style="width: 200px; padding: 5px;" size="11">
		</select>
		<button id="player-promote" style="background: #fafafa url('/style/<?php echo $CONFIG['style'] ?>/images/icons/up.png') no-repeat; background-position: center right; width: 80px; text-align: left; margin-top: 10px; margin-left: 10px; -webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px;">Promote</button>
		<button id="player-demote" style="background: #fafafa url('/style/<?php echo $CONFIG['style'] ?>/images/icons/down.png') no-repeat; background-position: center left; width: 80px; text-align: right; margin-top: 10px; margin-left: 10px; -webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px;">Demote</button>
		</div>
		<div id="status">
		<div id="commands" class="commands"></div>
		<div>
			<table style="margin-left: 0px; margin-top: 10px; cell-padding: 2px;">
				<tbody>
					<form method="post">
						<input type="hidden" name="module" value="commands">
						<input type="hidden" name="action" value="create">
						<tr>
							<td>Command</td>
							<td style="padding-left: 14px;">Format</td>
						</tr>
						<tr>
							<td><input style="color:#333; margin-bottom: 0px; height: 20px; line-height: 20px;" type="text" name="command" value="" size="22" maxlength="20"></td>
							<td style="padding-left: 14px;"><input style="color:#333; margin-bottom: 0px; height: 20px; line-height: 20px;" type="text" name="format" value="" size="70" maxlength="500"></td>
						</tr>
						<tr>
							<td colspan="2">Rcon Command(s)</td>
						</tr>
						<tr>
							<td colspan="2"><input style="color:#333; margin-bottom: 0px; height: 20px; line-height: 20px;" type="text" name="rcon" value="" size="98" maxlength="500"></td>
						</tr>
						<tr>
							<td colspan="2" style="color: red; align: right"><input style="float: right; margin-left: 10px; margin-top: 10px; height: 20px; line-height: 20px;" type="submit" value="  Create  "><span style="float: right; margin-top: 10px;"><?php echo $_REQUEST['error']; ?></span></td>
						</tr>						
						</form>
					</form>
				</tbody>
			</table>			
		</div>
		</div>
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

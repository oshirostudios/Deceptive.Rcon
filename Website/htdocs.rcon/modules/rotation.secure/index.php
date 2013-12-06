<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Rotation</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_ROTATION);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_ROTATION))
		{
			$server = $user->Server($_SESSION['server-id']);
			
			if ($server !== false)
			{
				$group = $server->RotationGroup();
				$group_detail = $server->GroupDetail($group);
				
				if ($group_detail !== false)
				{
					?>
					<script type="text/javascript">
						var timeout = 10;
						
						$(document).ready(function()
						{
							setTimeout(PerformCountdown, 1000);
						});
						
						function PerformCountdown()
						{
							timeout = timeout - 1;
						
							if (timeout <= 0)
							{
								timeout = 10;
								RefreshServer();
							}
							
							setTimeout(PerformCountdown, 1000);
						}
						
						function RefreshServer()
						{
							$('#overview').load('/?module=rotation&action=server.ajax&mode=<?php echo MODE_OVERVIEW; ?>');
						}
						
						function LoadRotation(group)
						{
							$('#playlist').load('/?module=rotation&action=playlist.ajax&group=' + group);
						}
					</script>
					<div>
					<div id="overview">
						<?php
						$_REQUEST['mode'] = MODE_ROTATION;
						require_once('modules/rotation.secure/server.ajax.php');
						?>
					</div>
					<div id="status">
					<table id="playlist" class="standard">
						<?php
						require_once('modules/rotation.secure/playlist.ajax.php');
						?>
					</table>
					</div>
					</div>
					<?php
					}
					else
					{
					?>
						<table style="margin-left: 53px;">
							<thead>
								<tr>
									<th>Create Rotation Group</th>
								</tr>
							</thead>
							<tbody>
								<form method="post">
									<input type="hidden" name="module" value="rotation">
									<input type="hidden" name="action" value="group">
									<input type="hidden" name="type" value="create">
									<tr>
										<td width="630"><input type="text" name="name" size="100" maxlength="100" /></td>
										<td valign="top"><input type="submit" value="  Create  " /></td>
									</tr>
								</form>
							</tbody>
						</table>
						<?php
					}
				}
			}
		?>
		</div>
		</div>
	</div>
</section>

<?php
require_once('style/' . $CONFIG['style'] . '/footer.inc.php');
?>

<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Overview</h2>
		<span style="color: red">WARNING: Service will be shutdown and made unavailable on the 5th of December, if you would like the code system, email me jonathan <at> deceptive studios dot com</span>
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_OVERVIEW);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_OVERVIEW))
		{
		?>
		<script type="text/javascript">
			var timeout = 30;
			
			$(document).ready(function()
			{
				setTimeout(PerformCountdown, 1000);
			});
			
			function PerformCountdown()
			{
				timeout = timeout - 1;
			
				if (timeout <= 0)
				{
					timeout = 30;
					RefreshServer();
				}
				
				$('#countdown').html("<i>Update in " + timeout + " seconds</i>");
				setTimeout(PerformCountdown, 1000);
			}
			
			function RefreshServer()
			{
				$('#overview').load('/?module=overview&action=server.ajax&mode=<?php echo MODE_OVERVIEW; ?>');
				$('#players').load('/?module=overview&action=players.ajax&mode=<?php echo MODE_OVERVIEW; ?>');
			}
		</script>
		<div>
		<div id="overview">
			<?php
			$_REQUEST['mode'] = MODE_OVERVIEW;
			require_once('modules/overview.secure/server.ajax.php');
			?>
		</div>
		<div id="status">
		<table class="standard">
			<thead>
				<tr>
					<th class="border-top left">Slot</th>
					<th class="border-top">Name</th>
					<th class="border-top">GUID</th>
					<th class="border-top">Score</th>
					<th class="border-top">IP</th>
					<th class="border-top right">Ping</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td id="countdown" colspan="5" class="border-bottom left"><i>Update in 30 seconds</i></td>
					<td class="border-bottom right">&nbsp;</td>
				</tr>
			</tfoot>
			<tbody id="players">
				<!-- AJAX data here -->
				<?php
				$_REQUEST['mode'] = MODE_OVERVIEW;
				require_once('modules/overview.secure/players.ajax.php');
				?>
			</tbody>
		</table>
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

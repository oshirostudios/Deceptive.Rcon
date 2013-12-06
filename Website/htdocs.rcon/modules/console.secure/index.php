<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Console</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_CONSOLE);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_CONSOLE))
		{
			?>
			<script type="text/javascript" src="/include/ajax.js"></script>
			<script type="text/javascript">
				function ReceiveResponse()
				{
					if (xml_obj.readyState == 4)
					{
						var responseElement = document.getElementById("CommandResponse");
						var currentHTML = responseElement.innerHTML;
						
						if (xml_obj.status == 200)
						{
							responseElement.innerHTML = xml_obj.responseText + currentHTML;
						}
						else
						{
							responseElement.innerHTML = "There was an issue with the response: " + xml_obj.statusText + "<br/>" + currentHTML;
						}
					}
				}
				
				function SendCommand()
				{
					var command = document.CommandEntry.Command.value;
					var query = "module=console&action=server.ajax&command=" + escape(command)
					
					LoadXMLDoc("/index.php", query, ReceiveResponse);
					
					document.CommandEntry.Command.value = '';
				}
			</script>

			<br/><br/>
			<form name="CommandEntry" method="post" action="#" onsubmit="SendCommand(); return false;" autocomplete="off">
				<input type="command" name="Command" autocomplete="off">
			</form>
			
			<div id="CommandResponse" class="response"></div>
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

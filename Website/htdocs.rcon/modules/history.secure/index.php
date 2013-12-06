<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>History</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_HISTORY);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_HISTORY))
		{
			?>
			<script type="text/javascript" src="/include/ajax.js"></script>
			<script type="text/javascript">
				function ReceiveResponse()
				{
					if (xml_obj.readyState == 4)
					{
						var responseElement = document.getElementById("chat-history");
						var currentHTML = responseElement.innerHTML;
						
						if (xml_obj.status == 200)
						{
							responseElement.innerHTML = xml_obj.responseText;
						}
						else
						{
							responseElement.innerHTML = "There was an issue with the response: " + xml_obj.statusText + "<br/>" + currentHTML;
						}
					}
				}
				
				function UpdateHistory(start)
				{
					var player = document.HistoryEntry.Player.value;
					var start_date = document.HistoryEntry.StartDate.value;
					var end_date = document.HistoryEntry.EndDate.value;
					var type = document.HistoryEntry.Type.value;
					
					var query = "module=history&action=server.ajax";
					
					if (player.length > 0)
						query += "&player=" + escape(player);
						
					if (start_date.length > 0)
						query += "&start_date=" + start_date;

					if (end_date.length > 0)
						query += "&end_date=" + end_date;
						
					if (type > 0)
						query += "&type=" + type;

 					query += "&offset=" + start;
 					
					LoadXMLDoc("/index.php", query, ReceiveResponse);
				}
			</script>

			<br/>
			<div class="page_center">
				<form name="HistoryEntry" method="post" action="#" onsubmit="UpdateHistory(0); return false;" autocomplete="off">
					<div style="float: left; width: 170px;"><b>Player Name / GUID</b></div>
					<div style="float: left; width: 170px;"><b>Start Date</b></div>
					<div style="float: left; width: 170px;"><b>End Date</b></div><br />
					<div style="float: left; width: 170px;"><input type="user" name="Player" autocomplete="off"></div>
					<div style="float: left; width: 180px;"><input type="date" name="StartDate" oninput="UpdateHistory(0); return false;" autocomplete="off"></div>
					<div style="float: left; width: 180px;"><input type="date" name="EndDate" oninput="UpdateHistory(0); return false;" autocomplete="off"></div><br />
					<div>
						<select style="width: 165px;" name="Type" onchange="UpdateHistory(0); return false;">
							<option>All</option>
							<option value="1">Join</option>
							<option value="2">Quit</option>
							<option value="3">Name Change</option>
							<option value="4">Chat History</option>
							<option value="7">Warn History</option>
							<option value="6">Kick History</option>
							<option value="5">Ban History</option>
							<option value="8">Command Usage</option>
						</select>
					</div>
				</form>
			</div>
			
			<div id="chat-history"></div>
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

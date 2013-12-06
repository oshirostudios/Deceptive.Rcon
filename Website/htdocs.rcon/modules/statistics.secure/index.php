<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Statistics</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_STATISTICS);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_STATISTICS))
		{
			?>
			<script type="text/javascript" src="/include/ajax.js"></script>
			<script type="text/javascript">
				function ReceiveResponse()
				{
					if (xml_obj.readyState == 4)
					{
						var responseElement = document.getElementById("player-stats");
						var currentHTML = responseElement.innerHTML;
						
						if (xml_obj.status == 200)
							responseElement.innerHTML = xml_obj.responseText;
						else
							responseElement.innerHTML = "There was an issue with the response: " + xml_obj.statusText + "<br/>" + currentHTML;
					}
				}
				
				function ShowStatistics(order, letter)
				{
					var query = "module=statistics&action=server.ajax";
					
					if (order != null) query = query + "&order=" + order;
					if (letter != null) query = query + "&letter=" + letter;
									
					LoadXMLDoc("/index.php", query, ReceiveResponse);
				}
				
				function ShowPlayers(letter)
				{
					var query = "module=statistics&action=server.ajax&letter=" + letter;
					LoadXMLDoc("/index.php", query, ReceiveResponse);
					
					SetSelected(letter);
				}
				
				function SetSelected(letter)
				{
					var node = document.getElementsByTagName('alphabet')[0];
					var elements = node.getElementsByTagName('a');
					
					for (count = 0; count < elements.length; count++)
					{
						if (elements[count].innerHTML == letter)
							elements[count].className = 'selected';
						else
							elements[count].className = '';
					}
				}
				
				ShowStatistics();
			</script>

			<br/>
			<alphabet>
				<ul>
					<li><a href="javascript:ShowPlayers('#');">#</a></li>
					<?php
					$letters = range('A', 'Z');
					foreach ($letters as $letter)
					{
						echo "\t\t\t\t\t<li><a href=\"javascript:ShowPlayers('{$letter}');\">{$letter}</a></li>\n";
					}		
					?>
				</ul>
			</alphabet>
			
			<div id="player-stats"></div>
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

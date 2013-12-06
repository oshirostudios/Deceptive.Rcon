<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Restrictions</h2>
		
		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<div class="view">
		<div class="pages">

		<script type="text/javascript">
			$(document).ready(function()
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
								'module': 'restrictions', 
								'action': 'update.ajax', 
								'item': $(this).attr('name'),
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
								'module': 'restrictions', 
								'action': 'update.ajax', 
								'item': $(this).attr('name'),
								'value': '0'
							}
						});
					}
				}); 
			});
		</script>
			
		<style type="text/css">
			ul.checklist {
				height: 150px;
				width: 188px;
				overflow: auto;
				border: 1px solid;
				list-style-type: none; 
				margin: 0; 
				padding: 0; 
				overflow-x: hidden;
				color: #333;
				background: #fafafa;
			}
			
			ul.checklist li { margin: 0; padding: 0; }
			ul.checklist li input[type="checkbox"] { height: auto; margin: 3px 3px 3px 4px; }

			label { display: block; color: #333; background-color: #fafafa; margin: 0; padding: 0; width: 100%; }
			label:hover { background-color: #999; color: white; }
		</style>
		
		<?php
		// server status... select servers from accessible server ID's
		$user = $_SESSION['{$adb->prefix}user'];
		$user->ShowServerSelect(MODE_RESTRICTIONS);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_RESTRICTIONS))
		{
			$server_id = $_SESSION['server-id'];
			
			$type_query = "SELECT * FROM {$adb->prefix}item_types ORDER BY item_type_name";
			$type_result = $adb->query($type_query, false);
			
			if (!empty($type_result) && $adb->num_rows($type_result) > 0)
			{
				for ($count1 = 0; $count1 < $adb->num_rows($type_result); $count1++)
				{
					$type_id = $adb->query_result($type_result, $count1, 'item_type_id');
					$type_name = $adb->query_result($type_result, $count1, 'item_type_name');
					
					if (($count1 % 4) == 0)
						echo "<div style=\"margin-top: 10px; float: left; width: 190px;\">{$type_name}<br/>\n";
					else
						echo "<div style=\"margin-top: 10px; float: left; width: 190px; margin-left: 20px;\">{$type_name}<br/>\n";
					
					$item_query = "SELECT * FROM {$adb->prefix}items WHERE item_type_id = {$type_id} ORDER BY item_name";
					$item_result = $adb->query($item_query, false);
					
					if (!empty($item_result) && $adb->num_rows($item_result) > 0)
					{
						echo "<ul class=\"checklist\">\n";
						
						for ($count2 = 0; $count2 < $adb->num_rows($item_result); $count2++)
						{
							$item_id = $adb->query_result($item_result, $count2, 'item_id');
							$item_name = $adb->query_result($item_result, $count2, 'item_name');
							
							$restriction_query = "SELECT * FROM {$adb->prefix}server_restrictions WHERE server_id = {$server_id} AND item_id = {$item_id}";
							$restriction_result = $adb->query($restriction_query, false);
							
							$selected = '';
							
							if (!empty($restriction_result) && $adb->num_rows($restriction_result) > 0)
								$selected = ' checked';
							
							echo "<li><label for=\"item-{$item_id}\"><input type=\"checkbox\" id=\"item-{$item_id}\" name=\"item-{$item_id}\"{$selected}> {$item_name}</label></li>\n";
						}
						
						echo "</ul>\n";
					}
					
					echo "</div>\n";
					
					if ((($count1 + 1) % 4) == 0)
						echo "<div style=\"clear: both\"></div>\n";
				}
			}
			?>
			
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

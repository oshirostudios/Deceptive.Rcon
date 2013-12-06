<?php
require_once('modules/index.php');
require_once('style/' . $CONFIG['style'] . '/header.inc.php');
?>

<section id="home" class="home">
	<div class="content">
		<h2>Access</h2>

		<?php
		require_once('style/' . $CONFIG['style'] . '/navigation.inc.php');
		?>
		
		<script type="text/javascript" src="/include/ajax.js"></script>
		<script type="text/javascript">
			var userid = -1;
			$(document).ready(function()
			{
				$('#user-select').change(function()
				{
					$('#access').html('');
					$('#access').load('/?module=access&action=list.ajax&userid=' + $('#user-select').val(), function()
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
										'module': 'access', 
										'action': 'update.ajax', 
										'user': $('#user-select').val(), 
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
										'module': 'access', 
										'action': 'update.ajax', 
										'user': $('#user-select').val(), 
										'target': $(this).attr('name'),
										'value': '0'
									}
								});
							}
 						}); 
 					});
				});
			});
			

			function ReceiveResponse()
			{
				if (xml_obj.readyState == 4)
				{
					var responseElement = document.getElementById("response");
					
					if (xml_obj.status == 200)
					{
						// success returns no data
						if (xml_obj.responseText.length == 0)
							window.location.reload();
						else
							responseElement.innerHTML = "<b>ERROR:</b> " + xml_obj.responseText;
					}
					else
					{
						responseElement.innerHTML = "<b>ERROR:</b> There was an issue with the response: " + xml_obj.statusText;
					}
				}
			}
			
			function AddUser()
			{
				var username = document.userentry.user.value;
				var query = "module=access&action=server.ajax&task=add&username=" + escape(username)
				
				LoadXMLDoc("/index.php", query, ReceiveResponse);
				
				document.userentry.user.value = '';
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
		$user->ShowServerSelect(MODE_ACCESS, $server_id);
		
		if ($_SESSION['server-id'] != 0 && $user->HasAccess($_SESSION['server-id'], MODE_ACCESS))
		{
			?>
			<div>
				<br />
				<div id="overview">
					<select id="user-select" style="width: 200px; padding: 5px;" size="14">
					<?php
					$query = "SELECT * FROM {$adb->prefix}server_users INNER JOIN {$adb->prefix}users ON {$adb->prefix}server_users.user_id = {$adb->prefix}users.user_id WHERE server_id = {$_SESSION['server-id']} ORDER BY user_name";
					$result = $adb->query($query, false);
					
					if (!empty($result) && $adb->num_rows($result) > 0)
					{
						for ($count = 0; $count < $adb->num_rows($result); $count++)
						{
							$user_id = $adb->query_result($result, $count, 'user_id');
							$user_name = $adb->query_result($result, $count, 'user_name');
							
							echo "<option value=\"{$user_id}\">{$user_name}</option>\n";
						}
					}
					?>
					</select>
					<p style="margin-top: 16px;">
						Add Username:
						<form name="userentry" method="post" action="#" onsubmit="AddUser(); return false;">
							<input type="user" id="user" size="26">
						</form>
					</p>
				</div>
				<div id="status">
					<div id="access" class="commands"></div>
				</div>
				<div id="response" style="color: red; margin-top: 10px; float: left;"></div>
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

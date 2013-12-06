<div class="defaultSub">
<div id="sideNavi">
<?php
$current_item = "";

$module = $_SESSION['module'];

if ($_SESSION['secure_module'])
	$module_dir = $CONFIG['root_directory']."/modules/".$_SESSION['module'].".secure";
else if ($_SESSION['hidden_module'])
	$module_dir = $CONFIG['root_directory']."/modules/".$_SESSION['module'].".hidden";
else	
	$module_dir = $CONFIG['root_directory']."/modules/".$_SESSION['module'];

if (!$_SESSION['secure_module'] || isset($_SESSION['{$adb->prefix}user_id']))
{
	$action_dir = @scandir($module_dir);
	
	foreach ($action_dir as $action)
	{
		// .php files... not .hidden.php files... not .php. <- backup files
		if (ereg(".php", $action) && !ereg(".hidden", $action) && !ereg(".php.", $action))
		{
			$action_name = substr($action, 0, -4);
			
			if (ereg(".secure", $action_name))
				$action_name = substr($action_name, 0, -7);
			
			$detail = ucwords(str_replace("-", " ", $action_name));
	
			if ($detail != "Index")
			{
				$class = "";
				
				if ($action_name == $_SESSION['action'])
				{
					if ($current_item == "")
					{
						$current_item = "<ul>\n";
						$class = "act-first";
					}
					else
					{
						$class = "act";
					}
				}
				else if ($current_item == "")
				{
					$current_item = "<ul>\n";
					$class = "first";
				}
				
				if ($class == "")
					$current_item = $current_item . "<li><a href=\"/?module={$module}&action={$action_name}\">{$detail}</a></li>\n";
				else
					$current_item = $current_item . "<li><a class=\"{$class}\" href=\"/?module={$module}&action={$action_name}\">{$detail}</a></li>\n";
			}
		}
	}
	
	if ($current_item != "")
	{
		echo $current_item . "</ul>";
	}
}
?>
</div>

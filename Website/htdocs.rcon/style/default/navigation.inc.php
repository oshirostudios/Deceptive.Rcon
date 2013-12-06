<?php
echo '<!-- Navigation -->';
echo '<nav class="main">';
echo '<ul>';

$user = $_SESSION['{$adb->prefix}user'];
$modules = $user->Access();

$count = 0;

foreach ($modules as &$module)
{
	// folders, not .hidden and not .. or .
	if ($module != "." && $module != ".." && !ereg(".hidden", $module) && is_dir($CONFIG['root_directory'].'/modules/'.$module))
	{
		$module_name = $module;
		
		if (ereg(".secure", $module_name))
			$module_name = substr($module_name, 0, -7);
		
		if ($_SESSION['module'] == $module_name)
			echo '<li><a class="selected" href="/' . $module_name . '">' . ucwords($module_name) . '</a></li>' . "\n";
		else	
			echo '<li><a href="/' . $module_name . '">' . ucwords($module_name) . '</a></li>' . "\n";
			
		$count++;
		
		if ($count % 7 == 0)
		{
			echo "<p></p>\n";
			$count = 0;
		}
	}
}

echo "<li>\n";
echo "<form action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\" target=\"_blank\">\n";
echo "<input style=\"margin-bottom: 0px\" type=\"hidden\" name=\"cmd\" value=\"_s-xclick\">\n";
echo "<input style=\"margin-bottom: 0px\" type=\"hidden\" name=\"encrypted\" value=\"-----BEGIN PKCS7-----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-----END PKCS7-----\">\n";
echo "<input style=\"margin-bottom: 0px\" type=\"image\" src=\"https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG_global.gif\" border=\"0\" name=\"submit\" alt=\"PayPal – The safer, easier way to pay online.\">\n";
echo "<img alt=\"\" border=\"0\" src=\"https://www.paypalobjects.com/en_AU/i/scr/pixel.gif\" width=\"1\" height=\"1\">\n";
echo "</form>\n";
echo "</li>\n";


echo '</ul>';
echo '</nav>';
?>

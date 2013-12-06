<div id="content2">
<div class="inside">
<?php
$module = $_SESSION['module'];
$action = $_SESSION['action'];

$scan_dir = $CONFIG['root_directory']."/modules/{$module}/images";

if (file_exists($scan_dir))
{
	$image_dir = @scandir($scan_dir);
	
	foreach ($image_dir as $image)
	{
		if (ereg($action, $image) && (ereg(".jpg", $image) || ereg(".png", $image) || ereg(".gif", $image)))
		{
			$image_file = "/modules/{$module}/images/{$image}";
	
			echo "<div class=\"contentSpacing\">\n";
			echo "<img class=\"content2img\" src=\"{$image_file}\" />\n";
			echo "</div>\n";
		}
	}
}
?>
<div class="contentSpacing">
<h1 class="bodytext">Contact</h1>
<p class="bodytext">
Please do not hesitate to contact us if you need further information:<br><br>
<a class="contact" href="/?module=contact">General contact detail</a><br>
<a class="mail" href="javascript:linkTo_DecryptMailto('nbjmup;jogpAlwdb/dpn/bv');">info[at]kvca.com.au</a>
</p>
</div>
</div>
</div>

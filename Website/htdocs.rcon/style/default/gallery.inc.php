<?php
function GalleryAvailable($path)
{
	$abs_path = $_SESSION['root_directory'] . $path;
	$found = false;
	
	for ($counter = 1; $counter < 10 && !$found; $counter++)
	{
		if ((file_exists($abs_path . '/image-' . $counter . '.jpg') && file_exists($abs_path . '/thumb-' . $counter . '.jpg')) ||
			  (file_exists($abs_path . '/image-' . $counter . '.png') && file_exists($abs_path . '/thumb-' . $counter . '.png')) ||
			  (file_exists($abs_path . '/image-' . $counter . '.gif') && file_exists($abs_path . '/thumb-' . $counter . '.gif')))
		{
			$found = true;
		}
	}
	
	return $found;
}

function PrintGallery($path)
{
	$abs_path = $_SESSION['root_directory'] . $path;
	
	echo '<!-- Gallery -->' . "\n";
	echo '<ul class="blogpost_gallery portfolio">' . "\n";
	
	for ($counter = 1; $counter < 10; $counter++)
	{
		if (file_exists($abs_path . '/image-' . $counter . '.jpg') && file_exists($abs_path . '/thumb-' . $counter . '.jpg'))
			echo '<li><a href="' . $path . '/image-' . $counter . '.jpg"><img src="' . $path . '/thumb-' . $counter . '.jpg" alt=""></a></li>' . "\n";
		if (file_exists($abs_path . '/image-' . $counter . '.png') && file_exists($abs_path . '/thumb-' . $counter . '.png'))
			echo '<li><a href="' . $path . '/image-' . $counter . '.png"><img src="' . $path . '/thumb-' . $counter . '.png" alt=""></a></li>' . "\n";
		if (file_exists($abs_path . '/image-' . $counter . '.gif') && file_exists($abs_path . '/thumb-' . $counter . '.gif'))
			echo '<li><a href="' . $path . '/image-' . $counter . '.gif"><img src="' . $path . '/thumb-' . $counter . '.gif" alt=""></a></li>' . "\n";
	}
	
	echo '</ul>' . "\n";
}

function ShowGallery($path)
{
	if (GalleryAvailable($path))
	{
		PrintGallery($path);
	}
}
?>

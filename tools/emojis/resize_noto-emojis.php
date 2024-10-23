<?php
$config      = parse_ini_file('emoji_config.ini');
$totalCopied = 0;
$k           = 1;

if (!$config)
{
	die('Unable to load config file.');
}

if (!is_dir($config['src_dir']))
{
	die('Unable to access source folder.');
}

if (!is_dir($config['dst_dir']))
{
	mkdir($config['dst_dir']);
}

foreach (glob($config['src_dir'] . '/*.png') as $file)
{
	$finfo    = pathinfo($file);
	$image    = imagecreatefrompng($file);
	$newImage = imagecreatetruecolor($config['emoji_width'], $config['emoji_height']);
	imagealphablending($newImage, false);
	imagesavealpha($newImage, true);
	imagecopyresampled($newImage, $image, 0, 0, 0, 0, $config['emoji_width'], $config['emoji_height'], imagesx($image), imagesy($image));

	$image = $newImage;
	imagealphablending($image, false);
	imagesavealpha($image, true);
	imagepng($image, $config['dst_dir'] . '/' . $finfo['filename'] . '.png');

	echo ($k++) . '. Processed ' . $config['dst_dir'] . '/' . $finfo['filename'] . '.png!' . "\n";
	$totalCopied++;
}

if (!is_dir($config['src_dir_flags']))
{
	die('Unable to access source folder.');
}

if (!is_dir($config['dst_dir'] . '/flags/'))
{
	mkdir($config['dst_dir'] . '/flags/');
}

foreach (glob($config['src_dir_flags'] . '/*.png') as $file)
{
	// Skip empty images
	if (filesize($file) < 100)
	{
		continue;
	}

	$finfo = pathinfo($file);
	list($widthOrig, $heightOrig) = getimagesize($file);
	$image     = imagecreatefrompng($file);
	$ratioOrig = $widthOrig / $heightOrig;
	$flagWidth = floor($config['flag_height'] * $ratioOrig);
	$newImage  = imagecreatetruecolor($flagWidth, $config['flag_height']);
	imagealphablending($newImage, false);
	imagesavealpha($newImage, true);
	imagecopyresampled($newImage, $image, 0, 0, 0, 0, $flagWidth, $config['flag_height'], imagesx($image), imagesy($image));

	$image = $newImage;
	imagealphablending($image, false);
	imagesavealpha($image, true);
	imagepng($image, $config['dst_dir_flags'] . '/' . $finfo['filename'] . '.png');

	echo ($k++) . '. Processed ' . $config['dst_dir_flags'] . '/' . $finfo['filename'] . '.png!' . "\n";
	$totalCopied++;
}

echo "\nTotal processed: $totalCopied.\n";

<?php
$config = parse_ini_file('emoji_config.ini');

if (!$config)
{
	die('Unable to load config file.');
}

$tileWidth = (int) $config['tile_width'];
$tileHeight = (int) $config['tile_height'];
$numberOfCols = (int) $config['cols_per_row'];
$pxBetweenTiles = (int) $config['px_between_tiles'];
$leftOffSet = $topOffSet = 0;

if (!is_dir($config['dst_dir_shapes']))
{
	mkdir($config['dst_dir_shapes'], 0777, true);
}

function indexToCoords($index)
{
	$tileWidth = $GLOBALS['tileWidth'];
	$pxBetweenTiles = $GLOBALS['pxBetweenTiles'];
	$leftOffSet = $GLOBALS['leftOffSet'];
	$topOffSet = $GLOBALS['topOffSet'];
	$numberOfCols = $GLOBALS['numberOfCols'];

	$x = ($index % $numberOfCols) * ($tileWidth + $pxBetweenTiles) + $leftOffSet;
	$y = floor($index / $numberOfCols) * ($tileWidth + $pxBetweenTiles) + $topOffSet;

	return array($x, $y);
}

$files = file_get_contents($config['dst_dir_resized'] . '/files.json');
$css = 'div.sceditor-emoji {
	min-width: 25%;
	width: 50%;
}

div.emojis-dd-container {
	overflow-y: scroll;
	min-height: 150px;
}

div.emoji-chars {
	margin: .5em 0 !important;
	border-bottom: 2px solid #999;
}

div.emoji-chars:last-child {
	margin-bottom: 0 !important;
	border-bottom: none;
}

div.emoji-chars .group-title {
	color: #555;
	font-weight: 600 !important;
	margin-bottom: .25rem !important;
	display: block !important;
}

div.emoji-chars .subgroup-title {
	color: #b0b0b0;
	display: block !important;
	border-bottom: 1px solid #eaeaea;
	font-weight: 600;
	margin: .2rem 0;
}

div.emoji-chars .emojis {
	margin-bottom: .25rem;
}

div.emoji-chars i {
	text-decoration: none;
	cursor: pointer;
}' . "\n";
$cssBg = '';
$cssPos = '';
$cssClassName = [];

echo '<link href="assets/emoji.css" rel="stylesheet" />';

$list = json_decode($files, true);

foreach ($list as $i => $imgSrc)
{
	$groupClassName = mb_substr($i, 0, 3);
	$cssClassName[] = 'i.' . $groupClassName;
	$cssBg .= 'i.' . $groupClassName . '{background-image:url("' . $i . '.png");}' . "\n";

	if ($i != 'flags')
	{
		// Create base image
		$_tileWidth = $tileWidth + $pxBetweenTiles;
		$mapWidth = round($_tileWidth * $numberOfCols);
		$_totalWidth = count($imgSrc) * $_tileWidth;
		$mapHeight = round($_totalWidth / $numberOfCols);
		$mapHeight = $mapHeight < $tileHeight ? max($mapHeight, $tileHeight) : $mapHeight + $tileHeight;

		$mapImage = imagecreatetruecolor(floor($mapWidth), floor($mapHeight));
		imagesavealpha($mapImage, true);

		$bgColor = imagecolorallocatealpha($mapImage, 0, 0, 0, 127);
		imagefill($mapImage, 0, 0, $bgColor);

		echo $i . ': ' . count($imgSrc) . '<br>
			<div style="background-color: rgba(11,94,215,0.03);">';

		foreach ($imgSrc as $index => $filename)
		{
			$srcImagePath = $config['dst_dir_resized'] . '/' . $filename;

			list($x, $y) = indexToCoords($index);
			$tileImg = imagecreatefrompng($srcImagePath);
			imagecopy($mapImage, $tileImg, $x, $y, 0, 0, $tileWidth, $tileHeight);
			imagedestroy($tileImg);

			$left = $x == 0 ? '0' : '-' . $x . 'px';
			$top = $y == 0 ? '0' : '-' . $y . 'px';
			$className = str_ireplace('emoji_', '', preg_replace('#\.[^.]*$#', '', $filename));
			$cssPos .= 'i.' . $groupClassName . '.' . $className . '{background-position:' . $left . ' ' . $top . ';}' . "\n";

			echo '<i class="' . $groupClassName . ' ' . $className . '"></i>';
		}

		echo '</div>';
	}
	else
	{
		$tileWidth = 32;

		// Create base image
		$_tileWidth = $tileWidth + $pxBetweenTiles;
		$mapWidth = round($_tileWidth * $numberOfCols);
		$_totalWidth = count($imgSrc) * $_tileWidth;
		$mapHeight = round($_totalWidth / $numberOfCols);
		$mapHeight = $mapHeight < $tileHeight ? max($mapHeight, $tileHeight) : $mapHeight + $tileHeight;

		$mapImage = imagecreatetruecolor(floor($mapWidth), floor($mapHeight));
		imagesavealpha($mapImage, true);

		$bgColor = imagecolorallocatealpha($mapImage, 0, 0, 0, 127);
		imagefill($mapImage, 0, 0, $bgColor);

		echo $i . ': ' . count($imgSrc) . '<br>
			<div style="background-color: rgba(11,94,215,0.03);">';

		foreach ($imgSrc as $index => $filename)
		{
			$srcImagePath = $config['dst_dir_resized'] . '/' . $filename;

			if (!is_file($srcImagePath))
			{
				continue;
			}

			$size = @getimagesize($srcImagePath);
			list($x, $y) = indexToCoords($index);
			$tileImg = imagecreatefrompng($srcImagePath);
			imagecopy($mapImage, $tileImg, $x, $y, 0, 0, $tileWidth, $tileHeight);
			imagedestroy($tileImg);

			$left = $x == 0 ? '0' : '-' . $x . 'px';
			$top = $y == 0 ? '0' : '-' . $y . 'px';
			$className = str_ireplace('emoji_', '', preg_replace('#\.[^.]*$#', '', $filename));
			$width = (int) $size[0] > 16 ? ' width: ' . (int) $size[0] . 'px;' : '';
			$cssPos .= 'i.' . $groupClassName . '.' . $className . '{background-position:' . $left . ' ' . $top . ';' . $width . '}' . "\n";

			echo '<i class="' . $groupClassName . ' ' . $className . '"></i>';
		}

		echo '</div>';
	}

	imagepng($mapImage, $config['dst_dir_shapes'] . '/' . $i . '.png', (int) $config['shape_compress'], PNG_NO_FILTER);
	imagedestroy($mapImage);
}

$cssClassName = implode(', ', $cssClassName) . ' {background-repeat: no-repeat; width: 16px; height: 16px; display: inline-block; margin: 2px;}';

file_put_contents($config['dst_dir_shapes'] . '/emoji.css', $css . "\n" . $cssBg . "\n$cssClassName\n" . $cssPos);

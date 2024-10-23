<?php
$config = parse_ini_file('emoji_config.ini');

if (!$config)
{
	die('Unable to load config file.');
}

// Path to folders with resized images
$srcDir = $config['dst_dir'];
$srcFlagsDir = $config['dst_dir_flags'];

// Path where to copy files
$dstDir = $config['dst_dir_resized'];

// Files list
$dstFilelist = $dstDir . '/files.json';
$codeList = array();
$totalCopied = 0;
$totalError = 0;
$totalEmjGroups = 0;
$errorCodes = array();
$groups = array();

if (!is_file('emoji_data.json'))
{
	die('JSON data file not found.');
}

$emojiDataRaw = file_get_contents('emoji_data.json');

if (empty($emojiDataRaw))
{
	die('Empty JSON data file.');
}

$emojiData = json_decode($emojiDataRaw, true);

if (json_last_error() != 0)
{
	$errMsg = json_last_error_msg();

	die($errMsg);
}

foreach ($emojiData['emoji'] as $key => $emjGroup)
{
	$totalEmjGroups++;

	foreach ($emjGroup as $subgroupname => $subgroup)
	{
		$codes = explode(',', $subgroup);

		foreach ($codes as $code)
		{
			// Remove -fe0f from code because noto-emoji doesn't have it.
			$code = str_ireplace('-fe0f', '', $code);
			$filename = 'emoji_u' . strtolower(str_replace('-', '_', $code)) . '.' . $config['image_type'];

			if (!is_dir($dstDir))
			{
				mkdir($dstDir, 0777, true);
			}

			if (is_file($srcDir . '/' . $filename))
			{
				$groups[$key][] = $filename;
				copy($srcDir . '/' . $filename, $dstDir . '/' . $filename);
				$totalCopied++;
				echo "File $filename copied!\n";
			}
			else
			{
				// Try to find in flags
				$flagCode = 'U+' . str_replace('-', ' U+', strtoupper($code));
				$flagFile = array_search($flagCode, $emojiData['flagMap']);

				if ($flagFile !== false)
				{
					$flagFilename = strtoupper($flagFile) . '.' . $config['image_type'];
					$groups[$key][] = $filename;

					if (is_file($srcFlagsDir . '/' . $flagFilename))
					{
						$dstFilename = 'emoji_u' . strtolower(str_replace(' ', '_', str_ireplace('U+', '', $flagCode)));
						copy($srcFlagsDir . '/' . $flagFilename, $dstDir . '/' . $dstFilename . '.' . $config['image_type']);

						/* Do not use
						 * Some territories use flags from other countries. E.g.: Bouvet Island (bv) is Norway (no).
						 * if (in_array($flagFile, $emojiData['flagMapFix']))
						{
							$flagFile = array_search($flagFile, $emojiData['flagMapFix']);
							$flagFilename = strtoupper($flagFile) . '.' . $config['image_type'];
						}
						else
						{
							@copy($srcFlagsDir . $flagFile, $dstDir . '/' . $dstFilename . '.' . $config['image_type']);
						}*/
					}
					else
					{
						$totalError++;
						$errorCodes[] = 'U+' . str_replace('-', ' U+', $code);

						echo "Error for flag file $filename!\n";
					}
				}
				else
				{
					$totalError++;
					$errorCodes[] = 'U+' . str_replace('-', ' U+', $code);

					echo "Error for file $filename!\n";
				}
			}
		}
	}
}

echo 'Total copied: ' . $totalCopied . '. Not found: ' . $totalError . '.' . "\n" . '
Total groups: ' . $totalEmjGroups . '.' . "\n";
echo implode("\n", $errorCodes) . "\n\n";
echo 'Note! Some flag files skipped. GB-NIR, MX-AGU..., US-AK...';

file_put_contents($dstFilelist, json_encode($groups));

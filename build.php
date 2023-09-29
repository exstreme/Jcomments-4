<?php
/**
 * Class to build and zip component package
 *
 * @package       PkgBuilder
 * @copyright (C) 2022 by Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @since         0.1
 */
class PkgBuilder
{
	/**
	 * List of folders of component
	 * Format array(destination zip file => source folder)
	 *
	 * @var   array
	 * @since 0.1
	 */
	private $componentFolders = array(
		'com_jcomments/packages/com_jcomments.zip'               => 'component',
		'com_jcomments/packages/plg_content_jcomments.zip'       => 'plugins/content',
		'com_jcomments/packages/plg_jcommentslock_jcomments.zip' => 'plugins/editors-xtd/jcommentslock',
		'com_jcomments/packages/plg_jcommentsoff_jcomments.zip'  => 'plugins/editors-xtd/jcommentsoff',
		'com_jcomments/packages/plg_jcommentson_jcomments.zip'   => 'plugins/editors-xtd/jcommentson',
		'com_jcomments/packages/plg_quickicon_jcomments.zip'     => 'plugins/quickicon',
		'com_jcomments/packages/plg_system_jcomments.zip'        => 'plugins/system',
		'com_jcomments/packages/plg_user_jcomments.zip'          => 'plugins/user'
	);

	/**
	 * List of plugins folders
	 * Format array(destination zip file => xml file from source folder)
	 *
	 * @var   array
	 * @since 0.1
	 */
	private $pluginsFolders = array(
		'build/plugins/plg_jcomments_avatar.zip' => 'plugins/jcomments/avatar/avatar.xml',
		'build/plugins/plug_cbjcomments.zip'     => 'plugins/community builder/plug_cbjcomments/cb.jcomments.xml'
	);

	/**
	 * List of modules folders
	 * Format array(destination zip file => xml file from source folder)
	 *
	 * @var   array
	 * @since 0.1
	 */
	private $modulesFolders = array(
		'build/modules/mod_jcomments_latest.zip'           => 'modules/mod_jcomments_latest/mod_jcomments_latest.xml',
		'build/modules/mod_jcomments_latest_backend.zip'   => 'modules/mod_jcomments_latest_backend/mod_jcomments_latest_backend.xml',
		'build/modules/mod_jcomments_latest_commented.zip' => 'modules/mod_jcomments_latest_commented/mod_jcomments_latest_commented.xml',
		'build/modules/mod_jcomments_most_commented.zip'   => 'modules/mod_jcomments_most_commented/mod_jcomments_most_commented.xml',
		'build/modules/mod_jcomments_top_posters.zip'      => 'modules/mod_jcomments_top_posters/mod_jcomments_top_posters.xml'
	);

	/**
	 * Download URLs
	 *
	 * @var   array
	 * @since 0.1
	 */
	private $dlURL = array(
		'package' => 'https://github.com/exstreme/Jcomments-4/releases/download/v{version}/pkg_jcomments_{version}.zip',
		'modules' => array(
			'mod_jcomments_latest'           => 'https://github.com/exstreme/Jcomments-4/raw/master/build/modules/mod_jcomments_latest_{version}.zip',
			'mod_jcomments_latest_backend'   => 'https://github.com/exstreme/Jcomments-4/raw/master/build/modules/mod_jcomments_latest_backend_{version}.zip',
			'mod_jcomments_latest_commented' => 'https://github.com/exstreme/Jcomments-4/raw/master/build/modules/mod_jcomments_latest_commented_{version}.zip',
			'mod_jcomments_most_commented'   => 'https://github.com/exstreme/Jcomments-4/raw/master/build/modules/mod_jcomments_most_commented_{version}.zip',
			'mod_jcomments_top_posters'      => 'https://github.com/exstreme/Jcomments-4/raw/master/build/modules/mod_jcomments_top_posters_{version}.zip'),
		'plugins' => array(
			'plg_jcomments_avatar' => 'https://github.com/exstreme/Jcomments-4/raw/master/build/plugins/plg_jcomments_avatar_{version}.zip'
		)
	);

	/**
	 * Update xml file with new extension version
	 *
	 * @var   boolean
	 * @since 0.1
	 */
	private $updateXML = false;

	/**
	 * Create a hash file alongside the zip file.
	 *
	 * @var   boolean
	 * @since 0.1
	 */
	private $shaFile = false;

	/**
	 * Class constructor
	 *
	 * @since  0.1
	 */
	public function __construct()
	{
		$opts = getopt('', array('mod:', 'plg:', 'com', 'u', 'shafile'));

		// Create sha file with hashes
		if (array_key_exists('shafile', $opts))
		{
			$this->shaFile = true;
		}

		// Build component, all modules and plugins
		if (count($opts) == 0)
		{
			$this->makePackage();
			$this->packExtensions('mod');
			$this->packExtensions('plg');
		}
		else
		{
			// Test if we need to update xml file with new extension version, e.g. update-jcomments.xml after build component
			if (array_key_exists('u', $opts))
			{
				$this->updateXML = true;
			}

			// Build component
			if (array_key_exists('com', $opts))
			{
				$this->makePackage();
			}
			// Build modules
			elseif (array_key_exists('mod', $opts))
			{
				$mods = $this->filterString($opts['mod']);
				$modsArray = $this->makeArrayFromString($mods);

				$this->packExtensions('mod', $modsArray);
			}
			// Build plugins
			elseif (array_key_exists('plg', $opts))
			{
				$plugs = $this->filterString($opts['plg']);
				$plugsArray = $this->makeArrayFromString($plugs);

				$this->packExtensions('plg', $plugsArray);
			}
			else
			{
				die('Wrong option.');
			}
		}
	}

	/**
	 * Archive folders, files into zip recursively.
	 *
	 * @param   string  $srcPath  Source folder, file to zip.
	 * @param   string  $dstPath  Destination path with filename and extension.
	 *
	 * @return  boolean
	 *
	 * @since   0.1
	 */
	private function zipFolder(string $srcPath, string $dstPath): bool
	{
		$srcPath   = realpath($srcPath);
		$dstFolder = dirname($dstPath);
		$result    = true;

		if (!is_dir($dstFolder))
		{
			if (!mkdir($dstFolder, 0777, true))
			{
				die('Cannot create destination directory.');
			}
		}

		$zip = new ZipArchive;
		$zip->open($dstPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath), RecursiveIteratorIterator::LEAVES_ONLY);

		foreach ($files as $name => $file)
		{
			if (!$file->isDir())
			{
				$filePath = str_replace('\\', '/', $file->getRealPath());
				$relativePath = str_replace('\\', '/', substr($filePath, strlen($srcPath) + 1));

				if ($zip->addFile($filePath, $relativePath) === false)
				{
					$result = false;
					break;
				}
			}
		}

		$zip->close();

		return $result;
	}

	/**
	 * Make a component package zip, calculate sha hash.
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since  0.1
	 */
	private function makePackage()
	{
		// Get the component version
		$componentManifest = $this->loadXmlFile(__DIR__ . '/component/jcomments.xml');

		if ($componentManifest === false)
		{
			die(0);
		}

		$dst = __DIR__ . '/';

		foreach ($this->componentFolders as $dst => $src)
		{
			if ($this->zipFolder(__DIR__ . '/' . $src, __DIR__ . '/' . $dst))
			{
				echo __DIR__ . '/' . $dst . ' created.' . "\n";
			}
			else
			{
				echo __DIR__ . '/' . $dst . ' file error.' . "\n";
			}

			$dst = dirname(__DIR__ . '/' . $dst);
		}

		// Load package manifest and fix version from component manifest
		$packageManifest = $this->loadXmlFile(__DIR__ . '/build/pkg_jcomments.xml');

		if ($packageManifest === false)
		{
			die(0);
		}

		$packageManifest->version = $componentManifest->version;
		$packageManifest->asXML(__DIR__ . '/build/pkg_jcomments.xml');

		copy(__DIR__ . '/build/pkg_jcomments.php', dirname($dst) . '/pkg_jcomments.php');
		copy(__DIR__ . '/build/pkg_jcomments.xml', dirname($dst) . '/pkg_jcomments.xml');

		$dstFilepath = __DIR__ . '/pkg_jcomments_' . $componentManifest->version . '.zip';

		if ($this->zipFolder(dirname($dst), $dstFilepath))
		{
			echo 'Component package created at ' . $dstFilepath . "\n";
		}

		// Remove temp folder
		foreach (glob($dst . '/*.*', GLOB_NOCHECK) as $file)
		{
			$parentDir = dirname($file, 2);
			@unlink($file);
			@rmdir(dirname($file));

			foreach (glob($parentDir . '/*.*', GLOB_NOCHECK) as $_file)
			{
				@unlink($_file);
				@rmdir(dirname($_file));
			}
		}

		// Calculate sha hash
		$sha256 = hash_file('sha256', $dstFilepath);
		$sha384 = hash_file('sha384', $dstFilepath);
		$sha512 = hash_file('sha512', $dstFilepath);

		if ($this->shaFile)
		{
			$sha = 'sha256: ' . $sha256 . "\n";
			$sha .= 'sha384: ' . $sha384 . "\n";
			$sha .= 'sha512: ' . $sha512 . "\n";
			file_put_contents(dirname($dst, 2) . '/' . basename($dstFilepath) . '.sha', $sha);
		}

		if ($this->updateXML)
		{
			$updateXML = $this->loadXmlFile(__DIR__ . '/update-jcomments.xml');

			if ($updateXML === false)
			{
				die(0);
			}

			$updateXML->update->version = $componentManifest->version;
			$updateXML->update->sha256  = $sha256;
			$updateXML->update->sha384  = $sha384;
			$updateXML->update->sha512  = $sha512;
			$updateXML->update->downloads->downloadurl = str_replace('{version}', $componentManifest->version, $this->dlURL['package']);
			$updateXML->asXML(__DIR__ . '/update-jcomments.xml');

			echo 'update-jcomments.xml have a new ' . $componentManifest->version . ' version.' . "\n";
		}
	}

	/**
	 * Make a plugin or module zip, and update readme.md.
	 *
	 * @param   string  $type  Extension type. Can be: mod - modules, plg - plugins
	 * @param   array   $ext   Array with modules or plugins to process
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   0.1
	 */
	private function packExtensions(string $type, array $ext = array())
	{
		if ($type === 'mod')
		{
			$extType = 'modules';
		}
		elseif ($type === 'plg')
		{
			$extType = 'plugins';
		}
		else
		{
			exit('Extension type error!');
		}

		/** @see $pluginsFolders */
		/** @see $modulesFolders */
		$varFolders = $extType . 'Folders';
		$folders = $this->$varFolders;

		// Build listed modules/plugins.
		if (!empty($ext) && in_array('all', $ext) === false)
		{
			$folders = array();

			// Reduce an array with listed extensions in $this->$varFolders to listed in CMD.
			foreach ($this->$varFolders as $dest => $source)
			{
				foreach ($ext as $value)
				{
					preg_match('@' . $value . '\.zip@sixU', $dest, $matches);

					if (isset($matches[0]))
					{
						$folders[$dest] = $source;
					}
				}
			}
		}

		$readme = file_get_contents(__DIR__ . '/build/' . $extType . '/README.md');
		$xml    = false;

		if ($this->updateXML)
		{
			$xml = $this->loadXmlFile(__DIR__ . '/update-jcomments-' . $extType . '.xml');
		}

		foreach ($folders as $dst => $src)
		{
			// Fix plugin/module filename version
			$manifest = $this->loadXmlFile($src);

			if ($manifest === false)
			{
				continue;
			}

			$pathInfo    = pathinfo(__DIR__ . '/' . $dst);
			$dstFolder   = $pathInfo['dirname'];
			$dstFilename = $pathInfo['filename'];
			$dstPath     = $dstFolder . '/' . $dstFilename . '_' . $manifest->version . '.zip';

			if ($this->zipFolder(__DIR__ . '/' . dirname($src), $dstPath))
			{
				$sha256 = hash_file('sha256', $dstPath);
				$sha384 = hash_file('sha384', $dstPath);
				$sha512 = hash_file('sha512', $dstPath);

				if ($this->shaFile)
				{
					$sha = 'sha256: ' . $sha256 . "\n";
					$sha .= 'sha384: ' . $sha384 . "\n";
					$sha .= 'sha512: ' . $sha512 . "\n";
					file_put_contents($dstFolder . '/' . basename($dstPath) . '.sha', $sha);
				}

				if ($this->updateXML && $xml !== false)
				{
					foreach ($xml as $item)
					{
						$element = trim($item->element);
						$_type = trim($item->type);

						if ($_type == 'module')
						{
							if ($element === $dstFilename)
							{
								$item->version = $manifest->version;
								$item->sha256  = $sha256;
								$item->sha384  = $sha384;
								$item->sha512  = $sha512;
								$item->downloads->downloadurl = str_replace('{version}', $manifest->version, $this->dlURL[$extType][$element]);

								break;
							}
						}
						elseif ($_type == 'plugin')
						{
							if (strpos($dstFilename, $element) !== false)
							{
								$item->version = $manifest->version;
								$item->sha256  = $sha256;
								$item->sha384  = $sha384;
								$item->sha512  = $sha512;
								$item->downloads->downloadurl = str_replace('{version}', $manifest->version, $this->dlURL[$extType][$dstFilename]);

								break;
							}
						}
					}
				}

				echo $dstPath . ' created.' . "\n";
			}
			else
			{
				echo $dstPath . ' file error.' . "\n";
			}

			// Update 'build/plugins|modules/README.md'
			$readme = preg_replace(
				'/' . $dstFilename . '_(\d)\.(\d)\.(\d)\.zip/sixU',
				$dstFilename . '_' . $manifest->version . '.zip',
				$readme
			);
		}

		file_put_contents(__DIR__ . '/build/' . $extType . '/README.md', $readme);

		if ($this->updateXML && $xml !== false)
		{
			$xml->asXML(__DIR__ . '/update-jcomments-' . $extType . '.xml');

			echo 'update-jcomments-' . $extType . '.xml have a new extension version(s).' . "\n";
		}
	}

	/**
	 * FILTER_SANITIZE_STRING replacement
	 *
	 * @param   string  $string  Text to filter
	 *
	 * @return  string
	 *
	 * @since   0.1
	 */
	private function filterString(string $string): string
	{
		$str = preg_replace('/\x00|<[^>]*>?/', '', $string);

		return str_replace(["'", '"'], ['&#39;', '&#34;'], $str);
	}

	/**
	 * Utility function to make an array from coma separated string and cleanup it.
	 *
	 * @param   string  $input  String to filter
	 *
	 * @return  array
	 *
	 * @since   0.1
	 */
	private function makeArrayFromString(string $input): array
	{
		$inputs = explode(',', $input);
		$inputs = array_filter($inputs);

		return array_map(
			function ($val)
			{
				return trim($val);
			},
			$inputs
		);
	}

	/**
	 * Load XML file and parse errors if any.
	 *
	 * @param   string  $path  Path to a file
	 *
	 * @return  SimpleXMLElement|boolean  Return boolean false on error, SimpleXMLElement object otherwise.
	 *
	 * @since   0.1
	 */
	private function loadXmlFile(string $path)
	{
		libxml_use_internal_errors(true);

		$xml = simplexml_load_file($path);

		if ($xml === false)
		{
			$errors = libxml_get_errors();

			foreach ($errors as $error)
			{
				echo 'Error "' . trim($error->message) . '" in "' . $error->file . '" at line ' . $error->line . '.' . "\n";
			}
		}

		libxml_clear_errors();

		return $xml;
	}
}

$class = new PkgBuilder;

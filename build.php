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
	 * Class constructor
	 *
	 * @since  0.1
	 */
	public function __construct()
	{
		$opts = getopt('', array('mod:', 'plg:', 'com'));

		// Build component, all modules and plugins
		if (count($opts) == 0)
		{
			$this->makePackage();
			$this->packExtensions('mod');
			$this->packExtensions('plg');
		}
		else
		{
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
	private function zipFolder(string $srcPath, string $dstPath)
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
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen($srcPath) + 1);

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
	 * @since  0.1
	 */
	private function makePackage()
	{
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

		copy(__DIR__ . '/build/pkg_jcomments.php', dirname($dst) . '/pkg_jcomments.php');
		copy(__DIR__ . '/build/pkg_jcomments.xml', dirname($dst) . '/pkg_jcomments.xml');

		// Get the component version
		$componentManifest = simplexml_load_file(__DIR__ . '/component/jcomments.xml');

		// Load package manifest and fix version from component manifest
		$packageManifest = simplexml_load_file(__DIR__ . '/build/pkg_jcomments.xml');
		$packageManifest->version = $componentManifest->version;
		$packageManifest->asXML(dirname($dst) . '/pkg_jcomments.xml');

		$dstFilepath = __DIR__ . '/pkg_jcomments_' . $componentManifest->version . '.zip';

		if ($this->zipFolder(dirname($dst), $dstFilepath))
		{
			echo 'Component package created at ' . $dstFilepath;
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
		$sha = 'sha256: ' . hash_file('sha256', $dstFilepath) . "\n";
		$sha .= 'sha384: ' . hash_file('sha384', $dstFilepath) . "\n";
		$sha .= 'sha512: ' . hash_file('sha512', $dstFilepath) . "\n";
		file_put_contents(dirname($dst, 2) . '/' . basename($dstFilepath) . '.sha', $sha);
	}

	/**
	 * Make a plugin or module zip, and update readme.md.
	 *
	 * @param   string  $type  Extension type. Can be: mod - modules, plg - plugins
	 * @param   array   $ext   Array with modules or plugins to process
	 *
	 * @return  void
	 *
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
			exit('Type error');
		}

		/** @see $pluginsFolders */
		/** @see $modulesFolders */
		$varFolders = $extType . 'Folders';
		$folders = $this->$varFolders;

		// Build listed modules/plugins.
		if (!empty($ext) && in_array('all', $ext) === false)
		{
			$folders = array();

			// Reduce an array with listed extensions to listed in CMD
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

		foreach ($folders as $dst => $src)
		{
			// Fix plugin/module filename version
			$manifest    = simplexml_load_file($src);
			$pathInfo    = pathinfo(__DIR__ . '/' . $dst);
			$dstFolder   = $pathInfo['dirname'];
			$dstFilename = $pathInfo['filename'];
			$dstPath = $dstFolder . '/' . $dstFilename . '_' . $manifest->version . '.zip';

			if ($this->zipFolder(__DIR__ . '/' . dirname($src), $dstPath))
			{
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
}

$class = new PkgBuilder;

<?php
/**
 * JComments legacy bootstrap helpers.
 *
 * @package  JComments
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Loads the legacy JComments class layout without Joomla's compatibility plugin.
 *
 * @since  4.1.0
 */
final class JCommentsLegacyBootstrap
{
	/**
	 * Autoloader registration guard.
	 *
	 * @var    bool
	 * @since  4.1.0
	 */
	private static bool $registered = false;

	/**
	 * Register the legacy class autoloader.
	 *
	 * @return  void
	 *
	 * @since   4.1.0
	 */
	public static function register(): void
	{
		if (self::$registered)
		{
			return;
		}

		spl_autoload_register([self::class, 'loadClass']);
		self::$registered = true;
	}

	/**
	 * Create a legacy controller for the current request task.
	 *
	 * @param   string  $prefix    Controller class prefix.
	 * @param   string  $basePath  Component base path.
	 *
	 * @return  array{0: BaseController, 1: string}
	 *
	 * @throws  RuntimeException
	 * @since   4.1.0
	 */
	public static function createController(string $prefix, string $basePath): array
	{
		self::register();

		$app    = Factory::getApplication();
		$input  = $app->getInput();
		$task   = $input->getCmd('task', 'display');
		$format = $input->getCmd('format', '');

		$controllerName = '';
		$controllerTask = $task;

		if (strpos($task, '.') !== false)
		{
			[$controllerName, $controllerTask] = explode('.', $task, 2);
		}

		$class = $prefix . 'Controller' . ucfirst($controllerName);

		if ($controllerName === '' || !self::loadControllerFile($basePath, $controllerName, $format) || !class_exists($class))
		{
			self::loadControllerFile($basePath, '', $format);
			$class          = $prefix . 'Controller';
			$controllerTask = $task;
		}

		if (!class_exists($class))
		{
			throw new RuntimeException(sprintf('JComments controller "%s" was not found.', $class));
		}

		return [new $class(['base_path' => $basePath]), $controllerTask ?: 'display'];
	}

	/**
	 * Load a legacy class by its old global name.
	 *
	 * @param   string  $class  Class name.
	 *
	 * @return  void
	 *
	 * @since   4.1.0
	 */
	public static function loadClass(string $class): void
	{
		$paths = self::getClassCandidates($class);

		foreach ($paths as $path)
		{
			if (is_file($path))
			{
				require_once $path;

				return;
			}
		}
	}

	/**
	 * Load a controller file for a request.
	 *
	 * @param   string  $basePath        Component base path.
	 * @param   string  $controllerName  Controller name.
	 * @param   string  $format          Request format.
	 *
	 * @return  bool
	 *
	 * @since   4.1.0
	 */
	private static function loadControllerFile(string $basePath, string $controllerName, string $format = ''): bool
	{
		$name = strtolower($controllerName);

		$paths = $name === ''
			? [$basePath . '/controller.php']
			: [
				$basePath . '/controllers/' . $name . ($format !== '' ? '.' . $format : '') . '.php',
				$basePath . '/controllers/' . $name . '.php',
			];

		foreach ($paths as $path)
		{
			if (is_file($path))
			{
				require_once $path;

				return true;
			}
		}

		return false;
	}

	/**
	 * Build possible paths for legacy class names.
	 *
	 * @param   string  $class  Class name.
	 *
	 * @return  string[]
	 *
	 * @since   4.1.0
	 */
	private static function getClassCandidates(string $class): array
	{
		$site  = JPATH_ROOT . '/components/com_jcomments';
		$admin = JPATH_ADMINISTRATOR . '/components/com_jcomments';
		$lower = strtolower($class);
		$paths = [];

		$special = [
			'jcommentscontrollerform' => $admin . '/controllers/controllerform.php',
			'jcommentscontrollerlist' => $admin . '/controllers/controllerlist.php',
			'jcommentsmodelform'      => $admin . '/models/modelform.php',
			'jcommentsmodellist'      => $admin . '/models/modellist.php',
			'jcommentsmodel'          => $site . '/models/jcomments.php',
		];

		if (isset($special[$lower]))
		{
			$paths[] = $special[$lower];
		}

		if (stripos($class, 'JCommentsTable') === 0)
		{
			$paths[] = $admin . '/tables/' . strtolower(substr($class, 14)) . '.php';
		}

		if (stripos($class, 'JCommentsController') === 0 || stripos($class, 'JcommentsController') === 0)
		{
			$suffix  = substr($class, strlen('JcommentsController'));
			$paths[] = $admin . '/controllers/' . strtolower($suffix) . '.php';
			$paths[] = $site . '/controllers/' . strtolower($suffix) . '.php';
		}

		if (stripos($class, 'JCommentsModel') === 0 || stripos($class, 'JcommentsModel') === 0)
		{
			$suffix  = substr($class, strlen('JcommentsModel'));
			$paths[] = $admin . '/models/' . strtolower($suffix) . '.php';
			$paths[] = $site . '/models/' . strtolower($suffix) . '.php';
		}

		if (stripos($class, 'JCommentsView') === 0 || stripos($class, 'JcommentsView') === 0)
		{
			$suffix  = strtolower(substr($class, strlen('JcommentsView')));
			$paths[] = $admin . '/views/' . $suffix . '/view.html.php';
			$paths[] = $site . '/views/' . $suffix . '/view.html.php';
			$paths[] = $site . '/views/' . $suffix . '/view.feed.php';
		}

		if (stripos($class, 'JFormField') === 0)
		{
			$paths[] = $admin . '/models/fields/' . strtolower(substr($class, 10)) . '.php';
		}

		if (stripos($class, 'JFormRule') === 0)
		{
			$paths[] = $admin . '/models/rules/' . strtolower(substr($class, 9)) . '.php';
		}

		if (stripos($class, 'JComments') === 0)
		{
			$suffix  = strtolower(substr($class, 9));
			$paths[] = $site . '/classes/' . $suffix . '.php';
			$paths[] = $site . '/helpers/' . $suffix . '.php';
			$paths[] = $admin . '/helpers/' . $suffix . '.php';
		}

		return array_values(array_unique(array_filter($paths)));
	}
}

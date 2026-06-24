<?php
/**
 * Transitional frontend controller bridge.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Routes Joomla 6 controller tasks into the existing frontend entrypoint.
 *
 * @since  5.1.0
 */
abstract class LegacyController extends BaseController
{
	/**
	 * Legacy controller segment used in controller.task requests.
	 *
	 * @var    string
	 * @since  5.1.0
	 */
	protected string $legacyController = '';

	/**
	 * Execute the current task through the existing frontend entrypoint.
	 *
	 * @param   string  $task  The task to execute.
	 *
	 * @return  void
	 *
	 * @since   5.1.0
	 */
	public function execute($task): void
	{
		$task = (string) $task;

		if ($this->legacyController !== '' && $task !== '')
		{
			$this->input->set('task', $this->legacyController . '.' . $task);
		}

		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', JPATH_ROOT . '/components/com_jcomments');
		}

		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';
	}
}

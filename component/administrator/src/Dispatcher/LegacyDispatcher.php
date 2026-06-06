<?php
/**
 * JComments legacy dispatcher.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Administrator\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\Input\Input;

/**
 * Dispatches the legacy JComments entrypoints through Joomla 6 component booting.
 *
 * @since  4.1.0
 */
final class LegacyDispatcher implements DispatcherInterface
{
	/**
	 * The application.
	 *
	 * @var    CMSApplicationInterface
	 * @since  4.1.0
	 */
	private CMSApplicationInterface $app;

	/**
	 * The input object.
	 *
	 * @var    Input
	 * @since  4.1.0
	 */
	private Input $input;

	/**
	 * Constructor.
	 *
	 * @param   CMSApplicationInterface  $app    The application.
	 * @param   Input                    $input  The input.
	 *
	 * @since   4.1.0
	 */
	public function __construct(CMSApplicationInterface $app, Input $input)
	{
		$this->app   = $app;
		$this->input = $input;
	}

	/**
	 * Run the component.
	 *
	 * @return  void
	 *
	 * @since   4.1.0
	 */
	public function dispatch(): void
	{
		$this->app->input = $this->input;

		$entrypoint = $this->app->isClient('administrator')
			? JPATH_ADMINISTRATOR . '/components/com_jcomments/jcomments.php'
			: JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		require $entrypoint;
	}
}

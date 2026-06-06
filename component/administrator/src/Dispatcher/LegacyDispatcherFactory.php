<?php
/**
 * JComments legacy dispatcher factory.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Administrator\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\Input\Input;

/**
 * Creates the JComments legacy dispatcher.
 *
 * @since  4.1.0
 */
final class LegacyDispatcherFactory implements ComponentDispatcherFactoryInterface
{
	/**
	 * Create a dispatcher.
	 *
	 * @param   CMSApplicationInterface  $application  The application.
	 * @param   Input|null               $input        The input object.
	 *
	 * @return  DispatcherInterface
	 *
	 * @since   4.1.0
	 */
	public function createDispatcher(CMSApplicationInterface $application, ?Input $input = null): DispatcherInterface
	{
		return new LegacyDispatcher($application, $input ?? $application->getInput());
	}
}

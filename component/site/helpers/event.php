<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * JComments Event Helper
 *
 * @since  3.0
 */
class JCommentsEvent
{
	/**
	 * Triggers an event by dispatching arguments to all observers that handle
	 * the event and returning their return values.
	 *
	 * @param   string  $event  The event name
	 * @param   array   $args   An array of arguments
	 *
	 * @return  array   An array of results from each function call
	 *
	 * @since   3.0
	 */
	public static function trigger($event, $args = null)
	{
		static $initialised = false;

		$result = array();
		$config = ComponentHelper::getParams('com_jcomments');

		if ((int) $config->get('enable_plugins') == 1)
		{
			if (!$initialised)
			{
				PluginHelper::importPlugin('jcomments');
				$initialised = true;
			}

			if (is_array($args))
			{
				$result = Factory::getApplication()->triggerEvent($event, $args);
			}
			else
			{
				$result = Factory::getApplication()->triggerEvent($event);
			}
		}

		return $result;
	}
}

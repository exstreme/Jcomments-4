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

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

/**
 * Joomla plugins helper
 *
 * @since  3.0
 */
class JCommentsPlugin
{
	/**
	 * Gets the parameter object for a plugin
	 *
	 * @param   string  $pluginName  The plugin name
	 * @param   string  $type        The plugin type, relates to the sub-directory in the plugins directory
	 *
	 * @return  Registry
	 *
	 * @since   3.0
	 */
	public static function getParams($pluginName, $type = 'content')
	{
		$plugin = PluginHelper::getPlugin($type, $pluginName);

		if (is_object($plugin))
		{
			$pluginParams = new Registry($plugin->params);
		}
		else
		{
			$pluginParams = new Registry('');
		}

		return $pluginParams;
	}
}

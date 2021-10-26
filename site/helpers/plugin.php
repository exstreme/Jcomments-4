<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

/**
 * Joomla plugins helper
 */
class JCommentsPluginHelper
{
	/**
	 * Gets the parameter object for a plugin
	 *
	 * @param string $pluginName The plugin name
	 * @param string $type The plugin type, relates to the sub-directory in the plugins directory
	 */
	public static function getParams($pluginName, $type = 'content')
	{
		$plugin	= JPluginHelper::getPlugin($type, $pluginName);
 		if (is_object($plugin)) {
			$pluginParams = new JRegistry($plugin->params);
		} else {
			$pluginParams = new JRegistry('');
		}
		return $pluginParams;
	}
}
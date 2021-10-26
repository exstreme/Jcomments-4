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

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

/**
 * JComments Multilingual support
 */
class JCommentsMultilingual
{
	public static function isEnabled()
	{
		static $enabled = null;

		if (!isset($enabled)) {
			$app = JFactory::getApplication();

			if (JCommentsSystemPluginHelper::isSite($app)) { 
				$enabled = $app->getLanguageFilter();
			}
			 else {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);
				$query->select('enabled');
				$query->from($db->quoteName('#__extensions'));
				$query->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
				$query->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
				$query->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));
				$db->setQuery($query);

				$enabled = $db->loadResult();
			}

			JFactory::getConfig()->set('multilingual_support', $enabled);

			if ($enabled) {
				$enabled = JCommentsFactory::getConfig()->get('multilingual_support', $enabled);
			}
		}

		return $enabled;
	}

	public static function getLanguage()
	{
		static $language = null;

		if (!isset($language)) {
			$language = JFactory::getLanguage()->getTag();
		}

		return $language;
	}

	public static function getLanguages()
	{
		// TODO: JoomFish support
		return JLanguageHelper::getLanguages();
	}
}
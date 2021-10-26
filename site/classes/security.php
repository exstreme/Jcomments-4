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
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;

/**
 * JComments security functions
 */
class JCommentsSecurity
{
	public static function notAuth()
	{
		header('HTTP/1.0 403 Forbidden');
		jexit(Text::_('JERROR_ALERTNOAUTHOR'));
	}

	public static function badRequest()
	{
		return (int) (empty($_SERVER['HTTP_USER_AGENT']) || (!$_SERVER['REQUEST_METHOD'] == 'POST'));
	}

	public static function checkFlood($ip)
	{
		$app      = Factory::getApplication();
		$interval = (int) ComponentHelper::getParams('com_jcomments')->get('flood_time');

		if ($interval > 0)
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$now   = Factory::getDate()->toSql();
			$query = "SELECT COUNT(id) "
				. "\nFROM #__jcomments "
				. "\nWHERE ip = " . $db->Quote($ip)
				. "\nAND " . $db->Quote($now) . " < DATE_ADD(date, INTERVAL " . $interval . " SECOND)"
				. (JCommentsFactory::getLanguageFilter() ? "\nAND lang = " . $db->Quote($app->getLanguage()->getTag()) : '');
			$db->setQuery($query);

			return ($db->loadResult() == 0) ? 0 : 1;
		}

		return 0;
	}

	public static function checkIsForbiddenUsername($str)
	{
		$names = ComponentHelper::getParams('com_jcomments')->get('forbidden_names');

		if (!empty($names) && !empty($str))
		{
			$str = trim(StringHelper::strtolower($str));

			$names = StringHelper::strtolower(preg_replace("#,+#u", ',', preg_replace("#[\n|\r]+#u", ',', $names)));
			$names = explode(",", $names);

			foreach ($names as $name)
			{
				if (trim((string) $name) == $str)
				{
					return 1;
				}
			}
		}

		return 0;
	}

	public static function checkIsRegisteredUsername($name)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if ((int) $config->get('enable_username_check') == 1)
		{
			$name = StringHelper::strtolower($name);
			$db   = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true);
			$query->select('COUNT(id)');
			$query->from($db->quoteName('#__users'));
			$query->where('LOWER(name) = ' . $db->Quote($db->escape($name, true)), 'OR');
			$query->where('LOWER(username) = ' . $db->Quote($db->escape($name, true)), 'OR');
			$db->setQuery($query);

			return ($db->loadResult() == 0) ? 0 : 1;
		}

		return 0;
	}

	public static function checkIsRegisteredEmail($email)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if ((int) $config->get('enable_username_check') == 1)
		{
			$email = StringHelper::strtolower($email);
			$db    = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true);
			$query->select('COUNT(id)');
			$query->from($db->quoteName('#__users'));
			$query->where('LOWER(email) = ' . $db->Quote($db->escape($email, true)));
			$db->setQuery($query);

			return ($db->loadResult() == 0) ? 0 : 1;
		}

		return 0;
	}

	/**
	 * Check if given parameters are not listed in blacklist
	 *
	 * @param   array  $options  Array of options for check
	 *
	 * @return  boolean True on success, false otherwise
	 */
	public static function checkBlacklist($options = array())
	{
		$ip     = $options['ip'] ?? null;
		$userid = $options['userid'] ?? 0;
		$result = true;

		if (count($options))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true);
			$query->select('COUNT(id)');
			$query->from($db->quoteName('#__jcomments_blacklist'));

			if ($userid > 0)
			{
				$query->where($db->quoteName('userid') . ' = ' . (int) $userid);
			}
			else
			{
				if (!empty($ip))
				{
					$parts = explode('.', $ip);

					if (count($parts) == 4)
					{
						$conditions   = array();
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->Quote($ip);
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->Quote(sprintf('%s.%s.%s.*', $parts[0], $parts[1], $parts[2]));
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->Quote(sprintf('%s.%s.*.*', $parts[0], $parts[1]));
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->Quote(sprintf('%s.*.*.*', $parts[0]));

						$query->where($conditions, 'OR');
					}
					else
					{
						$query->where($db->quoteName('ip') . ' = ' . $db->Quote($ip));
					}
				}
			}

			$db->setQuery($query);

			$result = !($db->loadResult() > 0);
		}

		return $result;
	}

	public static function clearObjectGroup($str)
	{
		return trim(preg_replace('#[^0-9A-Za-z\-\_\,\.]#is', '', strip_tags($str)));
	}
}

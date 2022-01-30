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
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

/**
 * JComments security functions
 *
 * @since  3.0
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
			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$now   = Factory::getDate()->toSql();

			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__jcomments'))
				->where($db->quoteName('ip') . ' = ' . $db->quote($ip))
				->where($db->quote($now) . ' < DATE_ADD(date, INTERVAL ' . $interval . ' SECOND)');

			if (JCommentsFactory::getLanguageFilter())
			{
				$query->where($db->quoteName('lang') . ' = ' . $db->quote($app->getLanguage()->getTag()));
			}

			$db->setQuery($query);

			try
			{
				return ($db->loadResult() == 0) ? 0 : 1;
			}
			catch (RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
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

			/** @var DatabaseDriver $db */
			$db   = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__users'))
				->where('LOWER(name) = ' . $db->quote($db->escape($name, true)), 'OR')
				->where('LOWER(username) = ' . $db->quote($db->escape($name, true)), 'OR');
			$db->setQuery($query);

			try
			{
				return ($db->loadResult() == 0) ? 0 : 1;
			}
			catch (RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
		}

		return 0;
	}

	public static function checkIsRegisteredEmail($email)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if ((int) $config->get('enable_username_check') == 1)
		{
			$email = StringHelper::strtolower($email);

			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__users'))
				->where('LOWER(email) = ' . $db->quote($db->escape($email, true)));
			$db->setQuery($query);

			try
			{
				return ($db->loadResult() == 0) ? 0 : 1;
			}
			catch (RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
		}

		return 0;
	}

	/**
	 * Check if given parameters are not listed in blacklist
	 *
	 * @param   array  $options  Array of options for check
	 *
	 * @return  boolean True on success, false otherwise
	 *
	 * @since   3.0
	 */
	public static function checkBlacklist($options = array())
	{
		$ip     = $options['ip'] ?? null;
		$userid = $options['userid'] ?? 0;
		$result = true;

		if (count($options))
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__jcomments_blacklist'));

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
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote($ip);
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote(sprintf('%s.%s.%s.*', $parts[0], $parts[1], $parts[2]));
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote(sprintf('%s.%s.*.*', $parts[0], $parts[1]));
						$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote(sprintf('%s.*.*.*', $parts[0]));

						$query->where($conditions, 'OR');
					}
					else
					{
						$query->where($db->quoteName('ip') . ' = ' . $db->quote($ip));
					}
				}
			}

			$db->setQuery($query);

			try
			{
				$result = !($db->loadResult() > 0);
			}
			catch (RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
		}

		return $result;
	}

	// TODO Replace with InputFilter::clean()
	public static function clearObjectGroup($str)
	{
		return trim(preg_replace('#[^0-9A-Za-z\-\_\,\.]#is', '', strip_tags($str)));
	}
}

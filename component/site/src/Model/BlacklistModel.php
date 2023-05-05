<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;
use Joomla\Utilities\IpHelper;

/**
 * Comment item class
 *
 * @since  4.1
 */
class BlacklistModel extends BaseDatabaseModel
{
	/**
	 * Get the reason for the ban.
	 *
	 * @param   integer  $uid  User ID
	 *
	 * @return  mixed
	 *
	 * @since   4.1
	 */
	public function getBlacklistReason(int $uid): ?string
	{
		$db = $this->getDatabase();
		$ip = IpHelper::getIp();

		$query = $db->getQuery(true)
			->select($db->quoteName('reason'))
			->from($db->quoteName('#__jcomments_blacklist'))
			->where($db->quoteName('userid') . ' = :uid', 'OR')
			->bind(':uid', $uid, ParameterType::INTEGER);

		$parts = explode('.', $ip);

		if (count($parts) == 4)
		{
			$conditions   = array();
			$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote($ip);
			$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote(sprintf('%s.%s.%s.*', $parts[0], $parts[1], $parts[2]));
			$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote(sprintf('%s.%s.*.*', $parts[0], $parts[1]));
			$conditions[] = $db->quoteName('ip') . ' = ' . $db->quote(sprintf('%s.*.*.*', $parts[0]));

			$query->where($conditions);
		}
		else
		{
			$query->where($db->quoteName('ip') . ' = :ip')
				->bind(':ip', $ip);
		}

		try
		{
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
		}

		return false;
	}

	/**
	 * Check if userid or IP are not listed in blacklist.
	 *
	 * @param   string  $ip    IP address
	 * @param   mixed   $user  User object for checking current loggen in user or ID of any user.
	 *
	 * @return  boolean  True if blacklisted, false otherwise
	 *
	 * @since   4.0
	 * @see     JcommentsAcl::isUserBlocked()
	 */
	public function isBlacklisted(string $ip, $user = null): bool
	{
		$db     = $this->getDatabase();
		$result = false;
		$userId = $user;

		// Check only IP
		if (!is_null($user))
		{
			$userId = ($user instanceof User) ? $user->get('id') : (int) $user;
		}

		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments_blacklist'));

		if ($userId > 0)
		{
			$query->where($db->quoteName('userid') . ' = :uid')
				->bind(':uid', $userId, ParameterType::INTEGER);
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
					$query->where($db->quoteName('ip') . ' = :ip')
						->bind(':ip', $ip);
				}
			}
		}

		try
		{
			$db->setQuery($query);
			$result = $db->loadResult() > 0;
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
		}

		return $result;
	}
}

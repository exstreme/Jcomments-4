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
	 * Check if userid or IP are not listed in blacklist.
	 *
	 * @param   string  $ip    IP address
	 * @param   mixed   $user  User object for checking current loggen in user or ID of any user.
	 *
	 * @return  array
	 *
	 * @since   4.1
	 * @see     JcommentsAcl::isUserBlocked()
	 */
	public function isBlacklisted(string $ip, $user = null): array
	{
		$db     = $this->getDatabase();
		$result = array('block' => false, 'reason' => '');
		$userId = $user;

		if (!is_null($user))
		{
			$userId = ($user instanceof User) ? $user->get('id') : (int) $user;
		}

		if ($userId > 0)
		{
			$query = $db->getQuery(true)
				->select($db->quoteName('reason'))
				->from($db->quoteName('#__jcomments_blacklist'))
				->where($db->quoteName('userid') . ' = :uid')
				->bind(':uid', $userId, ParameterType::INTEGER);

			try
			{
				$db->setQuery($query);
				$reason = $db->loadResult();

				if (!empty($reason))
				{
					$result['block'] = true;
					$result['reason'] = $reason;
				}
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
			}
		}
		else
		{
			$query = $db->getQuery(true)
				->select($db->quoteName(array('ip', 'reason')))
				->from($db->quoteName('#__jcomments_blacklist'));

			try
			{
				$db->setQuery($query);
				$rows = $db->loadObjectList();

				foreach ($rows as $row)
				{
					// IPv4
					if (strpos($row->ip, '.') !== false)
					{
						$row->ip = $this->toCIDR($row->ip);
					}

					if (IpHelper::IPinList($ip, $row->ip))
					{
						$result['block'] = true;
						$result['reason'] = $row->reason;
						break;
					}
				}
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
			}
		}

		return $result;
	}

	/**
	 * Convert old IPv4 range to CIDR. For B/C only
	 *
	 * @param   string  $ip  User IP
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	private function toCIDR(string $ip): string
	{
		// Test if IP is a range, e.g. 127.0.0.*
		if (strpos($ip, '*') !== false)
		{
			$parts = explode('.', $ip);

			if (count($parts) == 4)
			{
				// Convert range to CIDR
				if (preg_match('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\*#', $ip))
				{
					$ip = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
				}
				elseif (preg_match('#\d{1,3}\.\d{1,3}\.\*\.\*#', $ip))
				{
					$ip = $parts[0] . '.' . $parts[1] . '.0.0/16';
				}
				elseif (preg_match('#\d{1,3}\.\*\.\*\.\*#', $ip))
				{
					$ip = $parts[0] . '.0.0.0/8';
				}
			}
		}

		return $ip;
	}
}

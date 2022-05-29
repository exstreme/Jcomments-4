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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Joomla\Component\Jcomments\Site\Helper\NotificationHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Comment item class
 *
 * @since  4.0
 */
class CommentModel extends BaseDatabaseModel
{
	/**
	 * Cached item object
	 *
	 * @var    object
	 * @since  1.6
	 */
	protected $_item;

	/**
	 * Cache group name. Same as in CommentsModel
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $cacheGroup = 'com_jcomments';

	/**
	 * Gets a comment object
	 *
	 * @param   integer  $id  ID for the comment
	 *
	 * @return  mixed    Object or null
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function &getItem(int $id)
	{
		if (!isset($this->_item))
		{
			$cacheGroup = strtolower($this->cacheGroup);

			/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', array('defaultgroup' => $cacheGroup));

			$table = $this->getTable();

			$loader = function ($id) use ($table)
			{
				$return = $table->load($id);

				if ($return === false && $table->getError())
				{
					$this->setError($table->getError());

					return false;
				}

				$properties = $table->getProperties(1);

				return ArrayHelper::toObject($properties, new \stdClass);
			};

			try
			{
				$this->_item = $cache->get($loader, array($id), md5(__METHOD__ . $id));
			}
			catch (CacheExceptionInterface $e)
			{
				$this->_item = $loader($id);
			}
		}

		return $this->_item;
	}

	/**
	 * Checking if user can leave the comment after certain amount of time.
	 *
	 * @param   string  $ip  User IP
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function checkFlood(string $ip): bool
	{
		$app      = Factory::getApplication();
		$db       = $this->getDbo();
		$interval = (int) ComponentHelper::getParams('com_jcomments')->get('flood_time');
		$now      = Factory::getDate()->toSql();

		if ($interval > 0)
		{
			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__jcomments'))
				->where($db->quoteName('ip') . ' = ' . $db->quote($ip))
				->where($db->quote($now) . ' < DATE_ADD(date, INTERVAL ' . $interval . ' SECOND)');

			if (Multilanguage::isEnabled())
			{
				$query->where($db->quoteName('lang') . ' = ' . $db->quote($app->getLanguage()->getTag()));
			}

			try
			{
				$db->setQuery($query);
				$result = $db->loadResult();

				return !($result === 0);
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
		}

		return false;
	}

	/**
	 * Checking if username is not in forbidden list.
	 *
	 * @param   string  $username  Username
	 *
	 * @return  boolean  True if in list.
	 *
	 * @since   3.0
	 */
	public function checkIsForbiddenUsername(string $username): bool
	{
		$names = ComponentHelper::getParams('com_jcomments')->get('forbidden_names');

		if (!empty($names) && !empty($username))
		{
			$username = trim(StringHelper::strtolower($username));
			$names    = StringHelper::strtolower(preg_replace("#,+#u", ',', preg_replace("#[\n|\r]+#u", ',', $names)));
			$names    = explode(',', $names);

			foreach ($names as $name)
			{
				if (trim((string) $name) == $username)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checking if username is not in registered list.
	 *
	 * @param   string  $username  Username
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function checkIsRegisteredUsername(string $username): bool
	{
		$db       = $this->getDbo();
		$config   = ComponentHelper::getParams('com_jcomments');
		$username = StringHelper::strtolower($username);

		if ((int) $config->get('enable_username_check') == 1)
		{
			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__users'))
				->where('LOWER(name) = ' . $db->quote($db->escape($username, true)), 'OR')
				->where('LOWER(username) = ' . $db->quote($db->escape($username, true)), 'OR');

			try
			{
				$db->setQuery($query);
				$result = $db->loadResult();

				return !($result === 0);
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
		}

		return false;
	}

	/**
	 * Checking if user email is not in registered list.
	 *
	 * @param   string  $email  User email
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function checkIsRegisteredEmail(string $email): bool
	{
		$db     = $this->getDbo();
		$config = ComponentHelper::getParams('com_jcomments');
		$email  = StringHelper::strtolower($email);

		if ((int) $config->get('enable_username_check') == 1)
		{
			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__users'))
				->where('LOWER(email) = ' . $db->quote($db->escape($email, true)));

			try
			{
				$db->setQuery($query);
				$result = $db->loadResult();

				return !($result === 0);
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
			}
		}

		return false;
	}

	/**
	 * Check if userid or IP are not listed in blacklist.
	 *
	 * @param   string  $ip    IP address
	 * @param   User    $user  User object
	 *
	 * @return  boolean  True if blacklisted, false otherwise
	 *
	 * @since   3.0
	 * @see     JcommentsAcl::isUserBlocked()
	 */
	public function isBlacklisted(string $ip, User $user): bool
	{
		$db     = $this->getDbo();
		$result = false;

		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments_blacklist'));

		// Check by IP only if user is guest.
		if ($user->get('guest'))
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
		else
		{
			$query->where($db->quoteName('userid') . ' = ' . $user->get('id'));
		}

		try
		{
			$db->setQuery($query);
			$result = $db->loadResult() > 0;
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
		}

		return $result;
	}

	/**
	 * Method to change the published state of one record.
	 *
	 * @param   integer  $pk     Primary key to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function publish(int $pk, int $value = 1): bool
	{
		$user = Factory::getApplication()->getIdentity();
		$table = $this->getTable();

		$table->reset();

		if ($table->load($pk))
		{
			if (!$user->authorise('core.edit.state', 'com_jcomments'))
			{
				Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'com_jcomments');

				return false;
			}

			// If the table is checked out by another user, drop it and report to the user trying to change its state.
			if ($table->hasField('checked_out') && $table->checked_out && ($table->checked_out != $user->id))
			{
				Log::add(Text::_('JLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'), Log::WARNING, 'com_jcomments');

				return false;
			}
		}

		// Attempt to change the state of the records.
		if (!$table->publish($pk, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		if ($value === 1)
		{
			NotificationHelper::push($table, 'comment-update');
		}

		$cacheGroup = strtolower($this->cacheGroup);
		JcommentsFactory::removeCache(md5($cacheGroup . $table->object_id), $cacheGroup);

		return true;
	}

	/**
	 * Delete comment from database.
	 *
	 * @param   object  $data  Comment object.
	 *
	 * @return  boolean  True on success, false otherwise
	 *
	 * @since   4.0
	 */
	public function delete($data): bool
	{
		if (isset($data->id))
		{
			$config = ComponentHelper::getParams('com_jcomments');

			/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
			$table = $this->getTable();
			$return = $table->load($data->id);

			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());

				return false;
			}

			$objectID = $table->object_id;

			if ($config->get('delete_mode') == 0)
			{
				$return = $table->delete();
			}
			else
			{
				$table->published = 0;
				$return = $table->markAsDeleted();
			}

			if ($return)
			{
				JcommentsFactory::removeCache(md5($this->cacheGroup . $objectID), $this->cacheGroup);
			}
			else
			{
				$this->setError($table->getError());

				return false;
			}

			NotificationHelper::push($table, 'comment-delete');
		}

		return true;
	}
}

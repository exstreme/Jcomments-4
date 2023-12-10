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
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Jcomments\Site\Helper\NotificationHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;
use Joomla\Utilities\IpHelper;

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
	 * Gets a comment object
	 *
	 * @param   integer|null  $pk        ID for the comment
	 * @param   boolean       $useCache  Store item in cache or not. Cache used only for guests.
	 *
	 * @return  mixed    Object or null
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function getItem(?int $pk = null, bool $useCache = false)
	{
		$pk = (int) ($pk ?: $this->getState('comment.id'));

		if (!isset($this->_item))
		{
			/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', array('defaultgroup' => 'com_jcomments_comments'));

			$app    = Factory::getApplication();
			$db     = $this->getDatabase();
			$params = ComponentHelper::getParams('com_jcomments');
			$user   = $app->getIdentity();
			$acl    = JcommentsFactory::getAcl();

			$loader = function ($pk) use ($acl, $db, $params, $user)
			{
				$query = $db->getQuery(true)
					->select('c.*')
					->select('CASE WHEN ' . $db->quoteName('c.parent') . ' = 0'
						. ' THEN UNIX_TIMESTAMP(' . $db->quoteName('c.date') . ') ELSE 0 END'
						. ' AS ' . $db->quoteName('threaddate')
					)
					->select(
						array(
							$db->quoteName('o.title', 'object_title'),
							$db->quoteName('o.link', 'object_link'),
							$db->quoteName('o.access', 'object_access'),
							$db->quoteName('o.userid', 'object_owner'),
							$db->quoteName('usr.block', 'user_blocked')
						)
					)
					->select('(CASE WHEN ' . $db->quoteName('b.id') . ' > 0 THEN 1 ELSE 0 END) AS ' . $db->quoteName('banned'))
					->select(
						array(
							$db->quoteName('l.lang_code', 'language'),
							$db->quoteName('l.title', 'language_title'),
							$db->quoteName('l.image', 'language_image')
						)
					)
					->from($db->quoteName('#__jcomments', 'c'))
					->leftJoin(
						$db->quoteName('#__jcomments_objects', 'o'),
						$db->quoteName('c.object_id') . ' = ' . $db->quoteName('o.object_id')
					)
					->leftJoin(
						$db->quoteName('#__jcomments_blacklist', 'b'),
						$db->quoteName('b.userid') . ' = ' . $db->quoteName('c.userid')
						. ' AND ('
						. 'ISNULL(' . $db->quoteName('b.expire') . ')'
						. ' OR ' . $db->quoteName('b.expire') . ' >= NOW()'
						. ')'
					)
					->leftJoin(
						$db->quoteName('#__users', 'usr'),
						$db->quoteName('usr.id') . ' = ' . $db->quoteName('c.userid')
					)
					->leftJoin(
						$db->quoteName('#__languages', 'l'),
						$db->quoteName('l.lang_code') . ' = ' . $db->quoteName('c.lang')
					);

				// Join over labels
				$query->select(array($db->quoteName('u.labels'), $db->quoteName('u.terms_of_use')))
					->leftJoin(
						$db->quoteName('#__jcomments_users', 'u'),
						$db->quoteName('u.id') . ' = ' . $db->quoteName('c.userid')
					);

				if ($params->get('enable_voting') == 1)
				{
					$query->select($db->quoteName('v.value', 'voted'));
					$query->leftJoin(
						$db->quoteName('#__jcomments_votes', 'v'),
						$db->quoteName('c.id') . ' = ' . $db->quoteName('v.commentid')
						. ($user->get('id')
							? ' AND ' . $db->quoteName('v.userid') . ' = ' . (int) $user->get('id')
							: ' AND ' . $db->quoteName('v.userid') . ' = 0 AND ' . $db->quoteName('v.ip') . ' = ' . $db->quote(IpHelper::getIp()))
					);
				}
				else
				{
					$query->select('1 AS voted');
				}

				// Check for user state and object access
				// Guest cannot access unpublished item
				$state = array(1);
				$pubState = $this->getState('published');

				if (!is_null($pubState))
				{
					$state[] = $pubState;
				}

				if (!$user->get('isRoot'))
				{
					if (!$user->get('guest'))
					{
						$state[] = $acl->canPublish() || $acl->canPublishForObject($this->getState('object_id'), $this->getState('object_group'))
							? 0 : 1;
					}

					$groups = $user->getAuthorisedViewLevels();

					$query->whereIn($db->quoteName('c.published'), array_unique($state))
						->whereIn($db->quoteName('o.access'), $groups);
				}

				if ($this->getState('last_comment') == 1)
				{
					$objectId    = $this->getState('object_id');
					$objectGroup = $this->getState('object_group');
					$parent      = $this->getState('parent');

					$query->where($db->quoteName('c.object_id') . ' = :oid')
						->where($db->quoteName('c.object_group') . ' = :ogroup')
						->where($db->quoteName('c.parent') . ' = :parent')
						->bind(':oid', $objectId, ParameterType::INTEGER)
						->bind(':ogroup', $objectGroup)
						->bind(':parent', $parent, ParameterType::INTEGER)
						->order($db->quoteName('c.date') . ' DESC')
						->setLimit(1, 0);
				}
				else
				{
					$query->where($db->quoteName('c.id') . ' = :id')
						->bind(':id', $pk, ParameterType::INTEGER);
				}

				$db->setQuery($query);

				return $db->loadObject();
			};

			try
			{
				if ($useCache)
				{
					$this->_item = $cache->get($loader, array($pk), md5(__METHOD__ . $pk));
				}
				else
				{
					$this->_item = $loader($pk);
				}
			}
			catch (CacheExceptionInterface $e)
			{
				$this->_item = $loader($pk);
			}
		}

		return $this->_item;
	}

	public function getLastComment(int $objectID, string $objectGroup = 'com_content', int $parent = 0)
	{
		$this->setState('object_id', $objectID);
		$this->setState('object_group', trim($objectGroup));
		$this->setState('parent', $parent);
		$this->setState('published', 1);

		return $this->getItem();
	}

	/**
	 * Checking if user can leave the comment/vote after certain amount of time.
	 *
	 * @param   string  $ip    User IP
	 * @param   string  $type  Content type for checking
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function checkFlood(string $ip, string $type = 'comment'): bool
	{
		$app    = Factory::getApplication();
		$db     = $this->getDatabase();
		$params = ComponentHelper::getParams('com_jcomments');
		$now    = Factory::getDate()->toSql();
		$ip     = $db->escape($ip);

		if ($type == 'comment')
		{
			$interval = (int) $params->get('flood_time');

			if ($interval > 0)
			{
				$query = $db->getQuery(true)
					->select('COUNT(id)')
					->from($db->quoteName('#__jcomments'))
					->where($db->quoteName('ip') . ' = :ip')
					->where($db->quote($now) . ' < DATE_ADD(' . $db->quoteName('date') . ', INTERVAL :interval SECOND)')
					->bind(':ip', $ip)
					->bind(':interval', $interval, ParameterType::INTEGER);

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
					Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
				}
			}
		}
		elseif ($type == 'vote')
		{
			$interval = (int) $params->get('vote_flood_time');

			if ($interval > 0)
			{
				$query = $db->getQuery(true)
					->select('COUNT(id)')
					->from($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('ip') . ' = :ip')
					->where($db->quote($now) . ' < DATE_ADD(' . $db->quoteName('date') . ', INTERVAL :interval SECOND)')
					->bind(':ip', $ip)
					->bind(':interval', $interval, ParameterType::INTEGER);

				try
				{
					$db->setQuery($query);
					$result = $db->loadResult();

					return !($result === 0);
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
				}
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
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
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
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
			}
		}

		return false;
	}

	/**
	 * Method to change the published state of one record.
	 *
	 * @param   integer  $pk     Primary key to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function publish(int $pk, int $value = 1): bool
	{
		$params = ComponentHelper::getParams('com_jcomments');
		$user   = Factory::getApplication()->getIdentity();
		$table  = $this->getTable();

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

		// Send notifications on publish state
		if ($params->get('enable_notification') && in_array(3, $params->get('notification_type')) && $value == 1)
		{
			// Send notification to subscribed users.
			NotificationHelper::push($table, 'comment-published');

			// Send notification to administrator(moderator). List of emails from 'notification_email' option.
			NotificationHelper::push($table, 'moderate-published');
		}
		// Send notifications on unpublished state only to administrator(moderator)
		elseif ($params->get('enable_notification') && in_array(3, $params->get('notification_type')) && $value == 0)
		{
			NotificationHelper::push($table, 'moderate-unpublished');
		}

		$this->cleanCache('com_jcomments_comments');

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
			$return = $table->load((int) $data->id);

			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());

				return false;
			}

			if ($config->get('delete_mode') == 0)
			{
				$return = $table->delete();
			}
			else
			{
				$table->published = 0;
				$return = $table->markAsDeleted();
			}

			if (!$return)
			{
				$this->setError($table->getError());

				return false;
			}

			if ($config->get('enable_notification') && in_array(4, $config->get('enable_notification')))
			{
				NotificationHelper::push($table, 'comment-delete');
				NotificationHelper::push($table, 'moderate-delete');
			}
		}

		return true;
	}

	/**
	 * Vote for comment.
	 *
	 * @param   integer  $id     Comment ID.
	 * @param   integer  $value  Value. Can be 1 or -1.
	 *
	 * @return  boolean  True on success, false otherwise
	 *
	 * @since   4.0
	 */
	public function storeVote(int $id, int $value): bool
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();
		$db   = $this->getDatabase();
		$acl  = JcommentsFactory::getAcl();
		$ip   = IpHelper::getIp();

		if ($this->isVoted($id) === false)
		{
			/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
			$table = $this->getTable();
			$result = $table->load($id);

			if ($result === false && $table->getError())
			{
				$this->setError($table->getError());

				return false;
			}

			if ($acl->canVote($table))
			{
				if ($this->checkFlood($ip, 'vote'))
				{
					$this->setError(Text::_('ERROR_FLOOD_CANT_VOTE'));

					return false;
				}

				$dispatcher = $this->getDispatcher();
				$eventResult = $dispatcher->dispatch(
					'onJCommentsCommentBeforeVote',
					AbstractEvent::create(
						'onJCommentsCommentBeforeVote',
						array('subject' => new \stdClass, 'table' => $table, 'value' => $value)
					)
				);

				if (!$eventResult->getArgument('abort', false))
				{
					if ($value > 0)
					{
						$table->isgood++;
					}
					else
					{
						$table->ispoor++;
					}

					if (!$table->store())
					{
						Log::add($table->getError() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
						$this->setError(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));

						return false;
					}

					$now    = Factory::getDate()->toSql();
					$values = array($table->id, $user->get('id'), $ip, $now, $value);
					$query  = $db->getQuery(true)
						->insert($db->quoteName('#__jcomments_votes'))
						->columns(
							array(
								$db->quoteName('commentid'),
								$db->quoteName('userid'),
								$db->quoteName('ip'),
								$db->quoteName('date'),
								$db->quoteName('value')
							)
						)
						->values(':id, :uid, :ip, :datetime, :value')
						->bind(
							array(':id', ':uid', ':ip', ':datetime', ':value'),
							$values,
							array(
								ParameterType::INTEGER,
								ParameterType::INTEGER,
								ParameterType::STRING,
								ParameterType::STRING,
								ParameterType::INTEGER
							)
						);

					try
					{
						$db->setQuery($query);
						$db->execute();
					}
					catch (\RuntimeException $e)
					{
						Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
						$this->setError(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));

						return false;
					}

					$dispatcher->dispatch(
						'onJCommentsCommentAfterVote',
						AbstractEvent::create(
							'onJCommentsCommentAfterVote',
							array('subject' => new \stdClass, 'table' => $table, 'value' => $value)
						)
					);
				}
			}
			else
			{
				$this->setError(Text::_('ERROR_PERMISSIONS_CANT_VOTE'));

				return false;
			}
		}
		else
		{
			$this->setError(Text::_('ERROR_ALREADY_VOTED'));

			return false;
		}

		return true;
	}

	/**
	 * Check if user already voted for comment.
	 *
	 * @param   integer  $id  Comment ID.
	 *
	 * @return  boolean  True on success, false otherwise
	 *
	 * @since   4.0
	 */
	public function isVoted(int $id): bool
	{
		$db     = $this->getDatabase();
		$user   = Factory::getApplication()->getIdentity();
		$ip     = IpHelper::getIp();
		$userId = $user->get('id');

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments_votes'))
			->where($db->quoteName('commentid') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		if ($user->get('id'))
		{
			$query->where($db->quoteName('userid') . ' = :uid')
				->bind(':uid', $userId, ParameterType::INTEGER);
		}
		else
		{
			$query->where($db->quoteName('userid') . ' = 0')
				->where($db->quoteName('ip') . ' = :ip')
				->bind(':ip', $ip);
		}

		try
		{
			$db->setQuery($query);

			return !($db->loadResult() == 0);
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
		}

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since   1.6
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->getInput()->getInt('id');
		$this->setState('comment.id', $pk);
	}
}

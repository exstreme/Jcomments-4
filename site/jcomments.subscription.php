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

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Class to manage subscriptions.
 *
 * @since    3.0
 */
class JCommentsSubscriptionManager
{
	/**
	 * An array of errors
	 *
	 * @var    array of error messages
	 *
	 * @since  4.0
	 */
	protected $_errors = array();

	/**
	 * Returns a reference to a subscription manager object,
	 * only creating it if it doesn't already exist.
	 *
	 * @return  JCommentsSubscriptionManager    A JCommentsSubscriptionManager object
	 *
	 * @since   3.0
	 */
	public static function getInstance()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JCommentsSubscriptionManager;
		}

		return $instance;
	}

	/**
	 * Subscribes user for new comments notifications for an object
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userid       The registered user identifier
	 * @param   string   $email        The user email (for guests only)
	 * @param   string   $name         The user name (for guests only)
	 * @param   string   $lang         The user language
	 *
	 * @return  boolean True on success, false otherwise.
	 *
	 * @throws  Exception
	 * @since   3.0
	 */
	public function subscribe($objectID, $objectGroup, $userid, $email = '', $name = '', $lang = '')
	{
		$objectID    = (int) $objectID;
		$objectGroup = trim($objectGroup);
		$userid      = (int) $userid;
		$result      = false;

		if ($lang == '')
		{
			$lang = Factory::getApplication()->getLanguage()->getTag();
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		if ($userid != 0)
		{
			$user  = Factory::getUser($userid);
			$name  = $user->name;
			$email = $user->email;
			unset($user);
		}

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('object_id') . ' = ' . (int) $objectID)
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup))
			->where($db->quoteName('email') . ' = ' . $db->quote($email));

		if (JCommentsFactory::getLanguageFilter())
		{
			$query->where($db->quoteName('lang') . ' = ' . $db->quote(Factory::getApplication()->getLanguage()->getTag()));
		}

		$db->setQuery($query);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jcomments/tables');

		/** @var JCommentsTableSubscription $subscription */
		$subscription = Table::getInstance('Subscription', 'JCommentsTable');

		if (count($rows) == 0)
		{
			$subscription->object_id    = $objectID;
			$subscription->object_group = $objectGroup;
			$subscription->name         = $name;
			$subscription->email        = $email;
			$subscription->userid       = $userid;
			$subscription->lang         = $lang;
			$subscription->published    = 1;

			if ($subscription->store())
			{
				$result = true;
			}
			else
			{
				$this->_errors[] = $subscription->getError();
				$result = false;
			}
		}
		else
		{
			// If current user is registered, but already exists subscription on same email by guest - update
			// subscription data
			if ($userid > 0 && $rows[0]->userid == 0)
			{
				$subscription->id        = $rows[0]->id;
				$subscription->name      = $name;
				$subscription->email     = $email;
				$subscription->userid    = $userid;
				$subscription->lang      = $lang;
				$subscription->published = 1;

				if ($subscription->store())
				{
					$result = true;
				}
				else
				{
					$this->_errors[] = $subscription->getError();
					$result = false;
				}
			}
			else
			{
				$this->_errors[] = Text::_('ERROR_ALREADY_SUBSCRIBED');
			}
		}

		if ($result)
		{
			/** @var CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', ['defaultgroup' => 'com_jcomments_subscriptions_' . strtolower($objectGroup)]);

			/** @var Cache $cache */
			$cache->clean();
		}

		return $result;
	}

	/**
	 * Unsubscribe guest from new comments notifications by subscription hash
	 *
	 * @param   string  $hash  The secret hash value of subscription
	 *
	 * @return  boolean True on success, false otherwise.
	 *
	 * @since   3.0
	 */
	public function unsubscribeByHash($hash)
	{
		if (!empty($hash))
		{
			$subscription = $this->getSubscriptionByHash($hash);

			if ($subscription !== null)
			{
				/** @var DatabaseDriver $db */
				$db = Factory::getContainer()->get('DatabaseDriver');

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_subscriptions'))
					->where($db->quoteName('hash') . ' = ' . $db->quote($hash));

				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

					return false;
				}

				/** @var CallbackController $cache */
				$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
					->createCacheController('callback', ['defaultgroup' => 'com_jcomments_subscriptions_' . strtolower($subscription->object_group)]);

				/** @var Cache $cache */
				$cache->clean();

				return true;
			}
		}

		return false;
	}

	/**
	 * Unsubscribe registered user from new comments notifications for an object
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userid       The registered user identifier
	 *
	 * @return  boolean True on success, false otherwise.
	 *
	 * @throws  Exception
	 * @deprecated  4.0.5  Use JcommentsModelSubscriptions instead
	 * @since   3.0
	 */
	public function unsubscribe($objectID, $objectGroup, $userid)
	{
		if ($userid != 0)
		{
			require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

			$model   = new JcommentsModelSubscriptions;
			$langTag = null;

			if (JCommentsFactory::getLanguageFilter())
			{
				$langTag = Factory::getApplication()->getLanguage()->getTag();
			}

			return $model->deleteSubscriptions($objectID, $objectGroup, $userid, $langTag);
		}

		return false;
	}

	public function getSubscriptionByHash($hash)
	{
		$subscription = null;

		if (!empty($hash))
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__jcomments_subscriptions'))
				->where($db->quoteName('hash') . ' = ' . $db->quote($hash));

			$db->setQuery($query);

			try
			{
				$subscription = $db->loadObject();
			}
			catch (RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

				return false;
			}
		}

		return $subscription;
	}

	/**
	 * Checks if given user is subscribed to new comments notifications for an object
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userid       The registered user identifier
	 * @param   string   $email        The user email (for guests only)
	 * @param   string   $language     The object language
	 *
	 * @return  integer
	 *
	 * @throws  Exception
	 * @since   3.0
	 */
	public function isSubscribed($objectID, $objectGroup, $userid, $email = '', $language = '')
	{
		static $data = null;

		$key = $objectID . $objectGroup . $userid . $email . $language;

		if (!isset($data[$key]))
		{
			/** @var CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', ['defaultgroup' => 'com_jcomments_subscriptions_' . strtolower($objectGroup)]);

			$data[$key] = $cache->get(
				array($this, 'subscribed'),
				array($objectID, $objectGroup, $userid, $email,	$language)
			);
		}

		return $data[$key];
	}

	/**
	 * Return an array of errors messages
	 *
	 * @return  array  The array of error messages
	 *
	 * @since   3.0
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * Checks if given user is subscribed to new comments notifications for an object
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userid       The registered user identifier
	 * @param   string   $email        The user email (for guests only)
	 * @param   string   $language     The object language
	 *
	 * @return  integer
	 *
	 * @throws  Exception
	 * @since   3.0
	 */
	public function subscribed($objectID, $objectGroup, $userid, $email = '', $language = '')
	{
		if (empty($language))
		{
			$language = Factory::getApplication()->getLanguage()->getTag();
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('object_id') . ' = ' . (int) $objectID)
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup))
			->where($db->quoteName('userid') . ' = ' . (int) $userid);

		if ($userid == 0)
		{
			$query->where($db->quoteName('email') . ' = ' . $db->quote($email));
		}

		if (JCommentsFactory::getLanguageFilter())
		{
			$query->where($db->quoteName('lang') . ' = ' . $db->quote($language));
		}

		$db->setQuery($query);

		try
		{
			$count = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return 0;
		}

		return ($count > 0 ? 1 : 0);
	}
}

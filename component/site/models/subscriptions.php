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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\DatabaseModelInterface;
use Joomla\CMS\Table\Table;

/**
 * Subscriptions class
 *
 * @since  4.0
 */
class JcommentsModelSubscriptions extends BaseDatabaseModel implements DatabaseModelInterface
{
	/**
	 * Add a new subscription.
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userID       The registered user identifier
	 * @param   string   $name         Username which is used only for guests
	 * @param   string   $email        User email
	 * @param   string   $lang         Content language
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   4.0
	 */
	public function subscribe($objectID, $objectGroup, $userID, $name = '', $email = '', $lang = '')
	{
		$db = $this->getDbo();

		if (!empty($userID))
		{
			/** @var \Joomla\CMS\User\UserFactory $userFactory */
			$userFactory = Factory::getContainer()->get('user.factory');
			$user = $userFactory->loadUserById($userID);

			$name  = $user->name;
			$email = $user->email;
			unset($user);
		}

		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'userid')))
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('object_id') . ' = ' . (int) $objectID)
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup))
			->where($db->quoteName('email') . ' = ' . $db->quote($email));

		if (JCommentsFactory::getLanguageFilter())
		{
			$query->where($db->quoteName('lang') . ' = ' . $db->quote($lang));
		}

		try
		{
			$db->setQuery($query);
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
			$subscription->userid       = $userID;
			$subscription->lang         = $lang;
			$subscription->published    = 1;
			$subscription->source       = '';

			if ($subscription->store())
			{
				$result = true;
			}
			else
			{
				$result = false;

				Log::add($subscription->getError(), Log::ERROR, 'com_jcomments');
			}
		}
		else
		{
			// If current user is registered, but already exists subscription on same email by guest - update
			// subscription data
			if ($userID > 0 && $rows[0]->userid == 0)
			{
				$subscription->id        = $rows[0]->id;
				$subscription->name      = $name;
				$subscription->email     = $email;
				$subscription->userid    = $userID;
				$subscription->lang      = $lang;
				$subscription->published = 1;
				$subscription->source    = '';

				if ($subscription->store())
				{
					$result = true;
				}
				else
				{
					$result = false;

					Log::add($subscription->getError(), Log::ERROR, 'com_jcomments');
				}
			}
			else
			{
				$result = false;

				$this->setError(Text::_('ERROR_ALREADY_SUBSCRIBED'));
			}
		}

		if ($result)
		{
			$this->cleanCache('com_jcomments_subscriptions_' . strtolower($objectGroup));
		}

		return $result;
	}

	/**
	 * Delete all subscriptions or only filtered by user ID.
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userID       The registered user identifier
	 * @param   string   $lang         Content language
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   4.0
	 */
	public function unsubscribe($objectID, $objectGroup, $userID = null, $lang = null)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('object_id') . ' = ' . (int) $objectID)
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup));

		if (!empty($userID))
		{
			$query->where($db->quoteName('userid') . ' = ' . (int) $userID);
		}

		if (!empty($lang))
		{
			$query->where($db->quoteName('lang') . ' = ' . $db->quote($lang));
		}

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		$this->cleanCache('com_jcomments_subscriptions_' . strtolower($objectGroup));

		return true;
	}

	/**
	 * Delete subscription by hash.
	 *
	 * @param   string   $hash  Hash
	 *
	 * @return  boolean|array  Array on success, false otherwise.
	 *
	 * @since   4.0
	 */
	public function unsubscribeByHash($hash)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName(array('object_id', 'object_group', 'lang')))
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('hash') . ' = ' . $db->quote($hash));

		try
		{
			$db->setQuery($query);
			$result = $db->loadAssoc();

			if (!empty($result))
			{
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_subscriptions'))
					->where($db->quoteName('hash') . ' = ' . $db->quote($hash));

				$db->setQuery($query);
				$db->execute();

				$this->cleanCache('com_jcomments_subscriptions_' . strtolower($result['object_group']));

				return $result;
			}
			else
			{
				$this->setError(Text::_('ERROR_ALREADY_UNSUBSCRIBED'));

				return false;
			}
		}
		catch (RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}
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
	 * @since   4.0
	 */
	public function isSubscribed($objectID, $objectGroup, $userid, $email = '', $language = '')
	{
		static $data = null;

		$key = $objectID . $objectGroup . $userid . $email . $language;

		if (!isset($data[$key]))
		{
			/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
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
	 * Checks if given user is subscribed to new comments notifications for an object
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userid       The registered user identifier
	 * @param   string   $email        The user email (for guests only)
	 * @param   string   $language     The object language
	 *
	 * @return  boolean
	 *
	 * @throws  Exception
	 * @since   4.0
	 */
	public function subscribed($objectID, $objectGroup, $userid, $email = '', $language = '')
	{
		if (empty($language))
		{
			$language = Factory::getApplication()->getLanguage()->getTag();
		}

		$db = $this->getDbo();

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

			return false;
		}

		return $count > 0;
	}
}

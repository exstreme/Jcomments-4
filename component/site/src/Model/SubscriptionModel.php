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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Subscriptions class
 *
 * @since  4.0
 */
class SubscriptionModel extends BaseDatabaseModel
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
	public function subscribe(int $objectID, string $objectGroup, int $userID, string $name, string $email, string $lang): bool
	{
		$db = $this->getDatabase();

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
			->where($db->quoteName('object_id') . ' = :oid')
			->where($db->quoteName('object_group') . ' = :ogroup')
			->where($db->quoteName('email') . ' = :email')
			->bind(':oid', $objectID, ParameterType::INTEGER)
			->bind(':ogroup', $objectGroup)
			->bind(':email', $email);

		if (Multilanguage::isEnabled())
		{
			$query->where($db->quoteName('lang') . ' = :lang')
				->bind(':lang', $lang);
		}

		try
		{
			$db->setQuery($query);
			$rows = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		/** @var \Joomla\Component\Jcomments\Administrator\Table\SubscriptionTable $subscription */
		$subscription = Factory::getApplication()->bootComponent('com_jcomments')->getMVCFactory()
			->createTable('Subscription', 'Administrator');

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

				Log::add($subscription->getError() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
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

					Log::add($subscription->getError() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
				}
			}
			else
			{
				$result = false;

				$this->setError(Text::_('ERROR_ALREADY_SUBSCRIBED'));
			}
		}

		return $result;
	}

	/**
	 * Delete all subscriptions or only filtered by user ID.
	 *
	 * @param   integer       $objectID     The object identifier
	 * @param   string        $objectGroup  The object group (component name)
	 * @param   integer|null  $userID       The registered user identifier
	 * @param   string|null   $lang         Content language
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   4.0
	 */
	public function unsubscribe(int $objectID, string $objectGroup, int $userID = null, string $lang = null): bool
	{
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('object_id') . ' = :oid')
			->where($db->quoteName('object_group') . ' = :ogroup')
			->bind(':oid', $objectID, ParameterType::INTEGER)
			->bind(':ogroup', $objectGroup);

		if (!empty($userID))
		{
			$query->where($db->quoteName('userid') . ' = :uid')
				->bind(':uid', $userID, ParameterType::INTEGER);
		}

		if (!empty($lang))
		{
			$query->where($db->quoteName('lang') . ' = :lang')
				->bind(':lang', $lang);
		}

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Delete subscription by hash.
	 *
	 * @param   string   $hash    Hash
	 * @param   integer  $userid  User identifier
	 *
	 * @return  boolean|array  Array on success, false otherwise.
	 *
	 * @since   4.0
	 */
	public function unsubscribeByHash(string $hash, int $userid)
	{
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->select($db->quoteName(array('object_id', 'object_group', 'lang', 'userid')))
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('hash') . ' = :hash')
			->bind(':hash', $hash);

		try
		{
			$db->setQuery($query);
			$result = $db->loadAssoc();

			if (!empty($result))
			{
				// Checking if the user trying to delete own subscription
				if ($result['userid'] == $userid)
				{
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__jcomments_subscriptions'))
						->where($db->quoteName('hash') . ' = :hash')
						->bind(':hash', $hash);

					$db->setQuery($query);
					$db->execute();

					return $result;
				}
				else
				{
					// Error message will set in controller
					return false;
				}
			}
			else
			{
				$this->setError(Text::_('ERROR_ALREADY_UNSUBSCRIBED'));

				return false;
			}
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}
	}

	/**
	 * Returns list of subscribers for given object and subscription type
	 *
	 * @param   int     $objectID     Object ID
	 * @param   string  $objectGroup  Object group, e.g. com_content
	 * @param   string  $lang         The language tag, e.g. en-GB
	 * @param   string  $type         The subscription type
	 *
	 * @return  object
	 *
	 * @since   4.0
	 */
	public function getSubscribers(int $objectID, string $objectGroup, string $lang, string $type): object
	{
		$db = $this->getDatabase();
		$subscribers = array();

		switch ($type)
		{
			case 'moderate-new':
			case 'moderate-update':
			case 'moderate-published':
			case 'moderate-unpublished':
			case 'moderate-delete':
			case 'report':
				$config = ComponentHelper::getParams('com_jcomments');

				if ($config->get('notification_email') != '')
				{
					$filter = InputFilter::getInstance();
					$emails = explode(',', $config->get('notification_email'));
					$emails = array_map(
						function (string $value) use ($filter): string
						{
							return $filter->clean($value);
						},
						$emails
					);

					$query = $db->getQuery(true)
						->select('*')
						->from($db->quoteName('#__users'))
						->whereIn($db->quoteName('email'), $emails, ParameterType::STRING);

					$db->setQuery($query);
					$users = $db->loadObjectList('email');

					foreach ($emails as $email)
					{
						$email = trim($email);

						$subscriber         = new \stdClass;
						$subscriber->userid = isset($users[$email]) ? $users[$email]->id : 0;
						$subscriber->name   = isset($users[$email]) ? $users[$email]->name : '';
						$subscriber->email  = $email;
						$subscriber->hash   = md5($email);

						$subscribers[] = $subscriber;
					}
				}

				break;
			case 'comment-new':
			case 'comment-reply':
			case 'comment-update':
			case 'comment-published':
			case 'comment-unpublished':
			default:
				$query = $db->getQuery(true)
					->select('DISTINCTROW js.name, js.email, js.hash, js.userid')
					->from($db->quoteName('#__jcomments_subscriptions', 'js'))
					->innerJoin(
						$db->quoteName('#__jcomments_objects', 'jo'),
						' js.object_id = jo.object_id AND js.object_group = jo.object_group'
					)
					->where($db->quoteName('js.object_group') . ' = :ogroup')
					->where($db->quoteName('js.object_id') . ' = :oid')
					->where($db->quoteName('js.published') . ' = 1')
					->bind(':oid', $objectID, ParameterType::INTEGER)
					->bind(':ogroup', $objectGroup);

				if (Multilanguage::isEnabled())
				{
					$query->where($db->quoteName('js.lang') . ' = ' . $db->quote($lang))
						->where($db->quoteName('jo.lang') . ' = ' . $db->quote($lang));
				}

				try
				{
					$db->setQuery($query);
					$subscribers = $db->loadObjectList();
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
				}

				break;
		}

		return (object) $subscribers;
	}
}

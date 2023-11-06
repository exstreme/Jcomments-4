<?php
/**
 * JComments user plugin - User plugin for updating user info in comments.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Plugin\User\Jcomments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * User plugin for updating user info in comments.
 *
 * @since 4.1
 */
final class Jcomments extends CMSPlugin
{
	use DatabaseAwareTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application Instance
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  4.0.0
	 */
	protected $app;

	/**
	 * Component ID.
	 *
	 * @var    integer
	 * @since  4.1
	 */
	private static $jcommentsId = 0;

	/**
	 * Inject the Jcomments data into the User Profile.
	 *
	 * This method is called whenever Joomla is preparing the data for an XML form for display.
	 *
	 * @param   string  $context  Form context, passed by Joomla
	 * @param   mixed   $data     Form data
	 *
	 * @return  boolean
	 * @since   4.0
	 */
	public function onContentPrepareData(string $context, $data): bool
	{
		// Check we are manipulating a valid form.
		if ($context != 'com_users.profile')
		{
			return true;
		}

		// $data must be an object
		if (!is_object($data))
		{
			return true;
		}

		// We expecting the numeric user ID in $data->id
		if (!isset($data->id))
		{
			return true;
		}

		// Get the user ID
		$userId = (int) $data->id;

		// Make sure we have a positive integer user ID
		if ($userId <= 0)
		{
			return true;
		}

		$data->comments = array();

		/**
		 * Modify the data for display in the user profile view page in the frontend.
		 *
		 * It's important to note that we deliberately not register HTMLHelper methods to do the
		 * same (unlike e.g. the actionlogs system plugin) because the names of our fields are too
		 * generic and we run the risk of creating naming clashes. Instead, we manipulate the data
		 * directly.
		 */
		if ($this->app->input->get('layout') !== 'edit')
		{
			$db = $this->getDatabase();

			if ($this->params->get('show_comments_link', 0))
			{
				try
				{
					$query = $db->getQuery(true)
						->select('COUNT(id)')
						->from($db->quoteName('#__jcomments'))
						->where($db->quoteName('userid') . ' = :uid')
						->where($db->quoteName('deleted') . ' = 0')
						->where($db->quoteName('published') . ' = 1')
						->bind(':uid', $userId, ParameterType::INTEGER);

					$db->setQuery($query);
					$totalComments = (int) $db->loadResult();
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'plg_user_jcomments');

					return false;
				}

				// Do not set this value to empty or 0 because it will display COM_USERS_PROFILE_VALUE_NOT_FOUND text instead of numeric zero.
				$data->comments['total_comments'] = Text::plural('PLG_USER_COMMENTS_TOTAL_N', $totalComments);

				if ($totalComments > 0 && !HTMLHelper::isRegistered('users.total_comments'))
				{
					HTMLHelper::register('users.total_comments', [__CLASS__, 'urlComment']);
				}
			}

			if ($this->params->get('show_votes_link', 0))
			{
				try
				{
					$query = $db->getQuery(true)
						->select('COUNT(v.id)')
						->from($db->quoteName('#__jcomments_votes', 'v'))
						->leftJoin(
							$db->quoteName('#__jcomments', 'c'),
							$db->quoteName('c.id') . ' = ' . $db->quoteName('v.commentid')
						)
						->where($db->quoteName('v.userid') . ' = :uid')
						->bind(':uid', $userId, ParameterType::INTEGER);

					$db->setQuery($query);
					$totalVotes = (int) $db->loadResult();
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'plg_user_jcomments');

					return false;
				}

				// Do not set this value to empty or 0 because it will display COM_USERS_PROFILE_VALUE_NOT_FOUND text instead of numeric zero.
				$data->comments['total_votes'] = Text::plural('PLG_USER_COMMENTS_VOTES_TOTAL_N', $totalVotes);

				if ($totalVotes > 0 && !HTMLHelper::isRegistered('users.total_votes'))
				{
					HTMLHelper::register('users.total_votes', [__CLASS__, 'urlVotes']);
				}
			}

			if ($this->params->get('show_subscriptions_link', 0))
			{
				try
				{
					$query = $db->getQuery(true)
						->select('COUNT(id)')
						->from($db->quoteName('#__jcomments_subscriptions'))
						->where($db->quoteName('published') . ' = 1')
						->where($db->quoteName('userid') . ' = :uid')
						->bind(':uid', $userId, ParameterType::INTEGER);

					$db->setQuery($query);
					$totalSubcriptions = (int) $db->loadResult();
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'plg_user_jcomments');

					return false;
				}

				// Do not set this value to empty or 0 because it will display COM_USERS_PROFILE_VALUE_NOT_FOUND text instead of numeric zero.
				$data->comments['subscriptions_link'] = Text::plural('PLG_USER_COMMENTS_SUBSCRIPTIONS_TOTAL_N', $totalSubcriptions);

				if ($totalSubcriptions > 0 && !HTMLHelper::isRegistered('users.subscriptions_link'))
				{
					HTMLHelper::register('users.subscriptions_link', [__CLASS__, 'urlSubscriptions']);
				}
			}
		}

		return true;
	}

	/**
	 * Returns an anchor tag for comments.
	 *
	 * @param   string  $value  Field value
	 *
	 * @return  mixed
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public static function urlComment(string $value)
	{
		if (empty($value))
		{
			return HTMLHelper::_('users.value', $value);
		}
		else
		{
			$itemid = self::getItemid();
			$url = Route::_('index.php?option=com_jcomments&task=user.comments&Itemid=' . $itemid);

			// If component not found Route will return a Null value. Check it and set url w/o route.
			$url = empty($url)
				? \Joomla\CMS\Uri\Uri::base() . 'index.php?option=com_jcomments&task=user.comments&Itemid=' . $itemid
				: $url;

			return '<a href="' . $url . '">' . $value . '</a>';
		}
	}

	/**
	 * Returns an anchor tag for votes.
	 *
	 * @param   string  $value  Field value
	 *
	 * @return  mixed
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public static function urlVotes(string $value)
	{
		if (empty($value))
		{
			return HTMLHelper::_('users.value', $value);
		}
		else
		{
			$itemid = self::getItemid();
			$url = Route::_('index.php?option=com_jcomments&task=user.votes&Itemid=' . $itemid);

			// If component not found Route will return a Null value. Check it and set url w/o route.
			$url = empty($url)
				? \Joomla\CMS\Uri\Uri::base() . 'index.php?option=com_jcomments&task=user.votes&Itemid=' . $itemid
				: $url;

			return '<a href="' . $url . '">' . $value . '</a>';
		}
	}

	/**
	 * Returns an anchor tag for subscriptions.
	 *
	 * @param   string  $value  Field value
	 *
	 * @return  mixed
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public static function urlSubscriptions(string $value)
	{
		if (empty($value))
		{
			return HTMLHelper::_('users.value', $value);
		}
		else
		{
			$itemid = self::getItemid();
			$url = Route::_('index.php?option=com_jcomments&task=user.subscriptions&Itemid=' . $itemid);

			// If component not found Route will return a Null value. Check it and set url w/o route.
			$url = empty($url)
				? \Joomla\CMS\Uri\Uri::base() . 'index.php?option=com_jcomments&task=user.subscriptions&Itemid=' . $itemid
				: $url;

			return '<a href="' . $url . '">' . $value . '</a>';
		}
	}

	/**
	 * Adds additional fields to the user form
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentPrepareForm(Form $form, $data): bool
	{
		// Check we are manipulating a valid form.
		$name = $form->getName();

		if ($name != 'com_users.profile')
		{
			return true;
		}

		// Add the registration fields to the form.
		FormHelper::addFieldPrefix('Joomla\\Plugin\\User\\Comments\\Field');
		FormHelper::addFormPath(dirname(__DIR__, 2) . '/forms');
		$form->loadFile('comments');

		if ($this->app->input->get('layout') == 'edit')
		{
			$form->removeField('total_comments', 'comments');
			$form->removeField('total_votes', 'comments');
			$form->removeField('subscriptions_manage_link', 'comments');
		}

		if (!$this->params->get('show_comments_link'))
		{
			$form->removeField('total_comments', 'comments');
		}

		if (!$this->params->get('show_votes_link'))
		{
			$form->removeField('total_votes', 'comments');
		}

		if (!$this->params->get('show_subscriptions_link'))
		{
			$form->removeField('subscriptions_manage_link', 'comments');
		}

		return true;
	}

	/**
	 * Saves user profile data
	 *
	 * @param   array        $data    entered user data
	 * @param   boolean      $isNew   true if this is a new user
	 * @param   boolean      $result  true if saving the user worked
	 * @param   string|null  $error   error message
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterSave(array $data, bool $isNew, bool $result, ?string $error)
	{
		if ($data && !$isNew)
		{
			$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

			if ($userId > 0 && trim($data['username']) != '' && trim($data['email']) != '')
			{
				$db = $this->getDatabase();

				try
				{
					// Update name, username and email in comments
					$query = $db->getQuery(true)
						->update($db->quoteName('#__jcomments'))
						->set($db->quoteName('name') . ' = ' . $db->quote($data['name']))
						->set($db->quoteName('username') . ' = ' . $db->quote($data['username']))
						->set($db->quoteName('email') . ' = ' . $db->quote($data['email']))
						->where($db->quoteName('userid') . ' = :uid')
						->bind(':uid', $userId, ParameterType::INTEGER);

					$db->setQuery($query);
					$db->execute();
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'plg_user_jcomments');
				}

				try
				{
					// Update email in subscriptions
					$query = $db->getQuery(true)
						->update($db->quoteName('#__jcomments_subscriptions'))
						->set($db->quoteName('email') . ' = ' . $db->quote($data['email']))
						->where($db->quoteName('userid') . ' = :uid')
						->bind(':uid', $userId, ParameterType::INTEGER);

					$db->setQuery($query);
					$db->execute();
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'plg_user_jcomments');
				}
			}
		}
	}

	/**
	 * Remove all user profile information for the given user ID
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was successfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterDelete(array $user, bool $success, string $msg)
	{
		if ($success)
		{
			$userId = ArrayHelper::getValue($user, 'id', 0, 'int');

			if ($userId > 0)
			{
				$db = $this->getDatabase();
				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments'))
					->set($db->quoteName('userid') . ' = 0')
					->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $userId, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $userId, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_subscriptions'))
					->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $userId, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_users'))
					->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $userId, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $userId, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Get com_jcomments component id
	 *
	 * @return  integer
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	private static function getItemid(): int
	{
		if (self::$jcommentsId > 0)
		{
			return self::$jcommentsId;
		}

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where(
				array(
					$db->quoteName('element') . ' = ' . $db->quote('com_jcomments'),
					$db->quoteName('type') . ' = ' . $db->quote('component'),
				)
			);

		try
		{
			$db->setQuery($query);
			$itemid = $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), 'plg_user_jcomments');
			$itemid = Factory::getApplication()->input->get('Itemid', 0);
		}

		self::$jcommentsId = (int) $itemid;

		return (int) $itemid;
	}
}

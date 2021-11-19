<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @authors       Sergey M. Litvinov <smart@joomlatune.ru>, exstreme <info@protectyoursite.ru>, Vladimir Globulopolis
 * @copyright     (C) 2006-2018 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright     (C) 2018-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;

/**
 * User plugin for updating user info in comments
 *
 * @since 1.5
 */
class PlgUserJComments extends CMSPlugin
{
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
	 * Database object.
	 *
	 * @var    \Joomla\Database\DatabaseInterface
	 * @since  4.0.0
	 */
	protected $db;

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
	public function onContentPrepareData($context, $data)
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

		// We expect the numeric user ID in $data->id
		if (!isset($data->id))
		{
			return true;
		}

		// Get the user ID
		$userId = isset($data->id) ? intval($data->id) : 0;

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
			$db = $this->db;

			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($db->quoteName('#__jcomments'))
				->where($db->quoteName('userid') . ' = ' . $userId);

			$db->setQuery($query);
			$total = $db->loadResult();

			// Do not set this value to empty or 0 because it will display COM_USERS_PROFILE_VALUE_NOT_FOUND text instead of numeric zero.
			$data->comments['total_comments'] = Text::plural('PLG_USER_COMMENTS_TOTAL_N', $total);

			if ($total > 0 && !HTMLHelper::isRegistered('users.total_comments'))
			{
				HTMLHelper::register('users.total_comments', [__CLASS__, 'url']);
			}
		}

		return true;
	}

	/**
	 * Returns an anchor tag.
	 *
	 * @param   string  $value  Field value
	 *
	 * @return  mixed|string
	 *
	 * @since   4.0
	 */
	public static function url($value)
	{
		if (empty($value))
		{
			return HTMLHelper::_('users.value', $value);
		}
		else
		{
			/** @var DatabaseDriver $db */
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

			$db->setQuery($query);
			$itemid = $db->loadResult();

			return '<a href="' . Route::_('index.php?option=com_jcomments&view=comments&task=show_all&Itemid=' . (int) $itemid) . '">' . $value . '</a>';
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
	public function onContentPrepareForm(Form $form, $data)
	{
		// Check we are manipulating a valid form.
		$name = $form->getName();

		if ($name != 'com_users.profile')
		{
			return true;
		}

		// Add the registration fields to the form.
		FormHelper::addFieldPrefix('Joomla\\Plugin\\User\\Comments\\Field');
		FormHelper::addFormPath(__DIR__ . '/forms');
		$form->loadFile('comments');

		if ($this->app->input->get('layout') == 'edit')
		{
			$form->removeField('total_comments', 'comments');
		}

		return true;
	}

	/**
	 * Saves user profile data
	 *
	 * @param   array    $data    entered user data
	 * @param   boolean  $isNew   true if this is a new user
	 * @param   boolean  $result  true if saving the user worked
	 * @param   string   $error   error message
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		if ($data && !$isNew)
		{
			$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

			if ($userId > 0 && trim($data['username']) != '' && trim($data['email']) != '')
			{
				$db = $this->db;

				// Update name, username and email in comments
				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments'))
					->set($db->quoteName('name') . ' = ' . $db->quote($data['name']))
					->set($db->quoteName('username') . ' = ' . $db->quote($data['username']))
					->set($db->quoteName('email') . ' = ' . $db->quote($data['email']))
					->where($db->quoteName('userid') . ' = ' . $userId);

				$db->setQuery($query);
				$db->execute();

				// Update email in subscriptions
				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments_subscriptions'))
					->set($db->quoteName('email') . ' = ' . $db->quote($data['email']))
					->where($db->quoteName('userid') . ' = ' . $userId);

				$db->setQuery($query);
				$db->execute();
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
	public function onUserAfterDelete($user, $success, $msg)
	{
		if ($success)
		{
			$userId = ArrayHelper::getValue($user, 'id', 0, 'int');

			if ($userId > 0)
			{
				$db = $this->db;

				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments'))
					->set($db->quoteName('userid') . ' = 0')
					->where($db->quoteName('userid') . ' = ' . $userId);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('userid') . ' = ' . $userId);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_subscriptions'))
					->where($db->quoteName('userid') . ' = ' . $userId);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('userid') . ' = ' . $userId);

				$db->setQuery($query);
				$db->execute();
			}
		}
	}
}

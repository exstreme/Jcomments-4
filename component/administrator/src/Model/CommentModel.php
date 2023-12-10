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

namespace Joomla\Component\Jcomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Component\Jcomments\Site\Helper\CacheHelper;
use Joomla\Component\Jcomments\Site\Helper\NotificationHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * Comment Model
 *
 * @since  4.0
 */
class CommentModel extends AdminModel
{
	/**
	 * Get list of user reports.
	 *
	 * @param   mixed  $pk  Comment ID
	 *
	 * @return  mixed  An array on success, false on failure
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function getReports($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
		$db = $this->getDatabase();

		try
		{
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__jcomments_reports'))
				->where($db->quoteName('commentid') . ' = :id')
				->bind(':id', $pk, ParameterType::INTEGER)
				->order($db->quoteName('date'));

			$db->setQuery($query);

			return $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}
	}

	/**
	 * Delete one or more reports.
	 *
	 * @param   array  $ids  Comment ID
	 *
	 * @return  boolean
	 *
	 * @since   4.0
	 */
	public function deleteReports(array $ids): bool
	{
		$db  = $this->getDatabase();
		$ids = ArrayHelper::toInteger($ids);

		try
		{
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__jcomments_reports'))
				->whereIn($db->quoteName('id'), $ids);

			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_jcomments.comment', 'comment', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		if (!$this->canEditState((object) $data))
		{
			$form->setFieldAttribute('published', 'disabled', 'true');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jcomments.edit.comment.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			// In wysiwyg editor <br> must be replaced by new line.
			$data->comment = JcommentsText::br2nl($data->comment);
		}

		return $data;
	}

	/**
	 * Delete items
	 *
	 * @param   array  $pks  The primary keys related to the comment(s) that was deleted.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.7.0
	 */
	public function delete(&$pks)
	{
		/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
		$table  = $this->getTable();
		$pks    = ArrayHelper::toInteger((array) $pks);
		$config = ComponentHelper::getParams('com_jcomments');

		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if ($this->canDelete($table))
				{
					$table->published = 0;

					if ($config->get('delete_mode') == 0)
					{
						if (!$table->delete($pk))
						{
							$this->setError($table->getError());

							return false;
						}
					}
					else
					{
						if (!$table->markAsDeleted())
						{
							$this->setError($table->getError());

							return false;
						}
					}

					// Clean stored items in cache
					CacheHelper::removeCachedItem(
						md5('Joomla\Component\Jcomments\Site\Model\CommentModel::getItem' . $pk),
						'com_jcomments_comments'
					);
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();

					if ($error)
					{
						Log::add($error, Log::WARNING, 'com_jcomments');

						return false;
					}
					else
					{
						Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'com_jcomments');

						return false;
					}
				}
			}
			else
			{
				$this->setError($table->getError());

				return false;
			}
		}

		if ($config->get('delete_mode') == 1)
		{
			Factory::getApplication()->enqueueMessage(Text::plural('A_COMMENTS_HAS_BEEN_MARKED_N_DELETED', count($pks)));
		}

		return true;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    $pks    A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
		/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
		$table  = $this->getTable();
		$user   = Factory::getApplication()->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');
		$pks    = ArrayHelper::toInteger((array) $pks);

		// Access checks.
		foreach ($pks as $pk)
		{
			$table->reset();

			if ($table->load($pk))
			{
				if (!$this->canEditState($table))
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

				$publishedColumnName = $table->getColumnAlias('published');

				if (property_exists($table, $publishedColumnName) && $table->get($publishedColumnName, $value) != $value)
				{
					if (!$table->publish($pk, $value, $user->get('id')))
					{
						$this->setError($table->getError());

						return false;
					}

					// Clean stored items in cache
					CacheHelper::removeCachedItem(
						md5('Joomla\Component\Jcomments\Site\Model\CommentModel::getItem' . $pk),
						'com_jcomments_comments'
					);

					// Send notifications only on publish state. Unpublish state will process only on frontend.
					if ($params->get('enable_notification') && in_array(3, $params->get('notification_type')) && $value == 1)
					{
						// Send notification to subscribed users.
						NotificationHelper::push($table, 'comment-published');

						// Send notification to administrator(moderator). List of emails from 'notification_email' option.
						NotificationHelper::push($table, 'moderate-published');
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function save($data)
	{
		$params     = ComponentHelper::getParams('com_jcomments');
		$oldState   = 0;
		$oldComment = '';
		$pk         = !empty($data['id']) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$isNew      = empty($data['id']);

		// Loading previous record to check for publishing state.
		if ($pk > 0)
		{
			/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $origTable */
			$origTable = $this->getTable();
			$origTable->load($pk);

			$oldState = $origTable->published;
			$oldComment = $origTable->comment;
		}

		if ($data['userid'] == 0)
		{
			$data['name']     = preg_replace('/[\'"\>\<\(\)\[\]]?+/i', '', $data['name']);
			$data['username'] = $data['name'];
		}
		else
		{
			/** @var \Joomla\CMS\User\UserFactory $userFactory */
			$userFactory      = Factory::getContainer()->get('user.factory');
			$user             = $userFactory->loadUserById($data['userid']);
			$data['name']     = $user->name;
			$data['username'] = $user->username;
			$data['email']    = $user->email;
		}

		$data['title']   = stripslashes($data['title']);
		$data['comment'] = stripslashes($data['comment']);
		$data['comment'] = JcommentsText::nl2br($data['comment']);
		$data['comment'] = JcommentsText::filterText($data['comment']);

		if ($data['date'] == $this->getDatabase()->getNullDate() || empty($data['date']))
		{
			$data['date'] = Factory::getDate()->toSql();
		}

		if (parent::save($data))
		{
			// Clean stored items in cache
			CacheHelper::removeCachedItem(
				md5('Joomla\Component\Jcomments\Site\Model\CommentModel::getItem' . $pk),
				'com_jcomments_comments'
			);

			if ($params->get('enable_notification'))
			{
				if ($isNew && $data['published'])
				{
					// Send notification about new comment added.
					if (in_array(1, $params->get('notification_type')))
					{
						NotificationHelper::push($data);
						NotificationHelper::push($data, 'moderate-new');
					}
				}
				elseif (!$isNew && $data['published'] && $oldState != $data['published'])
				{
					if (in_array(3, $params->get('notification_type')))
					{
						NotificationHelper::push($data, 'comment-published');
						NotificationHelper::push($data, 'moderate-published');
					}
				}
				elseif (!$isNew && $oldComment != $data['comment'])
				{
					if (in_array(1, $params->get('notification_type')))
					{
						NotificationHelper::push($data, 'comment-update');

						// Change the 'comment' field by including the old and new comments so that the administrator sees changes.
						$data['comment'] = $params->get('mail_style') == 'html'
							? Text::sprintf('A_COMMENT_TEXT_FOR_MODDERS_HTML', $data['comment'], $oldComment)
							: Text::sprintf('A_COMMENT_TEXT_FOR_MODDERS', $data['comment'], $oldComment);

						NotificationHelper::push($data, 'moderate-update');
					}
				}
			}

			return true;
		}

		return false;
	}
}

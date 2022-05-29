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
use Joomla\CMS\Event\Model\BeforeBatchEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Component\Jcomments\Site\Helper\NotificationHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class CommentModel extends AdminModel
{
	/**
	 * Get list of user reports.
	 *
	 * @param   integer  $pk  Comment ID
	 *
	 * @return  mixed  An array on success, false on failure
	 *
	 * @since   4.0
	 */
	public function getReports($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
		$db = $this->getDbo();

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
		$db  = $this->getDbo();
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
			$data          = $this->getItem();
			$data->comment = strip_tags(str_replace('<br />', "\n", $data->comment));
		}

		return $data;
	}

	/**
	 * Batch language changes for a group of rows.
	 *
	 * @param   string  $value     The new value matching a language.
	 * @param   array   $pks       An array of row IDs.
	 * @param   array   $contexts  An array of item contexts.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since   2.5
	 */
	protected function batchLanguage($value, $pks, $contexts)
	{
		// Initialize re-usable member properties, and re-usable local variables
		$this->initBatch();

		foreach ($pks as $pk)
		{
			if ($this->user->authorise('core.edit', $contexts[$pk]))
			{
				$this->table->reset();
				$this->table->load($pk);
				$this->table->language = $value;

				$event = new BeforeBatchEvent(
					$this->event_before_batch,
					['src' => $this->table, 'type' => 'language']
				);
				$this->dispatchEvent($event);

				// Check the row.
				if (!$this->table->check())
				{
					$this->setError($this->table->getError());

					return false;
				}

				if (!$this->table->store())
				{
					$this->setError($this->table->getError());

					return false;
				}
			}
			else
			{
				$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

				return false;
			}
		}

		$this->cleanCache('com_jcomments');

		return true;
	}

	/**
	 * Delete items
	 *
	 * @param   array  $pks  The primary keys related to the comment(s) that was deleted.
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public function delete(&$pks)
	{
		$pks    = ArrayHelper::toInteger((array) $pks);
		$table  = $this->getTable();
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

		$this->cleanCache('com_jcomments');

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
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
		$user  = Factory::getApplication()->getIdentity();
		$table = $this->getTable();
		$pks   = ArrayHelper::toInteger((array) $pks);

		// Access checks.
		foreach ($pks as $i => $pk)
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

					// Send notifications only on publish state
					if ($value == 1)
					{
						NotificationHelper::push($table, ($value == 1) ? 'comment-published' : 'comment-unpublished');
					}
				}
			}
		}

		$this->cleanCache('com_jcomments');

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		$prevPublished = 0;
		$pk = !empty($data['id']) ? $data['id'] : (int) $this->getState($this->getName() . '.id');

		// Loading previous record to check for publishing state.
		if ($pk > 0)
		{
			/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $origTable */
			$origTable = $this->getTable();
			$origTable->load($pk);

			$prevPublished = $origTable->published;
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
		//$table->comment = JCommentsText::nl2br($table->comment); // TODO Remove JCommentsText::nl2br()
		$data['comment'] = JcommentsFactory::getBbcode()->filter($data['comment']);

		if ($data['date'] == $this->getDbo()->getNullDate() || empty($data['date']))
		{
			$data['date'] = Factory::getDate()->toSql();
		}

		if (parent::save($data))
		{
			if ($data['published'] && $prevPublished != $data['published'])
			{
				NotificationHelper::push(ArrayHelper::toObject($data));
			}

			$this->cleanCache('com_jcomments');

			return true;
		}

		return false;
	}
}

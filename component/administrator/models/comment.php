<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class JCommentsModelComment extends JCommentsModelForm
{
	public function getTable($type = 'Comment', $prefix = 'JCommentsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getReports($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__jcomments_reports'))
			->where('commentid = ' . (int) $pk)
			->order($db->escape('date'));

		$db->setQuery($query);
		$items = $db->loadObjectList();

		return is_array($items) ? $items : array();
	}

	public function deleteReport($id)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->delete()
			->from($db->quoteName('#__jcomments_reports'))
			->where('id = ' . (int) $id);

		$db->setQuery($query);
		$db->execute();

		return true;
	}

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

	public function save($data)
	{
		/** @var JCommentsTableComment $table */
		$table   = $this->getTable();
		$bbcodes = new JCommentsBBCode;
		$pkName  = $table->getKeyName();
		$pk      = (!empty($data[$pkName])) ? $data[$pkName] : (int) $this->getState($this->getName() . '.id');

		try
		{
			if ($pk > 0)
			{
				$table->load($pk);
			}

			$prevPublished = $table->published;

			if (!$table->bind($data))
			{
				$this->setError($table->getError());

				return false;
			}

			if ($table->userid == 0)
			{
				$table->name     = preg_replace('/[\'"\>\<\(\)\[\]]?+/i', '', $table->name);
				$table->username = $table->name;
			}
			else
			{
				$user            = Factory::getUser($table->userid);
				$table->name     = $user->name;
				$table->username = $user->username;
				$table->email    = $user->email;
			}

			$table->title   = stripslashes($table->title);
			$table->comment = stripslashes($table->comment);
			//$table->comment = JCommentsText::nl2br($table->comment); // TODO Remove JCommentsText::nl2br()
			$table->comment = $bbcodes->filter($table->comment);

			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			if (!$table->store())
			{
				$this->setError($table->getError());

				return false;
			}

			if ($table->published && $prevPublished != $table->published)
			{
				JCommentsNotification::push(array('comment' => $table), 'comment-new');
			}

			$this->cleanCache('com_jcomments');

		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}

		return true;
	}
}

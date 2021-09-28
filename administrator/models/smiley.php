<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

Table::addIncludePath(JPATH_COMPONENT . '/tables');

class JCommentsModelSmiley extends JCommentsModelForm
{
	public function getTable($type = 'Smiley', $prefix = 'JCommentsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_jcomments.smiley', 'smiley', array('control' => 'jform', 'load_data' => $loadData));
		
		if (empty($form))
		{
			return false;
		}

		if (!$this->canEditState())
		{
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'filter', 'unset');

			$form->setFieldAttribute('published', 'disabled', 'true');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jcomments.edit.smiley.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	public function save($data)
	{
		$table  = $this->getTable();
		$pkName = $table->getKeyName();
		$pk     = (!empty($data[$pkName])) ? $data[$pkName] : (int) $this->getState($this->getName() . '.id');

		try
		{
			if ($pk > 0)
			{
				$table->load($pk);
			}

			if (!$table->bind($data))
			{
				$this->setError($table->getError());

				return false;
			}

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

			$this->saveLegacy();

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

	public function saveLegacy()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select("code, image")
			->from($db->quoteName('#__jcomments_smilies'))
			->where('published = 1')
			->order('ordering');

		$db->setQuery($query);

		$items = $db->loadObjectList();

		if (count($items))
		{
			$values = array();

			foreach ($items as $item)
			{
				if ($item->code != '' && $item->image != '')
				{
					$values[] = $item->code . "\t" . $item->image;
				}
			}

			$values = count($values) ? implode("\n", $values) : '';

			$query = $db->getQuery(true)
				->select("COUNT(*)")
				->from($db->quoteName('#__jcomments_settings'))
				->where('component = ' . $db->quote(''))
				->where('name = ' . $db->quote('smilies'));

			$db->setQuery($query);
			$count = $db->loadResult();

			if ($count)
			{
				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments_settings'))
					->set($db->quoteName('value') . ' = ' . $db->quote($values))
					->where('name = ' . $db->quote('smilies'));

				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__jcomments_settings'))
					->columns(array($db->quoteName('name'), $db->quoteName('value')))
					->values($db->quote('smilies') . ', ' . $db->quote($values));

				$db->setQuery($query);
				$db->execute();
			}
		}
	}
}

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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

class UserModel extends AdminModel
{
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_jcomments.user', 'user', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jcomments.edit.user.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  false|object  Object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if ($item !== false)
		{
			if (property_exists($item, 'labels'))
			{
				$registry = new Registry($item->labels);
				$item->labels = $registry->toArray();
			}
		}

		return $item;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		/** @var \Joomla\Component\Jcomments\Administrator\Table\UserTable $table */
		$table          = $this->getTable();
		$input          = Factory::getApplication()->input;
		$db             = $this->getDatabase();
		$data['labels'] = json_encode($data['labels']);
		$id             = $input->get->getInt('id');

		PluginHelper::importPlugin($this->events_map['save']);

		try
		{
			$loadData = $table->load($data['id']);

			// Row found in table
			if ((!empty($id) && !empty($data['id']) && $id == $data['id']) && $loadData === true)
			{
				$isNew = false;
				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments_users'))
					->set($db->quoteName('labels') . ' = ' . $db->quote($data['labels']))
					->set($db->quoteName('terms_of_use') . ' = :tos')
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER)
					->bind(':tos', $data['terms_of_use'], ParameterType::INTEGER);
			}
			elseif (empty($id) && !empty($data['id']) && $loadData === false)
			{
				$isNew = true;
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__jcomments_users'))
					->values(':id, ' . $db->quote($data['labels']) . ', :tos')
					->bind(':id', $data['id'], ParameterType::INTEGER)
					->bind(':tos', $data['terms_of_use'], ParameterType::INTEGER);
			}
			else
			{
				$this->setState($this->getName() . '.id', $data['id']);

				return false;
			}

			$db->setQuery($query);

			if (!$db->execute())
			{
				$this->setError($this->getError());

				return false;
			}
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$this->setState($this->getName() . '.id', $data['id']);
		$this->setState($this->getName() . '.new', $isNew);

		return true;
	}
}

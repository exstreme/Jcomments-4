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
use Joomla\CMS\MVC\Model\AdminModel;

abstract class JCommentsModelForm extends AdminModel
{
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

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState()
	{
		$table = $this->getTable();
		$key = $table->getKeyName();

		$pk = Factory::getApplication()->input->getInt($key);
		$this->setState($this->getName() . '.id', $pk);
	}
}

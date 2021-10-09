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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;

class JCommentsModelList extends ListModel
{
	protected $context = null;
	protected $tableName = null;
	protected $tablePrefix = 'JCommentsTable';

	public function __construct($config = array(), MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		parent::addIncludePath(JPATH_BASE . '/components/com_jcomments/tables');

		// Get table name
		$this->tableName = substr($this->getName(), 0, -1);
	}

	public function delete(&$pks)
	{
		$table = $this->getTable($this->tableName, $this->tablePrefix);
		$total = count($pks);

		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if (Factory::getApplication()->getIdentity()->authorise('core.delete', $this->option))
				{
					// Comments can be marked as deleted.
					if ($this->context == 'com_jcomments.comments')
					{
						$config = JCommentsFactory::getConfig();

						if ($config->getInt('delete_mode') == 0)
						{
							if (!$table->delete($pk))
							{
								$this->setError($table->getError());

								return false;
							}
						}
						else
						{
							$table->markAsDeleted();
							Factory::getApplication()->enqueueMessage(Text::plural('A_COMMENTS_HAS_BEEN_MARKED_N_DELETED', $total));
						}
					}
					else
					{
						if (!$table->delete($pk))
						{
							$this->setError($table->getError());

							return false;
						}
					}
				}
				else
				{
					unset($pks[$i]);
					$error = $this->getError();

					if ($error)
					{
						Log::add($error, Log::WARNING, 'jerror');

						return false;
					}
					else
					{
						Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'jerror');

						return false;
					}
				}
			}
		}

		$this->cleanCache('com_jcomments');

		return true;
	}

	public function publish(&$pks, $value = 1)
	{
		$pks   = (array) $pks;
		$user  = Factory::getApplication()->getIdentity();
		$table = $this->getTable($this->tableName, $this->tablePrefix);

		foreach ($pks as $i => $pk)
		{
			$table->reset();

			if ($table->load($pk))
			{
				if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', $this->option))
				{
					unset($pks[$i]);
					Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');

					return false;
				}

				if (!$table->publish(array($pk), $value, $user->get('id')))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	public function checkin($pks = array())
	{
		$table   = $this->getTable($this->tableName, $this->tablePrefix);
		$checkin = property_exists($table, 'checked_out');
		$count   = 0;

		if ($checkin === false)
		{
			return $count;
		}

		foreach ($pks as $pk)
		{
			if (!$table->load($pk) || !$table->checkin($pk))
			{
				$this->setError($table->getError());

				return false;
			}

			$count++;
		}

		return $count;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   Table  $table  A \Table object.
	 *
	 * @return  array  An array of conditions to add to ordering queries.
	 *
	 * @since   1.6
	 */
	protected function getReorderConditions($table)
	{
		return array();
	}

	public function saveOrder($pks = null, $order = null)
	{
		if (!empty($pks))
		{
			$table      = $this->getTable($this->tableName, $this->tablePrefix);
			$conditions = array();
			$ordering   = property_exists($table, 'ordering');

			if ($ordering)
			{
				foreach ($pks as $i => $pk)
				{
					$table->load((int) $pk);

					if ($table->ordering != $order[$i])
					{
						$table->ordering = $order[$i];

						if (!$table->store())
						{
							$this->setError($table->getError());

							return false;
						}

						$reorderCondition = $this->getReorderConditions();
						$found            = false;

						foreach ($conditions as $condition)
						{
							if ($condition[1] == $reorderCondition)
							{
								$found = true;
								break;
							}
						}

						if (!$found)
						{
							$key          = $table->getKeyName();
							$conditions[] = array($table->$key, $reorderCondition);
						}
					}
				}

				foreach ($conditions as $condition)
				{
					$table->load($condition[0]);
					$table->reorder($condition[1]);
				}
			}
		}

		return true;
	}


	protected function populateState($ordering = null, $direction = null)
	{
		if ($this->context)
		{
			$app = Factory::getApplication();

			$value = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'), 'uint');
			$limit = $value;
			$this->setState('list.limit', $limit);

			$value = $app->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0);
			$start = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
			$this->setState('list.start', $start);

			$value = $app->getUserStateFromRequest($this->context . '.filter.order', 'filter_order', $ordering);

			if (!in_array($value, $this->filter_fields))
			{
				$value = $ordering;
				$app->setUserState($this->context . '.filter.order', $value);
			}

			$this->setState('list.ordering', $value);
			$value = $app->getUserStateFromRequest($this->context . '.filter.order', 'filter_order_Dir', $direction);

			if (!in_array(strtoupper($value), array('ASC', 'DESC', '')))
			{
				$value = $direction;
				$app->setUserState($this->context . '.filter.order_Dir', $value);
			}

			$this->setState('list.direction', $value);
		}
		else
		{
			$this->setState('list.start', 0);
		}
	}
}

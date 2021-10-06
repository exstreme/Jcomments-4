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

	/*public function getItems()
	{
		$store = $this->getStoreId();

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$query = $this->_getListQuery();

		try
		{
			$items = $this->_getList($query, $this->getStart(), $this->getState('list.limit'));
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$this->cache[$store] = $items;

		return $this->cache[$store];
	}

	public function getPagination()
	{
		$store = $this->getStoreId('getPagination');

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$limit = (int) $this->getState('list.limit');
		$page = new Pagination($this->getTotal(), $this->getStart(), $limit);

		$this->cache[$store] = $page;

		return $this->cache[$store];
	}

	/*protected function getListQuery()
	{
		return $this->getDbo()->getQuery(true);
	}*/

	/*protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->getState('list.start');
		$id .= ':' . $this->getState('list.limit');
		$id .= ':' . $this->getState('list.ordering');
		$id .= ':' . $this->getState('list.direction');

		return md5($this->context . ':' . $id);
	}

	public function getTotal()
	{
		$store = $this->getStoreId('getTotal');

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$query = $this->_getListQuery();

		try
		{
			$total = (int) $this->_getListCount($query);
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$this->cache[$store] = $total;

		return $this->cache[$store];
	}

	public function getStart()
	{
		$store = $this->getStoreId('getStart');

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$start = $this->getState('list.start');
		$limit = $this->getState('list.limit');
		$total = $this->getTotal();
		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		$this->cache[$store] = $start;

		return $this->cache[$store];
	}


	protected function _getListQuery()
	{
		static $lastStoreId;

		$currentStoreId = $this->getStoreId();

		if ($lastStoreId != $currentStoreId || empty($this->query))
		{
			$lastStoreId = $currentStoreId;
			$this->query = $this->getListQuery();
		}

		return $this->query;
	}

	protected function _getListCount($query)
	{
		$db = $this->getDbo();
		$db->setQuery($query);
		$db->execute();

		return $db->getNumRows();
	}*/

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

		if ($checkin === false) {
			return $count;
		}

		foreach ($pks as $pk)
		{
			if (!$table->load($pk) || !$table->checkin($pk)) {
				$this->setError($table->getError());

				return false;
			}

			$count++;
		}

		return $count;
	}

	protected function getReorderConditions($table)
	{
		return array();
	}

	public function saveOrder($pks = null, $order = null)
	{
		if (!empty($pks))
		{
			/* @var Table $table */
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

						$reorderCondition = $this->getReorderConditions($table);
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

	public function reorder($pks, $delta = 0)
	{
		$table  = $this->getTable($this->tableName, $this->tablePrefix);
		$pks    = (array) $pks;
		$result = true;

		$allowed = true;

		foreach ($pks as $i => $pk)
		{
			$table->reset();

			if ($table->load($pk) && $this->checkout($pk))
			{
				// Access checks.
				if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', $this->option))
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$this->checkin($pk);
					Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');
					$allowed = false;
					continue;
				}

				$where = $this->getReorderConditions($table);

				if (!$table->move($delta, $where))
				{
					$this->setError($table->getError());
					unset($pks[$i]);
					$result = false;
				}

				$this->checkin($pk);
			}
			else
			{
				$this->setError($table->getError());
				unset($pks[$i]);
				$result = false;
			}
		}

		if ($allowed === false && empty($pks))
		{
			$result = null;
		}

		if ($result == true)
		{
			$this->cleanCache();
		}

		return $result;
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

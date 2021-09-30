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

class JCommentsModelCustomBBCodes extends JCommentsModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'jcb.id',
				'name', 'jcb.name',
				'button_enabled', 'jcb.button_enabled',
				'published', 'jcb.published',
				'ordering', 'jcb.ordering',
			);
		}

		parent::__construct($config);
	}

	public function getTable($type = 'CustomBBCode', $prefix = 'JCommentsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select("jcb.*");
		$query->from($db->quoteName('#__jcomments_custom_bbcodes') . ' AS jcb');

		// Join over the users
		$query->select('u.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON u.id = jcb.checked_out');

		// Filter by published state
		$state = $this->getState('filter.state');
		if (is_numeric($state))
		{
			$query->where('jcb.published = ' . (int) $state);
		}

		// Filter by search in name or email
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('(jcb.name LIKE ' . $search . ' OR jcb.button_title LIKE ' . $search . ')');
		}

		$ordering  = $this->state->get('list.ordering', 'ordering');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($ordering . ' ' . $direction));

		return $query;
	}

	public function changeButtonState(&$pks, $state = 1)
	{
		$pks   = (array) $pks;
		$table = $this->getTable();
		$key   = $table->getKeyName();
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->update($table->getTableName());
		$query->set('button_enabled = ' . (int) $state);
		$query->where($key . ' = ' . implode(' OR ' . $key . ' = ', $pks));

		$db->setQuery($query);
		$db->execute();

		return true;
	}

	public function duplicate(&$pks)
	{
		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			if ($table->load($pk, true))
			{
				$table->id = 0;

				$m = null;
				if (preg_match('#\((\d+)\)$#', $table->name, $m))
				{
					$table->name = preg_replace('#\(\d+\)$#', '(' . ($m[1] + 1) . ')', $table->name);
				}
				else
				{
					$table->name .= ' (2)';
				}

				$table->published = 0;

				if (!$table->check() || !$table->store())
				{
					throw new Exception($table->getError());
				}
			}
			else
			{
				throw new Exception($table->getError());
			}
		}

		$this->cleanCache();

		return true;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		parent::populateState('ordering', 'asc');
	}
}

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

class JCommentsModelCustomBBCodes extends JCommentsModelList
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $context = 'com_jcomments.custombbcodes';

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

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'jcb.*'
			)
		);
		$query->from($db->quoteName('#__jcomments_custom_bbcodes') . ' AS jcb');

		// Join over the users
		$query->select('u.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON u.id = jcb.checked_out');

		// Filter by published state
		$state = $this->getState('filter.published');

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
		$table = $this->getTable('CustomBBCode', 'JCommentsTable');
		$key   = $table->getKeyName();
		$db    = $this->getDbo();

		$query = $db->getQuery(true)
			->update($table->getTableName())
			->set('button_enabled = ' . (int) $state)
			->where($key . ' = ' . implode(' OR ' . $key . ' = ', $pks));

		$db->setQuery($query);
		$db->execute();

		return true;
	}

	public function duplicate($pks)
	{
		$table = $this->getTable('CustomBBCode', 'JCommentsTable');

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

	protected function populateState($ordering = 'ordering', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');

		return parent::getStoreId($id);
	}
}

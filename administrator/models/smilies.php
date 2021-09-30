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

class JCommentsModelSmilies extends JCommentsModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'js.id',
				'code', 'js.code',
				'name', 'js.name',
				'image', 'js.image',
				'published', 'js.published',
				'ordering', 'js.ordering',
			);
		}

		parent::__construct($config);
	}

	public function getTable($type = 'Smiley', $prefix = 'JCommentsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select("js.*");
		$query->from($db->quoteName('#__jcomments_smilies') . ' AS js');

		// Join over the users
		$query->select('u.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON u.id = js.checked_out');

		// Filter by published state
		$state = $this->getState('filter.state');
		
		if (is_numeric($state))
		{
			$query->where('js.published = ' . (int) $state);
		}

		// Filter by search in name or email
		$search = $this->getState('filter.search');
		
		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('(js.name LIKE ' . $search . ' OR js.code LIKE ' . $search . ')');
		}

		$ordering  = $this->state->get('list.ordering', 'js.ordering');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($ordering . ' ' . $direction));

		return $query;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		parent::populateState('js.ordering', 'asc');
	}
}

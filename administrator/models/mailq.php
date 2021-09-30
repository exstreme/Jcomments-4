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

class JCommentsModelMailq extends JCommentsModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'name',
				'email',
				'subject',
				'priority',
				'attempts',
				'created',
			);
		}

		parent::__construct($config);
	}

	public function getTable($type = 'Mailq', $prefix = 'JCommentsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from($db->quoteName('#__jcomments_mailq'));

		// Filter by search in name or email
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('(' . $db->quoteName('name') . ' LIKE ' . $search .
				' OR ' . $db->quoteName('email') . ' LIKE ' . $search . ')'
			);
		}

		$ordering  = $this->state->get('list.ordering', $db->quoteName('created'));
		$direction = $this->state->get('list.direction', 'ASC');
		$query->order($db->escape($ordering . ' ' . $direction));

		return $query;
	}

	public function purge()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->delete();
		$query->from($db->quoteName('#__jcomments_mailq'));
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		parent::populateState('created', 'desc');
	}
}

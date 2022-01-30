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

class JCommentsModelMailqueues extends JCommentsModelList
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

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				$db->quoteName(
					array('id', 'name', 'email', 'subject', 'body', 'created', 'attempts', 'priority', 'session_id')
				)
			)
		);
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
		$query = $db->getQuery(true)
			->delete()
			->from($db->quoteName('#__jcomments_mailq'));

		$db->setQuery($query);
		$db->execute();

		return true;
	}

	protected function populateState($ordering = 'created', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

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

		return parent::getStoreId($id);
	}
}

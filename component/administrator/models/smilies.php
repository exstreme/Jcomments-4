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

class JCommentsModelSmilies extends JCommentsModelList
{
	protected $context = 'com_jcomments.smilies';

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

	public function getTable($name = '', $prefix = '', $options = array())
	{
		return parent::getTable('Smiley', 'JCommentsTable', $options);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'js.*'
			)
		);
		$query->from($db->quoteName('#__jcomments_smilies') . ' AS js');

		// Join over the users
		$query->select('u.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON u.id = js.checked_out');

		// Filter by published state
		$state = $this->getState('filter.published');

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

	protected function populateState($ordering = 'js.ordering', $direction = 'asc')
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

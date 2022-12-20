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
use Joomla\CMS\MVC\Model\ListModel;

class UsersModel extends ListModel
{
	protected $context = 'com_jcomments.users';

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array('ju.id', 'u.name', 'username');
		}

		parent::__construct($config);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'ju.*'
			)
		);
		$query->from($db->quoteName('#__jcomments_users', 'ju'));

		// Join over the users
		$query->select($db->quoteName('u.name', 'username'))
			->leftJoin($db->quoteName('#__users', 'u'), 'u.id = ju.id');

		// Filter by search in name
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(u.name LIKE ' . $search . ')');
		}

		$ordering  = $this->state->get('list.ordering', 'ju.id');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($ordering . ' ' . $direction));

		return $query;
	}

	protected function populateState($ordering = 'ju.id', $direction = 'asc')
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

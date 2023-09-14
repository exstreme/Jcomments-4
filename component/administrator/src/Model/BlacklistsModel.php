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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\String\StringHelper;

class BlacklistsModel extends ListModel
{
	protected $context = 'com_jcomments.blacklists';

	public function __construct($config = array(), MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'jb.id',
				'ip', 'jb.ip',
				'created', 'jb.created',
				'expire', 'jb.expire',
				'name', 'u.name',
			);
		}

		parent::__construct($config, $factory);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				$db->quoteName(
					array(
						'jb.id', 'jb.ip', 'jb.userid', 'jb.created', 'jb.created_by', 'jb.expire', 'jb.reason',
						'jb.notes', 'jb.checked_out', 'jb.checked_out_time'
					)
				)
			)
		);
		$query->from($db->quoteName('#__jcomments_blacklist', 'jb'));

		// Join over the users
		$query->select($db->quoteName('u.name'));
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON u.id = jb.created_by');

		// Join over the users
		$query->select($db->quoteName('u2.name', 'editor'));
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u2 ON u.id = jb.checked_out');

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'reason:') === 0)
			{
				$search = trim(StringHelper::substr($search, 7));
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('jb.reason LIKE ' . $search);
			}
			elseif (stripos($search, 'notes:') === 0)
			{
				$search = trim(StringHelper::substr($search, 5));
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('jb.notes LIKE ' . $search);
			}
			else
			{
				$search = $db->quote('%' . $db->escape(trim($search), true) . '%');
				$query->where('(LOWER(jb.ip) LIKE ' . $search . ' OR LOWER(jb.reason) LIKE ' . $search . ' OR LOWER(jb.notes) LIKE ' . $search . ')');
			}
		}

		$listOrdering = $this->getState('list.ordering', 'jb.ip');
		$listDirn = $db->escape($this->getState('list.direction', 'ASC'));
		$query->order($db->escape($listOrdering . ' ' . $listDirn));
		$query->group($db->quoteName('jb.id'));

		return $query;
	}

	protected function populateState($ordering = 'jb.ip', $direction = 'asc')
	{
		$app = Factory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// List state information.
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

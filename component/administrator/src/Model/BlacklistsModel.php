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
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $context = 'com_jcomments.blacklists';

	/**
	 * Constructor
	 *
	 * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   1.6
	 * @throws  \Exception
	 */
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
				'username', 'u3.username',
			);
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  \Joomla\Database\DatabaseQuery|string  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
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
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u2 ON u2.id = jb.checked_out');

		// Join over the users
		$query->select($db->quoteName('u3.name', 'login_name') . ', ' . $db->quoteName('u3.username', 'login_username'));
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u3 ON u3.id = jb.userid');

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'reason:') === 0)
			{
				$search = trim(StringHelper::substr($search, 7));
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('jb.reason LIKE ' . $search);
			}
			elseif (stripos($search, 'login:') === 0)
			{
				$search = trim(StringHelper::substr($search, 6));
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('u3.username LIKE ' . $search);
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

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
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

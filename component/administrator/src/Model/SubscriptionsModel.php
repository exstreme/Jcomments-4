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

class SubscriptionsModel extends ListModel
{
	protected $context = 'com_jcomments.subscriptions';

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'js.id',
				'title', 'jo.title',
				'name', 'js.name',
				'email', 'js.email',
				'published', 'js.published',
				'object_group', 'js.object_group',
				'lang', 'js.lang'
			);
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
				array(
					$db->quoteName('js.id'),
					$db->quoteName('js.object_id'),
					$db->quoteName('js.object_group'),
					$db->quoteName('js.lang'),
					$db->quoteName('js.userid'),
					$db->quoteName('js.name'),
					$db->quoteName('js.email'),
					$db->quoteName('js.hash'),
					$db->quoteName('js.published'),
					$db->quoteName('js.source'),
					$db->quoteName('js.checked_out'),
					$db->quoteName('js.checked_out_time')
				)
			)
		);
		$query->from($db->quoteName('#__jcomments_subscriptions', 'js'));

		// Join over the objects
		$query->select('jo.title AS object_title, jo.link AS object_link')
			->leftJoin($db->quoteName('#__jcomments_objects', 'jo'), 'jo.object_id = js.object_id AND jo.object_group = js.object_group AND jo.lang = js.lang');

		// Join over the users
		$query->select($db->quoteName('u.name', 'editor'))
			->leftJoin($db->quoteName('#__users', 'u'), 'u.id = js.checked_out');

		// Join over the language
		$query->select($db->quoteName('l.title', 'language_title'))
			->select($db->quoteName('l.image', 'language_image'))
			->leftJoin($db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('js.lang'));

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('js.published = ' . (int) $published);
		}

		// Filter by component (object group)
		$objectGroup = $this->getState('filter.object_group');

		if ($objectGroup != '')
		{
			$query->where('js.object_group = ' . $db->quote($db->escape($objectGroup)));
		}

		// Filter by language
		$language = $this->getState('filter.language');

		if ($language != '')
		{
			$query->where('js.lang = ' . $db->quote($db->escape($language)));
		}

		// Filter by search in name or email
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('js.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(js.name LIKE ' . $search . ' OR js.email LIKE ' . $search . ')');
			}
		}

		$ordering  = $this->state->get('list.ordering', 'js.name');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($ordering . ' ' . $direction));

		return $query;
	}

	protected function populateState($ordering = 'js.name', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		$objectGroup = $app->getUserStateFromRequest($this->context . '.filter.object_group', 'filter_object_group', '');
		$this->setState('filter.object_group', $objectGroup);

		$language = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

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
		$id .= ':' . $this->getState('filter.object_group');
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
	}
}

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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class CommentsModel extends ListModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $context = 'com_jcomments.comments';

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'jc.id',
				'title', 'jc.title',
				'name', 'jc.name',
				'username', 'jc.username',
				'published', 'jc.published',
				'object_title', 'jo.title',
				'object_group', 'jc.object_group',
				'date', 'jc.date',
				'lang', 'jc.lang',
				'checked_out', 'jc.checked_out',
				'checked_out_time', 'jc.checked_out_time',
				'ip', 'jc.ip',
				'language', 'jc.lang'
			);
		}

		parent::__construct($config);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				array(
					$db->quoteName('jc.id'),
					$db->quoteName('jc.object_id'),
					$db->quoteName('jc.object_group'),
					$db->quoteName('jc.lang'),
					$db->quoteName('jc.userid'),
					$db->quoteName('jc.name'),
					$db->quoteName('jc.username'),
					$db->quoteName('jc.email'),
					$db->quoteName('jc.title'),
					$db->quoteName('jc.comment'),
					$db->quoteName('jc.ip'),
					$db->quoteName('jc.date'),
					$db->quoteName('jc.published'),
					$db->quoteName('jc.deleted'),
					$db->quoteName('jc.checked_out'),
					$db->quoteName('jc.checked_out_time')
				)
			)
		)
			->select('(SELECT COUNT(*) FROM ' . $db->quoteName('#__jcomments_reports') . ' AS r  WHERE r.commentid = jc.id) AS reports')
			->from($db->quoteName('#__jcomments') . ' AS jc');

		// Join over the objects
		$query->select('jo.title as object_title, jo.link as object_link')
			->leftJoin(
				$db->quoteName('#__jcomments_objects', 'jo'),
				'jo.object_id = jc.object_id AND jo.object_group = jc.object_group AND jo.lang = jc.lang'
			);

		// Join over the users
		$query->select($db->quoteName('u.name', 'editor'))
			->leftJoin($db->quoteName('#__users', 'u'), 'u.id = jc.checked_out');

		// Join over the blacklist
		$query->select($db->quoteName('ban.id', 'banned'))
			->leftJoin(
				$db->quoteName('#__jcomments_blacklist', 'ban'),
				'ban.userid = jc.userid AND (ban.expire > NOW() AND ban.expire IS NOT NULL)'
			);

		// Join over the language
		$query->select($db->quoteName('l.title', 'language_title'))
			->select($db->quoteName('l.image', 'language_image'))
			->leftJoin(
				$db->quoteName('#__languages', 'l'),
				$db->quoteName('l.lang_code') . ' = ' . $db->quoteName('jc.lang')
			);

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			if ($published == 2)
			{
				$query->where('EXISTS (SELECT * FROM ' . $db->quoteName('#__jcomments_reports') . ' AS jr WHERE jr.commentid = jc.id)');
			}
			elseif ($published == -1)
			{
				$query->where('jc.deleted = 1');
			}
			else
			{
				$query->where('jc.published = ' . (int) $published);
			}
		}

		// Filter by component (object group)
		$objectGroup = $this->getState('filter.object_group');

		if ($objectGroup != '')
		{
			$query->where('jc.object_group = ' . $db->Quote($db->escape($objectGroup)));
		}

		// Filter by user
		$authors = $this->getState('filter.author_id');
		$authors = ArrayHelper::toInteger($authors);

		if (!empty($authors))
		{
			$query->where('jc.userid IN (' . implode(',', $authors) . ')');
		}

		// Filter by language
		$language = $this->getState('filter.language');

		if ($language != '')
		{
			$query->where('jc.lang = ' . $db->Quote($db->escape($language)));
		}

		// Filter by search in name or email
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('jc.id = ' . (int) StringHelper::substr($search, 3));
			}
			elseif (stripos($search, 'user:') === 0)
			{
				$escaped = $db->Quote('%' . $db->escape(StringHelper::substr($search, 5), true) . '%');
				$query->where('(jc.email LIKE ' . $escaped . ' OR jc.name LIKE ' . $escaped . ' OR jc.username LIKE ' . $escaped . ')');
			}
			elseif (stripos($search, 'object:') === 0)
			{
				$escaped = $db->Quote('%' . $db->escape(StringHelper::substr($search, 7), true) . '%');
				$query->where('jo.title LIKE ' . $escaped);
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('(jc.comment LIKE ' . $search . ' OR jc.title LIKE ' . $search . ')');
			}
		}

		$ordering  = $this->state->get('list.ordering', 'jc.date');
		$direction = $this->state->get('list.direction', 'DESC');
		$query->order($db->escape($ordering . ' ' . $direction));

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
	 * @throws  \Exception
	 * @since   1.6
	 */
	protected function populateState($ordering = 'jc.date', $direction = 'desc')
	{
		$app    = Factory::getApplication();
		$config = ComponentHelper::getParams('com_jcomments');

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		$state = $app->getUserStateFromRequest($this->context . '.filter.ip', 'filter_ip', '', 'string');
		$this->setState('filter.ip', $state);

		$objectGroup = $app->getUserStateFromRequest($this->context . '.filter.object_group', 'filter_object_group', '');
		$this->setState('filter.object_group', $objectGroup);

		$language = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$this->setState('config.comment_title', (int) $config->get('comment_title'));

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
		$id .= ':' . serialize($this->getState('filter.author_id'));
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
	}
}

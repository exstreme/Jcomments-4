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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class JCommentsModelComments extends JCommentsModelList
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
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$reportsSubQuery = ', (SELECT COUNT(*) FROM ' . $db->quoteName('#__jcomments_reports') . ' AS r  WHERE r.commentid = jc.id) AS reports';

		$query->select(
			$this->getState(
				'list.select',
				'jc.*' . $reportsSubQuery
			)
		);
		$query->from($db->quoteName('#__jcomments') . ' AS jc');

		// Join over the objects
		$query->select('jo.title as object_title, jo.link as object_link');
		$query->join('LEFT', $db->quoteName('#__jcomments_objects') . ' AS jo ON jo.object_id = jc.object_id AND jo.object_group = jc.object_group AND jo.lang = jc.lang');

		// Join over the users
		$query->select('u.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON u.id = jc.checked_out');

		// Join over the language
		$query->select($db->quoteName('l.title', 'language_title'))
			->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('jc.lang'));

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
	 * Method to change the published state of one or more records.
	 *
	 * This is necessary because the component need to trigger some events.
	 *
	 * @param   array  $pks    Primary keys array.
	 * @param   int    $value  Publishing state.
	 *
	 * @return  string  A store id.
	 *
	 * @throws  Exception
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
		$user         = Factory::getApplication()->getIdentity();
		$language     = Factory::getApplication()->getLanguage();
		$table        = $this->getTable($this->tableName, $this->tablePrefix);
		$canEditState = Factory::getApplication()->getIdentity()->authorise('core.edit.state', $this->option);

		$lastLanguage = '';

		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if (!$canEditState)
				{
					unset($pks[$i]);
					Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');

					return false;
				}

				if ($table->published != $value)
				{
					$result = JCommentsEventHelper::trigger('onJCommentsCommentBeforePublish', array(&$table));

					if (!in_array(false, $result, true))
					{
						if (!$table->publish(array($pk), $value, $user->get('id')))
						{
							$this->setError($table->getError());

							return false;
						}

						JCommentsEventHelper::trigger('onJCommentsCommentAfterPublish', array(&$table));

						if ($table->published)
						{
							if ($lastLanguage != $table->lang)
							{
								$lastLanguage = $table->lang;
								$language->load('com_jcomments', JPATH_SITE, $table->lang);
							}

							JCommentsNotificationHelper::push(array('comment' => $table));
						}
					}
				}
			}
		}

		return true;
	}

	protected function populateState($ordering = 'jc.date', $direction = 'desc')
	{
		$app    = Factory::getApplication();
		$config = JCommentsFactory::getConfig();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		$state = $app->getUserStateFromRequest($this->context . '.filter.ip', 'filter_ip', '', 'string');
		$this->setState('filter.ip', $state);

		$object_group = $app->getUserStateFromRequest($this->context . '.filter.object_group', 'filter_object_group', '');
		$this->setState('filter.object_group', $object_group);

		$language = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$this->setState('config.comment_title', $config->getInt('comment_title'));

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

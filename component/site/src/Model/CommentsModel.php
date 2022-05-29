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

namespace Joomla\Component\Jcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

/**
 * JComments model
 *
 * @since  4.0
 */
class CommentsModel extends ListModel
{
	/**
	 * Returns a comments count for given object
	 *
	 * @param   array  $options  Array with columns from comments table.
	 *
	 * @return  integer
	 *
	 * @since   4.0
	 */
	public function getCommentsCount(array $options = array()): int
	{
		$db    = $this->getDbo();
		$total = 0;

		try
		{
			$db->setQuery($this->getCommentsCountQuery($options));
			$total = $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
		}

		return $total;
	}

	/**
	 * Method to get a list of articles.
	 *
	 * @return  mixed  An array of objects on success, false on failure.
	 *
	 * @since   1.6
	 * @deprecated 4.0
	 * @todo Need refactoring
	 */
	public function getItems()
	{
		$params = ComponentHelper::getParams('com_jcomments', true);
		/*if (!isset($options['orderBy']))
		{
			$options['orderBy'] = $this->getDefaultOrder();
		}*/

		$db = $this->getDbo();
		$rows = array();
		$pagination = $options['pagination'] ?? '';

		if ($params->get('template_view') == 'tree')
		{
			/*$options['level'] = 0;

			$db->setQuery(self::getCommentsQuery($options));
			$rows = $db->loadObjectList();

			if (count($rows))
			{
				$threads = array();

				foreach ($rows as $row)
				{
					$threads[] = $row->id;
				}

				unset($options['level']);
				unset($options['limit']);

				$options['filter'] = ($options['filter'] ? $options['filter'] . ' AND ' : '') . 'c.thread_id IN (' . implode(', ', $threads) . ')';

				$db->setQuery(self::getCommentsQuery($options));
				$rows = array_merge($rows, $db->loadObjectList());
			}*/
		}

		return parent::getItems();
	}

	public static function getLastComment($objectID, $objectGroup = 'com_content', $parent = 0)
	{
		/** @var DatabaseDriver $db */
		$db      = Factory::getContainer()->get('DatabaseDriver');
		$config  = ComponentHelper::getParams('com_jcomments');
		$comment = null;

		$options['object_id']    = (int) $objectID;
		$options['object_group'] = trim($objectGroup);
		$options['parent']       = (int) $parent;
		$options['published']    = 1;
		$options['orderBy']      = 'c.date DESC';
		$options['limit']        = 1;
		$options['limitStart']   = 0;
		$options['votes']        = (int) $config->get('enable_voting');

		$db->setQuery(self::getCommentsQuery($options));
		$rows = $db->loadObjectList();

		if (count($rows))
		{
			$comment = $rows[0];
		}

		return $comment;
	}

	/**
	 * Delete all comments for given ids
	 *
	 * @param   array  $ids  Array of comments ids.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 * @deprecated 4.0
	 * @todo Need refactoring
	 */
	public static function deleteCommentsByIds($ids)
	{
		if (is_array($ids))
		{
			if (count($ids))
			{
				/** @var DatabaseDriver $db */
				$db = Factory::getContainer()->get('DatabaseDriver');

				$query = $db->getQuery(true)
					->select('DISTINCT object_group, object_id')
					->from($db->quoteName('#__jcomments'))
					->where($db->quoteName('parent') . ' IN (' . implode(',', $ids) . ')');

				$db->setQuery($query);
				$objects = $db->loadObjectList();

				if (count($objects))
				{
					require_once JPATH_ROOT . '/components/com_jcomments/libraries/joomlatune/tree.php';

					$descendants = array();

					foreach ($objects as $o)
					{
						$query = $db->getQuery(true)
							->select($db->quoteName(array('id', 'parent')))
							->from($db->quoteName('#__jcomments'))
							->where($db->quoteName('object_group') . ' = ' . $db->quote($o->object_group))
							->where($db->quoteName('object_id') . ' = ' . (int) $o->object_id);

						$db->setQuery($query);
						$comments = $db->loadObjectList();

						$tree = new JoomlaTuneTree($comments);

						foreach ($ids as $id)
						{
							$descendants = array_merge($descendants, $tree->descendants((int) $id));
						}

						unset($tree);
						$descendants = array_unique($descendants);
					}

					$ids = array_merge($ids, $descendants);
				}

				unset($descendants);

				$ids = implode(',', $ids);

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments'))
					->where($db->quoteName('id') . ' IN (' . $ids . ')');

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('commentid') . ' IN (' . $ids . ')');

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('commentid') . ' IN (' . $ids . ')');

				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	public static function deleteComments($objectID, $objectGroup = 'com_content')
	{
		$objectGroup = trim($objectGroup);
		$objectIDs   = is_array($objectID) ? implode(',', $objectID) : (int) $objectID;

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup))
			->where($db->quoteName('object_id') . ' IN (' . $objectIDs . ')');

		$db->setQuery($query);
		$cids = $db->loadColumn();

		self::deleteCommentsByIds($cids);

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jcomments_objects'))
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup))
			->where($db->quoteName('object_id') . ' IN (' . $objectIDs . ')');

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}
	}

	/**
	 * Build a query for count comments
	 *
	 * @param   array  $options  Filter options
	 *
	 * @return  \Joomla\Database\QueryInterface
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected function getCommentsCountQuery($options)
	{
		$db = $this->getDbo();

		$objectID    = $options['object_id'] ?? null;
		$objectGroup = $options['object_group'] ?? null;
		$published   = $options['published'] ?? null;
		$userid      = $options['userid'] ?? null;
		$parent      = $options['parent'] ?? null;
		$level       = $options['level'] ?? null;
		$lang        = $options['lang'] ?? null;

		/** @see JCommentsPagination::getCommentPage() for example */
		$filter      = @$options['filter'];

		$query = $db->getQuery(true)
			->select('COUNT(c.id)')
			->from($db->quoteName('#__jcomments', 'c'));

		if (!empty($objectID))
		{
			$query->where($db->quoteName('c.object_id') . ' = :oid')
				->bind(':oid', $objectID, ParameterType::INTEGER);
		}

		if (!empty($objectGroup))
		{
			$query->where($db->quoteName('c.object_group') . ' = :ogroup')
				->bind(':ogroup', $objectGroup);
		}

		if ($parent !== null)
		{
			$query->where($db->quoteName('c.parent') . ' = :parent')
				->bind(':parent', $parent, ParameterType::INTEGER);
		}

		if ($level !== null)
		{
			$query->where($db->quoteName('c.level') . ' = :level')
				->bind(':level', $level, ParameterType::INTEGER);
		}

		if ($published !== null)
		{
			$query->where($db->quoteName('c.published') . ' = :state')
				->bind(':state', $published, ParameterType::INTEGER);
		}

		if ($userid !== null)
		{
			$query->where($db->quoteName('c.userid') . ' = :uid')
				->bind(':uid', $userid, ParameterType::INTEGER);
		}

		if (JcommentsFactory::getLanguageFilter())
		{
			if ($lang)
			{
				$query->where($db->quoteName('c.lang') . ' = :lang')
					->bind(':lang', $lang);
			}
		}

		if ($filter != '')
		{
			$query->where($filter);
		}

		return $query;
	}

	/**
	 * Get the master query for retrieving a list of comments subject to the model state.
	 *
	 * @return  \Joomla\Database\DatabaseQuery
	 *
	 * @throws  \Exception
	 * @since   1.6
	 * @deprecated 4.0
	 * @todo Need refactoring
	 */
	protected function getListQuery()
	{
		$db     = $this->getDbo();
		$user   = Factory::getApplication()->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');
		$state  = $this->getState('comments.list.data');

		$objectID    = $state['object_id'] ?? null;
		$objectGroup = $state['object_group'] ?? null;
		/*$parent      = $options['parent'] ?? null;
		$level       = $options['level'] ?? null;
		$userid      = $options['userid'] ?? null;
		$filter      = $options['filter'] ?? null;
		$orderBy     = $options['orderBy'] ?? null;
		$limitStart  = $options['limitStart'] ?? 0;
		$limit       = $options['limit'] ?? null;*/
		$objectinfo  = $state['object_info'] ?? false;

		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'c.id', 'c.parent', 'c.object_id', 'c.object_group', 'c.userid', 'c.name', 'c.username',
						'c.title', 'c.comment', 'c.email', 'c.homepage', 'c.date', 'c.ip', 'c.published', 'c.deleted',
						'c.checked_out', 'c.checked_out_time', 'c.isgood', 'c.ispoor'
					)
				)
			)
			->select($db->quoteName('c.date', 'datetime'));

		if ($params->get('enable_voting') == 1)
		{
			$query->select($db->quoteName('v.value', 'voted'));
			$query->leftJoin($db->quoteName('#__jcomments_votes', 'v'), 'c.id = v.commentid'
				. ($user->get('id')
					? ' AND  v.userid = ' . $user->get('id')
					: ' AND v.userid = 0 AND v.ip = ' . $db->quote(JcommentsFactory::getAcl()->getIP()))
			);
		}
		else
		{
			$query->select('1 AS voted');
		}

		$query->select('CASE WHEN c.parent = 0 THEN UNIX_TIMESTAMP(c.date) ELSE 0 END AS threaddate');

		if ($objectinfo)
		{
			$query->select($db->quoteName('jo.title', 'object_title'))
				->select($db->quoteName('jo.link', 'object_link'))
				->select($db->quoteName('jo.access', 'object_access'));

			$query->leftJoin($db->quoteName('#__jcomments_objects', 'jo'), 'jo.object_id = c.object_id AND jo.object_group = c.object_group AND jo.lang = c.lang');
		}
		else
		{
			$query->select('CAST(NULL AS CHAR(0)) AS object_title')
				->select('CAST(NULL AS CHAR(0)) AS object_link')
				->select('0 AS object_access')
				->select('0 AS object_owner');
		}

		$query->from($db->quoteName('#__jcomments', 'c'));

		if (!empty($objectID))
		{
			$query->where($db->quoteName('c.object_id') . ' = ' . (int) $objectID);
		}

		if (!empty($objectGroup))
		{
			if (is_array($objectGroup))
			{
				$query->where('(' . $db->quoteName('c.object_group') . " = '" . implode("' OR c.object_group = '", $objectGroup) . "')");
			}
			else
			{
				$query->where($db->quoteName('c.object_group') . ' = ' . $db->quote($objectGroup));
			}
		}

		/*if ($parent !== null)
		{
			$query->where($db->quoteName('c.parent') . ' = ' . $parent);
		}

		if ($level !== null)
		{
			$query->where($db->quoteName('c.level') . ' = ' . (int) $level);
		}*/

		$query->where($db->quoteName('c.published') . ' = 1');

		/*if ($userid !== null)
		{
			$query->where($db->quoteName('c.userid') . ' = ' . (int) $userid);
		}*/

		if (JCommentsFactory::getLanguageFilter())
		{
			$language = $options['lang'] ?? Factory::getApplication()->getLanguage()->getTag();
			$query->where($db->quoteName('c.lang') . ' = ' . $db->quote($language));
		}

		/*if ($objectinfo && isset($options['access']))
		{
			if (is_array($options['access']))
			{
				$query->where($db->quoteName('jo.access') . ' IN (' . implode(',', $options['access']) . ')');
			}
			else
			{
				$query->where($db->quoteName('jo.access') . ' <= ' . (int) $options['access']);
			}
		}*/

		/*if ($filter != '')
		{
			$query->where($filter);
		}*/

		$query->order($this->getDefaultOrder());

		/*if ($limit > 0)
		{
			$query->setLimit($limit, $limitStart);
		}*/
echo '<pre>';
echo $query;
echo '</pre>';
		return $query;
	}

	/**
	 * Returns default order for comments list
	 *
	 * @return  string
	 *
	 * @since   3.0
	 * @deprecated 4.0
	 * @todo Need refactoring
	 */
	protected function getDefaultOrder()
	{
		$params = ComponentHelper::getParams('com_jcomments');

		if ($params->get('template_view') == 'tree')
		{
			switch ((int) $params->get('comments_tree_order'))
			{
				case 2:
					$result = 'threadDate DESC, c.date ASC';
					break;
				case 1:
					$result = 'c.parent, c.date DESC';
					break;
				default:
					$result = 'c.parent, c.date ASC';
					break;
			}
		}
		else
		{
			$result = 'c.date ' . $params->get('comments_list_order');
		}

		return $result;
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
	 * @since   3.0.1
	 * @todo Need refactoring
	 */
	/*protected function populateState($ordering = 'c.date', $direction = 'ASC')
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_jcomments');

		// List state information
		/*$value = $app->input->get('limit', $app->get('list_limit', 0), 'uint');
		$this->setState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);*/

		/*$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $params->get('comments_list_order'));
	}*/
}

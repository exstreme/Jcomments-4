<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;

/**
 * JComments model
 *
 * @since   3.0
 */
class JCommentsModel
{
	/**
	 * Returns a comments count for given object
	 *
	 * @param   array    $options  Array with columns from comments table.
	 * @param   boolean  $noCache  Use cached result or not.
	 *
	 * @return  integer
	 *
	 * @since   3.0
	 */
	public static function getCommentsCount($options = array(), $noCache = false)
	{
		static $cache = array();

		$key = md5(serialize($options));

		if (!isset($cache[$key]) || $noCache == true)
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');
			$db->setQuery(self::getCommentsCountQuery($options));
			$cache[$key] = (int) $db->loadResult();
		}

		return $cache[$key];
	}

	/**
	 * Returns list of comments
	 *
	 * @param   array  $options  Array with options.
	 *
	 * @return  array
	 *
	 * @since   3.0
	 */
	public static function getCommentsList($options = array())
	{
		if (!isset($options['orderBy']))
		{
			$options['orderBy'] = self::getDefaultOrder();
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$pagination = $options['pagination'] ?? '';

		if (isset($options['limit']) && $pagination == 'tree')
		{
			$options['level'] = 0;

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
			}
		}
		else
		{
			$db->setQuery(self::getCommentsQuery($options));
			$rows = $db->loadObjectList();
		}

		return $rows;
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

	protected static function getCommentsCountQuery($options)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$objectID    = $options['object_id'] ?? null;
		$objectGroup = $options['object_group'] ?? null;
		$published   = $options['published'] ?? null;
		$userid      = $options['userid'] ?? null;
		$parent      = $options['parent'] ?? null;
		$level       = $options['level'] ?? null;

		/** @see JCommentsPagination::getCommentPage() for example */
		$filter      = @$options['filter'];

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments', 'c'));

		if (!empty($objectID))
		{
			$query->where($db->quoteName('c.object_id') . ' = ' . (int) $objectID);
		}

		if (!empty($objectGroup))
		{
			$query->where($db->quoteName('c.object_group') . ' = ' . $db->quote($objectGroup));
		}

		if ($parent !== null)
		{
			$query->where($db->quoteName('c.parent') . ' = ' . (int) $parent);
		}

		if ($level !== null)
		{
			$query->where($db->quoteName('c.level') . ' = ' . (int) $level);
		}

		if ($published !== null)
		{
			$query->where($db->quoteName('c.published') . ' = ' . (int) $published);
		}

		if ($userid !== null)
		{
			$query->where($db->quoteName('c.userid') . ' = ' . (int) $userid);
		}

		if (JCommentsFactory::getLanguageFilter())
		{
			$query->where($db->quoteName('c.lang') . ' = ' . $db->quote(Factory::getApplication()->getLanguage()->getTag()));
		}

		if ($filter != '')
		{
			$query->where($filter);
		}

		return $query;
	}

	protected static function getCommentsQuery($options)
	{
		/** @var DatabaseDriver $db */
		$db   = Factory::getContainer()->get('DatabaseDriver');
		$user = Factory::getApplication()->getIdentity();

		$objectID    = $options['object_id'] ?? null;
		$objectGroup = $options['object_group'] ?? null;
		$parent      = $options['parent'] ?? null;
		$level       = $options['level'] ?? null;
		$published   = $options['published'] ?? null;
		$userid      = $options['userid'] ?? null;
		$filter      = $options['filter'] ?? null;
		$orderBy     = $options['orderBy'] ?? null;
		$limitStart  = $options['limitStart'] ?? 0;
		$limit       = $options['limit'] ?? null;
		$votes       = $options['votes'] ?? true;
		$objectinfo  = $options['objectinfo'] ?? false;

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

		if ($votes)
		{
			$query->select($db->quoteName('v.value', 'voted'));

			$query->leftJoin($db->quoteName('#__jcomments_votes', 'v') . ' ON c.id = v.commentid '
				. ($user->get('id')
					? " AND  v.userid = " . $user->get('id')
					: " AND v.userid = 0 AND v.ip = '" . $_SERVER['REMOTE_ADDR'] . "'")
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

			$query->leftJoin($db->quoteName('#__jcomments_objects', 'jo') . ' ON jo.object_id = c.object_id AND jo.object_group = c.object_group AND jo.lang = c.lang');
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

		if ($parent !== null)
		{
			$query->where($db->quoteName('c.parent') . ' = ' . $parent);
		}

		if ($level !== null)
		{
			$query->where($db->quoteName('c.level') . ' = ' . (int) $level);
		}

		if ($published !== null)
		{
			$query->where($db->quoteName('c.published') . ' = ' . (int) $published);
		}

		if ($userid !== null)
		{
			$query->where($db->quoteName('c.userid') . ' = ' . (int) $userid);
		}

		if (JCommentsFactory::getLanguageFilter())
		{
			$language = $options['lang'] ?? Factory::getApplication()->getLanguage()->getTag();
			$query->where($db->quoteName('c.lang') . ' = ' . $db->quote($language));
		}

		if ($objectinfo && isset($options['access']))
		{
			if (is_array($options['access']))
			{
				$query->where($db->quoteName('jo.access') . ' IN (' . implode(',', $options['access']) . ')');
			}
			else
			{
				$query->where($db->quoteName('jo.access') . ' <= ' . (int) $options['access']);
			}
		}

		if ($filter != '')
		{
			$query->where($filter);
		}

		$query->order($orderBy);

		if ($limit > 0)
		{
			$query->setLimit($limit, $limitStart);
		}

		return $query;
	}

	/**
	 * Returns default order for comments list
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	protected static function getDefaultOrder()
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if ($config->get('template_view') == 'tree')
		{
			switch ((int) $config->get('comments_tree_order'))
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
			$result = 'c.date ' . $config->get('comments_list_order');
		}

		return $result;
	}
}

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
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPagination;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsTree;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Joomla\Utilities\IpHelper;

/**
 * JComments model
 *
 * @since  4.0
 */
class CommentsModel extends ListModel
{
	/**
	 * @var    integer  Object ID
	 * @since  4.1
	 */
	public $objectId = null;

	/**
	 * @var    string  Object group
	 * @since  4.1
	 */
	public $objectGroup = '';

	/**
	 * @var    array  List of fields to exclude from main query
	 * @since  4.1
	 */
	private $excludeFields = array();

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		$input = Factory::getApplication()->input;

		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'date', 'c.date'
			);
		}

		$this->objectId = $config['object_id'] ?? $input->getInt('id');
		$this->objectGroup = $config['object_group'] ?? $input->getCmd('option');

		if (isset($config['object_title']))
		{
			$this->setState('title', $config['object_title']);
		}

		if (isset($config['exclude_fields']))
		{
			$this->excludeFields = $config['exclude_fields'];
		}

		parent::__construct($config);
	}

	/**
	 * Method to get a Pagination object for the data set.
	 *
	 * @return  Pagination  A Pagination object for the data set.
	 *
	 * @since   1.6
	 */
	public function getPagination()
	{
		// Get a storage key.
		$store = $this->getStoreId('getPagination');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$limit = (int) $this->getState('list.limit') - (int) $this->getState('list.links');

		// Create the pagination object and add the object to the internal cache.
		$this->cache[$store] = new JcommentsPagination($this->getTotal(), $this->getStart(), $limit);

		return $this->cache[$store];
	}

	/**
	 * Method to get a list of comments.
	 *
	 * @return  mixed  An array of objects on success, false on failure.
	 *
	 * @since   4.1
	 */
	public function getItems()
	{
		$db = $this->getDatabase();
		$limit = $this->getState('list.limit');

		// TODO Where is this and for what?
		$pagination = $this->getState('list.options.pagination');

		if (isset($limit) && $pagination == 'tree')
		{
			// Code bellow going from an old Jcomments version and do nothing(?).
			$this->setState('list.options.level', 0);

			$items = parent::getItems();

			if (count($items))
			{
				$threads = array();

				foreach ($items as $item)
				{
					$threads[] = (int) $item->id;
				}

				$this->setState('list.options.level');
				$this->setState('list.options.limit');

				$filter = $this->getState('list.options.filter');
				$this->setState(
					'list.options.filter',
					($filter ? $filter . ' AND ' : '') . $db->quoteName('c.thread_id') . ' IN (' . implode(', ', $threads) . ')'
				);

				try
				{
					// Do not use parent::getItems() because changed model state will be ignored.
					$db->setQuery($this->getListQuery());
					$_items = $db->loadObjectList();

					$items = array_merge($items, $_items);
				}
				catch (\RuntimeException $e)
				{
					Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
				}
			}
		}
		else
		{
			$items = parent::getItems();
		}

		return $items;
	}

	/**
	 * Delete all comments, votes, reports for given comments ids
	 *
	 * @param   array  $ids  Array of comments ids.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	private function deleteCommentsByIds(array $ids): bool
	{
		$ids = ArrayHelper::toInteger($ids);

		if (count($ids))
		{
			$db = $this->getDatabase();

			try
			{
				$query = $db->getQuery(true)
					->select('DISTINCT ' . $db->quoteName('object_group') . ', ' . $db->quoteName('object_id'))
					->from($db->quoteName('#__jcomments'))
					->whereIn($db->quoteName('parent'), $ids);

				$db->setQuery($query);
				$objects = $db->loadObjectList();

				if (count($objects))
				{
					$descendants = array();

					foreach ($objects as $object)
					{
						$query = $db->getQuery(true)
							->select($db->quoteName(array('id', 'parent')))
							->from($db->quoteName('#__jcomments'))
							->where($db->quoteName('object_group') . ' = :ogroup')
							->where($db->quoteName('object_id') . ' = :oid')
							->bind(':ogroup', $object->object_group)
							->bind(':oid', $object->object_id, ParameterType::INTEGER);

						$db->setQuery($query);
						$comments = $db->loadObjectList();

						$tree = new JcommentsTree($comments);

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

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments'))
					->whereIn($db->quoteName('id'), $ids);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->whereIn($db->quoteName('commentid'), $ids);

				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_reports'))
					->whereIn($db->quoteName('commentid'), $ids);

				$db->setQuery($query);
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Delete one comment or list of comments
	 *
	 * @param   mixed   $objectID     Object ID
	 * @param   string  $objectGroup  Object group
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function delete($objectID, string $objectGroup = 'com_content'): bool
	{
		$db          = $this->getDatabase();
		$objectGroup = $db->escape(trim($objectGroup));
		$objectIDs   = is_array($objectID) ? implode(',', $objectID) : array($objectID);
		$objectIDs   = ArrayHelper::toInteger($objectIDs);

		try
		{
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__jcomments'))
				->where($db->quoteName('object_group') . ' = :ogroup')
				->whereIn($db->quoteName('object_id'), $objectIDs)
				->bind(':ogroup', $objectGroup);

			$db->setQuery($query);
			$cids = $db->loadColumn();

			// Try to delete comments, votes, reports
			if (!$this->deleteCommentsByIds($cids))
			{
				return false;
			}

			$queryResult = true;
			$db->lockTable('#__jcomments_objects');
			$db->transactionStart();

			// Delete each object record only if no comments found for this object and id
			foreach ($objectIDs as $_objectID)
			{
				if (\Joomla\Component\Jcomments\Site\Helper\ObjectHelper::getTotalCommentsForObject($_objectID, $objectGroup) === 0)
				{
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__jcomments_objects'))
						->where($db->quoteName('object_id') . ' = :oid')
						->where($db->quoteName('object_group') . ' = :ogroup')
						->bind(':oid', $_objectID, ParameterType::INTEGER)
						->bind(':ogroup', $objectGroup);

					$db->setQuery($query . ';');

					if ($db->execute() === false)
					{
						$queryResult = false;
						break;
					}
				}
			}

			if ($queryResult === false)
			{
				$db->transactionRollback();
				$db->unlockTables();

				return false;
			}
			else
			{
				$db->transactionCommit();
			}

			$db->unlockTables();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Delete user votes.
	 *
	 * @param   array  $pks  An array of record primary keys.
	 *
	 * @return  boolean
	 *
	 * @since   4.1
	 */
	public function deleteVotes(&$pks): bool
	{
		$pks = ArrayHelper::toInteger((array) $pks);
		$db  = $this->getDatabase();
		$uid = Factory::getApplication()->getIdentity()->get('id');

		try
		{
			// Select vote results for selected votes
			$query = $db->getQuery(true)
				->select($db->quoteName(array('v.id', 'v.commentid', 'v.value', 'c.isgood', 'c.ispoor')))
				->from($db->quoteName('#__jcomments_votes', 'v'))
				->leftJoin($db->quoteName('#__jcomments', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('v.commentid'))
				->whereIn($db->quoteName('v.id'), $pks);

			$db->setQuery($query);
			$votes = $db->loadAssocList();

			if (count($votes) < 1)
			{
				return false;
			}

			$queryResult = true;
			$db->lockTable('#__jcomments');
			$db->transactionStart();

			// Generate UPDATE query and run in transaction.
			foreach ($votes as $vote)
			{
				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments'));

				if ((int) $vote['value'] == -1)
				{
					if ($vote['ispoor'] > 0)
					{
						$query->set($db->quoteName('ispoor') . ' = ' . (int) ($vote['ispoor'] - 1));
					}
				}
				elseif ((int) $vote['value'] == 1)
				{
					if ($vote['isgood'] > 0)
					{
						$query->set($db->quoteName('isgood') . ' = ' . (int) ($vote['isgood'] - 1));
					}
				}

				$query->where($db->quoteName('id') . ' = ' . (int) $vote['commentid']);

				$db->setQuery($query . ';');

				if ($db->execute() === false)
				{
					$queryResult = false;
					break;
				}
			}

			if ($queryResult === false)
			{
				$db->transactionRollback();
				$db->unlockTables();

				return false;
			}
			else
			{
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__jcomments_votes'))
					->whereIn($db->quoteName('id'), $pks)
					->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $uid, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();

				$db->transactionCommit();
			}

			$db->unlockTables();
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
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
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.access');

		return parent::getStoreId($id);
	}

	/**
	 * Get the master query for retrieving a list of items subject to the model state.
	 *
	 * @return  \Joomla\Database\QueryInterface
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		if (Factory::getApplication()->input->getCmd('task') == 'votes')
		{
			return $this->getVotesQuery();
		}

		return $this->getCommentsQuery();
	}

	/**
	 * Get the master query for retrieving a list of comments subject to the model state.
	 *
	 * @return  \Joomla\Database\QueryInterface
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	protected function getCommentsQuery()
	{
		$db          = $this->getDatabase();
		$user        = Factory::getApplication()->getIdentity();
		$params      = ComponentHelper::getParams('com_jcomments');
		$objectID    = $this->getState('object_id');
		$objectGroup = $this->getState('object_group');

		$excludeFields = array_map(
			function ($value) use ($db)
			{
				return $db->quoteName($value);
			},
			$this->excludeFields
		);

		$query = $db->getQuery(true)
			->select(
				$this->getState(
					'list.select',
					array_diff(
						array(
							$db->quoteName('c.id'),
							$db->quoteName('c.parent'),
							$db->quoteName('c.object_id'),
							$db->quoteName('c.object_group'),
							$db->quoteName('c.userid'),
							$db->quoteName('c.name'),
							$db->quoteName('c.username'),
							$db->quoteName('c.title'),
							$db->quoteName('c.comment'),
							$db->quoteName('c.email'),
							$db->quoteName('c.homepage'),
							$db->quoteName('c.date'),
							$db->quoteName('c.date', 'datetime'),
							$db->quoteName('c.ip'),
							$db->quoteName('c.published'),
							$db->quoteName('c.deleted'),
							$db->quoteName('c.isgood'),
							$db->quoteName('c.ispoor'),
							$db->quoteName('c.checked_out'),
							$db->quoteName('c.checked_out_time'),
							'CASE WHEN ' . $db->quoteName('c.parent') . ' = 0'
							. ' THEN UNIX_TIMESTAMP(' . $db->quoteName('c.date') . ') ELSE 0 END'
							. ' AS ' . $db->quoteName('threaddate')
						),
						$excludeFields
					)
				)
			);

		$query->from($db->quoteName('#__jcomments', 'c'));

		// Join over the users for check out
		$query->select($db->quoteName('usr_c.name', 'editor'))
			->leftJoin($db->quoteName('#__users', 'usr_c'), 'usr_c.id = c.checked_out');

		// Join over language for comments list in user profile
		$commentLang = $this->getState('list.options.comment_lang');

		if ($commentLang)
		{
			$query->select(
				array(
					$db->quoteName('l.lang_code', 'language'),
					$db->quoteName('l.title', 'language_title'),
					$db->quoteName('l.image', 'language_image')
				)
			)->leftJoin(
				$db->quoteName('#__languages', 'l'),
				$db->quoteName('l.lang_code') . ' = ' . $db->quoteName('c.lang')
			);
		}

		// Join over labels
		$labels = $this->getState('list.options.labels');

		if ($labels)
		{
			$query->select(array($db->quoteName('u.labels'), $db->quoteName('u.terms_of_use')))
				->leftJoin(
					$db->quoteName('#__jcomments_users', 'u'),
					$db->quoteName('u.id') . ' = ' . $db->quoteName('c.userid')
				);
		}

		// Join over blacklist and users
		$blacklist = $this->getState('list.options.blacklist');

		if ($blacklist)
		{
			$query->select('(CASE WHEN ' . $db->quoteName('b.id') . ' > 0 THEN 1 ELSE 0 END) AS ' . $db->quoteName('banned'))
				->leftJoin(
					$db->quoteName('#__jcomments_blacklist', 'b'),
					$db->quoteName('b.userid') . ' = ' . $db->quoteName('c.userid')
					. ' AND ('
					. 'ISNULL(' . $db->quoteName('b.expire') . ')'
					. ' OR ' . $db->quoteName('b.expire') . ' >= NOW()'
					. ')'
				);

			// Get user block state from main user table
			$query->select($db->quoteName('usr.block', 'user_blocked'))
				->leftJoin(
					$db->quoteName('#__users', 'usr'),
					$db->quoteName('usr.id') . ' = ' . $db->quoteName('c.userid')
				);
		}

		$votes = $this->getState('list.options.votes');

		if ($votes == 1)
		{
			$query->select($db->quoteName('v.value', 'voted'));
			$query->leftJoin(
				$db->quoteName('#__jcomments_votes', 'v'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('v.commentid')
				. ($user->get('id')
					? ' AND ' . $db->quoteName('v.userid') . ' = ' . (int) $user->get('id')
					: ' AND ' . $db->quoteName('v.userid') . ' = 0 AND ' . $db->quoteName('v.ip') . ' = ' . $db->quote(IpHelper::getIp()))
			);
		}
		else
		{
			$query->select('1 AS voted');
		}

		$objectInfo = $this->getState('list.options.object_info');

		if ($objectInfo)
		{
			$query->select(
				array(
					$db->quoteName('jo.title', 'object_title'),
					$db->quoteName('jo.link', 'object_link'),
					$db->quoteName('jo.access', 'object_access'),
					$db->quoteName('jo.userid', 'object_owner')
				)
			);

			$query->leftJoin(
				$db->quoteName('#__jcomments_objects', 'jo'),
				$db->quoteName('jo.object_id') . ' = ' . $db->quoteName('c.object_id')
					. ' AND ' . $db->quoteName('jo.object_group') . ' = ' . $db->quoteName('c.object_group')
					. ' AND ' . $db->quoteName('jo.lang') . ' = ' . $db->quoteName('c.lang')
			);
		}
		else
		{
			$query->select('CAST(NULL AS CHAR(0)) AS object_title')
				->select('CAST(NULL AS CHAR(0)) AS object_link')
				->select('0 AS object_access')
				->select('0 AS object_owner');
		}

		if (!empty($objectID))
		{
			$query->where($db->quoteName('c.object_id') . ' = :oid')
				->bind(':oid', $objectID, ParameterType::INTEGER);
		}

		if (!empty($objectGroup))
		{
			if (is_array($objectGroup))
			{
				$filter = InputFilter::getInstance();
				$objectGroup = array_map(
					function ($objectGroup) use ($filter)
					{
						return $filter->clean($objectGroup, 'cmd');
					},
					$objectGroup
				);

				$query->where(
					'(' . $db->quoteName('c.object_group') . " = '"
						. implode("' OR " . $db->quoteName('c.object_group') . " = '", $objectGroup) . "')"
				);
			}
			else
			{
				$query->where($db->quoteName('c.object_group') . ' = :ogroup')
					->bind(':ogroup', $objectGroup);
			}
		}

		$parent = $this->getState('list.options.parent');

		if ($parent !== null)
		{
			$query->where($db->quoteName('c.parent') . ' = :parent')
				->bind(':parent', $parent, ParameterType::INTEGER);
		}

		$level = $this->getState('list.options.level');

		if ($level !== null)
		{
			$query->where($db->quoteName('c.level') . ' = :level')
				->bind(':level', $level, ParameterType::INTEGER);
		}

		$published = $this->getState('list.options.published');

		if ($published !== null)
		{
			$query->where($db->quoteName('c.published') . ' = :state')
				->bind(':state', $published, ParameterType::INTEGER);
		}

		$userid = $this->getState('list.options.userid');

		if ($userid !== null)
		{
			$query->where($db->quoteName('c.userid') . ' = :uid')
				->bind(':uid', $userid, ParameterType::INTEGER);
		}

		if (JcommentsFactory::getLanguageFilter())
		{
			$lang = $this->getState('list.options.lang');

			if ($lang !== null)
			{
				$query->where($db->quoteName('c.lang') . ' = :lang')
					->bind(':lang', $lang);
			}
		}

		$access = $this->getState('list.options.access');

		if ($objectInfo && isset($access))
		{
			if (is_array($access))
			{
				$access = ArrayHelper::toInteger($access);

				$query->whereIn($db->quoteName('jo.access'), $access);
			}
			else
			{
				$query->where($db->quoteName('jo.access') . ' <= :access')
					->bind(':access', $access, ParameterType::INTEGER);
			}
		}

		$filter = $this->getState('list.options.filter');

		if ($filter != '')
		{
			$query->where($filter);
		}

		$query->order(
			$db->escape(
				$this->getDefaultOrder(
					$params->get('template_view'),
					$params->get('comments_' . strtolower($params->get('template_view')) . '_order')
				)
			)
		);

		return $query;
	}

	/**
	 * Get the master query for retrieving a list of votes subject to the model state.
	 *
	 * @return  \Joomla\Database\QueryInterface
	 *
	 * @since   4.1
	 */
	public function getVotesQuery()
	{
		$db       = $this->getDatabase();
		$uid      = $this->getState('list.options.userid');
		$objectId = $this->getState('object_id');

		$query = $db->getQuery(true)
			->select(
				$this->getState(
					'list.select',
					array(
						$db->quoteName('v.id', 'vote_id'),
						$db->quoteName('v.commentid'),
						$db->quoteName('v.userid'),
						$db->quoteName('v.date'),
						$db->quoteName('v.value'),
						$db->quoteName('c.id'),
						$db->quoteName('c.parent'),
						$db->quoteName('c.object_id'),
						$db->quoteName('c.object_group'),
						$db->quoteName('c.userid'),
						$db->quoteName('c.name'),
						$db->quoteName('c.username'),
						$db->quoteName('c.title'),
						$db->quoteName('c.comment'),
						$db->quoteName('c.email'),
						$db->quoteName('c.homepage'),
						$db->quoteName('c.date'),
						$db->quoteName('c.date', 'datetime'),
						$db->quoteName('c.ip'),
						$db->quoteName('c.published'),
						$db->quoteName('c.deleted'),
						$db->quoteName('c.isgood'),
						$db->quoteName('c.ispoor'),
						$db->quoteName('c.checked_out'),
						$db->quoteName('c.checked_out_time')
					)
				)
			)
			->select(
				array(
					$db->quoteName('l.lang_code', 'language'),
					$db->quoteName('l.title', 'language_title'),
					$db->quoteName('l.image', 'language_image')
				)
			)
			->from($db->quoteName('#__jcomments_votes', 'v'))
			->leftJoin(
				$db->quoteName('#__jcomments', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('v.commentid')
			)
			->leftJoin(
				$db->quoteName('#__languages', 'l'),
				$db->quoteName('l.lang_code') . ' = ' . $db->quoteName('c.lang')
			)
			->where($db->quoteName('v.userid') . ' = :uid')
			->bind(':uid', $uid, ParameterType::INTEGER);

		if ($objectId > 0)
		{
			$query->where($db->quoteName('c.object_id') . ' = :oid')
				->bind(':oid', $objectId, ParameterType::INTEGER);
		}

		$objectInfo = $this->getState('list.options.object_info');

		if ($objectInfo)
		{
			$query->select(
				array(
					$db->quoteName('jo.title', 'object_title'),
					$db->quoteName('jo.link', 'object_link'),
					$db->quoteName('jo.access', 'object_access'),
					$db->quoteName('jo.userid', 'object_owner')
				)
			);

			$query->leftJoin(
				$db->quoteName('#__jcomments_objects', 'jo'),
				$db->quoteName('jo.object_id') . ' = ' . $db->quoteName('c.object_id')
				. ' AND ' . $db->quoteName('jo.object_group') . ' = ' . $db->quoteName('c.object_group')
				. ' AND ' . $db->quoteName('jo.lang') . ' = ' . $db->quoteName('c.lang')
			);
		}
		else
		{
			$query->select('CAST(NULL AS CHAR(0)) AS object_title')
				->select('CAST(NULL AS CHAR(0)) AS object_link')
				->select('0 AS object_access')
				->select('0 AS object_owner');
		}

		$query->order($db->escape($this->getDefaultOrder('list', 'DESC')));

		return $query;
	}

	/**
	 * Returns default order for comments list
	 *
	 * @param   string  $listType  List view. Can be 'tree' or 'list'
	 * @param   string  $order     Items ordering
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	protected function getDefaultOrder(string $listType, string $order): string
	{
		$db = $this->getDatabase();

		if ($listType == 'tree')
		{
			switch ($order)
			{
				case 2:
					$result = $db->quoteName('threadDate') . ' DESC, ' . $db->quoteName('c.date') . ' ASC';
					break;
				case 1:
					$result = $db->quoteName('c.parent') . ', ' . $db->quoteName('c.date') . ' DESC';
					break;
				default:
					$result = $db->quoteName('c.parent') . ', ' . $db->quoteName('c.date') . ' ASC';
					break;
			}
		}
		else
		{
			$result = $db->quoteName('c.date') . ' ' . $order;
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
	 * @throws  \Exception
	 * @since   4.1
	 */
	protected function populateState($ordering = 'c.date', $direction = 'ASC')
	{
		parent::populateState($ordering, $direction);

		$app = Factory::getApplication();
		$acl = JcommentsFactory::getAcl();

		// Load the parameters.
		$params = ComponentHelper::getParams('com_jcomments');
		$this->setState('params', $params);

		$objectGroup = $app->input->getCmd('object_group', $this->objectGroup);
		$this->setState('object_group', $objectGroup);

		$objectID = $app->input->getInt('object_id', $this->objectId);
		$this->setState('object_id', $objectID);

		if ($params->get('template_view') == 'list')
		{
			// List state information
			$limit = $app->input->get('jc_limit', $params->get('list_limit', $app->get('list_limit', 0)), 'uint');
			$this->setState('list.limit', $limit);

			$limitstart = $app->input->get('jc_limitstart', 0, 'uint');
			$this->setState('list.start', $limitstart);

			$orderCol = $app->input->get('filter_order', 'c.date');

			if (!in_array($orderCol, $this->filter_fields))
			{
				$orderCol = 'c.date';
			}

			$this->setState('list.ordering', $orderCol);

			$listOrder = $app->input->get('filter_order_Dir', $params->get('comments_list_order'));

			if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
			{
				$listOrder = 'ASC';
			}

			$this->setState('list.direction', $listOrder);
		}
		else
		{
			$this->setState('list.limit', 0);
			$this->setState('list.start', 0);
		}

		$this->setState('list.options.parent');
		$this->setState('list.options.level');
		$this->setState('list.options.published', $acl->canPublish() || $acl->canPublishForObject($objectID, $objectGroup) ? null : 1);
		$this->setState('list.options.userid');
		$this->setState('list.options.access');
		$this->setState('list.options.filter');
		$this->setState('list.options.votes', $params->get('enable_voting', 0));
		$this->setState('list.options.lang', $app->getLanguage()->getTag());
		$this->setState('list.options.object_info', false);
		$this->setState('list.options.labels', true);
		$this->setState('list.options.blacklist', true);
		$this->setState('list.options.comment_lang', true);
	}
}

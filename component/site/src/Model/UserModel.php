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
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Jcomments\Site\Helper\CacheHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Joomla\Utilities\IpHelper;

/**
 * JComments model
 *
 * @since  4.0
 */
class UserModel extends ListModel
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
	 * Method to get simple vote stats.
	 *
	 * @return  object|boolean
	 *
	 * @since   4.1
	 */
	public function getVoteStats()
	{
		$db = $this->getDatabase();
		$userId = Factory::getApplication()->getIdentity()->get('id');

		$query = $db->getQuery(true)
			->select($db->quoteName(array('commentid', 'date', 'value')))
			->from($db->quoteName('#__jcomments_votes'))
			->where($db->quoteName('userid') . ' = :uid')
			->bind(':uid', $userId, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query);
			$result = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		return $result;
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

				// If somehow ispoor or isgood is empty for selected comment skip adding query to queries list and just delete vote.
				try
				{
					$db->setQuery($query . ';');
				}
				catch (\Exception $e)
				{
					continue;
				}

				if ($db->execute() === false)
				{
					$queryResult = false;
					break;
				}
				else
				{
					CacheHelper::removeCachedItem(
						md5('Joomla\Component\Jcomments\Site\Model\CommentModel::getItem' . (int) $vote['commentid']),
						'com_jcomments_comments'
					);
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
							$db->quoteName('c.lang'),
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

		if (Multilanguage::isEnabled())
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

		$query->order($db->escape($db->quoteName($this->getState('list.ordering')) . ' ' . $this->getState('list.direction')));

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

		$query->order($db->escape($db->quoteName($this->getState('list.ordering')) . ' ' . $this->getState('list.direction')));

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

		// List state information
		$limit = $app->input->get('limit', $params->get('list_limit', $app->get('list_limit', 0)), 'uint');
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->get('limitstart', 0, 'uint');
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

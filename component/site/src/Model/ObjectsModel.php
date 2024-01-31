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

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Jcomments\Site\Helper\CacheHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * JComments objects model
 *
 * @since  4.0
 */
class ObjectsModel extends BaseDatabaseModel
{
	/**
	 * Cached item object
	 *
	 * @var    object
	 * @since  1.6
	 */
	protected $_item;

	/**
	 * Get total rows for certain object.
	 *
	 * @param   integer       $objectID     Object ID.
	 * @param   string        $objectGroup  Object group.
	 * @param   integer|null  $state        Comment state.
	 * @param   integer|null  $deleted      Comment is deleted?
	 * @param   string|null   $lang         Object(item) language
	 *
	 * @return  integer
	 *
	 * @since   4.1
	 */
	public function getTotalCommentsForObject(int $objectID, string $objectGroup, ?int $state = null, ?int $deleted = null, ?string $lang = null): int
	{
		$db          = $this->getDatabase();
		$total       = 0;
		$objectGroup = $db->escape($objectGroup);

		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('object_id') . ' = :oid')
			->where($db->quoteName('object_group') . ' = :ogroup')
			->bind(':oid', $objectID, ParameterType::INTEGER)
			->bind(':ogroup', $objectGroup);

		if (!is_null($state))
		{
			$query->where($db->quoteName('published') . ' = :state')
				->bind(':state', $state, ParameterType::INTEGER);
		}

		if (!is_null($deleted))
		{
			$query->where($db->quoteName('deleted') . ' = :deleted')
				->bind(':deleted', $deleted, ParameterType::INTEGER);
		}

		if (!is_null($lang))
		{
			$query->where($db->quoteName('lang') . ' = :lang')
				->bind(':lang', $lang);
		}

		try
		{
			$db->setQuery($query);
			$total = $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
		}

		return $total;
	}

	/**
	 * Clean objects cache.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
	 * @todo Outdated
	 */
	public function cleanObjectsCache(): bool
	{
		$app = Factory::getApplication();
		$db  = $this->getDbo();

		/*if ($app->get('caching') != 0)
		{
			try
			{
				// Clean cache for all object groups
				$query = $db->getQuery(true)
					->select('DISTINCT ' . $db->quoteName('object_group'))
					->from($db->quoteName('#__jcomments_objects'));

				$db->setQuery($query);
				$rows = $db->loadColumn();
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

				return false;
			}

			foreach ($rows as $row)
			{
				$this->cleanCache('com_jcomments_objects_' . strtolower($row));
			}
		}*/

		return true;
	}

	/**
	 * Remove stored objects information from database.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
	 * @todo Outdated
	 */
	public function cleanObjectsTable(): bool
	{
		$db  = $this->getDbo();

		/*try
		{
			$db->setQuery('TRUNCATE TABLE ' . $db->quoteName('#__jcomments_objects'));
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}*/

		return true;
	}

	/**
	 * Get object infromation from cache or database.
	 *
	 * @param   integer  $objectID     Object ID
	 * @param   string   $objectGroup  Object group. E.g. com_content
	 * @param   mixed    $language     Object language tag or null
	 *
	 * @return  object|null
	 *
	 * @since   4.1
	 */
	public function getItem(int $objectID, string $objectGroup, $language): ?object
	{
		if (!isset($this->_item))
		{
			$app         = Factory::getApplication();
			$db          = $this->getDatabase();
			$language    = empty($language) ? $app->getLanguage()->getTag() : $db->escape($language);
			$filter      = InputFilter::getInstance();
			$objectGroup = StringHelper::strtolower($filter->clean($objectGroup));
			$cacheGroup  = 'com_jcomments_objects_' . $objectGroup;
			$cacheId     = md5(__METHOD__ . $objectID);

			// WARNING! Do not use createCacheController()->get() as it will lead to create empty cached object
			$cache = Factory::getCache($cacheGroup, '');

			if ($cache->contains($cacheId))
			{
				$this->_item = $cache->get($cacheId);
			}
			else
			{
				$query = $db->getQuery(true);
				$objectGroup = $db->escape($objectGroup);

				$query->select(
					array(
						$db->quoteName('id'),
						$db->quoteName('object_id'),
						$db->quoteName('object_group'),
						$db->quoteName('category_id', 'catid'),
						$db->quoteName('lang', 'object_lang'),
						$db->quoteName('title', 'object_title'),
						$db->quoteName('link', 'object_link'),
						$db->quoteName('access', 'object_access'),
						$db->quoteName('userid', 'object_owner'),
						$db->quoteName('expired'),
						$db->quoteName('modified')
					)
				)
					->from($db->quoteName('#__jcomments_objects'))
					->where($db->quoteName('object_id') . ' = :id')
					->where($db->quoteName('object_group') . ' = :group')
					->where($db->quoteName('lang') . ' = :lang')
					->bind(':id', $objectID, ParameterType::INTEGER)
					->bind(':group', $objectGroup)
					->bind(':lang', $language);

				$db->setQuery($query);

				$this->_item = $db->loadObject();

				if (!empty($this->_item))
				{
					$cache->store($this->_item, $cacheId);
				}
			}
		}

		return $this->_item;
	}

	/**
	 * Update informations in object table. Can fix duplicated comment records.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
	 * @todo Outdated
	 */
	public function refreshObjectsData(/*$step, $lang = ''*/)
	{
		$db    = $this->getDbo();
		$total = $this->countObjectsWithoutInfo();

		if ($total > 0)
		{
			try
			{
				// Get list of first objects without information
				$query = $db->getQuery(true)
					->select('DISTINCT ' . implode(', ', $db->quoteName(array('c.object_id', 'c.object_group', 'c.lang'))))
					->from($db->quoteName('#__jcomments', 'c'))
					->where('IFNULL(' . $db->quoteName('c.lang') . ', "") <> ""');

				$subquery = $db->getQuery(true)
					->select($db->quoteName('o.id'))
					->from($db->quoteName('#__jcomments_objects', 'o'))
					->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.object_id'))
					->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.object_group'))
					->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.lang'));

				$query->where('NOT EXISTS (' . $subquery . ')')
					->order($db->quoteName(array('c.object_group', 'c.lang')));

				$db->setQuery($query);
				$rows = $db->loadObjectList();

				if (count($rows))
				{
					foreach ($rows as $row)
					{
						if ($nextLanguage != $row->lang && $multilanguage)
						{
							$nextLanguage = $row->lang;
							//break;
						}

						// Retrieve and store object information
						//$this->storeObjectInfo($row->object_id, $row->object_group, $row->lang, false, true);
					}
				}

				// TODO Использовать транзакции и генерацию запросов в цикле.
			}
			catch (\RuntimeException $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

				return false;
			}
		}

		return true;
	}

	/**
	 * Count objects without information.
	 *
	 * @return  integer
	 *
	 * @throws  \Exception
	 * @since   4.0
	 * @todo Outdated
	 */
	public function countObjectsWithoutInfo(): int
	{
		$db = $this->getDbo();

		try
		{
			$query = $db->getQuery(true)
				->clear()
				->select('COUNT(DISTINCT ' . implode(', ', $db->quoteName(array('c.object_id', 'c.object_group', 'c.lang'))) . ')')
				->from($db->quoteName('#__jcomments', 'c'))
				->where('IFNULL(' . $db->quoteName('c.lang') . ', "") <> ""');

			$db->setQuery($query);
			$total = (int) $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			$total = 0;
		}

		return $total;
	}

	/**
	 * Update informations in object table. Can fix duplicated comment records.
	 *
	 * @return  array|boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	/*public function refreshObjectsData($step, $lang = '')
	{
		$db = $this->getDbo();

		try
		{
			// Count objects without information
			$query = $db->getQuery(true)
				->clear()
				->select('COUNT(DISTINCT ' . implode(', ', $db->quoteName(array('c.object_id', 'c.object_group', 'c.lang'))) . ')')
				->from($db->quoteName('#__jcomments', 'c'))
				->where('IFNULL(' . $db->quoteName('c.lang') . ', "") <> ""');

			$db->setQuery($query);
			$total = (int) $db->loadResult();
			$count = 0;

			if ($total > 0)
			{
				// Get list of first objects without information
				$query = $db->getQuery(true)
					->select('DISTINCT ' . implode(', ', $db->quoteName(array('c.object_id', 'c.object_group', 'c.lang'))))
					->from($db->quoteName('#__jcomments', 'c'))
					->where('IFNULL(' . $db->quoteName('c.lang') . ', "") <> ""');

				$subquery = $db->getQuery(true)
					->select($db->quoteName('o.id'))
					->from($db->quoteName('#__jcomments_objects', 'o'))
					->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.object_id'))
					->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.object_group'))
					->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.lang'));

				$query->where('NOT EXISTS (' . $subquery . ')')
					->order($db->quoteName(array('c.object_group', 'c.lang')));

				$db->setQuery($query, 0, $count);
				$rows = $db->loadObjectList();

				$i             = 0;
				$multilanguage = JcommentsFactory::getLanguageFilter();
				$nextLanguage  = $lang;

				if (count($rows))
				{
					foreach ($rows as $row)
					{
						if ($nextLanguage != $row->lang && $multilanguage)
						{
							$nextLanguage = $row->lang;
							//break;
						}

						// Retrieve and store object information
						$this->storeObjectInfo($row->object_id, $row->object_group, $row->lang, false, true);
						$i++;
					}
				}

				if ($i > 0)
				{
					$query = $db->getQuery(true)
						->select('COUNT(id)')
						->from($db->quoteName('#__jcomments_objects'));

					$db->setQuery($query);
					$count = (int) $db->loadResult();
				}

				$percent = ceil(($count / $total) * 100);
				$percent = min($percent, 100);
			}
			else
			{
				$percent = 100;
			}

			$step++;

			$langCodes   = LanguageHelper::getLanguages('lang_code');
			$languageSef = isset($langCodes[$nextLanguage]) ? $langCodes[$nextLanguage]->sef : $nextLanguage;
			$data        = array(
				'count'        => $count,
				'total'        => $total,
				'percent'      => $percent,
				'step'         => $step,
				'object_group' => null,
				'lang'         => $nextLanguage,
				'lang_sef'     => $languageSef
			);
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return $data;
	}*/

	/**
	 * Save object information into database.
	 *
	 * @param   integer|null  $objectID    Object ID.
	 * @param   object        $objectInfo  Object with information.
	 *
	 * @return  boolean|object  Object on insert, true on update, false on error.
	 *
	 * @since   4.0
	 */
	public function save(?int $objectID, object $objectInfo)
	{
		$db          = $this->getDatabase();
		$query       = $db->getQuery(true);
		$modified    = Factory::getDate()->toSql();
		$objectGroup = $db->escape($objectInfo->object_group);

		if ($objectGroup == 'com_content')
		{
			$expired = $objectInfo->expired;
		}
		else
		{
			$expired = null;
		}

		// Load object information from database(not cache) to test if record for certain object ID and object group exists.
		$_objectInfo = $this->getItem($objectInfo->object_id, $objectGroup, $objectInfo->object_lang);

		if (!empty($objectID) && !empty($_objectInfo))
		{
			$query->update($db->quoteName('#__jcomments_objects'))
				->set($db->quoteName('access') . ' = :access')
				->set($db->quoteName('userid') . ' = :uid')
				->set($db->quoteName('expired') . ' = :expired')
				->set($db->quoteName('modified') . ' = :modified')
				->bind(':access', $objectInfo->object_access, ParameterType::INTEGER)
				->bind(':uid', $objectInfo->object_owner, ParameterType::INTEGER)
				->bind(':expired', $expired)
				->bind(':modified', $modified);

			if (!empty($objectInfo->object_title))
			{
				$query->set($db->quoteName('title') . ' = :title')
					->bind(':title', $objectInfo->object_title);
			}

			if (!empty($objectInfo->object_link))
			{
				$query->set($db->quoteName('link') . ' = :link')
					->bind(':link', $objectInfo->object_link);
			}

			if (!empty($objectInfo->catid))
			{
				$query->set($db->quoteName('category_id') . ' = :catid')
					->bind(':catid', $objectInfo->catid, ParameterType::INTEGER);
			}

			$query->where($db->quoteName('object_id') . ' = :oid')
				->where($db->quoteName('object_group') . ' = :ogroup')
				->bind(':oid', $objectID, ParameterType::INTEGER)
				->bind(':ogroup', $objectGroup);
		}
		else
		{
			$id          = null;
			$lang        = $db->escape($objectInfo->object_lang);
			$title       = $db->escape($objectInfo->object_title);
			$link        = $db->escape($objectInfo->object_link);
			$modified    = $db->escape($modified);
			$query->insert($db->quoteName('#__jcomments_objects'))
				->columns(
					$db->quoteName(
						array(
							'id', 'object_id', 'object_group', 'category_id', 'lang', 'title', 'link', 'access',
							'userid', 'expired', 'modified'
						)
					)
				)
				->values(':id, :oid, :ogroup, :cat, :lang, :title, :link, :access, :uid, :expired, :modified')
				->bind(':id', $id)
				->bind(':oid', $objectInfo->object_id, ParameterType::INTEGER)
				->bind(':ogroup', $objectGroup)
				->bind(':cat', $objectInfo->catid, ParameterType::INTEGER)
				->bind(':lang', $lang)
				->bind(':title', $title)
				->bind(':link', $link)
				->bind(':access', $objectInfo->object_access, ParameterType::INTEGER)
				// Userid should be placed in quotes because for guest it will be -1 and throws an 'out of range' error.
				->bind(':uid', $objectInfo->object_owner)
				->bind(':expired', $expired)
				->bind(':modified', $modified);
		}

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		if (empty($objectID))
		{
			// Clean cache before store new cache object
			CacheHelper::removeCachedItem('', 'com_jcomments_comments');
			CacheHelper::removeCachedItem('', 'com_jcomments_objects');

			$filter      = InputFilter::getInstance();
			$objectGroup = strtolower($filter->clean($objectInfo->object_group));
			$cacheGroup  = 'com_jcomments_objects_' . $objectGroup;
			$cacheId     = md5('Joomla\Component\Jcomments\Site\Model\ObjectsModel::getItem' . (int) $objectInfo->object_id);
			$data        = (object) array(
				'id'           => $db->insertid(),
				'object_id'    => (int) $objectInfo->object_id,
				'object_group' => $objectGroup,
				'category_id'  => $objectInfo->catid,
				'lang'         => $objectInfo->object_lang,
				'title'        => $objectInfo->object_title,
				'link'         => $objectInfo->object_link,
				'access'       => $objectInfo->object_access,
				'userid'       => $objectInfo->object_owner,
				'expired'      => $expired,
				'modified'     => $modified
			);

			// WARNING! Do not use createCacheController()->store() as it will lead to create wrong cached object
			$cache = Factory::getCache($cacheGroup, '');
			$cache->store($data, $cacheId);

			return $data;
		}

		return true;
	}

	/**
	 * Stores object information (inserts new or updates existing)
	 *
	 * @param   integer  $objectID     Object ID.
	 * @param   string   $objectGroup  Object group, e.g. com_content
	 * @param   string   $language     Language tag
	 * @param   boolean  $cleanCache
	 * @param   boolean  $allowEmpty
	 *
	 * @return  JCommentsObjectInfo
	 *
	 * @throws  \Exception
	 * @since   4.0
	 * @todo Outdated
	 */
	public function storeObjectInfo($objectID, $objectGroup = 'com_content', $language = null, $cleanCache = false, $allowEmpty = false)
	{
		$app = Factory::getApplication();

		if (empty($language))
		{
			$language = $app->getLanguage()->getTag();
		}

		// Try to load object information from database
		$object   = $this->getObjectInfo($objectID, $objectGroup, $language);
		$objectId = $object === false ? 0 : $object->id;

		if ($objectId == 0 && $app->isClient('administrator'))
		{
			// Return empty object because we can not create link in backend
			return new JCommentsObjectInfo;
		}

		// Get object information via plugins
		$info = ObjectHelper::getObjectInfoFromPlugin($objectID, $objectGroup, $language);

		if (!ObjectHelper::isEmpty($info) || $allowEmpty)
		{
			if ($app->isClient('administrator'))
			{
				// We do not have to update object's link from backend
				$info->link = null;
			}

			// Insert/update object information
			$this->saveObjectInfo($objectId, $info);

			if ($cleanCache)
			{
				// Clean cache for given object group
				$this->cleanCache('com_jcomments_objects_' . strtolower($objectGroup));
			}
		}

		return $info;
	}

	/**
	 * Update object link field for all rows in table for certain object id and group.
	 *
	 * NOTE! Only com_categories supported.
	 *
	 * @param   integer  $id           Object ID
	 * @param   string   $objectGroup  Object group. E.g. com_content
	 * @param   mixed    $language     Object language tag or null
	 *
	 * @return  boolean
	 *
	 * @since   4.1
	 */
	public function updateLink(int $id, string $objectGroup = 'com_content', $language = null)
	{
		$db          = $this->getDatabase();
		$query       = $db->getQuery(true);
		$objectGroup = $db->escape($objectGroup);

		try
		{
			$query->select(
				$db->quoteName(
					array(
						'id', 'object_id', 'object_group', 'category_id', 'lang', 'title', 'link', 'access',
						'userid', 'expired', 'modified'
					)
				)
			)->from($db->quoteName('#__jcomments_objects'));

			if ($objectGroup == 'com_categories')
			{
				$query->where($db->quoteName('category_id') . ' = :catid')
					->where($db->quoteName('object_group') . ' = ' . $db->quote('com_content'))
					->bind(':catid', $id, ParameterType::INTEGER);
			}

			if (!empty($lang))
			{
				$query->where($db->quoteName('lang') . ' = :lang')
					->bind(':lang', $language);
			}

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			/** @var \Joomla\Component\Jcomments\Administrator\Table\ObjectTable $table */
			$table = $this->getTable('Object', 'Administrator');

			foreach ($rows as $row)
			{
				if ($objectGroup == 'com_categories')
				{
					$objectInfo = ObjectHelper::getObjectInfoFromPlugin($row->object_id, $row->object_group, $row->lang);

					if ($table->load($row->id))
					{
						$table->link = $objectInfo->link;
						$table->store();
					}
				}
			}
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
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
	 * @return  void
	 *
	 * @since   4.1
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		$objectGroup = $app->input->getCmd('object_group', $app->input->getCmd('option'));
		$this->setState('object_group', $objectGroup);

		$objectID = $app->input->getInt('object_id', $app->input->getInt('id'));
		$this->setState('object_id', $objectID);
	}
}

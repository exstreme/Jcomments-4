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

use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Database\ParameterType;

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
	 * Clean objects cache.
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.0
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
	 * @param   boolean  $useCache     Load infromation from cache. If set to false when information will be loaded from DB.
	 *
	 * @return  object
	 *
	 * @since   3.0
	 */
	public function &getItem(int $objectID, string $objectGroup, $language, bool $useCache = true)
	{
		if (!isset($this->_item))
		{
			$app      = Factory::getApplication();
			$db       = $this->getDatabase();
			$user     = $app->getIdentity();
			$language = empty($language) ? $app->getLanguage()->getTag() : $language;

			/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
			$cache = Factory::getCache('com_jcomments_objects_' . strtolower($objectGroup), 'callback');

			$loader = function ($objectID, $objectGroup, $language, $user) use ($db)
			{
				$query = $db->getQuery(true);

				$query->select(
					$db->quoteName(
						array(
							'id', 'object_id', 'object_group', 'category_id', 'lang', 'title', 'link', 'access',
							'userid', 'expired', 'modified'
						)
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

				return $db->loadObject();
			};

			if ($useCache === false)
			{
				$this->_item = $loader($objectID, $objectGroup, $language, $user);
			}
			else
			{
				try
				{
					$this->_item = $cache->get($loader, array($objectID, $objectGroup, $language, $user), md5(__METHOD__ . $objectID));
				}
				catch (CacheExceptionInterface $e)
				{
					$this->_item = $loader($objectID, $objectGroup, $language, $user);
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
	 * @param   integer  $objectID  Object ID.
	 * @param   object   $info      Object with information.
	 *
	 * @return  boolean
	 *
	 * @since   4.0
	 */
	public function save($objectID, $info): bool
	{
		$db       = $this->getDbo();
		$query    = $db->getQuery(true);
		$modified = Factory::getDate()->toSql();

		if (!empty($objectID))
		{
			$query->update($db->quoteName('#__jcomments_objects'))
				->set($db->quoteName('access') . ' = :access')
				->set($db->quoteName('userid') . ' = :uid')
				->set($db->quoteName('expired') . ' = "0"')
				->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()))
				->bind(':access', $info->access, ParameterType::INTEGER)
				->bind(':uid', $info->userid, ParameterType::INTEGER)
				->bind(':modified', $modified);

			if (empty($info->title))
			{
				$query->set($db->quoteName('title') . ' = :title')
					->bind(':title', $info->title);
			}

			if (empty($info->link))
			{
				$query->set($db->quoteName('link') . ' = :link')
					->bind(':link', $info->link);
			}

			if (empty($info->category_id))
			{
				$query->set($db->quoteName('category_id') . ' = :catid')
					->bind(':catid', $info->category_id, ParameterType::INTEGER);
			}

			$query->where($db->quoteName('id') . ' = :oid')
				->bind(':oid', $objectID, ParameterType::INTEGER);
		}
		else
		{
			$query->insert($db->quoteName('#__jcomments_objects'))
				->set($db->quoteName('object_id') . ' = ' . (int) $info->object_id)
				->set($db->quoteName('object_group') . ' = ' . $db->quote($info->object_group))
				->set($db->quoteName('category_id') . ' = ' . (int) $info->category_id)
				->set($db->quoteName('lang') . ' = ' . $db->quote($info->lang))
				->set($db->quoteName('title') . ' = ' . $db->quote($info->title))
				->set($db->quoteName('link') . ' = ' . $db->quote($info->link))
				->set($db->quoteName('access') . ' = ' . (int) $info->access)
				// Userid should be placed in quotes because for guest it will be -1 and throws an 'out of range' error.
				->set($db->quoteName('userid') . ' = ' . $db->quote($info->userid))
				->set($db->quoteName('expired') . ' = 0')
				->set($db->quoteName('modified') . ' = ' . $db->quote($modified));
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

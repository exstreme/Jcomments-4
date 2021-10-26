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

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Factory;

require_once JPATH_ROOT . '/components/com_jcomments/models/object.php';
require_once JPATH_ROOT . '/components/com_jcomments/classes/objectinfo.php';

/**
 * JComments objects frontend helper
 *
 * @since  3.0
 */
class JCommentsObject
{
	/**
	 * Returns title for given object
	 *
	 * @param   integer  $objectID     Object ID.
	 * @param   string   $objectGroup  Object group, e.g. com_content
	 * @param   string   $language     Language tag
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public static function getTitle($objectID, $objectGroup = 'com_content', $language = null)
	{
		$info = self::getObjectInfo($objectID, $objectGroup, $language);

		return $info->title;
	}

	/**
	 * Returns URI for given object
	 *
	 * @param   integer  $objectID     Object ID.
	 * @param   string   $objectGroup  Object group, e.g. com_content
	 * @param   string   $language     Language tag
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public static function getLink($objectID, $objectGroup = 'com_content', $language = null)
	{
		$info = self::getObjectInfo($objectID, $objectGroup, $language);

		return $info->link;
	}

	/**
	 * Returns identifier of user who is owner of an object
	 *
	 * @param   integer  $objectID     Object ID.
	 * @param   string   $objectGroup  Object group, e.g. com_content
	 * @param   string   $language     Language tag
	 *
	 * @return  integer
	 *
	 * @since   3.0
	 */
	public static function getOwner($objectID, $objectGroup = 'com_content', $language = null)
	{
		$info = self::getObjectInfo($objectID, $objectGroup, $language);

		return $info->userid;
	}

	protected static function _call($class, $methodName, $args = array())
	{
		if (!is_callable(array($class, $methodName)))
		{
			$class = new JCommentsPlugin;
		}

		return call_user_func_array(array($class, $methodName), $args);
	}

	protected static function _loadObjectInfo($objectID, $objectGroup = 'com_content', $language = null)
	{
		static $plugins = array();
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		// Get object information via plugins
		if (!isset($plugins[$objectGroup]))
		{
			ob_start();
			include_once JPATH_ROOT . '/components/com_jcomments/plugins/' . $objectGroup . '.plugin.php';
			ob_end_clean();

			$className = 'jc_' . $objectGroup;

			if (class_exists($className))
			{
				$plugins[$objectGroup] = $className;
			}
			else
			{
				$plugins[$objectGroup] = 'JCommentsPlugin';
			}
		}

		$className = $plugins[$objectGroup];
		$class     = new $className;

		if (is_callable(array($class, 'getObjectInfo')))
		{
			// Retrieve object information via getObjectInfo plugin's method
			$info = self::_call($class, 'getObjectInfo', array($objectID, $language));
		}
		else
		{
			// Retrieve object information via separate plugin's methods (old plugins)
			$info = new JCommentsObjectInfo;

			$info->title  = self::_call($class, 'getObjectTitle', array($objectID, $language));
			$info->link   = self::_call($class, 'getObjectLink', array($objectID, $language));
			$info->userid = self::_call($class, 'getObjectOwner', array($objectID, $language));
		}

		$info->lang         = $language;
		$info->object_id    = $objectID;
		$info->object_group = $objectGroup;

		return $info;
	}

	public static function fetchObjectInfo($objectID, $objectGroup = 'com_content', $language = null)
	{
		$object = JCommentsModelObject::getObjectInfo($objectID, $objectGroup, $language);

		if ($object !== false)
		{
			// Use object information stored in database
			$info = new JCommentsObjectInfo($object);
		}
		else
		{
			// Get object information via plugins
			$info = self::_loadObjectInfo($objectID, $objectGroup, $language);

			if (!JCommentsModelObject::isEmpty($info))
			{
				if (!Factory::getApplication()->isClient('administrator'))
				{
					// Insert object information
					JCommentsModelObject::setObjectInfo(0, $info);
				}
			}
		}

		return $info;
	}

	/**
	 * Returns object information
	 *
	 * @param   integer  $objectID     Object ID.
	 * @param   string   $objectGroup  Object group, e.g. com_content
	 * @param   string   $language     Language tag
	 * @param   boolean  $useCache     Use cache
	 *
	 * @return  JCommentsObjectInfo
	 *
	 * @since   3.0
	 */
	public static function getObjectInfo($objectID, $objectGroup = 'com_content', $language = null, $useCache = true)
	{
		static $info = array();

		if (empty($language))
		{
			$language = Factory::getApplication()->getLanguage()->getTag();
		}

		$key = md5($objectGroup . '_' . $objectID . '_' . ($language ?: ''));

		if (!isset($info[$key]))
		{
			if ($useCache)
			{
				/** @var CallbackController $cache */
				$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
					->createCacheController('callback', ['defaultgroup' => 'com_jcomments_objects_' . strtolower($objectGroup)]);
				$info[$key] = $cache->get(array('JCommentsObject', 'fetchObjectInfo'), array($objectID, $objectGroup, $language));
			}
			else
			{
				$info[$key] = self::fetchObjectInfo($objectID, $objectGroup, $language);
			}
		}

		return $info[$key];
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
	 * @since   3.0
	 */
	public static function storeObjectInfo($objectID, $objectGroup = 'com_content', $language = null, $cleanCache = false, $allowEmpty = false)
	{
		$app = Factory::getApplication();

		if (empty($language))
		{
			$language = $app->getLanguage()->getTag();
		}

		// Try to load object information from database
		$object   = JCommentsModelObject::getObjectInfo($objectID, $objectGroup, $language);
		$objectId = $object === false ? 0 : $object->id;

		if ($objectId == 0 && $app->isClient('administrator'))
		{
			// Return empty object because we can not create link in backend
			return new JCommentsObjectInfo;
		}

		// Get object information via plugins
		$info = self::_loadObjectInfo($objectID, $objectGroup, $language);

		if (!JCommentsModelObject::isEmpty($info) || $allowEmpty)
		{
			if ($app->isClient('administrator'))
			{
				// We do not have to update object's link from backend
				$info->link = null;
			}

			// Insert/update object information
			JCommentsModelObject::setObjectInfo($objectId, $info);

			if ($cleanCache)
			{
				// Clean cache for given object group
				/** @var CallbackController $cache */
				$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
					->createCacheController('callback', ['defaultgroup' => 'com_jcomments_objects_' . strtolower($objectGroup)]);

				/** @var Cache $cache */
				$cache->clean();
			}
		}

		return $info;
	}
}

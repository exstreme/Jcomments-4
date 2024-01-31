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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Filesystem\File;
use Joomla\Utilities\ArrayHelper;

/**
 * JComments objects frontend helper
 *
 * @since  4.0
 */
class ObjectHelper
{
	/**
	 * An array to hold plugin classes
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected static $plugins = array();

	/**
	 * Get one field from object item. Load object infromation from database if 'Object info' empty.
	 *
	 * @param   mixed        $objectInfo   Object info
	 * @param   string       $field        Object field
	 * @param   int|null     $objectID     Object ID
	 * @param   string|null  $objectGroup  Option, e.g. com_content
	 * @param   mixed        $language     Content(item) language tag
	 *
	 * @return  mixed
	 *
	 * @since   4.1
	 */
	public static function getObjectField($objectInfo, string $field, ?int $objectID, ?string $objectGroup = 'com_content', $language = null)
	{
		if (!is_object($objectInfo))
		{
			$objectInfo = self::getObjectInfo($objectID, $objectGroup, $language);
		}

		if (!property_exists($objectInfo, $field))
		{
			$objectInfo->$field = null;
		}

		return $objectInfo->$field;
	}

	/**
	 * Proxy for Joomla\Component\Jcomments\Site\Model\ObjectsModel::getTotalCommentsForObject()
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
	public static function getTotalCommentsForObject(int $objectID, string $objectGroup, ?int $state = null, ?int $deleted = null, ?string $lang = null): int
	{
		/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
		$model  = Factory::getApplication()->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Objects', 'Site', ['ignore_request' => true]);

		return $model->getTotalCommentsForObject($objectID, $objectGroup, $state, $deleted, $lang);
	}

	/**
	 * Checking if object have title and link
	 *
	 * @param   object|array  $object  Object(comment object) or array with information.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public static function isEmpty($object): bool
	{
		if (is_array($object))
		{
			$object = ArrayHelper::toObject($object);
		}

		$titleKey = property_exists($object, 'object_title') ? 'object_title' : 'title';
		$linkKey = property_exists($object, 'object_link') ? 'object_link' : 'link';

		return empty($object->{$titleKey}) || empty($object->{$linkKey});
	}

	/**
	 * Call methods from jcomments plugins.
	 *
	 * @param   object  $class   Class name to init.
	 * @param   string  $method  Method to call.
	 * @param   array   $args    Arguments
	 *
	 * @return  mixed
	 *
	 * @since   4.0
	 */
	protected static function call($class, string $method, array $args = array())
	{
		if (!is_callable(array($class, $method)))
		{
			$class = new JcommentsPlugin;
		}

		return call_user_func_array(array($class, $method), $args);
	}

	/**
	 * Get object information via plugin
	 *
	 * @param   integer  $objectID     Object(article) ID.
	 * @param   string   $objectGroup  Option, e.g. com_content
	 * @param   mixed    $language     Content(item) language tag
	 *
	 * @return  mixed
	 *
	 * @since   3.0
	 */
	public static function getObjectInfoFromPlugin(int $objectID, string $objectGroup = 'com_content', $language = null)
	{
		$plugin = self::loadClass($objectGroup);

		// Retrieve object information via getObjectInfo plugin's method
		$info = self::call($plugin, 'getObjectInfo', array($objectID, $language));

		if (is_null($info))
		{
			$info = new \StdClass;
		}

		$info->object_lang  = !is_null($info->object_lang) ? $info->object_lang : $language;
		$info->object_id    = $objectID;
		$info->object_group = $objectGroup;

		return $info;
	}

	/**
	 * Returns object information
	 *
	 * @param   int|null     $objectID     Object ID
	 * @param   string|null  $objectGroup  Option, e.g. com_content
	 * @param   mixed        $language     Content(item) language tag
	 *
	 * @return  JcommentsObjectinfo
	 *
	 * @throws \Exception
	 * @since   3.0
	 */
	public static function getObjectInfo(?int $objectID, ?string $objectGroup = 'com_content', $language = null)
	{
		$app = Factory::getApplication();

		if (empty($language))
		{
			$language = $app->getLanguage()->getTag();
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
		$model  = $app->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Objects', 'Site', ['ignore_request' => true]);
		$object = $model->getItem($objectID, $objectGroup, $language);

		if ($object)
		{
			// Use object information stored in database or cache
			$info = new JcommentsObjectinfo($object);
		}
		else
		{
			// Get object information via plugins
			// NOTE! Do not set third parameter because we need to get item language later
			$info = self::getObjectInfoFromPlugin($objectID, $objectGroup);

			if (!self::isEmpty($info))
			{
				$model->save(null, $info);
			}
		}

		return $info;
	}

	private static function loadClass($objectGroup)
	{
		$objectGroup = InputFilter::getInstance()->clean($objectGroup, 'cmd');

		if (!isset(static::$plugins[$objectGroup]))
		{
			ob_start();
			include_once JPATH_ROOT . '/components/com_jcomments/plugins/' . File::makeSafe($objectGroup . '.plugin.php');
			ob_end_clean();

			$className = 'jc_' . $objectGroup;

			if (class_exists($className))
			{
				static::$plugins[$objectGroup] = $className;
			}
			else
			{
				static::$plugins[$objectGroup] = 'JcommentsPlugin';
			}
		}

		$className = static::$plugins[$objectGroup];

		return new $className;
	}
}

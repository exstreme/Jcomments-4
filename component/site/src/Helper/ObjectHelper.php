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

/**
 * JComments objects frontend helper
 *
 * @since  4.0
 */
class ObjectHelper
{
	/**
	 * @param   mixed        $objectInfo   Object info
	 * @param   string       $field        Object field
	 * @param   int|null     $objectID     Object ID
	 * @param   string|null  $objectGroup  Option, e.g. com_content
	 * @param   mixed        $language     Content(item) language tag
	 *
	 * @return  mixed
	 *
	 * @since   4.0
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
	 * Proxy for Joomla\Component\Jcomments\Site\Model::getTotalCommentsForObject()
	 *
	 * @param   integer       $objectID     Object ID.
	 * @param   string        $objectGroup  Object group.
	 * @param   integer|null  $state        Comment state.
	 * @param   integer|null  $deleted      Comment is deleted?
	 *
	 * @return  integer
	 *
	 * @since   4.1
	 */
	public static function getTotalCommentsForObject(int $objectID, string $objectGroup, ?int $state = null, ?int $deleted = null): int
	{
		/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
		$model  = Factory::getApplication()->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Objects', 'Site', ['ignore_request' => true]);

		return $model->getTotalCommentsForObject($objectID, $objectGroup, $state, $deleted);
	}

	/**
	 * Checking if object have title and link
	 *
	 * @param   object  $object  Object with information.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public static function isEmpty(object $object): bool
	{
		return empty($object->title) && empty($object->link);
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
	 * @param   integer  $objectID     Object ID
	 * @param   string   $objectGroup  Option, e.g. com_content
	 * @param   mixed    $language     Content(item) language tag
	 *
	 * @return  mixed
	 *
	 * @since   3.0
	 */
	public static function getObjectInfoFromPlugin(int $objectID, string $objectGroup = 'com_content', $language = null)
	{
		static $plugins = array();

		$objectGroup = InputFilter::getInstance()->clean($objectGroup, 'cmd');

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
				$plugins[$objectGroup] = 'JcommentsPlugin';
			}
		}

		$className = $plugins[$objectGroup];
		$class     = new $className;

		// Retrieve object information via getObjectInfo plugin's method
		$info = self::call($class, 'getObjectInfo', array($objectID, $language));

		if (is_null($info))
		{
			$info = new \StdClass;
		}

		$info->lang         = $language;
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
			$language = Factory::getApplication()->getLanguage()->getTag();
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
		$model  = $app->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Objects', 'Site', ['ignore_request' => true]);
		$object = $model->getItem($objectID, $objectGroup, $language);

		if ($object)
		{//echo 1;
			// Use object information stored in database
			$info = new JcommentsObjectinfo($object);
		}
		else
		{echo 2;
			// Get object information via plugins
			$info = self::getObjectInfoFromPlugin($objectID, $objectGroup, $language);

			if (!self::isEmpty($info))
			{
				if (!$app->isClient('administrator'))
				{
					// Insert object information
					// TODO Несуществующий метод
					//$model->save(null, $info);
					//JCommentsModelObject::setObjectInfo(0, $info);
				}
			}
		}
//echo '<pre>';
//var_dump($info);
//echo '</pre>';
//exit;
		return $info;
	}
}

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

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

/**
 * JComments objects model
 *
 * @since   3.0
 */
class JCommentsModelObject
{
	public static function getObjectInfo($objectID, $objectGroup, $language)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'id', 'object_id', 'object_group', 'category_id', 'lang', 'title', 'link', 'access', 'userid',
						'expired', 'modified'
					)
				)
			)
			->from($db->quoteName('#__jcomments_objects'))
			->where($db->quoteName('object_id') . ' = ' . (int) $objectID)
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup))
			->where($db->quoteName('lang') . ' = ' . $db->quote($language));

		$db->setQuery($query);
		$info = $db->loadObject();

		return empty($info) ? false : $info;
	}

	public static function setObjectInfo($objectId, $info)
	{
		/** @var DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		if (!empty($objectId))
		{
			$query->update($db->quoteName('#__jcomments_objects'))
				->set($db->quoteName('access') . ' = ' . (int) $info->access)
				->set($db->quoteName('userid') . ' = ' . (int) $info->userid)
				->set($db->quoteName('expired') . ' = 0')
				->set($db->quoteName('modified') . ' = ' . $db->quote(JFactory::getDate()->toSql()));

			if (empty($info->title))
			{
				$query->set($db->quoteName('title') . ' = ' . $db->Quote($info->title));
			}

			if (empty($info->link))
			{
				$query->set($db->quoteName('link') . ' = ' . $db->Quote($info->link));
			}

			if (empty($info->category_id))
			{
				$query->set($db->quoteName('category_id') . ' = ' . (int) $info->category_id);
			}

			$query->where($db->quoteName('id') . ' = ' . (int) $objectId);
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
				->set($db->quoteName('userid') . ' = ' . (int) $info->userid)
				->set($db->quoteName('expired') . ' = 0')
				->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()));
		}

		$db->setQuery($query);
		$db->execute();
	}

	public static function isEmpty($object)
	{
		return empty($object->title) && empty($object->link);
	}
}

<?php
/**
 * JComments Latest Commented - Shows latest commented items
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Module\LatestCommented\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;

// TODO Must be removed later when component frontend will use namespaces.
require_once JPATH_ROOT . '/components/com_jcomments/classes/factory.php';

/**
 * Helper for mod_jcomments_latest_commented
 *
 * @since  1.5
 */
class LatestCommentedHelper
{
	/**
	 * Retrieve list of articles
	 *
	 * @param   \Joomla\Registry\Registry  $params  Module parameters
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public static function getList(&$params)
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db      = Factory::getContainer()->get('DatabaseDriver');
		$user    = Factory::getApplication()->getIdentity();
		$source  = $params->get('source', 'com_content');
		$date    = Factory::getDate();
		$nowDate = $date->toSql();
		$access  = array_unique(Access::getAuthorisedViewLevels($user->get('id')));

		if (!is_array($source))
		{
			$source = explode(',', $source);
		}

		$query = $db->getQuery(true)
			->select($db->qn(array('obj.id', 'obj.title', 'obj.link')))
			->select('COUNT(c.id) AS commentsCount, MAX(c.date) AS commentdate')
			->from($db->qn('#__jcomments_objects', 'obj'))
			->innerJoin($db->qn('#__jcomments', 'c'), 'c.object_id = obj.object_id AND c.object_group = obj.object_group AND c.lang = obj.lang')
			->where(
				array(
					$db->qn('c.published') . ' = 1',
					$db->qn('c.deleted') . ' = 0',
					$db->qn('obj.link') . " <> ''",
					$db->qn('obj.access') . (is_array($access) ? ' IN (' . implode(',', $access) . ')' : ' <= ' . (int) $access)
				)
			);

		// TODO Must be changed later when component frontend will use namespaces.
		if (\JCommentsFactory::getLanguageFilter())
		{
			$langTag = Factory::getApplication()->getLanguage()->getTag();
			$query->where($db->qn('obj.lang') . ' = ' . $db->quote($langTag));
		}

		if (count($source) == 1 && $source[0] == 'com_content')
		{
			$query->innerJoin($db->qn('#__content', 'content'), 'content.id = obj.object_id')
				->leftJoin($db->qn('#__categories', 'cat'), 'cat.id = content.catid')
				->where(
					array(
						$db->qn('c.object_group') . ' = ' . $db->quote($source[0]),
						'(' . $db->qn('content.publish_up') . ' IS NULL OR ' . $db->qn('content.publish_up') . ' <= :publishUp)',
						'(' . $db->qn('content.publish_down') . ' IS NULL OR ' . $db->qn('content.publish_down') . ' >= :publishDown)'
					)
				)
				->bind(':publishUp', $nowDate)
				->bind(':publishDown', $nowDate);

			$categories = $params->get('catid');

			if (!is_array($categories))
			{
				$categories = explode(',', $categories);
			}

			$categories = array_filter($categories);
			ArrayHelper::toInteger($categories);

			if (!empty($categories))
			{
				$query->where($db->qn('content.catid') . ' IN (' . implode(',', $categories) . ')');
			}
		}
		elseif (count($source))
		{
			$query->where($db->qn('c.object_group') . ' IN (' . $db->quote(implode("','", $source), false) . ')');
		}

		$query->group($db->qn(array('obj.id', 'obj.title', 'obj.link')))
			->order($db->qn('commentdate') . ' DESC');

		try
		{
			$db->setQuery($query, 0, $params->get('count'));
			$list = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_jcomments_latest_commented');

			return array();
		}

		return $list;
	}
}

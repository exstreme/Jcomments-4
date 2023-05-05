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
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Log\Log;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Helper for mod_jcomments_latest_commented
 *
 * @since  1.5
 */
class LatestCommentedHelper implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * Retrieve list of comments
	 *
	 * @param   Registry         $params  Module parameters
	 * @param   SiteApplication  $app     Application
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function getComments(Registry $params, SiteApplication $app)
	{
		$db      = $this->getDatabase();
		$user    = $app->getIdentity();
		$source  = $params->get('source', 'com_content');
		$date    = Factory::getDate();
		$nowDate = $date->toSql();
		$access  = array_unique(Access::getAuthorisedViewLevels($user->get('id')));

		if (!is_array($source))
		{
			$source = explode(',', $source);
			$filter = InputFilter::getInstance();
			$source = array_map(
				function ($source) use ($filter)
				{
					return $filter->clean($source, 'cmd');
				},
				$source
			);
		}

		$query = $db->getQuery(true)
			->select($db->qn(array('obj.id', 'obj.title', 'obj.link')))
			->select('COUNT(' . $db->qn('c.id') . ') AS commentsCount, MAX(' . $db->qn('c.date') . ') AS commentdate')
			->from($db->qn('#__jcomments_objects', 'obj'))
			->innerJoin(
				$db->qn('#__jcomments', 'c'),
				$db->qn('c.object_id') . ' = ' . $db->qn('obj.object_id')
					. ' AND ' . $db->qn('c.object_group') . ' = ' . $db->qn('obj.object_group')
					. ' AND ' . $db->qn('c.lang') . ' = ' . $db->qn('obj.lang')
			)
			->where(
				array(
					$db->qn('c.published') . ' = 1',
					$db->qn('c.deleted') . ' = 0',
					$db->qn('obj.link') . " <> ''"
				)
			)
			->whereIn($db->qn('obj.access'), $access);

		if (JcommentsFactory::getLanguageFilter())
		{
			$langTag = $app->getLanguage()->getTag();
			$query->where($db->qn('obj.lang') . ' = :lang')
				->bind(':lang', $langTag);
		}

		if (count($source) == 1 && $source[0] == 'com_content')
		{
			$query->innerJoin(
				$db->qn('#__content', 'content'),
				$db->qn('content.id') . ' = ' . $db->qn('obj.object_id')
			)
				->leftJoin(
					$db->qn('#__categories', 'cat'),
					$db->qn('cat.id') . ' = ' . $db->qn('content.catid')
				)
				->where(
					array(
						$db->qn('c.object_group') . ' = :source',
						'(' . $db->qn('content.publish_up') . ' IS NULL OR ' . $db->qn('content.publish_up') . ' <= :publishUp)',
						'(' . $db->qn('content.publish_down') . ' IS NULL OR ' . $db->qn('content.publish_down') . ' >= :publishDown)'
					)
				)
				->bind(':source', $source[0])
				->bind(':publishUp', $nowDate)
				->bind(':publishDown', $nowDate);

			$categories = $params->get('catid');

			if (!is_array($categories))
			{
				$categories = explode(',', $categories);
			}

			$categories = ArrayHelper::toInteger($categories);
			$categories = array_filter($categories);

			if (!empty($categories))
			{
				$query->whereIn($db->qn('content.catid'), $categories);
			}
		}
		elseif (count($source))
		{
			$query->whereIn($db->qn('c.object_group'), $source, ParameterType::STRING);
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
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'mod_jcomments_latest_commented');

			return array();
		}

		return $list;
	}
}

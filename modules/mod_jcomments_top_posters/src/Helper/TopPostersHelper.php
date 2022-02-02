<?php
/**
 * JComments Top Posters - Shows list of top posters
 *
 * @version           4.0.0
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Module\TopPosters\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;

// @TODO Must be removed later when component frontend will use namespaces.
require_once JPATH_ROOT . '/components/com_jcomments/classes/factory.php';
require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

/**
 * Helper for mod_jcomments_top_posters
 *
 * @since  1.5
 */
class TopPostersHelper
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
		$db       = Factory::getContainer()->get('DatabaseDriver');
		$date     = Factory::getDate();
		$interval = $params->get('interval', '');

		$query = $db->getQuery(true)
			->select($db->qn('c.userid') . ", '' as avatar, '' as profileLink")
			->select('CASE WHEN c.userid = 0 THEN c.email ELSE u.email END AS email')
			->select('CASE WHEN c.userid = 0 THEN c.name ELSE u.name END AS name')
			->select('CASE WHEN c.userid = 0 THEN c.username ELSE u.username END AS username')
			->select('COUNT(c.userid) AS commentsCount')
			->select('SUM(c.isgood) AS isgood, SUM(c.ispoor) AS ispoor, SUM(c.isgood - c.ispoor) AS votes')
			->from($db->qn('#__jcomments', 'c'))
			->innerJoin($db->qn('#__users', 'u'), 'u.id = c.userid')
			->where(
				array(
					$db->qn('c.published') . ' = 1',
					$db->qn('c.deleted') . ' = 0'
				)
			);

		if (!empty($interval))
		{
			$timestamp = $date->toUnix();

			switch ($interval)
			{
				case '1-day':
					$timestamp = strtotime('-1 day', $timestamp);
					break;

				case '1-week':
					$timestamp = strtotime('-1 week', $timestamp);
					break;

				case '2-week':
					$timestamp = strtotime('-2 week', $timestamp);
					break;

				case '1-month':
					$timestamp = strtotime('-1 month', $timestamp);
					break;

				case '3-month':
					$timestamp = strtotime('-3 month', $timestamp);
					break;

				case '6-month':
					$timestamp = strtotime('-6 month', $timestamp);
					break;

				case '1-year':
					$timestamp = strtotime('-1 year', $timestamp);
					break;
				default:
					$timestamp = null;
					break;
			}

			if ($timestamp !== null)
			{
				$dateFrom = Factory::getDate($timestamp);
				$dateTo = $date;

				$query->where($db->qn('c.date') . ' BETWEEN ' . $db->quote($dateFrom->toSQL()) . ' AND ' . $db->quote($dateTo->toSQL()));
			}
		}

		$query->group($db->qn(array('c.userid', 'email', 'name', 'username', 'avatar', 'profileLink')));

		switch ($params->get('ordering', ''))
		{
			case 'votes':
				$query->order($db->qn('votes') . ' DESC');
				break;

			case 'comments':
			default:
				$query->order($db->qn('commentsCount') . ' DESC');
				break;
		}

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

		$showAvatar = $params->get('show_avatar', 0);

		if ($showAvatar)
		{
			PluginHelper::importPlugin('jcomments');

			Factory::getApplication()->triggerEvent('onPrepareAvatars', array(&$list));
		}

		foreach ($list as &$item)
		{
			$item->displayAuthorName = \JCommentsContent::getCommentAuthorName($item);

			if ($showAvatar && empty($item->avatar))
			{
				$item->author = $item->displayAuthorName;
				$item->avatar = \JcommentsFactory::getGravatar($item);
			}
		}

		return $list;
	}
}

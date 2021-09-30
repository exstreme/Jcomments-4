<?php
/**
 * JComments plugin for TZ Portfolio support
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_tz_portfolio extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$routerHelper = JPATH_ROOT.'/components/com_tz_portfolio/helpers/route.php';
		if (is_file($routerHelper)) {
			require_once($routerHelper);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('a.id, a.title, a.created_by, a.access, a.alias, a.catid');
			$query->from('#__content AS a');

			$query->select('c.alias AS category_alias');
			$query->join('LEFT', '#__categories AS c ON c.id = a.catid');
			
			$query->where('a.id = ' . (int) $id);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row)) {
				$row->slug = $row->alias ? ($row->id.':'.$row->alias) : $row->id;
				$row->catslug = $row->category_alias ? ($row->catid.':'.$row->category_alias) : $row->catid;

				$info->category_id = $row->category_id;
				$info->title = $row->title;
				$info->userid = $row->created_by;
				$info->link = JRoute::_(TZ_PortfolioHelperRoute::getArticleRoute($row->slug, $row->catslug));
			}
		}

		return $info;
	}
}
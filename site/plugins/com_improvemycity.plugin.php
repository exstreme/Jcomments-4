<?php
/**
 * JComments plugin for ImproveMyCity support
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_improvemycity extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		if (is_file(JPATH_ROOT.'/components/com_improvemycity/improvemycity.php')) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('a.id, a.title, a.userid, a.catid');
			$query->from('#__improvemycity AS a');
			$query->where('a.id = ' . (int) $id);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row)) {
				$Itemid = self::getItemid('com_improvemycity');
				$Itemid = $Itemid > 0 ? '&Itemid='.$Itemid : '';

				$info->category_id = $row->catid;
				$info->title = $row->title;
				$info->userid = $row->userid;
				$info->link = JRoute::_('index.php?option=com_improvemycity&view=issue&issue_id=' . (int) $id . $Itemid);
			}
		}

		return $info;
	}
}
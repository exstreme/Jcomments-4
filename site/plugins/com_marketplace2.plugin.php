<?php
/**
 * JComments plugin for MarketPlace 2.0 ads objects support
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_marketplace2 extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = 'SELECT e.id, e.headline, e.user_id, e.category_id '
			. " , CASE WHEN CHAR_LENGTH(e.alias) THEN CONCAT_WS(':', e.id, e.alias) ELSE e.id END as slug"
			. " , CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(':', c.id, c.alias) ELSE c.id END as categorySlug"
			. " FROM #__marketplace_entries AS e"
			. " JOIN #__marketplace_categories AS c ON c.id = e.category_id"
			. " WHERE e.id = " . $id
			;
		$db->setQuery($query);
		$row = $db->loadObject();

		$info = new JCommentsObjectInfo();

		if (!empty($row)) {
			$Itemid = self::getItemid('com_marketplace');
			$Itemid = $Itemid > 0 ? '&Itemid='.$Itemid : '';

			$info->category_id = $row->catid;
			$info->title = $row->headline;
			$info->userid = $row->user_id;
			$info->link = JRoute::_("index.php?option=com_marketplace&amp;view=entry&amp;catid=".htmlspecialchars($row->categorySlug)."&amp;entry=".$row->slug.$Itemid);
		}

		return $info;
	}
}
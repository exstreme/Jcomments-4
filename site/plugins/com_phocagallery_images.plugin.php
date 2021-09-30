<?php
/**
 * JComments plugin for PhocaGallery
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_phocagallery_images extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = 'SELECT a.id, a.title '
			.' , CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug'
			.' , CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'
			.' FROM #__phocagallery AS a'
			.' LEFT JOIN #__phocagallery_categories AS c ON c.id = a.catid'
			.' WHERE a.id = '. $id
			;
		$db->setQuery($query);
		$row = $db->loadObject();
			
		if (!empty($row)) {
			$Itemid = self::getItemid('com_phocagallery');
			$Itemid = $Itemid > 0 ? '&Itemid='.$Itemid : '';

			$info->title = $row->title;
			$info->access = 0;
			$info->userid = $row->owner_id;

			// Comment is displayed in popup window so we must create link to category view
			// Because of possible pagination only this one image will be displayed not all

			$info->link = JRoute::_('index.php?option=com_phocagallery&view=category&id=' . $row->catslug.'&cimgid=' . $row->slug . $Itemid);
		}

		return $info;
	}
}
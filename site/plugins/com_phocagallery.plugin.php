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

class jc_com_phocagallery extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = 'SELECT id, title, owner_id '
			. ' , CASE WHEN CHAR_LENGTH(alias) THEN CONCAT_WS(\':\', id, alias) ELSE id END as slug'
			. ' FROM #__phocagallery_categories'
			. ' WHERE id = '.$id
			;
		$db->setQuery($query);
		$row = $db->loadObject();
			
		if (!empty($row)) {
			$Itemid = self::getItemid('com_phocagallery');
			$Itemid = $Itemid > 0 ? '&Itemid='.$Itemid : '';

			$info->title = $row->title;
			$info->access = 0;
			$info->userid = $row->owner_id;
			$info->link = JRoute::_('index.php?option=com_phocagallery&view=category&id=' . $row->slug . $Itemid);
		}

		return $info;
	}
}
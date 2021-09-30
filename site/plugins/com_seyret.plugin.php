<?php
/**
 * JComments plugin for Seyret support
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_seyret extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT id, title, addedby FROM #__seyret_items WHERE id = '.$id);
		$row = $db->loadObject();

		$info = new JCommentsObjectInfo();

		if (!empty($row)) {
			$Itemid = self::getItemid('com_seyret');
			$Itemid = $Itemid > 0 ? '&amp;Itemid='.$Itemid : '';

			$info->title = $row->title;
			$info->userid = $row->addedby;
			$info->link = JRoute::_('index.php?option=com_seyret&amp;task=videodirectlink&amp;id='.$id.$Itemid);
		}

		return $info;
	}
}
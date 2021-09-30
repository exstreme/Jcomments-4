<?php
/**
 * JComments plugin for EventGallery (http://www.svenbluege.de/) support
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_eventgallery extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery("SELECT id, title, file, folder, userid FROM #__eventgallery_file WHERE id = " . $id);
		$row = $db->loadObject();
 
		$info = new JCommentsObjectInfo();
 
		if (!empty($row)) {
			$Itemid = self::getItemid('com_eventgallery');
			$Itemid = $_Itemid > 0 ? '&Itemid=' . $Itemid : '';
 
			$info->title = $row->title;
			$info->userid = $row->created_by;
			$info->link = JRoute::_('index.php?option=com_eventgallery&view=singleimage&folder='. $row->folder . '&file=' . $row->file . $Itemid);
		}
 
		return $info;
	}
}
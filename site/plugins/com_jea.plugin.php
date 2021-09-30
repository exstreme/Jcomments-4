<?php
/**
 * JComments plugin for Joomla Estate Agency (JEA) objects support
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_jea  extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = "SELECT id, alias, title, access, created_by"
			. " FROM #__jea_properties"
			. " WHERE id = " . $id;
		$db->setQuery($query);
		$row = $db->loadObject();

		$info = new JCommentsObjectInfo();

		if (!empty($row)) {
			$Itemid = self::getItemid('com_jea');
			$Itemid = $Itemid > 0 ? '&amp;Itemid=' . $Itemid : '';

			$slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;

			$info->title = $row->title;
			$info->userid = $row->created_by;
			$info->access = $row->access;
			$info->link = JRoute::_('index.php?option=com_jea&view=property&id='. $slug . $Itemid);
		}

		return $info;
	}
}
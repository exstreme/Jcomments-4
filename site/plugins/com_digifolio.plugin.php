<?php
/**
 * JComments plugin for DigiFolio projects support
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_digifolio extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id, name, alias, created_by, access');
		$query->from('#__digifolio_projects');
		$query->where('id = ' . (int)$id);
		$db->setQuery($query);

		$row = $db->loadObject();

		$info = new JCommentsObjectInfo();

		if (!empty($row)) {
			$Itemid = self::getItemid('com_digifolio');
			$Itemid = $Itemid > 0 ? '&Itemid='.$Itemid : '';

			$slug = $row->alias ? ($row->id.':'.$row->alias) : $row->id;

			$info->title = $row->name;
			$info->access = $row->access;
			$info->userid = $row->created_by;
			$info->link = JRoute::_('index.php?option=com_digifolio&amp;view=project&amp;id=' . $slug . $Itemid);
		}

		return $info;
	}
}
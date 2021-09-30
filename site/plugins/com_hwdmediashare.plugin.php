<?php
/**
 * JComments plugin for hwdMediaShare support
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_hwdmediashare extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$routerHelper = JPATH_SITE.'/components/com_hwdmediashare/helpers/route.php';
		if (is_file($routerHelper)) {
			require_once($routerHelper);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$db->setQuery('SELECT id, title, access, created_user_id FROM #__hwdms_media WHERE id = ' . $id);
			$row = $db->loadObject();
			
			if (!empty($row)) {
				$slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;

				$info->title = $row->title ? 'Unknown hwdMediaShare Content' : $row->title;
				$info->userid = $row->created_user_id;
				$info->access = $row->access;
				$info->link = JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($slug));
			}
		}

		return $info;
	}
}
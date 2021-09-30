<?php
/**
 * JComments plugin for Cobalt 7
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2012 by Sergey Romanov
 * @copyright (C) 2012-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_cobalt extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$helper = JPATH_ROOT.'/components/com_cobalt/library/php/helper.php';

		if (is_file($helper)) {
			require_once($helper);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('*');
			$query->from('#__js_res_record');
			$query->where('id = ' . (int)$id);
			$db->setQuery($query);

			$record = $db->loadObject();

			if (!empty($record)) {
				$info->title = $record->title;
				$info->access = $record->access;
				$info->userid = $record->user_id;
				$info->link = JRoute::_(Url::record($record));
			}
		}

		return $info;
	}
}
<?php
/**
 * JComments plugin for JEvents objects support
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_jevents extends JCommentsPlugin 
{
	function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = 'SELECT det.summary, rpt.rp_id, ev.created_by, ev.access'
			. ' FROM #__jevents_repetition AS rpt '
			. ' LEFT JOIN #__jevents_vevdetail AS det ON det.evdet_id = rpt.eventdetail_id '
			. ' LEFT JOIN #__jevents_vevent AS ev ON ev.ev_id = rpt.eventid '
			. ' WHERE ev.ev_id = ' . $id;

		$db->setQuery($query);
		$row = $db->loadObject();
			
		if (!empty($row)) {
			$info->title = $row->summary;
			$info->access = $row->access;
			$info->userid = $row->created_by;
			$info->link = JRoute::_( 'index.php?option=com_jevents&task=icalrepeat.detail&evid=' . $row->rp_id );
		}

		return $info;
	}
}
<?php
/**
 * JComments plugin for JEvents (https://www.jevents.net/) objects support
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_jevents extends JCommentsPlugin 
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo;

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('det.summary', 'rpt.rp_id', 'ev.created_by', 'ev.access')))
			->from($db->quoteName('#__jevents_repetition', 'rpt'))
			->join('LEFT', $db->quoteName('#__jevents_vevdetail', 'det'), 'det.evdet_id = rpt.eventdetail_id')
			->join('LEFT', $db->quoteName('#__jevents_vevent', 'ev'), 'ev.ev_id = rpt.eventid')
			->where($db->quoteName('ev.ev_id') . ' = :eid')
			->bind(':eid', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		if (!empty($row))
		{
			$info->title  = $row->summary;
			$info->access = $row->access;
			$info->userid = $row->created_by;
			$info->link   = Route::_('index.php?option=com_jevents&task=icalrepeat.detail&evid=' . $row->rp_id);
		}

		return $info;
	}
}

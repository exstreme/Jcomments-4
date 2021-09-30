<?php
/**
 * JComments plugin for EventList
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_eventlist extends JCommentsPlugin 
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT title, id FROM #__eventlist_events WHERE id = ' . $id );
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = 'SELECT a.id, CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'
			. ' FROM #__eventlist_events AS a'
			. ' WHERE id = ' . $id
			;
		$db->setQuery($query);
		$slug = $db->loadResult();

		require_once(JPATH_SITE.'/includes/application.php');

		$eventListRouter = JPATH_SITE.'/components/com_eventlist/helpers/route.php';
		if (is_file($eventListRouter)) {
			require_once($eventListRouter);
			$link = JRoute::_( EventListHelperRoute::getRoute($slug) );
		} else {
			$link = JRoute::_( 'index.php?option=com_eventlist&view=details&id=' . $slug );
		}

		return $link;
	}

	function getObjectOwner($id) {

		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT created_by, id FROM #__eventlist_events WHERE id = ' . $id );
		$userid = $db->loadResult();
		
		return $userid;
	}
}
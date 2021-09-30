<?php
/**
 * JComments plugin for Smart Blog objects support
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_blog extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT post_title, id FROM #__blog_postings WHERE id = ' . $id);
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		$link = 'index.php?option=com_blog&view=comments&pid='. $id;

		require_once(JPATH_SITE.DS.'includes'.DS.'application.php');

		$component = JComponentHelper::getComponent('com_blog');
		$menus = JApplication::getMenu('site');
		$items = $menus->getItems('componentid', $component->id);

		if (count($items)) {
			$link .= "&Itemid=" . $items[0]->id;
		}

		$link = JRoute::_($link);
		return $link;
	}

	function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = "SELECT user_id FROM #__blog_postings WHERE id = " . $id;
		$db->setQuery( $query );
		$userid = $db->loadResult();
		
		return intval($userid);
	}
}
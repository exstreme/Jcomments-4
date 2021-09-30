<?php
/**
 * JComments plugin for GroupJive (http://www.groupjive.org/) support
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_groupjive extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT subject FROM #__gj_bul WHERE id = ' . $id );
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		$Itemid = self::getItemid('com_groupjive');
		$Itemid = $Itemid > 0 ? '&Itemid=' . $Itemid : '';

		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT group_id FROM #__gj_bul WHERE id = ' . $id );
		$gid = $db->loadResult();

		$link = JRoute::_('index.php?option=com_groupjive&amp;task=showfullmessage&amp;idm=' . $id . '&amp;groupid=' . $gid . $Itemid);
		return $link;
	}

	function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT author_id FROM #__gj_bul WHERE id = ' . $id );
		$userid = $db->loadResult();
		
		return $userid;
	}
}
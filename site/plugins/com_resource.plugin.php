<?php
/**
 * JComments plugin for JoomSuite Content [com_resource] - (http://www.joomsuite.com/)
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_resource extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT title, id FROM #__js_res_record WHERE id = ' . $id );
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		include_once(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_resource'.DS.'library'.DS.'helper.php');
		if (class_exists('MEUrl')) {
			$link = MEUrl::link_record($id);
		} else {
			$_Itemid = self::getItemid('com_resource');
			$link = 'index.php?option=com_resource&controller=article&article='.$id;
			$link .= ($_Itemid > 0) ? ('&Itemid=' . $_Itemid) : '';
		}

		$link = JRoute::_( $link );

		return $link;
	}

	function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( "SELECT created_by FROM #__js_res_record WHERE id='$id'");
		$userid = $db->loadResult();
		
		return $userid;
	}
}
<?php
/**
 * JComments plugin for JColletion items support
 *
 * @version 2.1
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_jcollection extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT name, id FROM #__jc WHERE id = ' . $id );
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		$link = 'index.php?option=com_jcollection&amp;view=item&amp;id=' . $id;
		$_Itemid = self::getItemid('com_jcollection');
		$link .= $_Itemid > 0 ? '&Itemid=' . $_Itemid : '';
		$link = JRoute::_( $link );
		return $link;
	}

	function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT created_by, id FROM #__jc WHERE id = ' . $id );
		$userid = $db->loadResult();
		
		return $userid;
	}
}
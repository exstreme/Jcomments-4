<?php
/**
 * JComments plugin for Remository objects support
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_remository extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT filetitle, id FROM #__downloads_files WHERE id = ' . $id );
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		$_Itemid = self::getItemid('com_remository');
		$link = JoomlaTuneRoute::_( 'index.php?option=com_remository&amp;func=fileinfo&amp;id=' . $id . '&amp;Itemid=' . $_Itemid );
		return $link;
	}

	function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery( 'SELECT userid FROM #__downloads_files WHERE id = ' . $id );
		$userid = $db->loadResult();
		
		return $userid;
	}
}
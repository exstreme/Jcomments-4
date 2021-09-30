<?php
/**
 * JComments plugin for JaVoice events support
 *
 * @version 1.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;
 
class jc_com_javoice extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT title, id FROM #__jav_items WHERE id = '.$id);
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		$_Itemid = self::getItemid('com_javoice');

		//get type_id
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT voice_types_id FROM #__jav_items WHERE id = '.$id);
		$type_id = $db->loadResult();
		
		$link = JRoute::_('index.php?option=com_javoice&amp;view=items&amp;layout=item&amp;cid='.$id.'&amp;type='.$type_id.'&amp;Itemid='.$_Itemid);
		return $link;
	}

	function getObjectOwner($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT user_id FROM #__jav_items WHERE id = '.$id);
		$userid = (int) $db->loadResult();
		
		return $userid;
	}
}
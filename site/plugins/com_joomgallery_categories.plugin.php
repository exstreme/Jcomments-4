<?php
/**
 * JComments plugin for JoomGallery categories comments support
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_joomgallery_categories extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		// Category comments
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT name, cid FROM #__joomgallery_catg WHERE cid = ' . $id);
		return $db->loadResult();
	}

	function getObjectLink($id)
	{
		// Get an Itemid of JoomGallery
		// First, check whether there was set one in the configuration
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT jg_itemid FROM #__joomgallery_config LIMIT 1');
		if (!$_Itemid = $db->loadResult()) {
			$_Itemid = self::getItemid('com_joomgallery');
		}

		// Category view
		return JRoute::_('index.php?option=com_joomgallery&amp;view=category&amp;catid=' . $id . '&amp;Itemid=' . $_Itemid);
	}

	function getObjectOwner($id)
	{
		// Category owner
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT owner FROM #__joomgallery_catg WHERE cid = ' . $id);
		$userid = $db->loadResult();
		return intval($userid);
	}
}
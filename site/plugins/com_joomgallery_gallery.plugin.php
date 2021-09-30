<?php
/**
 * JComments plugin for JoomGallery gallery comments support
 *
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */


use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_joomgallery_gallery extends JCommentsPlugin
{
	function getObjectTitle($id)
	{
		// Gallery comments
		$language = JFactory::getLanguage();
		$language->load('com_joomgallery', JPATH_ROOT);
		return JText::_('JGS_COMMON_GALLERY');
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

		return JRoute::_('index.php?option=com_joomgallery&amp;view=gallery&amp;Itemid=' . $_Itemid);
	}

	function getObjectOwner($id)
	{
		// Gallery owner (a super administrator)
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT id FROM #__users WHERE gid = 25');
		$userid = $db->loadResult();
		return intval($userid);
	}
}
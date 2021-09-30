<?php
/**
 * JComments plugin for LyftenBloggie entries support
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_lyftenbloggie extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = "SELECT e.id, e.title, e.created_by, e.created, e.access"
			. ', CASE WHEN CHAR_LENGTH(e.alias) THEN CONCAT_WS(":", e.id, e.alias) ELSE e.id END as slug'
			. ' FROM #__bloggies_entries AS e'
			. ' WHERE e.id = '.$id
			;

		$db->setQuery( $query, 0, 1);
		$entry = $db->loadObject();

		$info = new JCommentsObjectInfo();

		if (!empty($entry)) {
			$routerHelper = JPATH_ROOT.'/components/com_lyftenbloggie/helpers/route.php';
			if (is_file($routerHelper)) {
				include_once(JPATH_SITE.DS.'includes'.DS.'application.php');
				include_once(JPATH_SITE.DS.'components'.DS.'com_lyftenbloggie'.DS.'helpers'.DS.'route.php');
				include_once(JPATH_SITE.DS.'components'.DS.'com_lyftenbloggie'.DS.'router.php');

				$entry->archive	= JHTML::_('date', $entry->created, '&year=%Y&month=%m&day=%d');

				$info->title = $entry->title;
				$info->access = $entry->access;
				$info->userid = $entry->created_by;
				$info->link = JRoute::_(LyftenBloggieHelperRoute::getEntryRoute($entry->archive, $entry->slug));;
			}
		}

		return $info;
	}
}
<?php
/**
 * JComments plugin for JComments ;)
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

class jc_com_jcomments extends JCommentsPlugin
{
	public function getObjectInfo($id, $language)
	{
		$info = new JCommentsObjectInfo;
		$menu = self::getMenuItem($id);

		if ($menu != '')
		{
			$params = new Registry($menu->params);

			$info->title  = $params->get('page_title') ? $params->get('page_title') : $menu->title;
			$info->access = $menu->access;
			$info->link   = Route::_('index.php?option=com_jcomments&Itemid=' . $menu->id);
			$info->userid = 0;
		}

		return $info;
	}

	protected static function getMenuItem($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('m.*')
			->from($db->quoteName('#__menu', 'm'))
			->join('INNER', $db->quoteName('#__extensions', 'e'), 'm.component_id = e.extension_id')
			->where('m.type = \'component\'')
			->where('e.element = \'com_jcomments\'')
			->where('m.published = 1')
			->where('m.parent_id > 0')
			->where('m.client_id = 0')
			->where('m.params LIKE \'%"object_id":":oid"%\'')
			->bind(':oid', $id);

		$db->setQuery($query, 0, 1);
		$menus = $db->loadObjectList();

		return count($menus) ? $menus[0] : null;
	}
}

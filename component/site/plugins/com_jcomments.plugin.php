<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Registry\Registry;

class jc_com_jcomments extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JcommentsObjectinfo;
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
		/** @var Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('m.*')
			->from($db->quoteName('#__menu', 'm'))
			->innerJoin($db->quoteName('#__extensions', 'e'), 'm.component_id = e.extension_id')
			->where($db->quoteName('m.type') . ' = ' . $db->quote('component'))
			->where($db->quoteName('e.element') . ' = ' . $db->quote('com_jcomments'))
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('m.parent_id') . ' > 0')
			->where($db->quoteName('m.client_id') . ' = 0')
			->where($db->quoteName('m.params') . " LIKE '%\"object_id\":\"" . $id . "\"%'");

		try
		{
			$db->setQuery($query, 0, 1);
			$menus = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return null;
		}

		return count($menus) ? $menus[0] : null;
	}
}

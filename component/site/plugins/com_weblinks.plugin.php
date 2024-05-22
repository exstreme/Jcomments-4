<?php
/**
 * JComments plugin for Joomla Weblinks (https://extensions.joomla.org/extension/weblinks/) component
 *
 * @version       4.0
 * @package       JComments
 * @author        Tommy Nilsson (tommy@architechtsoftomorrow.com)
 * @copyright (C) 2011 Tommy Nilsson (https://www.architechtsoftomorrow.com)
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_weblinks extends JCommentsPlugin
{
	public function getObjectTitle($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'title')))
			->from($db->quoteName('#__categories', 'u'))
			->where($db->quoteName('section') . " = 'com_weblinks'")
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}

	public function getObjectLink($id)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName('alias'))
			->from($db->quoteName('#__categories', 'u'))
			->where($db->quoteName('section') . " = 'com_weblinks'")
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$alias = $db->loadResult();

		$link      = 'index.php?option=com_weblinks&view=category&id=' . $id . ':' . $alias;
		$component = ComponentHelper::getComponent('com_weblinks');

		/** @var \Joomla\CMS\Menu\SiteMenu $menus */
		$menus     = Factory::getApplication()->getMenu('site');
		$items     = $menus->getItems('componentid', $component->id);

		if (count($items))
		{
			$link .= "&Itemid=" . $items[0]->id;
		}

		return Route::_($link);
	}
}

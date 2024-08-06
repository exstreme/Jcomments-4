<?php
/**
 * JComments plugin for FW Gallery (http://fastw3b.net)
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_fwgallery extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'name', 'user_id')))
			->from($db->quoteName('#__fwg_file'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$row = $db->loadObject();

		$info = new JCommentsObjectInfo;

		if (!empty($row))
		{
			$itemid = self::_getItemid('item');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->name;
			$info->userid = $row->user_id;
			$info->link   = Route::_('index.php?option=com_fwgallery&view=item&id=' . $row->id . ':' . OutputFilter::stringURLSafe($row->name) . $itemid);
		}

		return $info;
	}

	protected static function _getItemid($view = 'fwgallery', $id = 0, $default = 0)
	{
		$item = null;
		$menu = Factory::getApplication()->getMenu('site');

		if ($id && $items = $menu->getItems('link', 'index.php?option=com_fwgallery&view=' . $view))
		{
			foreach ($items as $menuItem)
			{
				if ((is_object($menuItem->params) && $id == $menuItem->params->get('file_id')))
				{
					$item = $menuItem;
					break;
				}
			}
		}

		if ($item === null)
		{
			$item = $menu->getItems('link', 'index.php?option=com_fwgallery&view=fwgallery', true);
		}

		if ($item)
		{
			return $item->id;
		}
		elseif ($default)
		{
			return $default;
		}
		elseif ($item = $menu->getActive())
		{
			return $item->id;
		}
		else
		{
			return null;
		}
	}
}

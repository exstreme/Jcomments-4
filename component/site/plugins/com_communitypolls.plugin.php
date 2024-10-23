<?php
/**
 * JComments plugin for Community Polls
 *
 * @version       4.0
 * @package       JComments
 * @author        CoreJoomla (support@corejoomla.com) & Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2011-2012 by CoreJoomla (http://www.corejoomla.com)
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_communitypolls extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.title', 'a.alias', 'a.created_by')))
			->from($db->quoteName('#__jcp_polls', 'a'))
			->where($db->quoteName('a.id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		$info = new JcommentsObjectInfo;

		if (!empty($row))
		{
			$row->slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;

			$items  = Factory::getApplication()->getMenu('site')->getItems('link', 'index.php?option=com_communitypolls&controller=polls');
			$itemid = isset($items[0]) ? '&Itemid=' . $items[0]->id : '';

			$info->title  = $row->title;
			$info->userid = $row->created_by;
			$info->link   = Route::_('index.php?option=com_communitypolls&controller=polls&task=viewpoll&id=' . $row->slug . $itemid);
		}

		return $info;
	}
}

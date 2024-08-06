<?php
/**
 * JComments plugin for iCagenda (https://www.icagenda.com/)
 *
 * @version       4.0
 * @package       JComments
 * @author        Denys Nosov (denys@joomla-ua.org)
 * @copyright (C) 2013 JoomliC, www.joomlic.com
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_icagenda extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'title', 'access', 'created_by', 'alias')))
			->from($db->quoteName('#__icagenda_events'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		$info = new JCommentsObjectInfo;

		if (!empty($row))
		{
			$itemid = self::getItemid('com_icagenda', 'index.php?option=com_icagenda&view=events');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$row->slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;

			$info->title  = $row->title;
			$info->access = $row->access;
			$info->userid = $row->created_by;
			$info->link   = Route::_('index.php?option=com_icagenda&view=event&id=' . $row->slug . $itemid);
		}

		return $info;
	}
}

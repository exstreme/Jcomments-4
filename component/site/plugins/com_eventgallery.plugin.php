<?php
/**
 * JComments plugin for EventGallery (https://www.svenbluege.de/) support
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
use Joomla\Database\ParameterType;

class jc_com_eventgallery extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'title', 'file', 'folder', 'userid')))
			->from($db->quoteName('#__eventgallery_file'))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		$row = $db->loadObject();

		$info = new JCommentsObjectInfo;

		if (!empty($row))
		{
			$itemid = self::getItemid('com_eventgallery');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->title;
			$info->userid = $row->created_by;
			$info->link   = Route::_('index.php?option=com_eventgallery&view=singleimage&folder=' . $row->folder . '&file=' . $row->file . $itemid);
		}

		return $info;
	}
}

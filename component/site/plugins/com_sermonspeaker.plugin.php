<?php
/**
 * JComments plugin for SermonSpeaker support (https://www.sermonspeaker.net/)
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

class jc_com_sermonspeaker extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo;

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'sermon_title', 'created_by')))
			->from($db->quoteName('#__sermon_sermons'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		if (!empty($row))
		{
			$itemid = self::getItemid('com_sermonspeaker');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->sermon_title;
			$info->access = 0;
			$info->userid = $row->created_by;
			$info->link   = Route::_('index.php?option=com_sermonspeaker&view=sermon&id=' . $row->id . $itemid);
		}

		return $info;
	}
}

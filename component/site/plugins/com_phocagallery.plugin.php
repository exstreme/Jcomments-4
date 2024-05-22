<?php
/**
 * JComments plugin for Phoca Gallery (https://www.phoca.cz/phocagallery)
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

class jc_com_phocagallery extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo;

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'title', 'owner_id')))
			->select('CASE WHEN CHAR_LENGTH(alias) THEN CONCAT_WS(\':\', id, alias) ELSE id END as slug')
			->from($db->quoteName('#__phocagallery_categories'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		if (!empty($row))
		{
			$itemid = self::getItemid('com_phocagallery');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->title;
			$info->access = 0;
			$info->userid = $row->owner_id;
			$info->link   = Route::_('index.php?option=com_phocagallery&view=category&id=' . $row->slug . $itemid);
		}

		return $info;
	}
}

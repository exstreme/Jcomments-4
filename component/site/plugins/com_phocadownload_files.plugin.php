<?php
/**
 * JComments plugin for Phoca Download (https://www.phoca.cz/phocadownload)
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

class jc_com_phocadownload_files extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo;

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.title', 'a.access')))
			->select('CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug')
			->select('CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug')
			->from($db->quoteName('#__phocadownload', 'a'))
			->join('LEFT', $db->quoteName('#__phocadownload_categories', 'c'), 'c.id = a.catid')
			->where($db->quoteName('a.id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		if (!empty($row))
		{
			$itemid = self::getItemid('com_phocadownload');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->title;
			$info->access = $row->access;
			$info->link   = Route::_('index.php?option=com_phocadownload&view=file&catid=' . $row->catslug . '&id=' . $row->slug . $itemid);
		}

		return $info;
	}
}

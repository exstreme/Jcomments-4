<?php
/**
 * JComments plugin for Mosets tree (https://www.mosets.com/) support
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

defined('_JEXEC') or die;

class jc_com_mtree extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('link_id', 'link_name', 'user_id')))
			->from($db->quoteName('#__mt_links'))
			->where($db->quoteName('link_id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$row = $db->loadObject();

		$info = new JcommentsObjectInfo;

		if (!empty($row))
		{
			$itemid = self::getItemid('com_mtree');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->link_name;
			$info->userid = $row->user_id;
			$info->link   = Route::_('index.php?option=com_mtree&task=viewlink&link_id=' . $id . $itemid);
		}

		return $info;
	}
}

<?php
/**
 * JComments plugin for FLEXIcontent (https://www.flexicontent.org) contents support
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

defined('_JEXEC') or die;

class jc_com_flexicontent extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo;

		$routerHelper = JPATH_ROOT . '/components/com_flexicontent/helpers/route.php';

		if (is_file($routerHelper))
		{
			require_once $routerHelper;

			/** @var \Joomla\Database\DatabaseInterface $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('i.id', 'i.title', 'i.access', 'i.created_by')))
				->select('CASE WHEN CHAR_LENGTH(i.alias) THEN CONCAT_WS(\':\', i.id, i.alias) ELSE i.id END as slug')
				->select('CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug')
				->from($db->quoteName('#__content', 'i'))
				->join('LEFT', $db->quoteName('#__categories', 'c'), 'c.id = i.catid')
				->where($db->quoteName('i.id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row))
			{
				$info->category_id = $row->catid;
				$info->title       = $row->title;
				$info->access      = $row->access;
				$info->userid      = $row->created_by;
				$info->link        = Route::_(FlexicontentHelperRoute::getItemRoute($row->slug, $row->catslug));
			}
		}

		return $info;
	}
}

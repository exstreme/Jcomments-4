<?php
/**
 * JComments plugin for DJ-Classifieds objects support (https://dj-extensions.com/dj-classifieds)
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

class jc_com_djclassifieds extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JCommentsObjectInfo;

		$routerHelper = JPATH_ROOT . '/administrator/components/com_djclassifieds/lib/djseo.php';

		if (is_file($routerHelper))
		{
			require_once $routerHelper;

			/** @var \Joomla\Database\DatabaseInterface $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('a.id', 'a.alias', 'a.name', 'a.user_id')))
				->select('c.id AS category_id, c.alias AS category_alias')
				->from($db->quoteName('#__djcf_items', 'a'))
				->join('LEFT', $db->quoteName('#__djcf_categories', 'c'), 'c.id = a.cat_id')
				->where('a.id = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row))
			{
				$slug    = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;
				$catslug = $row->category_alias ? ($row->category_id . ':' . $row->category_alias) : $row->category_id;

				$info->title       = $row->name;
				$info->category_id = $row->category_id;
				$info->userid      = $row->user_id;
				$info->link        = Route::_(DJClassifiedsSEO::getItemRoute($slug, $catslug));
			}
		}

		return $info;
	}
}

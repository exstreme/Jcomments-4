<?php
/**
 * JComments plugin for DJ-Catalog2 objects support (https://dj-extensions.com/dj-catalog2)
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
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_djcatalog2 extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JcommentsObjectInfo;

		$djcatalog2RouterHelper = JPATH_ROOT . '/components/com_djcatalog2/helpers/route.php';

		if (is_file($djcatalog2RouterHelper))
		{
			require_once $djcatalog2RouterHelper;

			/** @var \Joomla\Database\DatabaseInterface $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('a.id', 'a.alias', 'a.name', 'a.created_by')))
				->select('c.id AS category_id, c.alias AS category_alias')
				->from($db->quoteName('#__djc2_items', 'a'))
				->join('LEFT', $db->quoteName('#__djc2_categories', 'c'), 'c.id = a.cat_id')
				->where('a.id = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row))
			{
				$slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;
				$catslug = $row->category_alias ? ($row->category_id . ':' . $row->category_alias) : $row->category_id;

				$info->title       = $row->name;
				$info->category_id = $row->category_id;
				$info->userid      = $row->created_by;
				$info->link        = Route::_(DJCatalog2HelperSiteRoute::buildRoute('getItemRoute', array($slug, $catslug)));
			}
		}

		return $info;
	}
}

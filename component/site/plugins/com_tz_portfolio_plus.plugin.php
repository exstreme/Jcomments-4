<?php
/**
 * JComments plugin for TZ Portfolio+ (https://www.tzportfolio.com/) support
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

class jc_com_tz_portfolio_plus extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JcommentsObjectInfo;

		$routerHelper = JPATH_ROOT . '/components/com_tz_portfolio_plus/helpers/route.php';

		if (is_file($routerHelper))
		{
			require_once $routerHelper;

			/** @var \Joomla\Database\DatabaseInterface $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('a.id', 'a.title', 'a.created_by', 'a.access', 'a.alias', 'a.catid')))
				->select($db->quoteName('c.alias', 'category_alias'))
				->from($db->quoteName('#__content', 'a'))
				->join('LEFT', $db->quoteName('#__categories', 'c'), 'c.id = a.catid')
				->where($db->quoteName('a.id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$row = $db->loadObject();

			if (!empty($row))
			{
				$row->slug    = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;
				$row->catslug = $row->category_alias ? ($row->catid . ':' . $row->category_alias) : $row->catid;

				$info->category_id = $row->category_id;
				$info->title       = $row->title;
				$info->userid      = $row->created_by;
				$info->link        = Route::_(TZ_Portfolio_PlusHelperRoute::getArticleRoute($row->slug, $row->catslug));
			}
		}

		return $info;
	}
}

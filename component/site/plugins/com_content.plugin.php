<?php
/**
 * JComments plugin for standart content objects support
 *
 * @version       2.3
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

require_once JPATH_ROOT . '/components/com_jcomments/classes/plugin.php';

class jc_com_content extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$link  = null;
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.id, a.title, a.created_by, a.access, a.alias, a.catid, a.language');
		$query->from('#__content AS a');

		// Join over the categories.
		$query->select('c.title AS category_title, c.path AS category_route, c.access AS category_access, c.alias AS category_alias');
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');
		$query->where('a.id = ' . (int) $id);

		$db->setQuery($query);
		$article = $db->loadObject();

		if (!empty($article))
		{
			$user = Factory::getUser();

			$article->slug    = $article->alias ? ($article->id . ':' . $article->alias) : $article->id;
			$article->catslug = $article->category_alias ? ($article->catid . ':' . $article->category_alias) : $article->catid;

			$authorised  = Access::getAuthorisedViewLevels($user->get('id'));
			$checkAccess = in_array($article->access, $authorised);

			if ($checkAccess)
			{
				$link = Route::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->language), false);
			}
			else
			{
				$returnURL = Route::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->language));
				$menu      = Factory::getApplication()->getMenu();
				$active    = $menu->getActive();
				$itemId    = $active->id;
				$link      = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
				$uri       = new Uri($link);
				$uri->setVar('return', base64_encode($returnURL));
				$link = $uri->toString();
			}
		}

		$info = new JCommentsObjectInfo;

		if (!empty($article))
		{
			$info->category_id = $article->catid;
			$info->title       = $article->title;
			$info->access      = $article->access;
			$info->userid      = $article->created_by;
			$info->link        = $link;
		}

		return $info;
	}
}

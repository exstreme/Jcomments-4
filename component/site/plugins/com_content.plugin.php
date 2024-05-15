<?php
/**
 * JComments plugin for standart content objects support
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\ParameterType;

class jc_com_content extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$link  = null;
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($db->quoteName(array('a.id', 'a.title', 'a.created_by', 'a.access', 'a.alias', 'a.catid', 'a.language')));
		$query->from($db->quoteName('#__content', 'a'));

		// Join over the categories.
		$query->select('c.title AS category_title, c.path AS category_route, c.access AS category_access, c.alias AS category_alias');
		$query->join('LEFT', $db->quoteName('#__categories', 'c'), 'c.id = a.catid');
		$query->where('a.id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$article = $db->loadObject();

		if (!empty($article))
		{
			$user = Factory::getApplication()->getIdentity();

			$article->slug    = $article->alias ? ($article->id . ':' . $article->alias) : $article->id;
			$article->catslug = $article->category_alias ? ($article->catid . ':' . $article->category_alias) : $article->catid;

			$authorised  = Access::getAuthorisedViewLevels($user->get('id'));
			$checkAccess = in_array($article->access, $authorised);

			if ($checkAccess)
			{
				$link = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catslug, $article->language), false);
			}
			else
			{
				$returnURL = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catslug, $article->language));
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

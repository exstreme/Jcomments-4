<?php
/**
 * JComments plugin for standart content objects support
 *
 * @version 2.3
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class jc_com_content extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$article = null;
		$link = null;

		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.id, a.title, a.created_by, a.access, a.alias, a.catid, a.language');
		$query->from('#__content AS a');

		// Join over the categories.
		$query->select('c.title AS category_title, c.path AS category_route, c.access AS category_access, c.alias AS category_alias');
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');
		$query->where('a.id = ' . (int) $id);

		$db->setQuery( $query );
		$article = $db->loadObject();

		if (!empty($article)) {
			$user = JFactory::getUser();

			$article->slug = $article->alias ? ($article->id.':'.$article->alias) : $article->id;
			$article->catslug = $article->category_alias ? ($article->catid.':'.$article->category_alias) : $article->catid;

			$authorised = JAccess::getAuthorisedViewLevels($user->get('id'));
			$checkAccess = in_array($article->access, $authorised);
			if ($checkAccess) {
				$link = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->language));
			} else {
				$returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->language));
				$menu = JFactory::getApplication()->getMenu();
				$active = $menu->getActive();
				$ItemId = $active->id;
				$link = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $ItemId);
				$uri = new JURI($link);
				$uri->setVar('return', base64_encode($returnURL));
				$link = $uri->toString();
			}
		}

		$info = new JCommentsObjectInfo();

		if (!empty($article)) {
			$info->category_id = $article->catid;
			$info->title = $article->title;
			$info->access = $article->access;
			$info->userid = $article->created_by;
			$info->link = $link;
		}

		return $info;
	}
}
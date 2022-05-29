<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;

class jc_com_content extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$app  = Factory::getApplication();
		$link = null;

		/** @var Joomla\Component\Content\Site\Model\ArticleModel $model */
		$model   = $app->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Site', ['ignore_request' => true]);
		$model->setState('params', $app->getParams('com_content'));
		$article = $model->getItem($id);

		if (!empty($article))
		{
			$article->slug    = $article->alias ? ($article->id . ':' . $article->alias) : $article->id;
			$article->catslug = $article->category_alias ? ($article->catid . ':' . $article->category_alias) : $article->catid;

			$authorised  = Access::getAuthorisedViewLevels($app->getIdentity()->get('id'));
			$checkAccess = in_array($article->access, $authorised);

			if ($checkAccess)
			{
				$link = Route::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->language), false);
			}
			else
			{
				$returnURL = Route::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->language));
				$menu      = $app->getMenu();
				$active    = $menu->getActive();
				$itemId    = $active->id;
				$link      = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
				$uri       = new Uri($link);
				$uri->setVar('return', base64_encode($returnURL));
				$link = $uri->toString();
			}
		}

		$info = new JcommentsObjectinfo;

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

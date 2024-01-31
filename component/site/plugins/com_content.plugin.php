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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\String\StringHelper;

/**
 * Class to get object information from com_content extension.
 *
 * @since  2.0
 */
class jc_com_content extends JcommentsPlugin
{
	/**
	 * Get object information for com_content
	 *
	 * @param   integer  $id        Article ID
	 * @param   mixed    $language  Language tag
	 *
	 * @return  object
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function getObjectInfo(int $id, $language = null)
	{
		$app = Factory::getApplication();
		$link = null;

		/** @var Joomla\Component\Content\Site\Model\ArticleModel $model */
		$model = $app->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Site', ['ignore_request' => true]);
		$model->setState('params', ComponentHelper::getParams('com_content'));

		try
		{
			$article = $model->getItem($id);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return new JcommentsObjectinfo;
		}

		if (!empty($article))
		{
			$article->slug    = $article->alias ? ($article->id . ':' . $article->alias) : $article->id;
			$article->catslug = $article->category_alias ? ($article->catid . ':' . $article->category_alias) : $article->catid;
			$language         = StringHelper::substr(!empty($language) ? $language : $article->language, 0, 2);
			$authorised       = Access::getAuthorisedViewLevels($app->getIdentity()->get('id'));
			$checkAccess      = in_array($article->access, $authorised);

			if ($checkAccess)
			{
				$link = Route::link('site', RouteHelper::getArticleRoute($article->slug, $article->catslug, $language), false);
			}
			else
			{
				$returnURL = Route::link('site', RouteHelper::getArticleRoute($article->slug, $article->catslug, $language), false);
				$menu      = $app->getMenu();
				$active    = $menu->getActive();
				$itemId    = $active->id;
				$link      = Route::link('site', 'index.php?option=com_users&view=login&Itemid=' . $itemId,  false);
				$uri       = new Uri($link);
				$uri->setVar('return', base64_encode($returnURL));
				$link = $uri->toString();
			}
		}

		$info = new JcommentsObjectinfo;

		if (!empty($article))
		{
			$info->catid    = $article->catid;
			$info->object_lang     = $article->language;
			$info->object_title = $article->title;
			$info->object_access   = $article->access;
			$info->object_owner   = $article->created_by;
			$info->object_link     = $link;
			$info->expired  = $article->publish_down;
			$info->modified = $article->modified;
		}

		return $info;
	}
}

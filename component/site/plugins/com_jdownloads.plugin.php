<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 *
 * Modified file based on modified plugin version for the jDownloads 4 series by Arno Betz
 * Date: 2023-02-16
 */

defined('_JEXEC') or die;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_jdownloads extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');
		$link = null;

		try
		{
			$query = $db->getQuery(true)
				->select(
					array(
						$db->quoteName('a.id'),
						$db->quoteName('a.title'),
						$db->quoteName('a.created_by'),
						$db->quoteName('a.access'),
						$db->quoteName('a.alias'),
						$db->quoteName('a.catid'),
						$db->quoteName('a.language')
					)
				)
				->from($db->quoteName('#__jdownloads_files', 'a'))
				->where($db->quoteName('a.id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			// Join over the categories.
			$query->select(
				array(
					$db->quoteName('c.title', 'category_title'),
					$db->quoteName('c.cat_dir', 'category_route'),
					$db->quoteName('c.access', 'category_access'),
					$db->quoteName('c.alias', 'category_alias')
				)
			)
				->leftJoin(
					$db->quoteName('#__jdownloads_categories', 'c'),
					$db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid')
				)
				->where($db->quoteName('a.id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$db->setQuery($query);
			$download = $db->loadObject();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
		}

		if (!empty($download))
		{
			$app = Factory::getApplication();
			$user = $app->getIdentity();

			$download->slug    = $download->alias ? ($download->id . ':' . $download->alias) : $download->id;
			$download->catslug = $download->category_alias ? ($download->catid . ':' . $download->category_alias) : $download->catid;

			$authorised  = Access::getAuthorisedViewLevels($user->get('id'));
			$checkAccess = in_array($download->access, $authorised);

			if ($checkAccess)
			{
				$link = Route::_(RouteHelper::getDownloadRoute($download->slug, $download->catslug, $download->language), false);
			}
			else
			{
				$returnURL  = Route::_(RouteHelper::getDownloadRoute($download->slug, $download->catslug, $download->language));
				$activeMenu = $app->getMenu()->getActive();
				$itemId     = $activeMenu->id;
				$link       = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
				$uri        = new Uri($link);
				$uri->setVar('return', base64_encode($returnURL));
				$link = $uri->toString();
			}
		}

		$info = new JcommentsObjectInfo;

		if (!empty($download))
		{
			$info->category_id = $download->catid;
			$info->title       = $download->title;
			$info->access      = $download->access;
			$info->userid      = $download->created_by;
			$info->link        = $link;
		}

		return $info;
	}
}

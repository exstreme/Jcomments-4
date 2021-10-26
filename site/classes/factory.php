<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;

/**
 * JComments Factory class
 */
class JCommentsFactory
{
	/**
	 * Returns a reference to the global {@link JCommentsSmiles} object, only creating it if it does not already exist.
	 *
	 * @return JCommentsSmilies
	 */
	public static function getSmilies()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JCommentsSmilies;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JCommentsBBCode} object, only creating it if it does not already exist.
	 *
	 * @return JCommentsBBCode
	 */
	public static function getBBCode()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JCommentsBBCode;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JCommentsCustombbcode} object, only creating it if it does not already exist.
	 *
	 * @return JCommentsCustombbcode
	 */
	public static function getCustomBBCode()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JCommentsCustombbcode;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JoomlaTuneTemplateRender} object, only creating it if it does not already exist.
	 *
	 * @param   integer  $objectID
	 * @param   string   $objectGroup
	 * @param   boolean  $needThisUrl
	 *
	 * @return JoomlaTuneTemplateRender
	 */
	public static function getTemplate($objectID = 0, $objectGroup = 'com_content', $needThisUrl = true)
	{
		ob_start();

		$app      = Factory::getApplication();
		$language = $app->getLanguage();
		$config   = ComponentHelper::getParams('com_jcomments');

		$templateName = $config->get('template');

		if (empty($templateName))
		{
			$templateName = 'default';
			$config->set('template', $templateName);
		}

		include_once JPATH_ROOT . '/components/com_jcomments/libraries/joomlatune/template.php';

		$templateDefaultDirectory = JPATH_ROOT . '/components/com_jcomments/tpl/' . $templateName;
		$templateDirectory        = $templateDefaultDirectory;
		$templateUrl              = Uri::root() . 'components/com_jcomments/tpl/' . $templateName;

		$templateOverride = JPATH_SITE . '/templates/' . $app->getTemplate() . '/html/com_jcomments/' . $templateName;

		if (is_dir($templateOverride))
		{
			$templateDirectory = $templateOverride;
			$templateUrl       = Uri::root() . 'templates/' . $app->getTemplate() . '/html/com_jcomments/' . $templateName;
		}

		$tmpl = JoomlaTuneTemplateRender::getInstance();
		$tmpl->setRoot($templateDirectory);
		$tmpl->setDefaultRoot($templateDefaultDirectory);
		$tmpl->setBaseURI($templateUrl);
		$tmpl->addGlobalVar('siteurl', Uri::root());
		$tmpl->addGlobalVar('charset', 'utf-8');
		$tmpl->addGlobalVar('ajaxurl', self::getLink('ajax', $objectID, $objectGroup));
		$tmpl->addGlobalVar('smilesurl', self::getLink('smilies', $objectID, $objectGroup));

		if ((int) $config->get('enable_rss') == 1)
		{
			$tmpl->addGlobalVar('rssurl', self::getLink('rss', $objectID, $objectGroup));
		}

		$tmpl->addGlobalVar('template', $templateName);
		$tmpl->addGlobalVar('template_url', $templateUrl);
		$tmpl->addGlobalVar('itemid', $app->input->getInt('Itemid') ?: 1);
		$tmpl->addGlobalVar('direction', $language->isRTL() ? 'rtl' : 'ltr');
		$tmpl->addGlobalVar('comment-object_id', $objectID);
		$tmpl->addGlobalVar('comment-object_group', $objectGroup);

		if ($needThisUrl == true)
		{
			$tmpl->addGlobalVar('thisurl', JCommentsObject::getLink($objectID, $objectGroup, $language->getTag()));
		}

		ob_end_clean();

		return $tmpl;
	}

	/**
	 * Returns a reference to the global {@link JCommentsACL} object,
	 * only creating it if it doesn't already exist.
	 *
	 * @return JCommentsACL
	 */
	public static function getACL()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JCommentsACL;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JoomlaTuneAjaxResponse} object,
	 * only creating it if it doesn't already exist.
	 *
	 * @return JoomlaTuneAjaxResponse
	 */
	public static function getAjaxResponse()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JoomlaTuneAjaxResponse('utf-8');
		}

		return $instance;
	}

	public static function getCmdHash($cmd, $id)
	{
		return md5($cmd . $id . JPATH_ROOT . Factory::getApplication()->get('secret'));
	}

	public static function getCmdLink($cmd, $id)
	{
		$hash     = self::getCmdHash($cmd, $id);
		$liveSite = trim(str_replace('/administrator', '', Uri::root()), '/');
		$liveSite = str_replace(Uri::root(true), '', $liveSite);

		return $liveSite . Route::_('index.php?option=com_jcomments&task=cmd&cmd=' . $cmd . '&id=' . $id . '&hash=' . $hash . '&format=raw', false);
	}

	public static function getLink($type = 'ajax', $objectID = 0, $objectGroup = '', $lang = '')
	{
		$app    = Factory::getApplication();
		$config = ComponentHelper::getParams('com_jcomments');

		switch ($type)
		{
			case 'rss':
				$link = 'index.php?option=com_jcomments&task=rss&object_id=' . $objectID . '&object_group=' . $objectGroup . '&format=raw';

				if ($app->isClient('administrator'))
				{
					$link = Uri::root(true) . '/' . $link;
				}
				else
				{
					$link = Route::_($link, false);
				}

				return $link;

			case 'noavatar':
				return Uri::root() . 'media/com_jcomments/images/no_avatar.png';

			case 'smiles':
			case 'smilies':
				return Uri::root(true) . '/' . trim(str_replace('\\', '/', $config->get('smilies_path')), '/') . '/';

			case 'captcha':
				mt_srand((double) microtime() * 1000000);
				$random = mt_rand(10000, 99999);

				return Route::_('index.php?option=com_jcomments&task=captcha&format=raw&ac=' . $random, false);

			case 'ajax':
				// Support alternate language files
				$lsfx = ($config->get('lsfx') != '') ? ('&lsfx=' . $config->get('lsfx')) : '';

				// Support additional param for multilingual sites
				if (!empty($lang))
				{
					$lang = '&lang=' . $lang;
				}

				$link = Route::_('index.php?option=com_jcomments&tmpl=component' . $lang . $lsfx, false);

				// Fix to prevent cross-domain ajax call
				if (isset($_SERVER['HTTP_HOST']))
				{
					$httpHost = (string) $_SERVER['HTTP_HOST'];

					if (strpos($httpHost, '://www.') !== false && strpos($link, '://www.') === false)
					{
						$link = str_replace('://', '://www.', $link);
					}
					elseif (strpos($httpHost, '://www.') === false && strpos($link, '://www.') !== false)
					{
						$link = str_replace('://www.', '://', $link);
					}
				}

				return $link;

			default:
				return '';
		}
	}

	/**
	 * Convert relative link to absolute (add http:// and site url)
	 *
	 * @param   string  $link  The relative url.
	 *
	 * @return  string
	 */
	public static function getAbsLink($link)
	{
		$url = Uri::getInstance()->toString(array('scheme', 'user', 'pass', 'host', 'port'));

		if (strpos($link, $url) === false)
		{
			$link = $url . $link;
		}

		return $link;
	}

	/**
	 * Return the current state of the language filter.
	 *
	 * @return	boolean
	 *
	 * @since	4.0
	 */
	public static function getLanguageFilter()
	{
		static $enabled = null;

		if (!isset($enabled))
		{
			$app = Factory::getApplication();

			// SiteApplication class is not available in admin.
			if ($app->isClient('site'))
			{
				$enabled = $app->getLanguageFilter();
			}
			else
			{
				/** @var DatabaseDriver $db */
				$db = Factory::getContainer()->get('DatabaseDriver');

				$query = $db->getQuery(true)
					->select($db->quoteName('enabled'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
					->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
					->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));

				$db->setQuery($query);
				$enabled = $db->loadResult();
			}
		}

		return (boolean) $enabled;
	}
}

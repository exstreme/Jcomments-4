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

namespace Joomla\Component\Jcomments\Site\Library\Jcomments;

defined('_JEXEC') or die;

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Database\DatabaseDriver;

/**
 * JComments Factory class
 */
class JcommentsFactory
{
	/**
	 * Returns a reference to the global {@link JcommentsSmilies} object, only creating it if it does not already exist.
	 *
	 * @return JcommentsSmilies
	 */
	public static function getSmilies()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsSmilies;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JcommentsBbcode} object, only creating it if it does not already exist.
	 *
	 * @return JcommentsBbcode
	 */
	public static function getBbcode()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsBbcode;
		}

		return $instance;
	}

	/**
	 * Returns a reference to the global {@link JcommentsCustombbcode} object, only creating it if it does not already exist.
	 *
	 * @return JcommentsCustombbcode
	 */
	public static function getCustomBBCode()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsCustombbcode;
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

		$tmpl = \JoomlaTuneTemplateRender::getInstance();
		$tmpl->setRoot($templateDirectory);
		$tmpl->setDefaultRoot($templateDefaultDirectory);
		$tmpl->setBaseURI($templateUrl);
		$tmpl->addGlobalVar('siteurl', Uri::root());
		$tmpl->addGlobalVar('charset', 'utf-8');
		$tmpl->addGlobalVar('ajaxurl', self::getLink('ajax', $objectID, $objectGroup));
		$tmpl->addGlobalVar('smilesurl', self::getLink('smilies', $objectID, $objectGroup));
		$tmpl->addGlobalVar('template', $templateName);
		$tmpl->addGlobalVar('template_url', $templateUrl);
		$tmpl->addGlobalVar('itemid', $app->input->getInt('Itemid') ?: 1);
		$tmpl->addGlobalVar('direction', $language->isRTL() ? 'rtl' : 'ltr');
		$tmpl->addGlobalVar('comment-object_id', $objectID);
		$tmpl->addGlobalVar('comment-object_group', $objectGroup);

		if ($needThisUrl == true)
		{
			$tmpl->addGlobalVar('thisurl', ObjectHelper::getObjectField('link', $objectID, $objectGroup, $language->getTag()));
		}

		ob_end_clean();

		return $tmpl;
	}

	/**
	 * Returns a reference to the global {@link JCommentsAcl} object,
	 * only creating it if it doesn't already exist.
	 *
	 * @return JCommentsAcl
	 *
	 * @since  4.0
	 */
	public static function getAcl()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$instance = new JcommentsAcl;
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

	/**
	 * @param   string   $cmd  Command. Can be 'publish', 'unpublish', 'delete', 'banIP'
	 * @param   integer  $id   Comment ID
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public static function getCmdHash(string $cmd, int $id): string
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
		$config = ComponentHelper::getParams('com_jcomments');

		switch ($type)
		{
			case 'smiles':
			case 'smilies':
				return Uri::root(true) . '/' . trim(str_replace('\\', '/', $config->get('smilies_path')), '/') . '/';

			case 'captcha':
				mt_srand((double) microtime() * 1000000);
				$random = mt_rand(10000, 99999);

				return Route::_('index.php?option=com_jcomments&task=captcha&format=raw&ac=' . $random, false);

			case 'ajax':
				// Support additional param for multilingual sites
				if (!empty($lang))
				{
					$lang = '&lang=' . $lang;
				}

				$link = Route::_('index.php?option=com_jcomments&tmpl=component' . $lang, false);

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

		return (bool) $enabled;
	}

	/**
	 * Get the return URL.
	 *
	 * If a "return" variable has been passed in the request
	 *
	 * @return  string    The return URL.
	 *
	 * @since   4.0
	 */
	public static function getReturnPage(): string
	{
		$input  = Factory::getApplication()->input;
		$return = $input->get('return', null, 'base64');

		if (empty($return) || !Uri::isInternal(base64_decode($return)))
		{
			return Uri::base();
		}
		else
		{
			return base64_decode($return);
		}
	}

	/**
	 * Get cache handler
	 *
	 * @param   string  $handler  Cache handler
	 * @param   array   $options  Additional options
	 *
	 * @return  Cache
	 *
	 * @since   4.0
	 */
	public static function getCache($handler, $options)
	{
		$handler = ($handler === 'function') ? 'callback' : $handler;
		$options['defaultgroup'] = array_key_exists('defaultgroup', $options) ? $options['defaultgroup'] : null;
		$options['storage'] = array_key_exists('storage', $options) ? $options['storage'] : null;

		/** @var Cache $cache */
		return Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController($handler, $options);
	}

	/**
	 * Remove a cached data entry by ID and group
	 *
	 * @param   string  $id       The cache data ID
	 * @param   string  $group    The cache data group
	 * @param   array   $options  Additional options. It is necessary to add frontend 'language', otherwise the cache
	 *                            key will be incorrect when calling this method from backend.
	 *
	 * @return  boolean
	 *
	 * @see CacheStorage::_getCacheId()
	 * @since   4.0
	 */
	public static function removeCache($id, $group = null, $options = array())
	{
		$options['defaultgroup'] = $group;

		try
		{
			$cache = self::getCache('callback', $options);

			return $cache->remove($id, $group);
		}
		catch (CacheExceptionInterface $e)
		{
			return false;
		}
	}

	/**
	 * Remove a cached data entry by group
	 *
	 * @param   string  $group    The cache data group
	 * @param   array   $options  Additional options
	 *
	 * @return  boolean
	 *
	 * @since   4.0
	 */
	public static function removeCacheGroup($group = null, $options = array())
	{
		$options['defaultgroup'] = $group;

		try
		{
			$cache = self::getCache('callback', $options);

			return $cache->clean($group);
		}
		catch (CacheExceptionInterface $e)
		{
			return false;
		}
	}
}

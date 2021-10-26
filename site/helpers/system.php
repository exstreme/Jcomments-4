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
use Joomla\CMS\Uri\Uri;

/**
 * JComments System Plugin Helper
 *
 * @since  3.0
 */
class JCommentsSystem
{
	public static function getCoreJS()
	{
		return Uri::root(true) . '/media/com_jcomments/js/jcomments-v2.3.js';
	}

	public static function getAjaxJS()
	{
		return Uri::root(true) . '/components/com_jcomments/libraries/joomlatune/ajax.js?v=4';
	}

	public static function getCSS($isRTL = false, $template = '')
	{
		$app = Factory::getApplication();

		if (empty($template))
		{
			$config   = ComponentHelper::getParams('com_jcomments');
			$template = $config->get('template');
		}

		$cssName = $isRTL ? 'style_rtl.css' : 'style.css';

		$cssPath = JPATH_SITE . '/templates/' . $app->getTemplate() . '/html/com_jcomments/' . $template . '/' . $cssName;
		$cssUrl  = Uri::root(true) . '/templates/' . $app->getTemplate() . '/html/com_jcomments/' . $template . '/' . $cssName;

		if (!is_file($cssPath))
		{
			$cssPath = JPATH_SITE . '/components/com_jcomments/tpl/' . $template . '/' . $cssName;
			$cssUrl  = Uri::root(true) . '/components/com_jcomments/tpl/' . $template . '/' . $cssName;

			if ($isRTL && !is_file($cssPath))
			{
				$cssUrl = '';
			}
		}

		return $cssUrl;
	}
}

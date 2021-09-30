<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

abstract class JHtmlJComments
{
	protected static $loaded = array();

	public static function stylesheet()
	{
		if (!empty(self::$loaded[__METHOD__]))
		{
			return;
		}

		$document = Factory::getApplication()->getDocument();
		$document->addStylesheet(JURI::root(true) . '/administrator/components/com_jcomments/assets/css/style.css', 'text/css', null);

		if (Factory::getApplication()->getLanguage()->isRTL())
		{
			$document->addStylesheet(JURI::root(true) . '/administrator/components/com_jcomments/assets/css/style_rtl.css', 'text/css', null);
		}

		self::$loaded[__METHOD__] = true;

		return;
	}

	public static function jquery()
	{
		if (!empty(self::$loaded[__METHOD__]))
		{
			return;
		}

		HTMLHelper::_('jquery.framework');

		self::$loaded[__METHOD__] = true;

		return;
	}

	public static function bootstrap()
	{
		if (!empty(self::$loaded[__METHOD__]))
		{
			return;
		}

		HTMLHelper::_('bootstrap.framework');

		self::$loaded[__METHOD__] = true;

		return;
	}

	public static function modal($name = '', $text = '', $url = '', $title = '', $onClose = '', $iconClass = 'out-2', $buttonClass = 'btn-small', $width = 500, $height = 300)
	{

		if (strlen($title) == 0)
		{
			$title = $text;
		}

		$text  = Text::_($text);
		$title = Text::_($title);

		$html = "<button class=\"btn btn-micro " . $buttonClass . "\" data-toggle=\"modal\" data-target=\"#modal-" . $name . "\">\n";
		$html .= "<i class=\"icon-" . $iconClass . "\">\n</i>\n";
		$html .= "$text\n";
		$html .= "</button>\n";

		// Build the options array for the modal
		$params           = array();
		$params['title']  = $title;
		$params['url']    = (substr($url, 0, 4) !== 'http') ? Uri::base() . $url : $url;
		$params['height'] = $height;
		$params['width']  = $width;
		$html             .= HTMLHelper::_('bootstrap.renderModal', 'modal-' . $name, $params);

		// If an $onClose event is passed, add it to the modal JS object
		if (strlen($onClose) >= 1)
		{
			$html .= "<script>\n";
			$html .= "jQuery('#modal-" . $name . "').on('hide', function () {\n";
			$html .= $onClose . ";\n";
			$html .= "}";
			$html .= ");";
			$html .= "</script>\n";
		}

		echo $html;
	}

	public static function usergroups($name, $selected, $checkSuperAdmin = false)
	{
		static $count;

		$count++;

		$isSuperAdmin = Factory::getApplication()->getIdentity()->authorise('core.admin');

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('a.*, COUNT(DISTINCT b.id) AS level')
			->from($db->quoteName('#__usergroups') . ' AS a')
			->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id, a.title, a.lft, a.rgt, a.parent_id')
			->order('a.lft ASC');

		$db->setQuery($query);
		$groups = $db->loadObjectList();

		$html = array();

		for ($i = 0, $n = count($groups); $i < $n; $i++)
		{
			$item = &$groups[$i];

			// If checkSuperAdmin is true, only add item if the user is superadmin or the group is not super admin
			if ((!$checkSuperAdmin) || $isSuperAdmin || (!JAccess::checkGroup($item->id, 'core.admin')))
			{
				// Setup  the variable attributes.
				$eid = $count . 'group_' . $item->id;

				// Don't call in_array unless something is selected
				$checked = '';
				if ($selected)
				{
					$checked = in_array($item->id, $selected) ? ' checked="checked"' : '';
				}
				$rel = ($item->parent_id > 0) ? ' rel="' . $count . 'group_' . $item->parent_id . '"' : '';

				// Build the HTML for the item.
				$html[] = '	<div class="control-group">';
				$html[] = '		<div class="controls">';
				$html[] = '			<label class="checkbox" for="' . $eid . '">';
				$html[] = '			<input type="checkbox" name="' . $name . '[]" value="' . $item->id . '" id="' . $eid . '"';
				$html[] = '					' . $checked . $rel . ' />';
				$html[] = '			' . str_repeat('<span class="gi">|&mdash;</span>', $item->level) . $item->title;
				$html[] = '			</label>';
				$html[] = '		</div>';
				$html[] = '	</div>';
			}
		}

		return implode("\n", $html);
	}
}

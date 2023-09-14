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

namespace Joomla\Component\Jcomments\Administrator\Service\Html;

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;

class Jcomments
{
	public function modal($name = '', $text = '', $url = '', $title = '', $onClose = '', $iconClass = 'out-2', $buttonClass = 'btn-small', $width = 500, $height = 300)
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
		$params['url']    = (StringHelper::substr($url, 0, 4) !== 'http') ? Uri::base() . $url : $url;
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
}

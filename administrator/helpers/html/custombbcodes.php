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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

abstract class JHtmlCustomBBCodes
{
	public static function buttonState($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states = array(
			1 => array(
				'button_disable',
				'JENABLED',
				'A_DISABLE',
				'JENABLED',
				true,
				'publish',
				'publish'
			),
			0 => array(
				'button_enable',
				'JDISABLED',
				'A_ENABLE',
				'JDISABLED',
				true,
				'unpublish',
				'unpublish'
			),
		);

		return HTMLHelper::_('jgrid.state', $states, $value, $i, 'custombbcodes.', $enabled, true, $checkbox);
	}

	public static function sample($text)
	{
		$html = '<div class="jcomments-sample span4">';
		$html .= '<div class="jcomments-sample-heading">' . Text::_('A_CUSTOM_BBCODE_EXAMPLE') . '</div>';
		$html .= '<div class="jcomments-sample-text">' . $text . '</div>';
		$html .= '</div>';

		return $html;
	}
}

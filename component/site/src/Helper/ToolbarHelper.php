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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * JComments editor toolbar class
 *
 * @since  4.1
 */
class ToolbarHelper
{
	/**
	 * Prepare custom buttons for editor toolbar
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public static function prepareCustomToolbar(): array
	{
		$customButtons = JcommentsFactory::getBbcode()->getCustomBbcodesList()['raw'];
		$buttons = array();

		foreach ($customButtons as $button)
		{
			if ($button->button_enabled && $button->canUse)
			{
				$buttons[] = $button->tagName;
			}
		}

		return array_filter(array_unique($buttons));
	}

	/**
	 * Make final editor toolbar
	 *
	 * @param   object|null  $buttons  Editor buttons
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	public static function buildToolbar(?object $buttons): string
	{
		$buttons = JcommentsFactory::getBbcode()->getStandardCodes($buttons)['buttons'];
		$customButtons = self::prepareCustomToolbar();

		// Remove duplicate buttons from editor builtin buttons list, so we can override editor buttons with custom buttons.
		foreach ($buttons as $i => $button)
		{
			if (in_array($button, $customButtons) && $button !== '|')
			{
				unset($buttons[$i]);
			}
		}

		if (count($customButtons))
		{
			array_unshift($customButtons, '|');
		}

		$buttons = array_merge($buttons, $customButtons);
		$buttons = implode(',', $buttons);
		$buttons = str_replace(array(',|', '|,'), '|', $buttons);
		$buttons = preg_replace('~\|+~', '|', $buttons);

		return trim($buttons, '|');
	}
}

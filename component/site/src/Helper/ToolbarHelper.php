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

use Joomla\CMS\Factory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * JComments editor toolbar class
 *
 * @since  4.1
 */
class ToolbarHelper
{
	/**
	 * Get all builtin buttons with or without separator.
	 *
	 * @param   object|null  $buttons  Editor buttons
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public static function getStandardButtons($buttons = array()): array
	{
		$user = Factory::getApplication()->getIdentity();
		$_buttons = array();

		if (empty($buttons))
		{
			$buttons = ComponentHelper::getParams('com_jcomments')->get('editor_buttons');
		}

		if (!empty($buttons))
		{
			foreach ($buttons as $button)
			{
				if ($button->btn != '|')
				{
					$canUse = $user->authorise('comment.bbcode.' . $button->btn, 'com_jcomments');

					if ($button->btn == 'bold')
					{
						$canUse = $user->authorise('comment.bbcode.b', 'com_jcomments');
					}

					if ($button->btn == 'italic')
					{
						$canUse = $user->authorise('comment.bbcode.i', 'com_jcomments');
					}

					if ($button->btn == 'underline')
					{
						$canUse = $user->authorise('comment.bbcode.u', 'com_jcomments');
					}

					if ($button->btn == 'strike')
					{
						$canUse = $user->authorise('comment.bbcode.s', 'com_jcomments');
					}

					if ($button->btn == 'subscript')
					{
						$canUse = $user->authorise('comment.bbcode.sub', 'com_jcomments');
					}

					if ($button->btn == 'superscript')
					{
						$canUse = $user->authorise('comment.bbcode.sup', 'com_jcomments');
					}

					if ($button->btn == 'bulletlist' || $button->btn == 'orderedlist')
					{
						$canUse = $user->authorise('comment.bbcode.list', 'com_jcomments');
					}

					if ($button->btn == 'horizontalrule')
					{
						$canUse = $user->authorise('comment.bbcode.hr', 'com_jcomments');
					}

					if ($button->btn == 'image')
					{
						$canUse = $user->authorise('comment.bbcode.img', 'com_jcomments');
					}

					if ($button->btn == 'link')
					{
						$canUse = $user->authorise('comment.bbcode.url', 'com_jcomments');
					}

					if ($canUse)
					{
						$_buttons[] = $button->btn;
					}
				}
				else
				{
					$_buttons[] = '|';
				}
			}
		}

		return $_buttons;
	}

	/**
	 * Prepare custom buttons for editor toolbar
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public static function getCustomButtons(): array
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
		$buttons = self::getStandardButtons($buttons);
		$customButtons = self::getCustomButtons();

		if (count($customButtons))
		{
			// Remove duplicate buttons from editor builtin buttons list, so we can override editor buttons with custom buttons.
			foreach ($buttons as $i => $button)
			{
				if (in_array($button, $customButtons) && $button !== '|')
				{
					unset($buttons[$i]);
				}
			}

			array_unshift($customButtons, '|');
			$buttons = array_merge($buttons, $customButtons);
		}

		$buttons = implode(',', $buttons);
		$buttons = str_replace(array(',|', '|,'), '|', $buttons);
		$buttons = preg_replace('~\|+~', '|', $buttons);

		return trim($buttons, '|');
	}
}

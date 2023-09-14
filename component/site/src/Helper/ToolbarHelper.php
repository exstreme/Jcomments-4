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

/**
 * JComments editor toolbar class
 *
 * @since  4.1
 */
class ToolbarHelper
{
	/**
	 * Get all buttons for editor toolbar in format: array('button_name' => 'acl value', ...)
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public static function getButtons(): array
	{
		$user     = Factory::getApplication()->getIdentity();
		$buttons  = ComponentHelper::getParams('com_jcomments')->get('editor_buttons');
		$_buttons = array();

		if (empty($buttons))
		{
			return array();
		}

		foreach ($buttons as $button)
		{
			if ($button->btn == '|')
			{
				continue;
			}

			$_buttons[$button->btn] = $user->authorise('comment.bbcode.' . $button->btn, 'com_jcomments');
		}

		return $_buttons;
	}

	/**
	 * Prepare buttons for editor toolbar
	 *
	 * @param   object|null  $buttons   An object with buttons
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	public static function prepareToolbar(?object $buttons): string
	{
		$user = Factory::getApplication()->getIdentity();
		$_buttons = array();

		if (empty($buttons))
		{
			return '';
		}

		foreach ($buttons as $button)
		{
			if (!$user->authorise('comment.bbcode.' . $button->btn, 'com_jcomments') && $button->btn != '|')
			{
				continue;
			}

			$_buttons[] = $button->btn;
		}

		$_buttons = implode(',', $_buttons);
		$_buttons = str_replace(array(',|', '|,'), '|', $_buttons);
		$_buttons = preg_replace('~\|+~', '|', $_buttons);

		return trim($_buttons, '|');
	}
}

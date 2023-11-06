<?php
/**
 * JComments editors-xtd plugin - Provides button for editor.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Plugin\EditorsXtd\JcommentsLock\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Provides button to insert {jcomments lock} into content edit box
 *
 * @since  1.5
 */
final class JcommentsLock extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * JComments Lock button
	 *
	 * @param   string  $name  Editor field name
	 *
	 * @return  CMSObject  $button
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function onDisplay(string $name)
	{
		$js = "
		function insertJCommentsLock(editor) {
			var content = Joomla.editors.instances['$name'].getValue();

			if (!content.match(/{jcomments lock}/)) {
				Joomla.editors.instances['$name'].replaceSelection('{jcomments lock}');
			}
		}";

		Factory::getApplication()->getDocument()->addScriptDeclaration($js);

		// CMSObject only used. Using Registry or stdClass will cause an errors.
		$button          = new CMSObject;
		$button->class   = 'btn';
		$button->modal   = false;
		$button->onclick = 'insertJCommentsLock(\'' . $name . '\');return false;';
		$button->text    = Text::_('PLG_EDITORS-XTD_JCOMMENTSLOCK_BUTTON_JCOMMENTSLOCK');
		$button->name    = 'blank';
		$button->link    = '#';
		$button->icon    = 'lock';

		return $button;
	}
}

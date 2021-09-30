<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Provides button to insert {jcomments on} into content edit box
 *
 * @since  1.5
 */
class plgButtonJCommentsOn extends CMSPlugin
{
	protected $autoloadLanguage = true;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_editors-xtd_jcommentson', JPATH_ADMINISTRATOR);
	}

	/**
	 * JComments On button
	 *
	 * @param string $name Editor field name
	 *
	 * @return  CMSObject  $button
	 *
	 * @throws  Exception
	 * @since   1.5
	 */
	public function onDisplay($name)
	{
		$js = "
		function insertJCommentsOn(editor) {
			var content = Joomla.editors.instances['$name'].getValue();

			if (!content.match(/{jcomments on}/)) {
				Joomla.editors.instances['$name'].replaceSelection('{jcomments on}');
			}
		}";

		Factory::getApplication()->getDocument()->addScriptDeclaration($js);

		// CMSObject only used. Using Registry or stdClass will cause an errors.
		$button          = new CMSObject;
		$button->class   = 'btn';
		$button->modal   = false;
		$button->onclick = 'insertJCommentsOn(\'' . $name . '\');return false;';
		$button->text    = Text::_('PLG_EDITORS-XTD_JCOMMENTSON_BUTTON_JCOMMENTSON');
		$button->name    = 'blank';
		$button->link    = '#';

		return $button;
	}
}

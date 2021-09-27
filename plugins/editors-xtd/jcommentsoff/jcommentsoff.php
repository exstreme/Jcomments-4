<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 3.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Provides button to insert {jcomments off} into content edit box
 *
 * @since  1.5
 */
class plgButtonJCommentsOff extends CMSPlugin
{
	protected $autoloadLanguage = true;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_editors-xtd_jcommentson', JPATH_ADMINISTRATOR);
	}

	/**
	 * JComments Off button
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
		function insertJCommentsOff(editor) {
			var content = Joomla.editors.instances['$name'].getValue();

			if (!content.match(/{jcomments off}/)) {
				Joomla.editors.instances['$name'].replaceSelection('{jcomments off}');
			}
		}";

		Factory::getApplication()->getDocument()->addScriptDeclaration($js);

		$button = new CMSObject();
		$button->set('class', 'btn');
		$button->set('modal', false);
		$button->set('onclick', 'insertJCommentsOff(\'' . $name . '\');return false;');
		$button->set('text', Text::_('PLG_EDITORS-XTD_JCOMMENTSOFF_BUTTON_JCOMMENTSOFF'));
		$button->set('name', 'blank');
		$button->set('link', '#');

		return $button;
	}
}

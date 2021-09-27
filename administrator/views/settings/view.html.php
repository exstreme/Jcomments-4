<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JCommentsViewSettings extends HtmlView
{
	protected $item;
	protected $form;
	protected $state;
	protected $languages;
	protected $groups;
	protected $permissionForms;

	function display($tpl = null)
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$this->item            = $this->get('Item');
		$this->form            = $this->get('Form');
		$this->groups          = $this->get('UserGroups');
		$this->permissionForms = $this->get('PermissionForms');
		$this->state           = $this->get('State');

		$languages        = $this->get('Languages');
		$language_options = array();

		if (count($languages))
		{
			// $language_options[] = JHTML::_('select.option', '', JText::_('JALL_LANGUAGE'));
			foreach ($languages as $language)
			{
				$language_options[] = HTMLHelper::_('select.option', $language->lang_code, $language->title);
			}
		}

		$language = $this->state->get('settings.language');

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		HTMLHelper::_('behavior.formvalidator');
		HTMLHelper::_('bootstrap.tooltip');


		HTMLHelper::_('formbehavior.chosen', 'select:not(.jcommentscategories)');

		HTMLHelper::_('jcomments.stylesheet');

		JCommentsHelper::addSubmenu('settings');
		Sidebar::setAction('index.php?option=com_jcomments&view=settings');

		if (count($languages))
		{
			Sidebar::addFilter(
				JText::_('JOPTION_SELECT_LANGUAGE'),
				'language',
				HTMLHelper::_('select.options', $language_options, 'value', 'text', $language, true)
			);
		}

		$this->bootstrap = true;
		$this->sidebar   = Sidebar::render();

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$canDo = JCommentsHelper::getActions();

		ToolbarHelper::title(JText::_('A_SETTINGS'));

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::apply('settings.save');
		}

		ToolbarHelper::cancel('settings.cancel');

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::divider();
			ToolbarHelper::preferences('com_jcomments', '600', '800');
		}
	}
}

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
use Joomla\CMS\HTML\HTMLHelper;
class JCommentsViewCustombbcode extends JCommentsViewLegacy
{
	protected $item;
	protected $groups;
	protected $form;
	protected $state;

	function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');
		$this->state = $this->get('State');
		$this->groups = empty($this->item->button_acl) ? array() : explode(',', $this->item->button_acl);

		JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		if (version_compare(JVERSION, '4.0', '<')){
			JHtml::_('behavior.tooltip');
			JHtml::_('behavior.formvalidation');
		} else {
			HTMLHelper::_('bootstrap.tooltip');
			HTMLHelper::_('behavior.formvalidator');
		}

		if (version_compare(JVERSION, '4.0', '<')){
			if (version_compare(JVERSION, '3.0', 'ge')) {
				JHtml::_('formbehavior.chosen', 'select');
				$this->bootstrap = true;
			} else {
				JHtml::_('jcomments.bootstrap');
			}
		} else {
			HTMLHelper::_('formbehavior.chosen', 'select');
		}


		JHtml::_('jcomments.stylesheet');

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$userId = JFactory::getUser()->get('id');
		$canDo = JCommentsHelper::getActions();
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$isNew = ($this->item->id == 0);

		JFactory::getApplication()->input->set('hidemainmenu', 1);

		if (version_compare(JVERSION, '3.0', 'ge')) {
			JToolBarHelper::title(JText::_('A_CUSTOM_BBCODE'));
		} else {
			JToolBarHelper::title(JText::_('A_CUSTOM_BBCODE_EDIT'), 'jcomments-custombbcodes');
		}

		if (!$checkedOut && $canDo->get('core.edit')) {
			JToolBarHelper::apply('custombbcode.apply');
			JToolBarHelper::save('custombbcode.save');
		}

		if (!$isNew && $canDo->get('core.create')) {
			JToolbarHelper::save2copy('custombbcode.save2copy');
		}

		if ($isNew) {
			JToolBarHelper::cancel('custombbcode.cancel');
		} else {
			JToolBarHelper::cancel('custombbcode.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
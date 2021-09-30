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
class JCommentsViewSubscription extends JCommentsViewLegacy
{
	protected $item;
	protected $form;
	protected $state;

	function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');
		$this->state = $this->get('State');

		JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

        HTMLHelper::_('bootstrap.tooltip');
        HTMLHelper::_('behavior.formvalidator');
        HTMLHelper::_('formbehavior.chosen', 'select');

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
			JToolBarHelper::title(JText::_('A_SUBSCRIPTIONS'));
		} else {
			$title = $isNew ? JText::_('A_SUBSCRIPTION_NEW') : JText::_('A_SUBSCRIPTION_EDIT');
			JToolBarHelper::title($title, 'jcomments-subscriptions');
		}

		if (!$checkedOut && $canDo->get('core.edit')) {
			JToolBarHelper::apply('subscription.apply');
			JToolBarHelper::save('subscription.save');
		}

		if (!$isNew && $canDo->get('core.create')) {
			JToolbarHelper::save2new('subscription.save2new');
		}

		if ($isNew) {
			JToolBarHelper::cancel('subscription.cancel');
		} else {
			JToolBarHelper::cancel('subscription.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
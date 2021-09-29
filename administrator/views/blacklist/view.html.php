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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JCommentsViewBlacklist extends HtmlView
{
	protected $item;
	protected $form;
	protected $state;

	function display($tpl = null)
	{
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');
		$this->state = $this->get('State');

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$userId     = Factory::getApplication()->getIdentity()->get('id');
		$canDo      = JCommentsHelper::getActions();
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$isNew      = ($this->item->id == 0);

		Factory::getApplication()->input->set('hidemainmenu', 1);
		ToolbarHelper::title(JText::_('A_BLACKLIST'));

		if (!$checkedOut && $canDo->get('core.edit'))
		{
			ToolbarHelper::apply('blacklist.apply');
			ToolbarHelper::save('blacklist.save');
		}

		if (!$isNew && $canDo->get('core.create'))
		{
			JToolbarHelper::save2new('blacklist.save2new');
		}

		if ($isNew)
		{
			ToolbarHelper::cancel('blacklist.cancel');
		}
		else
		{
			ToolbarHelper::cancel('blacklist.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}

<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

class JCommentsViewCustombbcode extends HtmlView
{
	protected $item;
	protected $groups;
	protected $form;
	protected $state;

	function display($tpl = null)
	{
		$this->item   = $this->get('Item');
		$this->form   = $this->get('Form');
		$this->state  = $this->get('State');
		$this->groups = empty($this->item->button_acl) ? array() : explode(',', $this->item->button_acl);

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		$app        = Factory::getApplication();
		$userId     = $app->getIdentity()->get('id');
		$canDo      = ContentHelper::getActions('com_jcomments', 'component');
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$isNew      = ($this->item->id == 0);

		$app->input->set('hidemainmenu', 1);
		ToolbarHelper::title(Text::_('A_CUSTOM_BBCODE'));

		if (!$checkedOut && $canDo->get('core.edit'))
		{
			ToolbarHelper::apply('custombbcode.apply');
			ToolbarHelper::save('custombbcode.save');
		}

		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('custombbcode.save2copy');
		}

		if ($isNew)
		{
			ToolbarHelper::cancel('custombbcode.cancel');
		}
		else
		{
			ToolbarHelper::cancel('custombbcode.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
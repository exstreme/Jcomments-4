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

class JCommentsViewComment extends HtmlView
{
	protected $item;
	protected $reports;
	protected $form;
	protected $state;

	function display($tpl = null)
	{
		$this->item      = $this->get('Item');
		$this->reports   = $this->get('Reports');
		$this->form      = $this->get('Form');
		$this->state     = $this->get('State');

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$userId     = Factory::getApplication()->getIdentity()->get('id');
		$canDo      = ContentHelper::getActions('com_jcomments', 'component');
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

		Factory::getApplication()->input->set('hidemainmenu', 1);

		ToolbarHelper::title(Text::_('A_COMMENT_EDIT'), 'jcomments-comments');

		if (!$checkedOut && $canDo->get('core.edit'))
		{
			ToolbarHelper::apply('comment.apply');
			ToolbarHelper::save('comment.save');
		}

		ToolbarHelper::cancel('comment.cancel', 'JTOOLBAR_CLOSE');
	}
}

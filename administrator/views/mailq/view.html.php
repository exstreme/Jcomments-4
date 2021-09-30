<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

class JCommentsViewMailq extends JCommentsViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;

	function display($tpl = null)
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
		JCommentsHelper::addSubmenu('mailq');

		$this->addToolbar();

		parent::display($tpl);

	}

	protected function addToolbar()
	{
		$canDo = JCommentsHelper::getActions();

		ToolbarHelper::title(Text::_('A_MAILQ'), 'jcomments-mailq');

		if (($canDo->get('core.delete')))
		{
			ToolbarHelper::deletelist('', 'mailq.delete');
			ToolbarHelper::divider();
			ToolbarHelper::custom('mailq.purge', 'purge', 'icon-32-unpublish.png', 'A_MAILQ_PURGE_ITEMS', false);
		}
	}

	protected function getSortFields()
	{
		return array(
			'name'     => Text::_('A_MAILQ_HEADING_NAME'),
			'email'    => Text::_('A_MAILQ_HEADING_EMAIL'),
			'subject'  => Text::_('A_MAILQ_HEADING_SUBJECT'),
			'priority' => Text::_('A_MAILQ_HEADING_PRIORITY'),
			'attempts' => Text::_('A_MAILQ_HEADING_ATTEMPTS'),
			'created'  => Text::_('A_MAILQ_HEADING_CREATED'),
			'id'       => Text::_('JGRID_HEADING_ID')
		);
	}
}

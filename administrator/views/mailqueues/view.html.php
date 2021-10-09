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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

defined('_JEXEC') or die;

class JCommentsViewMailqueues extends HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	public $filterForm;
	public $activeFilters;

	function display($tpl = null)
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->state         = $this->get('State');

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		$this->addToolbar();

		parent::display($tpl);

	}

	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_jcomments', 'component');

		ToolbarHelper::title(Text::_('A_MAILQ'));

		if (($canDo->get('core.delete')))
		{
			ToolbarHelper::deletelist('', 'mailqueues.delete');
			ToolbarHelper::divider();
			ToolbarHelper::custom('mailqueues.purge', 'purge', '', 'A_MAILQ_PURGE_ITEMS', false);
		}
	}
}

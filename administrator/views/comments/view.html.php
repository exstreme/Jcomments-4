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

use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

class JCommentsViewComments extends HtmlView
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
		Sidebar::setAction('index.php?option=com_jcomments&view=comments');

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_jcomments', 'component');

		ToolbarHelper::title(Text::_('A_SUBMENU_COMMENTS'), 'jcomments-comments');

		if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::publishList('comments.publish');
			ToolbarHelper::unpublishList('comments.unpublish');
			ToolbarHelper::checkin('comments.checkin');
		}

		if ($canDo->get('core.delete'))
		{
			ToolbarHelper::deletelist('', 'comments.delete');
		}

		$bar = Toolbar::getInstance();
		$bar->appendButton('Popup', 'refresh', 'A_REFRESH_OBJECTS_INFO',
			'index.php?option=com_jcomments&amp;task=objects.refresh&amp;tmpl=component',
			500, 210, null, null, 'window.location.reload();', Text::_('A_COMMENTS'));
	}
}

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

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JCommentsViewBlacklists extends HtmlView
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

		JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
		JHtmlSidebar::setAction('index.php?option=com_jcomments&view=blacklists');

		$this->addToolbar();

		parent::display($tpl);

	}

	protected function addToolbar()
	{
		$canDo = JCommentsHelper::getActions();

		ToolbarHelper::title(Text::_('A_SUBMENU_BLACKLIST'), 'jcomments-blacklist');

		if (($canDo->get('core.create')))
		{
			ToolbarHelper::addNew('blacklist.add');
		}

		if (($canDo->get('core.edit')))
		{
			ToolbarHelper::editList('blacklist.edit');
		}

		if (($canDo->get('core.delete')))
		{
			ToolbarHelper::deletelist('', 'blacklists.delete');
		}
	}
}

<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Administrator\View\Subscriptions;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

class HtmlView extends BaseHtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	public $filterForm;
	public $activeFilters;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->state         = $this->get('State');

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 *
	 * @throws  \Exception
	 */
	protected function addToolbar()
	{
		$toolbar = Toolbar::getInstance();
		$canDo   = ContentHelper::getActions('com_jcomments', 'component');
		$user    = Factory::getApplication()->getIdentity();

		ToolbarHelper::title(Text::_('A_SUBMENU_SUBSCRIPTIONS'), 'envelope');

		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('subscription.add');
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('subscription.edit');
		}

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		if ($canDo->get('core.edit.state'))
		{
			$childBar->publish('subscriptions.publish')->listCheck(true);
			$childBar->unpublish('subscriptions.unpublish')->listCheck(true);
			$childBar->checkin('subscriptions.checkin')->listCheck(true);
		}

		if ($canDo->get('core.delete'))
		{
			$childBar->delete('subscriptions.delete')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		if ($user->authorise('core.admin', 'com_jcomments') || $user->authorise('core.options', 'com_jcomments'))
		{
			$toolbar->preferences('com_jcomments');
		}
	}
}

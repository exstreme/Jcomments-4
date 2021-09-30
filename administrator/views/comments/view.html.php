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

use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

class JCommentsViewComments extends HtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    function display($tpl = null)
    {
        require_once JPATH_COMPONENT . '/helpers/jcomments.php';

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        $filter_object_group = $this->state->get('filter.object_group');
        $filter_language = $this->state->get('filter.language');
        $filter_state = $this->state->get('filter.state');

        // Filter by published state
        $filter_state_options = array();
        $filter_state_options[] = HTMLHelper::_('select.option', '1', Text::_('A_FILTER_STATE_PUBLISHED'));
        $filter_state_options[] = HTMLHelper::_('select.option', '0', Text::_('A_FILTER_STATE_UNPUBLISHED'));
        $filter_state_options[] = HTMLHelper::_('select.option', '2', Text::_('A_FILTER_STATE_REPORTED'));

        // Filter by component (object_group)
        $filter_object_group_options = array();
        $object_groups = $this->get('FilterObjectGroups');
        foreach ($object_groups as $object_group) {
            $filter_object_group_options[] = HTMLHelper::_('select.option', $object_group->name, $object_group->name);
        }

        // Filter by language
        $filter_language_options = array();
        $languages = $this->get('FilterLanguages');
        foreach ($languages as $language) {
            $filter_language_options[] = HTMLHelper::_('select.option', $language->name, $language->name);
        }

        HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

        HTMLHelper::_('jcomments.stylesheet');

        //HTMLHelper::_('bootstrap.tooltip');
        HTMLHelper::_('formbehavior.chosen', 'select');

        Sidebar::setAction('index.php?option=com_jcomments&view=comments');


        $this->bootstrap = true;
        $this->sidebar = Sidebar::render();


        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $canDo = JCommentsHelper::getActions();

        ToolbarHelper::title(Text::_('A_SUBMENU_COMMENTS'), 'jcomments-comments');

        if (($canDo->get('core.edit'))) {
            ToolbarHelper::editList('comment.edit');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publishList('comments.publish');
            ToolbarHelper::unpublishList('comments.unpublish');
            ToolbarHelper::checkin('comments.checkin');
        }

        if (($canDo->get('core.delete'))) {
            ToolbarHelper::deletelist('', 'comments.delete');
        }

        ToolbarHelper::divider();

        $bar = JToolBar::getInstance('toolbar');
        $bar->appendButton('Popup', 'refresh', 'A_REFRESH_OBJECTS_INFO',
            'index.php?option=com_jcomments&amp;task=objects.refresh&amp;tmpl=component',
            500, 210, null, null, 'window.location.reload();', 'A_COMMENTS');
    }

    protected function getSortFields()
    {
        return array(
            'jc.published' => Text::_('JSTATUS'),
            'jc.title' => Text::_('A_COMMENT_TITLE'),
            'jc.name' => Text::_('A_COMMENT_NAME'),
            'jc.object_group' => Text::_('A_COMPONENT'),
            'jo.title' => Text::_('A_COMMENT_OBJECT_TITLE'),
            'jc.date' => Text::_('A_COMMENT_DATE'),
            'jc.id' => Text::_('JGRID_HEADING_ID')
        );
    }
}
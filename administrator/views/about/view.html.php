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

use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JCommentsViewAbout extends HtmlView
{
	function display($tpl = null)
	{
		require_once(JPATH_COMPONENT . '/helpers/jcomments.php');
		require_once(JPATH_COMPONENT . '/version.php');

		$this->version = new JCommentsVersion();
		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
		ToolbarHelper::title(JText::_('A_SUBMENU_ABOUT'));

		parent::display($tpl);
	}
}

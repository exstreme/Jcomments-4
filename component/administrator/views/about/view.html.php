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

use Joomla\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Base HTML View class for the a About component
 *
 * @since  3.0
 */
class JCommentsViewAbout extends HtmlView
{
	/**
	 * Component data from XML file
	 *
	 * @var    array
	 * @since  4.0
	 */
	protected $component;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @see     HtmlView::loadTemplate()
	 * @since   3.0
	 */
	public function display($tpl = null)
	{
		$this->component = Installer::parseXMLInstallFile(Path::clean(JPATH_ROOT . '/administrator/components/com_jcomments/jcomments.xml'));

		ToolbarHelper::title(Text::_('A_SUBMENU_ABOUT'));
		ToolbarHelper::preferences('com_jcomments');

		parent::display($tpl);
	}
}

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

namespace Joomla\Component\Jcomments\Administrator\View\About;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Filesystem\Path;

/**
 * Base HTML View class for 'About' view
 *
 * @since  4.0
 */
class HtmlView extends BaseHtmlView
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
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function display($tpl = null)
	{
		$toolbar = Toolbar::getInstance();
		$user    = Factory::getApplication()->getIdentity();
		$this->component = Installer::parseXMLInstallFile(Path::clean(JPATH_ROOT . '/administrator/components/com_jcomments/jcomments.xml'));

		ToolbarHelper::title(Text::_('A_SUBMENU_ABOUT'));

		if ($user->authorise('core.admin', 'com_jcomments') || $user->authorise('core.options', 'com_jcomments'))
		{
			$toolbar->preferences('com_jcomments');
		}

		parent::display($tpl);
	}
}

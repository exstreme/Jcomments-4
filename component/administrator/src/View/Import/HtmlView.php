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

namespace Joomla\Component\Jcomments\Administrator\View\Import;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	protected $items;
	protected $state;

	public function display($tpl = null)
	{
		$this->items = $this->get('Items');
		$this->state = $this->get('State');

		ToolbarHelper::title(Text::_('A_IMPORT'));

		parent::display($tpl);

	}

	public function modal($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->importUrl  = 'index.php?option=com_jcomments&task=import.ajax&tmpl=component';
		$this->objectsUrl = str_replace('/administrator', '', Route::_('index.php?option=com_jcomments&task=refreshObjectsAjax&amp;tmpl=component'));
		$this->hash       = md5(Factory::getApplication()->get('secret'));

		parent::display($tpl);
	}
}

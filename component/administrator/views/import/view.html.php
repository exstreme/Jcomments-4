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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

class JCommentsViewImport extends HtmlView
{
	protected $items;
	protected $state;

	function display($tpl = null)
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$this->items = $this->get('Items');
		$this->state = $this->get('State');

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
		ToolbarHelper::title(Text::_('A_IMPORT'));

		parent::display($tpl);

	}

	public function modal($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->importUrl  = 'index.php?option=com_jcomments&task=import.ajax&tmpl=component';
		$this->objectsUrl = str_replace('/administrator', '', Route::_('index.php?option=com_jcomments&task=refreshObjectsAjax&amp;tmpl=component'));
		$this->hash       = md5(Factory::getApplication()->get('secret'));

		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		parent::display($tpl);
	}
}

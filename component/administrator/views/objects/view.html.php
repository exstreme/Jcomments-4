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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;

class JCommentsViewObjects extends HtmlView
{
	function display($tpl = null)
	{
		if ($this->getLayout() == 'modal')
		{
			$this->url  = str_replace('/administrator', '', Route::_('index.php?option=com_jcomments&task=refreshObjectsAjax&tmpl=component'));
			$this->hash = md5(Factory::getApplication()->get('secret'));
		}

		parent::display($tpl);
	}
}

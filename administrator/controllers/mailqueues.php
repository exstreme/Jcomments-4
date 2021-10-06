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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class JCommentsControllerMailqueues extends JCommentsControllerList
{
	public function display($cachable = false, $urlparams = array())
	{
		$this->input->set('view', 'mailqueues');

		parent::display($cachable, $urlparams);
	}

	public function purge()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel('mailqueues');
		$model->purge();

		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=mailqueues', false), Text::_('A_MAILQ_EMAILS_PURGED'));
	}
}

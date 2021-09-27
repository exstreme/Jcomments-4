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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class JCommentsControllerMailq extends BaseController
{
	public function delete()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$cid = $this->input->get('cid', array(), 'array');

		if (!empty($cid))
		{
			$model = $this->getModel('mailq');
			$model->delete($cid);
		}

		$this->setRedirect(JRoute::_('index.php?option=com_jcomments&view=mailq', false));
	}

	public function purge()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel('mailq');
		$model->purge();

		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=mailq', false), Text::_('A_MAILQ_EMAILS_PURGED'));
	}
}

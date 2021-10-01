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

class JCommentsControllerBlacklists extends BaseController
{
	public function delete()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$cid = $this->input->get('cid', array(), 'array');

		if (!empty($cid))
		{
			$model = $this->getModel('blacklists');
			$model->delete($cid);
		}

		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=blacklists', false));
	}
}

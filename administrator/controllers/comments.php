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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

class JCommentsControllerComments extends JCommentsControllerList
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unpublish', 'publish');
	}

	function display($cachable = false, $urlparams = array())
	{
		$this->input->set('view', 'comments');

		parent::display($cachable, $urlparams);
	}

	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$cid   = $this->input->get('cid', array(), 'array');
		$data  = array('publish' => 1, 'unpublish' => 0);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (!empty($cid))
		{
			$model = $this->getModel('Comments', 'JCommentsModel', array('ignore_request' => true));
			$model->publish($cid, $value);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	public function checkin()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = Factory::getApplication()->input->post->get('cid', array(), 'array');

		$model  = $this->getModel('Comments', 'JCommentsModel', array('ignore_request' => true));
		$return = $model->checkin($ids);

		if ($return === false)
		{
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()),
				'error'
			);

			return false;
		}
		else
		{
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

			return true;
		}
	}
}

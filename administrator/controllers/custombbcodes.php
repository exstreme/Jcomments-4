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

class JCommentsControllerCustombbcodes extends JCommentsControllerList
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unpublish', 'publish');
		$this->registerTask('button_enable', 'changeButtonState');
		$this->registerTask('button_disable', 'changeButtonState');
	}

	public function display($cachable = false, $urlparams = array())
	{
		$this->input->set('view', 'custombbcodes');

		parent::display($cachable, $urlparams);
	}

	public function getModel($name = 'CustomBBCodes', $prefix = 'JCommentsModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function duplicate()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$pks = $this->input->post->get('cid', array(), 'array');

		ArrayHelper::toInteger($pks);

		if (!empty($pks))
		{
			$model = $this->getModel();
			$model->duplicate($pks);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$pks  = $this->input->get('cid', array(), 'array');
		$data = array('publish' => 1, 'unpublish' => 0);
		$task = $this->getTask();

		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (!empty($pks))
		{
			$model = $this->getModel();
			$model->publish($pks, $value);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	public function changeButtonState()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$pks  = $this->input->get('cid', array(), 'array');
		$data = array('button_enable' => 1, 'button_disable' => 0);
		$task = $this->getTask();

		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (!empty($pks))
		{
			$model = $this->getModel();
			$model->changeButtonState($pks, $value);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	public function reorder()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$pks = $this->input->post->get('cid', array(), 'array');
		$inc = ($this->getTask() == 'orderup') ? -1 : +1;

		ArrayHelper::toInteger($pks);

		$model  = $this->getModel();
		$return = $model->reorder($pks, $inc);

		if ($return === false)
		{
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError()),
				'error'
			);

			return false;
		}
		else
		{
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				Text::_('JLIB_APPLICATION_SUCCESS_ITEM_REORDERED')
			);

			return true;
		}
	}

	public function saveorder()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		$model  = $this->getModel();
		$return = $model->saveorder($pks, $order);

		if ($return === false)
		{
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError()),
				'error'
			);

			return false;
		}
		else
		{
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				Text::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED')
			);

			return true;
		}
	}

	public function saveOrderAjax()
	{
		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		$model = $this->getModel();

		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		Factory::getApplication()->close();
	}
}

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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

class JCommentsControllerSmilies extends JCommentsControllerList
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unpublish', 'publish');
	}

	public function display($cachable = false, $urlparams = array())
	{
		$this->input->set('view', 'smilies');

		parent::display($cachable, $urlparams);
	}

	public function publish()
	{
		$this->checkToken();

		$cid   = $this->input->get('cid', array(), 'array');
		$data  = array('publish' => 1, 'unpublish' => 0);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (!empty($cid))
		{
			$model = $this->getModel();
			$model->publish($cid, $value);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));

		return true;
	}

	public function delete()
	{
		$this->checkToken();

		$cid = $this->input->get('cid', array(), 'array');

		if (!empty($cid))
		{
			$model = $this->getModel();
			$model->delete($cid);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));

		return true;
	}

	public function saveorder()
	{
		$this->checkToken();

		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		$model = $this->getModel();

		$return = $model->saveorder($pks, $order);

		if ($return === false)
		{
			$message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false),
				$message, 'error'
			);

			return false;
		}
		else
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED'));
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));

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
			echo '1';
		}

		Factory::getApplication()->close();
	}
}

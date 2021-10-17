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

	public function duplicate()
	{
		$this->checkToken();

		$pks = $this->input->post->get('cid', array(), 'array');

		ArrayHelper::toInteger($pks);

		if (!empty($pks))
		{
			$model = $this->getModel('CustomBBCodes', 'JCommentsModel', $config = array('ignore_request' => true));
			$model->duplicate($pks);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));

		return true;
	}

	public function changeButtonState()
	{
		$this->checkToken();

		$ids   = $this->input->get('cid', array(), 'array');
		$data  = array('button_enable' => 1, 'button_disable' => 0);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($ids))
		{
			$this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'error');
		}
		else
		{
			$ids   = (array) $ids;
			$ids   = ArrayHelper::toInteger($ids);
			$model = $this->getModel('CustomBBCodes', 'JCommentsModel', $config = array('ignore_request' => true));
			$model->changeButtonState($ids, $value);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));

		return true;
	}

	public function saveOrderAjax()
	{
		$this->checkToken();

		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		$model  = $this->getModel('CustomBBCodes', 'JCommentsModel', $config = array('ignore_request' => true));
		$return = $model->saveorder($pks, $order);

		echo (string) $return;

		Factory::getApplication()->close();
	}
}

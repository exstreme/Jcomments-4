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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

class JCommentsControllerList extends BaseController
{
	protected $context;
	protected $option;
	protected $view;
	protected $text_prefix;

	public function __construct($config = array())
	{
		parent::__construct($config);

		if (empty($this->option))
		{
			$this->option = 'com_' . strtolower($this->getName());
		}

		if (empty($this->text_prefix))
		{
			$this->text_prefix = strtoupper($this->option);
		}

		if (empty($this->context))
		{
			$r = null;
			if (!preg_match('/(.*)Controller(.*)/i', get_class($this), $r))
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_CONTROLLER_GET_NAME'), 500);
			}
			$this->context = strtolower($r[2]);
		}

		if (empty($this->view))
		{
			$r = null;
			if (!preg_match('/(.*)Controller(.*)/i', get_class($this), $r))
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_CONTROLLER_GET_NAME'), 500);
			}
			$this->view = strtolower($r[2]);
		}
	}

	public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true))
	{
		if (empty($name))
		{
			$name = $this->context;
		}

		return parent::getModel($name, $prefix, $config);
	}

	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->post->get('cid', array(), 'array');

		if (empty($ids))
		{
			$this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'error');
		}
		else
		{
			$ids   = (array) $ids;
			$ids   = ArrayHelper::toInteger($ids);
			$model = $this->getModel();
			$data  = array('publish' => 1, 'unpublish' => 0);
			$task  = $this->getTask();
			$value = ArrayHelper::getValue($data, $task, 0, 'int');

			if (!$model->publish($ids, $value))
			{
				$this->setRedirect(
					Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false),
					$model->getError(),
					'error'
				);

				return;
			}
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));
	}

	public function delete()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->post->get('cid', array(), 'array');

		if (empty($ids))
		{
			$this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'error');
		}
		else
		{
			$ids   = (array) $ids;
			$ids   = ArrayHelper::toInteger($ids);
			$model = $this->getModel();

			if (!$model->delete($ids))
			{
				$this->setRedirect(
					Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false),
					$model->getError(),
					'error'
				);

				return;
			}
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false));
	}

	public function checkin()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->post->get('cid', array(), 'array');
		$msg = '';

		if (empty($ids))
		{
			$this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'error');
		}
		else
		{
			$app    = Factory::getApplication();
			$ids    = (array) $ids;
			$ids    = ArrayHelper::toInteger($ids);
			$model  = $this->getModel();
			$result = $model->checkin($ids);

			if ($result === false)
			{
				$app->enqueueMessage(Text::_('A_CUSTOM_BBCODE_N_ITEMS_CHECKED_IN_0'));
				$this->setRedirect(
					Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false),
					$model->getError(),
					'error'
				);

				return;
			}
			else
			{
				$msg = Text::plural('A_CUSTOM_BBCODE_N_ITEMS_CHECKED_IN', $result);
			}
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view, false), $msg);
	}
}

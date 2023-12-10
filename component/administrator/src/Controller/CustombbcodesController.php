<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

/**
 * Custom bbcodes list controller class.
 *
 * @since  4.1
 */
class CustombbcodesController extends AdminController
{
	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 *                                         Recognized key values include 'name', 'default_task', 'model_path', and
	 *                                         'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The JApplication for the dispatcher
	 * @param   Input                $input    Input
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerTask('button_enable', 'changeButtonState');
		$this->registerTask('button_disable', 'changeButtonState');
	}

	/**
	 * Duplicate custom bbcode(s)
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function duplicate()
	{
		$this->checkToken();

		$pks = $this->input->post->get('cid', array(), 'array');

		ArrayHelper::toInteger($pks);

		if (!empty($pks))
		{
			/** @var \Joomla\Component\Jcomments\Administrator\Model\CustombbcodeModel $model */
			$model = $this->getModel();
			$model->duplicate($pks);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

		return true;
	}

	/**
	 * Change button state
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
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

			/** @var \Joomla\Component\Jcomments\Administrator\Model\CustombbcodeModel $model */
			$model = $this->getModel();
			$model->changeButtonState($ids, $value);
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

		return true;
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Custombbcode', $prefix = 'Administrator', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}

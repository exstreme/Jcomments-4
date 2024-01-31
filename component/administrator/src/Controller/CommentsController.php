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

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

/**
 * Comment list controller class.
 *
 * @since  4.0
 */
class CommentsController extends AdminController
{
	/**
	 * Constructor.
	 *
	 * @param   array                        $config   An optional associative array of configuration settings.
	 *                                                 Recognized key values include 'name', 'default_task',
	 *                                                 'model_path', and 'view_path' (this list is not meant to be
	 *                                                 comprehensive).
	 * @param   ?MVCFactoryInterface         $factory  The factory.
	 * @param   ?CMSWebApplicationInterface  $app      The Application for the dispatcher
	 * @param   ?Input                       $input    The Input object for the request
	 *
	 * @since   3.0
	 */
	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSWebApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		// Define standard task mappings.
		$this->registerTask('unpin', 'pin');
	}

	/**
	 * Change comment pin state.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function pin()
	{
		$this->checkToken();

		$pks = $this->input->post->get('cid', array(), 'array');
		$value = ArrayHelper::getValue(['pin' => 1, 'unpin' => 0], $this->getTask(), 0, 'int');

		ArrayHelper::toInteger($pks);
		$pks = array_filter($pks);

		if (!JcommentsFactory::getAcl()->canPin())
		{
			$this->app->enqueueMessage(
				Text::plural($this->text_prefix . '_N_ITEMS_FAILED_PINNING', count($pks)),
				CMSWebApplicationInterface::MSG_ERROR
			);
		}

		if (!empty($pks))
		{
			/** @var \Joomla\Component\Jcomments\Administrator\Model\CommentModel $model */
			$model = $this->getModel();

			try
			{
				$model->pin($pks, $value);
				$errors = $model->getErrors();
				$ntext  = null;

				if ($value === 1)
				{
					if ($errors)
					{
						$this->app->enqueueMessage(
							Text::plural($this->text_prefix . '_N_ITEMS_FAILED_PINNING', count($pks)),
							CMSWebApplicationInterface::MSG_ERROR
						);
					}
					else
					{
						$ntext = $this->text_prefix . '_N_ITEMS_PINNED';
					}
				}
				else
				{
					$ntext = $this->text_prefix . '_N_ITEMS_UNPINNED';
				}

				if (count($pks))
				{
					$this->setMessage(Text::plural($ntext, count($pks)));
				}
			}
			catch (\Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}
		else
		{
			$this->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), ['category' => 'jerror']);
		}

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_list
				. $this->getRedirectToListAppend(),
				false
			)
		);
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
	public function getModel($name = 'Comment', $prefix = 'Administrator', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}

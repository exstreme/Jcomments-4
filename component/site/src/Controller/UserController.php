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

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;

/**
 * User main controller
 *
 * @since  4.1
 */
class UserController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = array())
	{
		if (Factory::getApplication()->getIdentity()->get('guest'))
		{
			$this->setRedirect('index.php', Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		$element = $this->getTask();
		$view = $this->getView('User', 'Html', 'Site');

		if ($element == 'subscriptions')
		{
			$model = $this->getModel('Subscriptions');
		}
		else
		{
			$model = $this->getModel();
		}

		if (!$model)
		{
			$this->setRedirect('index.php', Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		$view->setModel($model, true);
		$view->display(strtolower($element));
	}

	/**
	 * Remove selected user votes from profile.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function removeVotes()
	{
		$this->checkToken();

		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (!$this->app->getIdentity()->authorise('comment.vote', 'com_jcomments'))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($return, false));
		}

		$pks = (array) $this->input->get('cid', [], 'int');
		$pks = array_filter($pks);

		if (empty($pks))
		{
			$this->app->getLogger()->warning(Text::_('COM_JCOMMENTS_NO_ITEM_SELECTED'), ['category' => 'jerror']);
		}
		else
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\UserModel $model */
			$model = $this->getModel();

			// Remove the items.
			if ($model->deleteVotes($pks))
			{
				$this->setMessage(Text::plural('COM_JCOMMENTS_N_ITEMS_DELETED', count($pks)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}
		}

		$this->setRedirect(Route::_($return, false));
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.5
	 */
	public function getModel($name = 'User', $prefix = 'Site', $config = array())
	{
		return parent::getModel($name, $prefix, $config);
	}
}

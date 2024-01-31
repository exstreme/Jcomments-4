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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * Subscription controller class
 *
 * @since  4.1
 */
class SubscriptionsController extends BaseController
{
	/**
	 * Remove subscriptions.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function remove()
	{
		$this->checkToken();

		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (!JcommentsFactory::getAcl()->canSubscribe())
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($return, false));
		}

		$cid = (array) $this->input->get('cid', [], 'int');
		$cid = array_filter($cid);

		if (empty($cid))
		{
			$this->app->getLogger()->warning(Text::_('COM_JCOMMENTS_NO_ITEM_SELECTED'), ['category' => 'jerror']);
		}
		else
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionsModel $model */
			$model = $this->getModel();

			// Remove the items.
			if ($model->delete($cid))
			{
				$this->setMessage(Text::plural('COM_JCOMMENTS_N_ITEMS_DELETED', count($cid)));
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
	public function getModel($name = 'Subscriptions', $prefix = 'Site', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}

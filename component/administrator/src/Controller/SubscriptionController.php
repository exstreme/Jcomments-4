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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * Subscription item controller class.
 * This class is just placeholder for tasks subscription.{method}.
 *
 * @since  1.6
 */
class SubscriptionController extends FormController
{
	/**
	 * Function that allows controller access to model data after the data has been saved.
	 *
	 * @param   BaseDatabaseModel  $model      The data model object.
	 * @param   array              $validData  The validated data.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function postSaveHook(BaseDatabaseModel $model, $validData = array())
	{
		// Clean cache files for certain group. Delete cache group if failed.
		if ($this->getTask() === 'apply' || $this->getTask() === 'save' || $this->getTask() === 'save2new' || $this->getTask() === 'save2copy')
		{
			$cacheGroup = strtolower('com_jcomments_subscriptions_' . $validData['object_group']);
			$result     = JcommentsFactory::removeCache(
				md5($cacheGroup . $validData['object_id']),
				$cacheGroup,
				array('language' => ComponentHelper::getParams('com_languages')->get('site'))
			);

			if (!$result)
			{
				JcommentsFactory::removeCacheGroup($cacheGroup);
			}
		}
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 *
	 * @since   1.6
	 */
	public function batch($model = null)
	{
		$this->checkToken();

		/** @var \Joomla\Component\Jcomments\Administrator\Model\SubscriptionModel $model */
		$model = $this->getModel('Subscription', 'Administrator', array());

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=subscriptions' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}
}

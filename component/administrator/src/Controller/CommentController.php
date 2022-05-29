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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

class CommentController extends FormController
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
			$cacheGroup = strtolower('com_jcomments');
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
	 * Get list of user reports.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function getReports()
	{
		if (!$this->checkToken('get', false))
		{
			$this->setResponse(null, '', Text::_('JINVALID_TOKEN'), 'error');
		}

		$commentId = $this->input->getInt('id');
		$model = $this->getModel();

		$reports = $model->getReports($commentId);
		$html = LayoutHelper::render('reports-list', array('reports' => $reports));

		$this->setResponse(array('total' => count($reports), 'html' => $html), '', null, 'success');
	}

	/**
	 * Delete one or more reports.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function deleteReports()
	{
		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, '', Text::_('JINVALID_TOKEN'), 'error');
		}

		$commentId  = $this->input->getInt('id');
		$returnList = $this->input->getInt('list', 0);
		$cid   = (array) $this->input->get('cid', array(), 'int');
		$cid   = array_filter($cid);
		$model = $this->getModel();

		if (empty($cid))
		{
			$this->setResponse(null, '', Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'error');
		}

		if ($model->deleteReports($cid))
		{
			$reports = $model->getReports($commentId);

			if ($returnList == 1)
			{
				$html = LayoutHelper::render('reports-list', array('reports' => $reports));

				$this->setResponse(array('total' => count($reports), 'html' => $html), '', null, 'success');
			}
			else
			{
				$this->setResponse(array('total' => count($reports)), '', null, 'success');
			}
		}
		else
		{
			$this->setResponse(null, '', Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
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

		/** @var \Joomla\Component\Jcomments\Administrator\Model\CommentModel $model */
		$model = $this->getModel('Comment', 'Administrator', array());

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=comments' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	/**
	 * Method to redirect on typical calls or set json response on ajax.
	 *
	 * @param   array        $data  Array with some data to use with json responses.
	 * @param   string       $url   URL to redirect to. Not used for json.
	 * @param   string|null  $msg   Message to display.
	 * @param   mixed        $type  Message type.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	private function setResponse(array $data = null, string $url = '', string $msg = null, $type = null)
	{
		$format = $this->app->input->getWord('format');

		if ($format == 'json')
		{
			$type = $type !== 'success';

			echo new JsonResponse($data, $msg, $type);

			$this->app->close();
		}
		else
		{
			$this->setRedirect($url, $msg, $type);
		}
	}
}

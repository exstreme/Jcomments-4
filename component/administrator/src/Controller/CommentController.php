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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;

/**
 * Blacklist item controller class.
 * This class is for tasks comment.{method}.
 *
 * @since  1.6
 */
class CommentController extends FormController
{
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
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);

			$this->app->close();
		}

		/** @var \Joomla\Component\Jcomments\Administrator\Model\CommentModel $model */
		$model = $this->getModel();

		$reports = $model->getReports($this->input->getInt('id'));
		$html = LayoutHelper::render('reports-list', array('reports' => $reports));

		echo new JsonResponse(array('total' => count($reports), 'html' => $html));

		$this->app->close();
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
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);

			$this->app->close();
		}

		$commentId  = $this->input->getInt('id');
		$returnList = $this->input->getInt('list', 0);
		$cid        = (array) $this->input->get('cid', array(), 'int');
		$cid        = array_filter($cid);

		/** @var \Joomla\Component\Jcomments\Administrator\Model\CommentModel $model */
		$model      = $this->getModel();

		if (empty($cid))
		{
			echo new JsonResponse(null, Text::_('JGLOBAL_NO_ITEM_SELECTED'), true);

			$this->app->close();
		}

		if ($model->deleteReports($cid))
		{
			$reports = $model->getReports($commentId);

			if ($returnList == 1)
			{
				$html = LayoutHelper::render('reports-list', array('reports' => $reports));

				echo new JsonResponse(array('total' => count($reports), 'html' => $html));
			}
			else
			{
				echo new JsonResponse(array('total' => count($reports)));
			}
		}
		else
		{
			echo new JsonResponse(null, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);
		}

		$this->app->close();
	}
}

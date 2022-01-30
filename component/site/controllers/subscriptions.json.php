<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;

/**
 * Subscriptions controller class
 *
 * @since  4.0
 */
class JcommentsControllerSubscriptions extends BaseController
{
	/**
	 * Add subscription.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   4.0
	 */
	public function add()
	{
		$this->subscribe(1);
	}

	/**
	 * Remove subscription.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   4.0
	 */
	public function remove()
	{
		$this->subscribe(0);
	}

	/**
	 * Add or remove a subscription.
	 *
	 * @param   integer  $state  Action state. 1 - subscribe, 0 - unsubscribe, null - do nothing.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   4.0
	 */
	public function subscribe($state = null)
	{
		$app         = Factory::getApplication();
		$objectID    = $this->input->getInt('object_id', 0);
		$objectGroup = $this->input->getCmd('object_group', '');
		$user        = $app->getIdentity();
		$name        = $this->input->post->getString('name', '');
		$email       = $this->input->post->getString('email', '');
		$langTag     = $app->getLanguage()->getTag();
		$return      = base64_decode($this->input->getBase64('return'));

		if (!$user->authorise('comment.subscribe', 'com_jcomments') || is_null($state))
		{
			echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

			return;
		}

		// Check for errors. If user is guest but email field is not set as "required for guests" in component settings when throw an error.
		if (empty($objectID) || empty($objectGroup)
			|| ($user->get('guest') == 1 && ComponentHelper::getParams('com_jcomments')->get('author_email') != 2))
		{
			echo new JsonResponse(null, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);

			return;
		}

		// Email and username fields is required for Guest.
		if ($user->get('guest') == 1 && (empty($name) || empty($email)))
		{
			echo new JsonResponse(null, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);

			return;
		}

		/** @var JcommentsModelSubscriptions $model */
		$model = $this->getModel('subscriptions');

		if ($state === 1)
		{
			$result = $model->subscribe($objectID, $objectGroup, $user->get('id'), '', '', $langTag);
			$title = Text::_('BUTTON_UNSUBSCRIBE');
			$task  = 'subscriptions.remove';
			$msg   = Text::_('SUCCESSFULLY_SUBSCRIBED');
		}
		else
		{
			$result = $model->unsubscribe($objectID, $objectGroup, $user->get('id'), $langTag);
			$title = Text::_('BUTTON_SUBSCRIBE');
			$task  = 'subscriptions.add';
			$msg   = Text::_('SUCCESSFULLY_UNSUBSCRIBED');
		}

		if (!$result)
		{
			if (count($model->getErrors()))
			{
				echo new JsonResponse(null, implode('<br>', $model->getErrors()), true);
			}
			else
			{
				echo new JsonResponse(null, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);
			}

			return;
		}

		$data = array(
			'title' => $title,
			'href'  => Route::_(
				'index.php?option=com_jcomments&task=' . $task . '&object_id=' . $objectID . '&object_group=' . $objectGroup . '&return=' . base64_encode($return),
				false
			)
		);

		echo new JsonResponse($data, $msg);
	}
}

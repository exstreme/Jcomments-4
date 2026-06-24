<?php
/**
 * Frontend subscriptions controller.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;

/**
 * Subscriptions frontend controller.
 *
 * @since  5.0.2
 */
final class SubscriptionsController extends BaseController
{
	/**
	 * Add subscription.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   5.0.2
	 */
	public function add(): void
	{
		$this->subscribe(1);
	}

	/**
	 * Remove subscription.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   5.0.2
	 */
	public function remove(): void
	{
		$hash = $this->input->getAlnum('hash', '');

		if ($this->isJsonRequest() || $hash === '')
		{
			$this->subscribe(0);

			return;
		}

		$this->unsubscribeByHash($hash);
	}

	/**
	 * Add or remove a subscription.
	 *
	 * @param   integer|null  $state  Action state.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   5.0.2
	 */
	public function subscribe($state = null): void
	{
		$this->loadCore();

		$app         = Factory::getApplication();
		$objectID    = $this->input->getInt('object_id', 0);
		$objectGroup = $this->input->getCmd('object_group', '');
		$user        = $app->getIdentity();
		$name        = $this->input->post->getString('name', '');
		$email       = $this->input->post->getString('email', '');
		$langTag     = $app->getLanguage()->getTag();

		if (!$user->authorise('comment.subscribe', 'com_jcomments') || is_null($state))
		{
			$this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

			return;
		}

		if (empty($objectID) || empty($objectGroup)
			|| ($user->get('guest') == 1 && ComponentHelper::getParams('com_jcomments')->get('author_email') != 2))
		{
			$this->respond(null, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);

			return;
		}

		if ($user->get('guest') == 1 && (empty($name) || empty($email)))
		{
			$this->respond(null, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);

			return;
		}

		require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

		$model = new \JcommentsModelSubscriptions;

		if ($state === 1)
		{
			$result = $model->subscribe($objectID, $objectGroup, $user->get('id'), '', '', $langTag);
			$title  = Text::_('BUTTON_UNSUBSCRIBE');
			$task   = 'subscriptions.remove';
			$msg    = Text::_('SUCCESSFULLY_SUBSCRIBED');
		}
		else
		{
			$result = $model->unsubscribe($objectID, $objectGroup, $user->get('id'), $langTag);
			$title  = Text::_('BUTTON_SUBSCRIBE');
			$task   = 'subscriptions.add';
			$msg    = Text::_('SUCCESSFULLY_UNSUBSCRIBED');
		}

		if (!$result)
		{
			$this->respond(null, count($model->getErrors()) ? implode('<br>', $model->getErrors()) : Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), true);

			return;
		}

		if ($this->isJsonRequest())
		{
			$return = base64_decode($this->input->getBase64('return'));
			$data   = array(
				'title' => $title,
				'href'  => Route::_(
					'index.php?option=com_jcomments&task=' . $task . '&object_id=' . $objectID . '&object_group=' . $objectGroup . '&return=' . base64_encode($return),
					false
				)
			);

			echo new JsonResponse($data, $msg);

			return;
		}

		$this->setRedirect(Route::_(\JCommentsSystem::getReturnPage(), false), $msg);
	}

	/**
	 * Remove subscription by hash.
	 *
	 * @param   string  $hash  Subscription hash.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	public function unsubscribeByHash($hash): void
	{
		$this->loadCore();

		require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

		$model  = new \JcommentsModelSubscriptions;
		$result = $model->unsubscribeByHash($hash);

		if (!$result)
		{
			$message = count($model->getErrors()) ? implode('<br>', $model->getErrors()) : Text::_('JERROR_AN_ERROR_HAS_OCCURRED');
			$this->setRedirect(Route::_(\JCommentsSystem::getReturnPage(), false), $message, 'error');

			return;
		}

		$return = \JCommentsObject::getLink($result['object_id'], $result['object_group'], $result['lang']);

		if (empty($return))
		{
			$return = Route::_(\JCommentsSystem::getReturnPage());
		}

		$this->setRedirect(Route::_($return, false), Text::_('SUCCESSFULLY_UNSUBSCRIBED'), false);
	}

	/**
	 * Respond to JSON or redirect subscription requests.
	 *
	 * @param   mixed    $data     JSON data.
	 * @param   string   $message  Message.
	 * @param   boolean  $error    Error flag.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	private function respond($data, string $message, bool $error): void
	{
		if ($this->isJsonRequest())
		{
			echo new JsonResponse($data, $message, $error);

			return;
		}

		$this->setRedirect(Route::_(\JCommentsSystem::getReturnPage(), false), $message, $error ? 'error' : null);
	}

	/**
	 * Load frontend compatibility classes.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	private function loadCore(): void
	{
		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';
	}

	/**
	 * Check whether the current request expects JSON.
	 *
	 * @return  boolean
	 *
	 * @since   5.0.2
	 */
	private function isJsonRequest(): bool
	{
		return $this->input->getCmd('format') === 'json';
	}
}

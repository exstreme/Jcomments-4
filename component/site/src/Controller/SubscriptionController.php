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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * Subscription controller class
 *
 * @since  4.0
 */
class SubscriptionController extends BaseController
{
	/**
	 * Add subscription.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
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
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function remove()
	{
		$hash = $this->input->getAlnum('hash', '');

		// Unsubscribe user by hash from link.
		if (!empty($hash))
		{
			$this->unsubscribeByHash($hash);
		}
		else
		{
			$this->subscribe(0);
		}
	}

	/**
	 * Add or remove a subscription.
	 *
	 * @param   mixed  $state  Action state. 1 - subscribe, 0 - unsubscribe, null - do nothing.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function subscribe($state = null)
	{
		$objectID    = $this->input->getInt('object_id', 0);
		$objectGroup = $this->input->getCmd('object_group', '');
		$user        = $this->app->getIdentity();
		$name        = $this->input->post->getString('name', '');
		$email       = $this->input->post->getString('email', '');
		$langTag     = $this->app->getLanguage()->getTag();
		$return      = Route::_(JcommentsFactory::getReturnPage(), false);

		if (!$user->authorise('comment.subscribe', 'com_jcomments') || is_null($state))
		{
			$this->setResponse(null, $return, Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		// Check for errors. If user is guest but email field is not set as "required for guests" in component settings when throw an error.
		if (empty($objectID) || empty($objectGroup)
			|| ($user->get('guest') == 1 && ComponentHelper::getParams('com_jcomments')->get('author_email') != 2))
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		// Email and username fields is required for Guest.
		if ($user->get('guest') == 1 && (empty($name) || empty($email)))
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $model */
		$model = $this->getModel();

		if ($state === 1)
		{
			$result = $model->subscribe($objectID, $objectGroup, $user->get('id'), '', '', $langTag);
			$title  = Text::_('LINK_UNSUBSCRIBE');
			$task   = 'subscription.remove';
			$msg    = Text::_('SUCCESSFULLY_SUBSCRIBED');
		}
		else
		{
			$result = $model->unsubscribe($objectID, $objectGroup, $user->get('id'), $langTag);
			$title  = Text::_('LINK_SUBSCRIBE');
			$task   = 'subscription.add';
			$msg    = Text::_('SUCCESSFULLY_UNSUBSCRIBED');
		}

		if (!$result)
		{
			if (count($model->getErrors()))
			{
				$this->setResponse(null, $return, implode('<br>', $model->getErrors()), 'error');
			}
			else
			{
				$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
			}

			return;
		}

		$this->setResponse(
			array(
				'title' => $title,
				'href'  => Route::_(
					'index.php?option=com_jcomments&task=' . $task . '&object_id=' . $objectID . '&object_group=' . $objectGroup . '&return=' . base64_encode($return),
					false
				)
			),
			$return,
			$msg,
			'success'
		);
	}

	/**
	 * Remove subscription by hash.
	 *
	 * @param   string  $hash  Hash
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function unsubscribeByHash(string $hash)
	{
		$user   = $this->app->getIdentity();
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		if (!$user->authorise('comment.subscribe', 'com_jcomments'))
		{
			$this->setRedirect($return, Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $model */
		$model  = $this->getModel();
		$result = $model->unsubscribeByHash($hash, $user->get('id'));

		if (!$result)
		{
			if (count($model->getErrors()))
			{
				$this->setRedirect($return, implode('<br>', $model->getErrors()), 'error');
			}
			else
			{
				$this->setRedirect($return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
			}

			return;
		}

		$redirect = ObjectHelper::getObjectField(null, 'link', $result['object_id'], $result['object_group'], $result['lang']);

		if (empty($redirect))
		{
			$redirect = $return;
		}

		$this->setRedirect($redirect, Text::_('SUCCESSFULLY_UNSUBSCRIBED'), false);
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
	public function getModel($name = 'Subscription', $prefix = 'Site', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
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
		$format = $this->input->getWord('format');

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

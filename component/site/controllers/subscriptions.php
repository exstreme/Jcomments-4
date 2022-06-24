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
		$hash = $this->input->getAlnum('hash', '');

		// Unsubscribe user by hash from link from email.
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

		if (!$user->authorise('comment.subscribe', 'com_jcomments') || is_null($state))
		{
			$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		// Check for errors. If user is guest but email field is not set as "required for guests" in component settings when throw an error.
		if (empty($objectID) || empty($objectGroup)
			|| ($user->get('guest') == 1 && ComponentHelper::getParams('com_jcomments')->get('author_email') != 2))
		{
			$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		// Email and username fields is required for Guest.
		if ($user->get('guest') == 1 && (empty($name) || empty($email)))
		{
			$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		/** @var JcommentsModelSubscriptions $model */
		$model = $this->getModel('subscriptions');

		if ($state === 1)
		{
			$result = $model->subscribe($objectID, $objectGroup, $user->get('id'), '', '', $langTag);
		}
		else
		{
			$result = $model->unsubscribe($objectID, $objectGroup, $user->get('id'), $langTag);
		}

		if (!$result)
		{
			if (count($model->getErrors()))
			{
				$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), implode('<br>', $model->getErrors()), 'error');
			}
			else
			{
				$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
			}

			return;
		}

		$this->setRedirect(
			Route::_(JCommentsSystem::getReturnPage(), false),
			($state === 1 ? Text::_('SUCCESSFULLY_SUBSCRIBED') : Text::_('SUCCESSFULLY_UNSUBSCRIBED'))
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
	public function unsubscribeByHash($hash)
	{
		/** @var JcommentsModelSubscriptions $model */
		$model = $this->getModel('subscriptions');
		$result = $model->unsubscribeByHash($hash);

		if (!$result)
		{
			if (count($model->getErrors()))
			{
				$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), implode('<br>', $model->getErrors()), 'error');
			}
			else
			{
				$this->setRedirect(Route::_(JCommentsSystem::getReturnPage(), false), Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
			}

			return;
		}

		$return = JCommentsObject::getLink($result['object_id'], $result['object_group'], $result['lang']);

		if (empty($return))
		{
			$return = Route::_(JCommentsSystem::getReturnPage());
		}

		$this->setRedirect(Route::_($return, false), Text::_('SUCCESSFULLY_UNSUBSCRIBED'), false);
	}
}

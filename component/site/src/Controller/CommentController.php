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
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * Comment Controller
 *
 * @since  1.5
 */
class CommentController extends FormController
{
	/**
	 * Change item state to unpublished.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function unpublish()
	{
		$this->publish(0);
	}

	/**
	 * Change item state.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function publish($state = null)
	{
		$user   = $this->app->getIdentity();
		$acl    = JcommentsFactory::getAcl();
		$id     = $this->app->input->getInt('id', 0);
		$hash   = $this->app->input->get('hash', '');
		$cmd    = $state === 0 ? 'unpublish' : 'publish';
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		// Guests not allowed to do this action.
		if ($user->get('guest') || !$id)
		{
			$this->setResponse(null, $return, Text::_('ERROR_CANT_PUBLISH'), 'error');

			return;
		}

		if ($hash != JcommentsFactory::getCmdHash($cmd, $id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model  = $this->getModel();
		$record = $model->getItem($id);

		if (!isset($record->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		if ($acl->canPublish($record))
		{
			$result = $model->publish($id, $state);

			if ($result)
			{
				if ($state === 0)
				{
					$link = $return;
					$msg  = Text::_('SUCCESSFULLY_UNPUBLISHED');
				}
				else
				{
					$link = $return . '#comment-' . $record->id;
					$msg  = Text::_('JPUBLISHED');
				}

				$this->setResponse(null, $link, $msg, 'success');

				return;
			}
		}

		$this->setResponse(null, $return, Text::_('ERROR_CANT_PUBLISH'), 'error');
	}

	/**
	 * Remove comment.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function delete()
	{
		$user   = $this->app->getIdentity();
		$acl    = JcommentsFactory::getAcl();
		$id     = $this->app->input->getInt('id', 0);
		$hash   = $this->app->input->get('hash', '');
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		// Guests not allowed to do this action.
		if ($user->get('guest') || !$id)
		{
			$this->setResponse(null, $return, Text::_('ERROR_CANT_DELETE'), 'error');

			return;
		}

		if ($hash != JcommentsFactory::getCmdHash('delete', $id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model  = $this->getModel();
		$record = $model->getItem($id);

		if (!isset($record->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Check if registered user can do action
		if (!$acl->canDelete($record))
		{
			$this->setResponse(null, $return, Text::_('ERROR_CANT_DELETE'), 'error');

			return;
		}

		$result = $model->delete($record);

		if ($result)
		{
			$this->setResponse(null, $return, Text::_('COMMENT_DELETED'), 'success');
		}
		else
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
		}
	}

	/**
	 * Ban user by IP.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function banIP()
	{
		$config = $this->app->getParams('com_jcomments');
		$user   = $this->app->getIdentity();
		$acl    = JcommentsFactory::getAcl();
		$id     = $this->app->input->getInt('id', 0);
		$hash   = $this->app->input->get('hash', '');
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		// Guests not allowed to do this action.
		if ($user->get('guest') || !$id)
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		if ($config->get('enable_blacklist') == 0)
		{
			$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_DISABLED'), 'error');

			return;
		}

		if ($hash != JcommentsFactory::getCmdHash('banIP', $id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model  = $this->getModel();
		$record = $model->getItem($id);

		if (!isset($record->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Check if registered user can do action
		if (!$acl->canBan($record))
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		// We will not ban own IP
		if ($record->ip != $acl->getIP())
		{
			// Check if this IP already banned
			if (!$acl->isUserBlocked())
			{
				/** @var \Joomla\Component\Jcomments\Administrator\Table\BlacklistTable $table */
				$table = $this->app->bootComponent('com_jcomments')->getMVCFactory()->createTable('Blacklist', 'Administrator');
				$table->ip = $record->ip;
				$table->reason = $this->app->input->getString('reason', '');

				if (!$table->store())
				{
					Log::add(implode("\n", $table->getErrors()), Log::ERROR, 'com_jcomments');
					$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

					return;
				}

				$this->setResponse(null, $return, Text::_('SUCCESSFULLY_BANNED'), 'success');
			}
			else
			{
				$this->setResponse(null, $return, Text::_('ERROR_IP_ALREADY_BANNED'), 'error');
			}
		}
		else
		{
			$this->setResponse(null, $return, Text::_('ERROR_YOU_CAN_NOT_BAN_YOUR_IP'), 'error');
		}
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
	public function getModel($name = 'Comment', $prefix = 'Site', $config = array('ignore_request' => true))
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

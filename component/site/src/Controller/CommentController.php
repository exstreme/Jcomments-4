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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;
use Joomla\Utilities\IpHelper;

/**
 * Comment Controller
 *
 * @since  4.1
 */
class CommentController extends FormController
{
	/**
	 * Constructor.
	 *
	 * @param   array                     $config   An optional associative array of configuration settings.
	 *                                              Recognized key values include 'name', 'default_task', 'model_path', and
	 *                                              'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface|null  $factory  The factory.
	 * @param   CMSApplication|null       $app      The Application for the dispatcher
	 * @param   Input|null                $input    Input
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function __construct(
		$config = array(), MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerTask('unpublish', 'publish');
		$this->registerTask('voteUp', 'vote');
		$this->registerTask('voteDown', 'vote');
	}

	/**
	 * Get one comment item.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function show()
	{
		$document = $this->app->getDocument();
		$id       = empty($id) ? $this->input->getInt('id') : $id;
		$comment  = $this->preprocessComment(null, $id);

		if (!isset($comment))
		{
			$this->setResponse(null, '', Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		if ($document->getType() == 'html')
		{
			$document->getWebAssetManager()
				->useStyle('jcomments.style')
				->useScript('jquery')
				->useScript('bootstrap.modal')
				->useScript('bootstrap.collapse')
				->useScript('jcomments.core')
				->useScript('jcomments.frontend');
		}

		echo '<div class="comments-list-container">
			<div class="comment-container" id="comment-item-' . $id . '">'
				. LayoutHelper::render('comment', array('comment' => $comment, 'params' => ComponentHelper::getParams('com_jcomments')))
		. '</div>
		</div>';
	}

	/**
	 * Change item state.
	 *
	 * @note Do not check the form token, because the user must have access to comment.publish() and comment.unpublish()
	 * task from the link from the email. Guest cannot access this link.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function publish()
	{
		$user   = $this->app->getIdentity();
		$acl    = JcommentsFactory::getAcl();
		$id     = $this->input->getInt('id', 0);
		$hash   = $this->input->get('hash', '');
		$task   = $this->getTask();
		$cmd    = ($task == 'unpublish') ? 'unpublish' : 'publish';
		$state  = ($task == 'unpublish') ? 0 : 1;
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		// Guests not allowed to do this action.
		if ($user->get('guest') || !$id)
		{
			$this->setResponse(null, $return, Text::_('ERROR_CANT_PUBLISH'), 'error');

			return;
		}

		if ($this->input->getWord('format', '') != 'json')
		{
			if (!ComponentHelper::getParams('com_jcomments')->get('enable_quick_moderation'))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_DISABLED'), 'error');

				return;
			}

			if ($hash != JcommentsFactory::getCmdHash($cmd, $id))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH'), 'error');

				return;
			}
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model = $this->getModel();
		$comment = $model->getItem($id);

		if (!isset($comment->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		if ($acl->isLocked($comment))
		{
			$this->setResponse(null, $return, Text::_('ERROR_BEING_EDITTED'), 'error');

			return;
		}

		if ($acl->canPublish($comment))
		{
			$result = $model->publish($id, $state);

			if ($result)
			{
				if ($state === 0)
				{
					$link  = $return;
					$msg   = Text::_('SUCCESSFULLY_UNPUBLISHED');
					$title = Text::_('PUBLISH');
					$url   = Route::_('index.php?option=com_jcomments&task=comment.publish', false);
				}
				else
				{
					$link  = $return . '#comment-item-' . $comment->id;
					$msg   = Text::_('SUCCESSFULLY_PUBLISHED');
					$title = Text::_('UNPUBLISH');
					$url   = Route::_('index.php?option=com_jcomments&task=comment.unpublish', false);
				}

				$this->setResponse(array('url' => $url, 'title' => $title, 'current_state' => $state), $link, $msg, 'success');

				return;
			}
		}

		$this->setResponse(null, $return, Text::_('ERROR_CANT_PUBLISH'), 'error');
	}

	/**
	 * Remove comment.
	 *
	 * @note Do not check the form token, because the user must have access to comment.delete()
	 * task from the link from the email. Guest cannot access this link.
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
		$id     = $this->input->getInt('id', 0);
		$hash   = $this->input->get('hash', '');
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		// Guests not allowed to do this action.
		if ($user->get('guest') || !$id)
		{
			$this->setResponse(null, $return, Text::_('ERROR_CANT_DELETE'), 'error');

			return;
		}

		if ($this->input->getWord('format', '') != 'json')
		{
			if (!ComponentHelper::getParams('com_jcomments')->get('enable_quick_moderation'))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_DISABLED'), 'error');

				return;
			}

			if ($hash != JcommentsFactory::getCmdHash('delete', $id))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH'), 'error');

				return;
			}
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model = $this->getModel();
		$comment = $model->getItem($id);

		if (!isset($comment->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		if ($acl->isLocked($comment))
		{
			$this->setResponse(null, $return, Text::_('ERROR_BEING_EDITTED'), 'error');

			return;
		}

		// Check if registered user can do action
		if (!$acl->canDelete($comment))
		{
			$this->setResponse(null, $return, Text::_('ERROR_CANT_DELETE'), 'error');

			return;
		}

		$result = $model->delete($comment);

		if ($result)
		{
			$comment->deleted = 1;
			$html = LayoutHelper::render('comment', array('comment' => $this->preprocessComment($comment, $comment->id)));
			$this->setResponse($html, $return, Text::_('COMMENT_DELETED'), 'success');
		}
		else
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
		}
	}

	/**
	 * Ban user by IP.
	 *
	 * @note Do not check the form token, because the user must have access to comment.banIP()
	 * task from the link from the email. Guest cannot access this link.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function banIP()
	{
		$config = ComponentHelper::getParams('com_jcomments');
		$user   = $this->app->getIdentity();
		$acl    = JcommentsFactory::getAcl();
		$id     = $this->input->getInt('id', 0);
		$hash   = $this->input->get('hash', '');
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		// Guests not allowed to do this action.
		if ($user->get('guest') || !$id)
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		if ($config->get('enable_blacklist') == 0)
		{
			$this->setResponse(null, $return, Text::_('JDISABLED'), 'error');

			return;
		}

		if ($this->input->getWord('format', '') != 'json')
		{
			if ($hash != JcommentsFactory::getCmdHash('banIP', $id))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH'), 'error');

				return;
			}
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model = $this->getModel();
		$comment = $model->getItem($id);

		if (!isset($comment->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Check if registered user can do action
		if (!$acl->canBan($comment))
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		// We will not ban own IP
		if ($comment->ip != IpHelper::getIp())
		{
			// Check if comment IP already banned
			if (!$acl->isUserBlocked($comment->ip, $comment->userid))
			{
				PluginHelper::importPlugin('jcomments');

				// B/C for plugin events
				$options       = array();
				$options['ip'] = $comment->ip;

				$dispatcher = $this->getDispatcher();
				$eventResult = $dispatcher->dispatch(
					'onJCommentsUserBeforeBan',
					AbstractEvent::create(
						'onJCommentsUserBeforeBan',
						array('subject' => new \stdClass, array($comment, $options))
					)
				);

				if (!$eventResult->getArgument('abort', false))
				{
					/** @var \Joomla\Component\Jcomments\Administrator\Table\BlacklistTable $table */
					$table = $this->app->bootComponent('com_jcomments')->getMVCFactory()->createTable('Blacklist', 'Administrator');
					$table->ip = $comment->ip;
					$table->userid = $comment->userid;
					$table->reason = '';

					if (!$table->store())
					{
						Log::add(implode("\n", $table->getErrors()), Log::ERROR, 'com_jcomments');
						$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

						return;
					}
					else
					{
						$dispatcher->dispatch(
							'onJCommentsUserAfterBan',
							AbstractEvent::create(
								'onJCommentsUserAfterBan',
								array('subject' => new \stdClass, array($comment, $options))
							)
						);
					}
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
	 * Method to cancel an edit.
	 *
	 * @param   string  $key  The name of the primary key of the URL variable.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function cancel($key = 'comment_id')
	{
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$model   = $this->getModel();
		/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
		$table   = $model->getTable();
		$context = "$this->option.edit.$this->context";

		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		$recordId = $this->input->getInt($key);
		$acl = JCommentsFactory::getACL();

		// Attempt to check-in the current record.
		if ($recordId && $table->hasField('checked_out') && $table->checkin($recordId) === false && !$acl->isLocked($table))
		{
			// Check-in failed, go back to the record and display a notice.
			$this->setResponse(null, $return, Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $table->getError()), 'error');

			return;
		}

		$this->releaseEditId($context, $recordId);
		$this->setResponse(null, '', Text::_('JOK'), 'success');
	}

	/**
	 * Save user report.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function report()
	{
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$acl    = JcommentsFactory::getAcl();
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		if ($acl->isUserBlocked())
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		if (!$acl->canReport())
		{
			$this->setResponse(null, $return, Text::_('ERROR_YOU_HAVE_NO_RIGHTS_TO_REPORT'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\FormModel $model */
		$model = $this->getModel('Form');
		$data  = $this->input->post->get('jform', array(), 'array');
		$form  = $model->getForm($data, false);

		if (!$form)
		{
			$this->setResponse(null, $return, $model->getError(), 'error');

			return;
		}

		$objData = (object) $data;
		$this->getDispatcher()->dispatch(
			'onContentNormaliseRequestData',
			AbstractEvent::create(
				'onContentNormaliseRequestData',
				array($this->option . '.' . $this->context, $objData, $form, 'subject' => new \stdClass)
			)
		);
		$data = (array) $objData;

		$validData = $model->validate($form, $data);

		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();
			$msg = array();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof \Exception)
				{
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			$this->setResponse(null, $return, implode(",<br>", $msg), 'warning');

			return;
		}

		if (!$model->saveReport($validData))
		{
			$this->setResponse(null, $return, $model->getError(), 'error');

			return;
		}

		$this->setResponse(null, $return, Text::_('REPORT_SUCCESSFULLY_SENT'), 'success');
	}

	/**
	 * Method to preview a record.
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	public function preview()
	{
		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, '', Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$acl = JcommentsFactory::getAcl();

		if ($acl->isUserBlocked())
		{
			$app = Factory::getApplication();

			/** @var \Joomla\Component\Jcomments\Site\Model\BlacklistModel $blacklistModel */
			$blacklistModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Blacklist', 'Site', array('ignore_request' => true));
			$params         = ComponentHelper::getParams('com_jcomments');
			$lang           = $app->getLanguage();
			$message        = JcommentsText::getMessagesBasedOnLanguage($params->get('messages_fields'), 'message_banned', $lang->getTag());
			$reason         = $blacklistModel->getBlacklistReason($acl->userID);

			if ($message != '')
			{
				$reason = !empty($reason) ? '<br>' . Text::_('REPORT_REASON') . ': ' . $reason : '';
			}

			$this->setResponse(null, '', nl2br($message) . $reason, 'error');

			return;
		}

		$data = $this->input->post->get('jform', array(), 'array');

		if (!isset($data))
		{
			$this->setResponse(null, '', Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		$filter             = new InputFilter;
		$params             = ComponentHelper::getParams('com_jcomments');
		$data               = ArrayHelper::toObject($data);
		$data->deleted      = 0;
		$data->published    = 1;
		$data->object_id    = $this->input->getInt('object_id');
		$data->object_group = $this->input->getString('object_group', 'com_content');
		$data->parent       = $this->input->getInt('parent_id');
		$data->id           = $this->input->getInt('comment_id');
		$data->user_blocked = 0;
		$data->bottomPanel  = 1;
		$data->comment      = $filter->clean($data->comment);
		$data->comment      = JcommentsText::nl2br($data->comment);

		if ($params->get('editor_format') == 'bbcode')
		{
			$data->comment = JcommentsFactory::getBbcode()->filter($data->comment);

			if ((int) $params->get('enable_custom_bbcode'))
			{
				$data->comment = JCommentsFactory::getCustomBBCode()->filter($data->comment);
			}
		}
		else
		{
			// TODO Filter HTML
		}

		JcommentsContentHelper::prepareComment($data, true);

		$html = '<div class="comment-preview">
			<style>@import url("' . Uri::base() . 'media/com_jcomments/css/' . $params->get('custom_css') . '.css");</style>
			<div class="comments-list-container">
				<div class="comment-container" id="comment-item-">'
				. LayoutHelper::render('comment', array('comment' => $data, 'params' => $params))
				. '</div>
			</div>
			<div class="my-2 border-bottom border-success border-3"></div>
		</div>';

		$this->setResponse($html, '', '', 'success');
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.6
	 */
	public function save($key = null, $urlVar = 'comment_id')
	{
		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return false;
		}

		$data = $this->input->post->get('jform', array(), 'array');

		echo '<pre>';
		var_dump($data);
		echo '</pre>';
	}

	/**
	 * Vote for comment
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function vote()
	{
		$return = Route::_(JcommentsFactory::getReturnPage(), false);
		$id     = $this->input->getInt('id', 0);
		$task   = $this->getTask();
		$value  = $task == 'voteUp' ? 1 : -1;

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model = $this->getModel();
		$result = $model->vote($id, $value);

		if (!$result)
		{
			$this->setResponse(null, $return, $model->getError(), 'error');

			return;
		}

		$comment = $model->getItem($id);

		if ($comment->deleted == 1)
		{
			$comment->isgood = 0;
			$comment->ispoor = 0;
		}

		$html = LayoutHelper::render('comment-vote-value', array('comment' => $comment));

		$this->setResponse($html, $return, '', 'success');
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
	 * @param   mixed        $data  Array with some data to use with json responses.
	 * @param   string       $url   URL to redirect to. Not used for json.
	 * @param   string|null  $msg   Message to display.
	 * @param   mixed        $type  Message type.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	private function setResponse($data = null, string $url = '', string $msg = null, $type = null)
	{
		$format = $this->input->getWord('format');

		if ($format == 'json')
		{
			if (is_null($data) && $type !== 'success')
			{
				header('HTTP/1.1 500 Server error');
			}

			$type = $type !== 'success';

			echo new JsonResponse($data, $msg, $type);

			$this->app->close();
		}
		elseif ($format == 'raw')
		{
			if (is_null($data) && $type !== 'success')
			{
				header('HTTP/1.1 404 Not Found');
			}
			else
			{
				echo $data;
			}

			$this->app->close();
		}
		else
		{
			$this->setRedirect($url, $msg, $type);
		}
	}

	/**
	 * Preprocess comment.
	 *
	 * @param   mixed  $comment  Comment data.
	 * @param   mixed  $id       Comment ID.
	 *
	 * @return  object
	 *
	 * @since   4.1
	 */
	private function preprocessComment($comment = null, $id = null)
	{
		$user = $this->app->getIdentity();
		$id   = empty($id) ? $this->input->getInt('id') : $id;

		if (!is_object($comment))
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
			$model = $this->getModel();
			$comment = $model->getItem($id);
		}

		PluginHelper::importPlugin('jcomments');

		$dispatcher = $this->getDispatcher();
		$dispatcher->dispatch(
			'onJCommentsCommentsPrepare',
			AbstractEvent::create('onJCommentsCommentsPrepare', array('subject' => new \stdClass, array($comment)))
		);

		if ($user->authorise('comment.avatar', 'com_jcomments'))
		{
			$dispatcher->dispatch(
				'onPrepareAvatars',
				AbstractEvent::create('onPrepareAvatars', array('subject' => new \stdClass, array($comment)))
			);
		}

		// Run autocensor, replace quotes, smilies and other pre-view processing
		JcommentsContentHelper::prepareComment($comment);

		return $comment;
	}
}

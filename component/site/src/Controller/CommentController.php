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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
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
		$this->registerTask('unpin', 'pin');
		$this->registerTask('voteUp', 'vote');
		$this->registerTask('voteDown', 'vote');
	}

	/**
	 * Get one comment item.
	 *
	 * @return  void
	 *
	 * @throws  \RuntimeException
	 * @since   4.0
	 */
	public function show()
	{
		if (defined('JCOMMENTS_SHOW'))
		{
			return;
		}

		$user     = $this->app->getIdentity();
		$document = $this->app->getDocument();
		$id       = $this->input->getInt('id');
		$comment  = $this->preprocessComment(null, $id);
		$html     = '';

		if (!is_object($comment))
		{
			throw new \RuntimeException(Text::_('ERROR_NOT_FOUND'), 404);
		}

		if (!in_array($comment->object_access, $user->getAuthorisedViewLevels()))
		{
			throw new \RuntimeException(Text::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE'), 403);
		}

		if ($document->getType() == 'html')
		{
			$document->setTitle($comment->object_title);
			$document->getWebAssetManager()
				->useStyle('jcomments.style')
				->useScript('bootstrap.modal')
				->useScript('bootstrap.collapse')
				->useScript('jcomments.core');
			$document->addScriptOptions(
				'jcomments',
				array('object_id' => $comment->object_id, 'object_group' => $comment->object_group, 'return' => $comment->returnUrl)
			);

			$html .= '<h5 class="fs-5">' . Text::_('EMAIL_HEADER') . ' <a href="' . $comment->object_link . '">' . $comment->object_title . '</a></h5>';

			Text::script('LOADING');
			Text::script('BUTTON_DELETE_CONFIRM');
			Text::script('BUTTON_BANIP');
		}

		$html .= '<div class="list-unstyled comments">
			<div class="comment-container single-comment" id="comment-item-' . $id . '">'
				. LayoutHelper::render('comment', array('comment' => $comment, 'params' => ComponentHelper::getParams('com_jcomments')))
		. '</div>
		</div>';

		if ($document->getType() == 'html')
		{
			$html .= LayoutHelper::render('comment-report', null, '', array('component' => 'com_jcomments'));
		}

		echo $html;

		define('JCOMMENTS_SHOW', 1);
	}

	/**
	 * Change pinned item state.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function pin()
	{
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (!$this->checkToken('get', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$user   = $this->app->getIdentity();
		$acl    = JcommentsFactory::getAcl();
		$id     = $this->input->getInt('comment_id');
		$task   = $this->getTask();
		$state  = ($task == 'unpin') ? 0 : 1;
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);
		$params = ComponentHelper::getParams('com_jcomments');

		if (empty($id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Guests not allowed to do this action.
		if ($user->get('guest'))
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model = $this->getModel();
		$comment = $model->getItem($id);

		if (!isset($comment->id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		if ($acl->isCheckout($comment))
		{
			$this->setResponse(null, $return, Text::_('ERROR_BEING_EDITTED'), 'error');

			return;
		}

		if ($acl->canPin($comment))
		{
			$result = $model->pin($id, $state);

			if ($result)
			{
				if ($state === 0)
				{
					$link   = $return;
					$msg    = Text::_('SUCCESSFULLY_UNPINNED');
					$title  = Text::_('BUTTON_PIN');
					$url    = Route::_('index.php?option=com_jcomments&task=comment.pin&comment_id=' . $id, false, 0, true);
					$header = '';
				}
				else
				{
					$link   = $return . '#comment-item-' . $comment->id;
					$msg    = Text::_('SUCCESSFULLY_PINNED');
					$title  = Text::_('BUTTON_UNPIN');
					$url    = Route::_('index.php?option=com_jcomments&task=comment.unpin&comment_id=' . $id, false, 0, true);
					$header = LayoutHelper::render(
						'comment-header-pinned',
						array('comment' => $comment, 'params' => $params),
						'',
						array('component' => 'com_jcomments')
					);
				}

				$this->setResponse(array('url' => $url, 'title' => $title, 'current_state' => $state, 'header' => $header), $link, $msg, 'success');

				return;
			}
		}

		$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
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
		$id     = $this->input->getInt('comment_id');
		$hash   = $this->input->get('hash', '');
		$task   = $this->getTask();
		$cmd    = ($task == 'unpublish') ? 'unpublish' : 'publish';
		$state  = ($task == 'unpublish') ? 0 : 1;
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (empty($id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Guests not allowed to do this action.
		if ($user->get('guest'))
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		if ($this->input->getWord('format', '') != 'json')
		{
			if (!ComponentHelper::getParams('com_jcomments')->get('enable_quick_moderation'))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_DISABLED'), 'error');

				return;
			}

			if ($hash != JcommentsContentHelper::getCmdHash($cmd, $id))
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

		if ($acl->isCheckout($comment))
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
					$url   = Route::_('index.php?option=com_jcomments&task=comment.publish&comment_id=' . $id, false, 0, true);
				}
				else
				{
					$link  = $return . '#comment-item-' . $comment->id;
					$msg   = Text::_('SUCCESSFULLY_PUBLISHED');
					$title = Text::_('UNPUBLISH');
					$url   = Route::_('index.php?option=com_jcomments&task=comment.unpublish&comment_id=' . $id, false, 0, true);
				}

				$this->setResponse(array('url' => $url, 'title' => $title, 'current_state' => $state), $link, $msg, 'success');

				return;
			}
		}

		$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
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
		$id     = $this->input->getInt('comment_id');
		$hash   = $this->input->get('hash', '');
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (empty($id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Guests not allowed to do this action.
		if ($user->get('guest'))
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		if ($this->input->getWord('format', '') != 'json')
		{
			if (!ComponentHelper::getParams('com_jcomments')->get('enable_quick_moderation'))
			{
				$this->setResponse(null, $return, Text::_('ERROR_QUICK_MODERATION_DISABLED'), 'error');

				return;
			}

			if ($hash != JcommentsContentHelper::getCmdHash('delete', $id))
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

		if ($acl->isCheckout($comment))
		{
			$this->setResponse(null, $return, Text::_('ERROR_BEING_EDITTED'), 'error');

			return;
		}

		// Check if registered user can do action
		if (!$acl->canDelete($comment))
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		//$result = $model->delete($comment);
		$result = true;

		if ($result)
		{
			$comment->deleted   = 1;
			$comment->published = 0;
			$totalComments      = ObjectHelper::getTotalCommentsForObject($comment->object_id, $comment->object_group);
			$html               = LayoutHelper::render(
				'comment',
				array(
					'comment' => $this->preprocessComment($comment, $comment->id),
					'params'  => ComponentHelper::getParams('com_jcomments')
				)
			);

			$this->setResponse(array('total' => $totalComments, 'html' => $html), $return, Text::_('COMMENT_DELETED'), 'success');
		}
		else
		{
			$this->setResponse(null, $return, Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
		}
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 *                           (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since   1.6
	 */
	public function edit($key = null, $urlVar = 'comment_id')
	{
		// Do not cache the response to this, its a redirect, and mod_expires and google chrome browser bugs cache it forever!
		$this->app->allowCache(false);

		$acl         = JcommentsFactory::getACL();
		$return      = base64_decode($this->input->getBase64('return'));
		$canViewForm = $acl->canViewForm(true, true);

		if ($canViewForm !== true)
		{
			$this->setMessage($canViewForm, 'error');
			$this->setRedirect(Route::_($return, false));

			return false;
		}

		$model       = $this->getModel();
		$table       = $model->getTable();
		$cid         = (array) $this->input->post->get('cid', [], 'int');
		$context     = "$this->option.edit.$this->context";
		$objectId    = $this->input->getInt('object_id');
		$objectGroup = $this->input->getCmd('object_group', 'com_content');
		$redirect    = 'index.php?option=com_jcomments&view=form&object_id=' . $objectId . '&object_group=' . $objectGroup;

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		// Get the previous record id (if any) and the current record id.
		$recordId = (int) (count($cid) ? $cid[0] : $this->input->getInt($urlVar));
		$redirect = $redirect . '&' . $urlVar . '=' . $recordId . '&return=' . base64_encode($return);
		$table->load($recordId);

		// Access check.
		if (!$acl->canEdit($table))
		{
			if ($table->get('deleted') == 1)
			{
				$this->setMessage(Text::_('ERROR_NOT_FOUND'), 'error');
			}
			else
			{
				if ($acl->isCheckout($table))
				{
					$this->setMessage(Text::_('ERROR_BEING_EDITTED'), 'error');
				}
				else
				{
					$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
				}
			}

			$this->setRedirect(Route::_($return, false));

			return false;
		}

		// Check-out succeeded, push the new record id into the session.
		$this->holdEditId($context, $recordId);
		$this->app->setUserState($context . '.data', null);

		$this->setRedirect(Route::_($redirect, false));

		return true;
	}

	/**
	 * Method to add a new record.
	 *
	 * @return  boolean  True if the record can be added, false if not.
	 *
	 * @since   1.6
	 */
	public function add()
	{
		$context     = "$this->option.edit.$this->context";
		$acl         = JcommentsFactory::getAcl();
		$return      = Route::_(JcommentsContentHelper::getReturnPage(), false);
		$recordId    = $this->input->getInt('comment_id');
		$objectId    = $this->input->getInt('object_id');
		$objectGroup = $this->input->getCmd('object_group', 'com_content');
		$quote       = $this->input->getInt('quote');
		$redirect    = 'index.php?option=com_jcomments&view=form&object_id=' . $objectId . '&object_group=' . $objectGroup
			. '&comment_id=' . $recordId . '&quote=' . $quote . '&return=' . base64_encode($return);

		// Access check.
		if ($quote != 1)
		{
			if (!$acl->canComment())
			{
				// Set the internal error and also the redirect error.
				$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

				$this->setRedirect($return);

				return false;
			}
		}
		else
		{
			if (!$acl->canQuote())
			{
				// Set the internal error and also the redirect error.
				$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

				$this->setRedirect($return);

				return false;
			}
		}

		// Clear the record edit information from the session.
		$this->app->setUserState($context . '.data', null);

		// Redirect to the edit screen.
		$this->setRedirect(Route::_($redirect, false));

		return true;
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
		$id     = $this->input->getInt('comment_id');
		$hash   = $this->input->get('hash', '');
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (empty($id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		// Guests not allowed to do this action.
		if ($user->get('guest'))
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
			if ($hash != JcommentsContentHelper::getCmdHash('banIP', $id))
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
			if (!$acl->getUserBlockState()['state'])
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
					$table         = $this->app->bootComponent('com_jcomments')->getMVCFactory()->createTable('Blacklist', 'Administrator');
					$table->ip     = $comment->ip;
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
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);
		$acl    = JCommentsFactory::getACL();
		$config = ComponentHelper::getParams('com_jcomments');
		$app    = Factory::getApplication();
		$lang   = $app->getLanguage();
		$user   = $app->getIdentity();

		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$userState = $acl->getUserBlockState();

		if ($userState['state'])
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($config->get('messages_fields'), 'message_banned', $lang->getTag());
			$reason = '';

			if ($message != '')
			{
				$reason = !empty($userState['reason']) ? '<br>' . Text::_('REPORT_REASON') . ': ' . $userState['reason'] : '';
			}

			$this->setResponse(null, $return, nl2br(htmlspecialchars($message . $reason, ENT_QUOTES, 'UTF-8')), 'error');

			return;
		}

		if (!$user->authorise('comment.comment', 'com_jcomments'))
		{
			$message = JcommentsText::getMessagesBasedOnLanguage(
				$config->get('messages_fields'),
				'message_policy_whocancomment',
				$lang->getTag(),
				'JGLOBAL_AUTH_ACCESS_DENIED'
			);

			if ($message != '')
			{
				echo JcommentsComponentHelper::renderMessage(nl2br($message), 'warning');
			}

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
		$table->load($recordId);

		// Attempt to check-in the current record.
		if ($recordId && $table->hasField('checked_out') && $table->checkin($recordId) === false && !$acl->isCheckout($table))
		{
			// Check-in failed, go back to the record and display a notice.
			$this->setResponse(null, $return, Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $table->getError()), 'error');

			return;
		}

		$this->releaseEditId($context, $recordId);
		$this->setRedirect($return);
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
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return;
		}

		$acl = JcommentsFactory::getAcl();

		if ($acl->getUserBlockState()['state'])
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		if (!$acl->canReport())
		{
			$this->setResponse(null, $return, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

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

			$this->setResponse(null, $return, implode("<br>", $msg), 'warning');

			return;
		}

		if (!$model->saveReport($validData))
		{
			$this->setResponse(null, $return, $model->getError(), 'error');

			return;
		}

		// Just render default message. No redirection is needed.
		echo \Joomla\Component\Jcomments\Site\Helper\ComponentHelper::renderMessage(Text::_('REPORT_SUCCESSFULLY_SENT'), 'success');
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

		$app         = Factory::getApplication();
		$acl         = JcommentsFactory::getAcl();
		$params      = ComponentHelper::getParams('com_jcomments');
		$canViewForm = $acl->canViewForm(true, true);

		if ($canViewForm !== true)
		{
			$this->setResponse(null, '', $canViewForm, 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\FormModel $model */
		$model = $this->getModel('Form');
		$data = $this->input->post->get('jform', array(), 'array');

		if (!isset($data))
		{
			$this->setResponse(null, '', Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		$form = $model->getForm($data, false);

		if (!$form)
		{
			$this->setResponse(null, '', $model->getError(), 'error');

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

			$this->setResponse(null, '', implode("<br>", $msg), 'warning');

			return;
		}

		PluginHelper::importPlugin('jcomments');

		$dispatcher         = $app->getDispatcher();
		$data               = ArrayHelper::toObject($validData);
		$data->deleted      = 0;
		$data->published    = 1;
		$data->object_id    = $this->input->getInt('object_id');
		$data->object_group = $this->input->getInt('object_group', 'com_content');
		$data->id           = $data->comment_id ?? 0;
		$data->banned       = 0;
		$data->user_blocked = 0;
		$data->checked_out  = null;
		$data->title        = $data->title ?? null;
		$data->comment      = JcommentsText::nl2br($data->comment);
		$data->preview      = true;

		$dispatcher->dispatch(
			'onJCommentsCommentsPrepare',
			AbstractEvent::create(
				'onJCommentsCommentsPrepare',
				array('subject' => new \stdClass, array($data))
			)
		);

		if ($acl->canViewAvatar)
		{
			$app->getDispatcher()->dispatch(
				'onPrepareAvatars',
				AbstractEvent::create(
					'onPrepareAvatars',
					array('subject' => new \stdClass, 'items' => array($data))
				)
			);
		}

		JcommentsContentHelper::prepareComment($data);

		$html = '<div class="comment-preview">
			<div class="comment-container shadow-sm mb-3" id="comment-item-preview">
				' . LayoutHelper::render('comment', array('comment' => $data, 'params' => $params)) . '
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
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function save($key = null, $urlVar = 'comment_id')
	{
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);

		if (!$this->checkToken('post', false))
		{
			$this->setResponse(null, $return, Text::_('JINVALID_TOKEN'), 'error');

			return false;
		}

		$acl    = JcommentsFactory::getAcl();
		$params = ComponentHelper::getParams('com_jcomments');
		$lang   = $this->app->getLanguage();

		if (!$acl->canComment())
		{
			$message = Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN');
			$userstate = $acl->getUserBlockState();

			if ($userstate['state'])
			{
				if (!empty($userstate['reason']))
				{
					$message = addslashes($userstate['reason']);
				}
				else
				{
					$message = JcommentsText::getMessagesBasedOnLanguage(
						$params->get('messages_fields'),
						'message_banned',
						$lang->getTag(),
						'JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'
					);
				}
			}

			$this->setResponse(null, $return, $message, 'error');

			return false;
		}

		$canViewForm = $acl->canViewForm(true, true);

		if ($canViewForm !== true)
		{
			$this->setResponse(null, $return, $canViewForm, 'error');

			return false;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\FormModel $formModel */
		$formModel = $this->getModel('Form');
		$model     = $this->getModel();
		$data      = $this->input->post->get('jform', array(), 'array');
		$user      = $this->app->getIdentity();

		if (!isset($data))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return false;
		}

		$form = $formModel->getForm($data, false);

		if (!$form)
		{
			$this->setResponse(null, $return, $formModel->getError(), 'error');

			return false;
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

		$validData = $formModel->validate($form, $data);

		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $formModel->getErrors();
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

			$this->setResponse(null, $return, implode("<br>", $msg), 'warning');

			return false;
		}

		$commentsPerObject = $params->get('max_comments_per_object');

		if ($commentsPerObject > 0)
		{
			$commentsCount = ObjectHelper::getTotalCommentsForObject($validData['object_id'], $validData['object_group']);

			if ($commentsCount >= $commentsPerObject)
			{
				$message = JcommentsText::getMessagesBasedOnLanguage(
					$params->get('messages_fields'),
					'message_locked',
					$lang->getTag(),
					'JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'
				);

				$this->setResponse(null, $return, $message, 'error');

				return false;
			}
		}

		if ((!$user->authorise('comment.flood', 'com_jcomments')) && ($model->checkFlood(IpHelper::getIp())))
		{
			$this->setResponse(null, $return, Text::_('ERROR_TOO_QUICK'), 'error');

			return false;
		}

		$result = $formModel->save($validData);

		if (!$result)
		{
			$this->setResponse(null, $return, $formModel->getError(), 'error');

			return false;
		}

		$id = $this->app->input->getInt($urlVar, 0);
		$quote = $this->app->input->getInt('quote', 0);

		if ($id > 0 && $quote == 0 && ($result['state'] == 0 || $result['state'] == 1))
		{
			$msg = Text::_('SUCCESSFULLY_SAVED');

			if ($this->getTask() == 'apply')
			{
				$return = Route::_(
					'index.php?option=com_jcomments&view=form&object_id=' . $validData['object_id']
					. '&object_group=' . $validData['object_group'] . '&comment_id=' . $id . '&return=' . base64_encode($return),
					false
				);
			}
		}
		else
		{
			$msg = $result['state'] == 0
				? Text::_('THANK_YOU_YOUR_COMMENT_WILL_BE_PUBLISHED_ONCE_REVIEWED')
				: Text::_('THANK_YOU_FOR_YOUR_SUBMISSION');
		}

		$this->setResponse(null, $return, $msg, 'success');

		return $result;
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
		$return = Route::_(JcommentsContentHelper::getReturnPage(), false);
		$id     = $this->input->getInt('comment_id');
		$task   = $this->getTask();
		$value  = $task == 'voteUp' ? 1 : -1;

		if (empty($id))
		{
			$this->setResponse(null, $return, Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
		$model = $this->getModel();
		$result = $model->storeVote($id, $value);

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
	 * @return  \Joomla\Component\Jcomments\Site\Model\CommentModel
	 *
	 * @since   1.5
	 */
	public function getModel($name = 'Comment', $prefix = 'Site', $config = array('ignore_request' => true))
	{
		if ($this->getTask() == 'edit')
		{
			$name = 'Form';
		}

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
	 * @param   mixed    $comment  Comment data.
	 * @param   mixed    $id       Comment ID.
	 *
	 * @return  object|boolean|null
	 *
	 * @since   4.1
	 */
	private function preprocessComment($comment = null, $id = null)
	{
		$user = $this->app->getIdentity();
		$id = empty($id) ? $this->input->getInt('id') : $id;

		if (!is_object($comment))
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $model */
			$model = $this->getModel();
			$comment = $model->getItem($id);
		}

		if (!$comment)
		{
			return null;
		}

		$objectInfo = ObjectHelper::getObjectInfo(
			$this->input->getInt('object_id', $comment->object_id),
			$this->input->getCmd('object_group', $comment->object_group)
		);
		$comment->returnUrl = base64_encode(
			Uri::getInstance()->toString(array('scheme', 'user', 'pass', 'host', 'port'))
			. $objectInfo->object_link
		);

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
				AbstractEvent::create('onPrepareAvatars', array('subject' => new \stdClass, 'items' => array($comment)))
			);
		}

		// Run autocensor, replace quotes, smilies and other pre-view processing
		JcommentsContentHelper::prepareComment($comment);
		JcommentsContentHelper::dispatchContentEvents($dispatcher, $comment);

		return $comment;
	}
}

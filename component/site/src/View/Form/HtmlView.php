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

namespace Joomla\Component\Jcomments\Site\View\Form;

defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

/**
 * HTML View class for the Jcomments component
 *
 * @since  4.1
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The Form object
	 *
	 * @var    \Joomla\CMS\Form\Form
	 * @since  4.1
	 */
	protected $form;

	/**
	 * @var    object
	 * @since  4.1
	 */
	protected $item;

	/**
	 * The page to return to after the form is submitted
	 *
	 * @var    string
	 * @see    \Joomla\Component\Jcomments\Site\Model\FormModel::getReturnPage()
	 * @since  4.1
	 */
	protected $returnPage = '';

	/**
	 * @var    \Joomla\Registry\Registry
	 * @since  4.1
	 */
	protected $params;

	/**
	 * Should we show a captcha form for the submission of the comment?
	 *
	 * @var    boolean
	 * @since  4.1
	 */
	protected $captchaEnabled = false;

	/**
	 * Option value from request
	 *
	 * @var    string
	 * @since  4.1
	 */
	protected $objectGroup = '';

	/**
	 * Object ID
	 *
	 * @var    integer
	 * @since  4.1
	 */
	protected $objectID = 0;

	/**
	 * String with error text
	 *
	 * @var    string
	 * @since  4.1
	 */
	protected $error = '';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl   The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function display($tpl = null)
	{
		if ($this->getLayout() == 'report')
		{
			$this->displayReportForm($tpl);
		}
		else
		{
			$this->displayCommentForm($tpl);
		}
	}

	/**
	 * Execute and display a comment form template script.
	 *
	 * @param   string  $tpl   The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	public function displayCommentForm($tpl = null)
	{
		$app               = Factory::getApplication();
		$user              = $app->getIdentity();
		$acl               = JcommentsFactory::getACL();
		$state             = $this->get('State');
		$this->form        = $this->get('Form');
		$lang              = $app->getLanguage();
		$this->params      = $state->get('params');
		$this->objectGroup = $state->get('object_group');
		$this->objectID    = $state->get('object_id');

		PluginHelper::importPlugin('jcomments');

		// Set up document title
		$title = empty($this->form->getValue('comment_id')) ? 'FORM_HEADER' : 'FORM_HEADER_EDIT';
		$this->setDocumentTitle(Text::_($title));

		$this->document->getWebAssetManager()
			->useScript('bootstrap.alert')
			->addInlineScript(
				"document.addEventListener('DOMContentLoaded', function () {
					const alert = document.querySelector('.jc-alert');
					
					if (alert) {
						alert.addEventListener('close.bs.alert', function () {
							const editFormIframe = parent.document.querySelector('.commentEditFormFrame');
							
							if (editFormIframe) {
								editFormIframe.remove();
							}
						});
					}
				});"
			);

		/** @see ./plugins/content/jcomments/jcomments.php#L125 */
		if ($this->params->get('comments_locked'))
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($this->params->get('messages_fields'), 'message_locked', $lang->getTag());

			if ($message != '')
			{
				echo '<div class="alert alert-secondary text-center mt-2" role="alert">'
					. nl2br($this->escape($message)) . '</div>';
			}

			return;
		}

		if ($acl->userBlocked)
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($this->params->get('messages_fields'), 'message_banned', $lang->getTag());
			$reason = '';

			if ($message != '')
			{
				$reason = !empty($acl->userBlockedReason) ? '<br>' . Text::_('REPORT_REASON') . ': ' . $this->escape($acl->userBlockedReason) : '';
			}

			echo $this->alert(nl2br($this->escape($message)) . $reason);

			return;
		}

		if (!$user->authorise('comment.comment', 'com_jcomments'))
		{
			$message = JcommentsText::getMessagesBasedOnLanguage(
				$this->params->get('messages_fields'),
				'message_policy_whocancomment',
				$lang->getTag()
			);

			if ($message != '')
			{
				echo $this->alert(nl2br($this->escape($message)));
			}

			return;
		}

		// Access check when edit comment.
		if ($app->input->getInt('comment_id') > 0 && !$app->input->getInt('quote'))
		{
			$this->item = $this->get('Item');

			if ($acl->isLocked($this->item))
			{
				echo $this->alert(Text::_('ERROR_BEING_EDITTED'));

				return;
			}
			elseif (!$acl->canEdit($this->item))
			{
				if ($this->item->get('deleted') == 1)
				{
					echo $this->alert(Text::_('ERROR_NOT_FOUND'));
				}
				else
				{
					echo $this->alert(Text::_('ERROR_CANT_EDIT'));
				}

				return;
			}

			if (!$user->get('guest'))
			{
				/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
				$table = $app->bootComponent('com_jcomments')->getMVCFactory()
					->createTable('Comment', 'Administrator');
				$table->checkOut($user->get('id'), $this->form->getValue('comment_id'));
			}
		}

		$this->returnPage = $this->get('ReturnPage');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		Text::script('ERROR_YOUR_COMMENT_IS_TOO_LONG');

		/** @see \Joomla\Component\Jcomments\Site\Model\FormModel::getTotalCommentsForObject() */
		$commentsCount = $this->get('TotalCommentsForObject');

		$this->displayForm = ((int) $this->params->get('form_show') == 1)
			|| ((int) $this->params->get('form_show') == 2 && $commentsCount == 0)
			|| $app->input->getInt('comment_id') > 0;

		if ($acl->showPolicy())
		{
			$this->policy = JcommentsText::getMessagesBasedOnLanguage(
				$this->params->get('messages_fields'),
				'message_policy_post', $lang->getTag()
			);
		}
		else
		{
			$this->policy = '';
		}

		$this->terms = JcommentsText::getMessagesBasedOnLanguage(
			$this->params->get('messages_fields'),
			'message_terms_of_use', $lang->getTag()
		);
		$this->terms = !empty($this->terms) ? $this->terms : Text::_('FORM_ACCEPT_TERMS_OF_USE');

		if ($this->params->get('enable_plugins'))
		{
			$dispatcher = $this->getDispatcher();
			$this->event = new \StdClass;

			$eventResults = $dispatcher->dispatch(
				'onJCommentsFormBeforeDisplay',
				AbstractEvent::create(
					'onJCommentsFormBeforeDisplay',
					array(
						'eventClass' => 'Joomla\Component\Jcomments\Site\Event\FormEvent',
						'subject' => $this, 'objectId' => $this->objectID, 'objectGroup' => $this->objectGroup
					)
				)
			)->getArgument('result', array());
			$this->event->jcommentsFormBeforeDisplay = trim(
				implode("\n", array_key_exists(0, $eventResults) ? $eventResults[0] : array())
			);

			$eventResults = $dispatcher->dispatch(
				'onJCommentsFormAfterDisplay',
				AbstractEvent::create(
					'onJCommentsFormAfterDisplay',
					array(
						'eventClass' => 'Joomla\Component\Jcomments\Site\Event\FormEvent',
						'subject' => $this, 'objectId' => $this->objectID, 'objectGroup' => $this->objectGroup
					)
				)
			)->getArgument('result', array());
			$this->event->jcommentsFormAfterDisplay = trim(
				implode("\n", array_key_exists(0, $eventResults) ? $eventResults[0] : array())
			);

			$eventResults = $dispatcher->dispatch(
				'onJCommentsFormPrepend',
				AbstractEvent::create(
					'onJCommentsFormPrepend',
					array(
						'eventClass' => 'Joomla\Component\Jcomments\Site\Event\FormEvent',
						'subject' => $this, 'objectId' => $this->objectID, 'objectGroup' => $this->objectGroup
					)
				)
			)->getArgument('result', array());
			$this->event->jcommentsFormPrepend = trim(
				implode("\n", array_key_exists(0, $eventResults) ? $eventResults[0] : array())
			);

			$eventResults = $dispatcher->dispatch(
				'onJCommentsFormAppend',
				AbstractEvent::create(
					'onJCommentsFormAppend',
					array(
						'eventClass' => 'Joomla\Component\Jcomments\Site\Event\FormEvent',
						'subject' => $this, 'objectId' => $this->objectID, 'objectGroup' => $this->objectGroup
					)
				)
			)->getArgument('result', array());
			$this->event->jcommentsFormAppend = trim(
				implode("\n", array_key_exists(0, $eventResults) ? $eventResults[0] : array())
			);
		}

		$captchaSet = $this->params->get('captcha', $app->get('captcha', '0'));

		foreach (PluginHelper::getPlugin('captcha') as $plugin)
		{
			if ($captchaSet === $plugin->name)
			{
				$this->captchaEnabled = true;
				break;
			}
		}

		parent::display($tpl);
	}

	/**
	 * Execute and display a report form template script.
	 *
	 * @param   string  $tpl   The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void|boolean
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function displayReportForm($tpl = null)
	{
		$app          = Factory::getApplication();
		$acl          = JcommentsFactory::getACL();
		$state        = $this->get('State');
		$lang         = $app->getLanguage();
		$this->params = $state->get('params');
		$commentId    = $app->input->getInt('comment_id', 0);

		// Set up document title
		$this->setDocumentTitle(Text::_('REPORT_TO_ADMINISTRATOR'));

		// Blocked user cannot report to admin
		if ($acl->userBlocked)
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($this->params->get('messages_fields'), 'message_banned', $lang->getTag());
			$reason = '';

			if ($message != '')
			{
				$reason = !empty($acl->userBlockedReason) ? '<br>' . Text::_('REPORT_REASON') . ': ' . $this->escape($acl->userBlockedReason) : '';
			}

			echo $this->alert(nl2br($this->escape($message)) . $reason);

			return;
		}

		// Comment ID must not be empty
		if (empty($commentId))
		{
			throw new GenericDataException(Text::_('ERROR_NOT_FOUND'), 500);
		}

		/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $table */
		$table = $this->getModel()->getTable();
		$table->load($commentId);

		$this->item = (object) array();
		$this->item->deleted = $table->get('deleted');

		if (!$acl->canReport($this->item))
		{
			echo $this->alert(Text::_('ERROR_YOU_HAVE_NO_RIGHTS_TO_REPORT'));

			return false;
		}

		if ($table->get('userid') == $acl->userID)
		{
			echo $this->alert(Text::_('ERROR_YOU_CANNOT_REPORT_OWN'));

			return false;
		}

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->form = $this->get('Form');
		$captchaSet = $this->params->get('captcha', $app->get('captcha', '0'));

		foreach (PluginHelper::getPlugin('captcha') as $plugin)
		{
			if ($captchaSet === $plugin->name)
			{
				$this->captchaEnabled = true;
				break;
			}
		}

		parent::display($tpl);
	}

	/**
	 * Return formatted message.
	 *
	 * @param   string   $msg    Message.
	 * @param   boolean  $close  Close button.
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	private function alert(string $msg, bool $close = true): string
	{
		$closeBtn = $close ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' . Text::_('JCLOSE') . '"></button>' : '';
		$closeBtnClass = $close ? ' alert-dismissible' : '';

		return '<div class="jc-alert alert alert-warning mt-2 fade show' . $closeBtnClass . '" role="alert">' . $msg . $closeBtn . '</div>';
	}
}

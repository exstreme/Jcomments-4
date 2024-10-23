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
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
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
	 * @var    \Joomla\CMS\Document\Document  The active document object
	 * @since  3.0
	 */
	public $document;

	/**
	 * @var    \Joomla\CMS\Form\Form  The Form object
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
	 * @var    boolean  Should we show a captcha form for the submission of the comment?
	 * @since  4.1
	 */
	protected $captchaEnabled = false;

	/**
	 * @var    string  Option value from request
	 * @since  4.1
	 */
	protected $objectGroup = '';

	/**
	 * @var    integer  Object ID
	 * @since  4.1
	 */
	protected $objectID = 0;

	/**
	 * @var    mixed  Check if user can add comments
	 * @since  4.1
	 */
	protected $canComment;

	/**
	 * @var    mixed  Check if user can see comment form
	 * @since  4.1
	 */
	protected $canViewForm;

	/**
	 * @var    boolean  Display or hide form?
	 * @since  4.1
	 */
	protected $displayForm = true;

	/**
	 * @var    string  Page header and form title
	 * @since  4.1
	 */
	protected $formTitle = '';

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
		$this->document = $this->getDocument();

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
		$acl               = JcommentsFactory::getAcl();
		$state             = $this->get('State');
		$this->form        = $this->get('Form');
		$this->params      = $state->get('params');
		$this->objectGroup = $state->get('object_group');
		$this->objectID    = $state->get('object_id');
		$this->canViewForm = $acl->canViewForm();

		PluginHelper::importPlugin('jcomments');

		// Set up document title
		if ($app->input->getInt('quote') == 1)
		{
			$this->formTitle = 'FORM_HEADER_QUOTE';
		}
		elseif ($app->input->getInt('reply') == 1)
		{
			$this->formTitle = 'FORM_HEADER_REPLY';
		}
		else
		{
			if (empty($app->input->getInt('comment_id')))
			{
				$this->formTitle = 'FORM_HEADER_ADD';
			}
			else
			{
				$this->formTitle = 'FORM_HEADER_EDIT';
			}
		}

		$this->setDocumentTitle(Text::_($this->formTitle));
		$this->item = $this->get('Item');
		$input = Factory::getApplication()->input;

		if ($input->getInt('comment_id') > 0 && ($input->getInt('quote') == 0 && $input->getInt('reply') == 0))
		{
			$this->canComment = $acl->canEdit($this->item);
		}
		else
		{
			if ($input->getInt('quote') > 0)
			{
				$this->canComment = $acl->canQuote();
			}
			elseif ($input->getInt('reply') > 0)
			{
				$this->canComment = $acl->canReply();
			}
			else
			{
				$this->canComment = $acl->canComment();
			}
		}

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->returnPage = $this->get('ReturnPage');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		Text::script('LOADING');
		Text::script('ERROR_YOUR_COMMENT_IS_TOO_LONG');

		$commentsCount = ObjectHelper::getTotalCommentsForObject($this->objectID, $this->objectGroup, 1, 0);
		$this->displayForm = ((int) $this->params->get('form_show') == 1)
			|| ((int) $this->params->get('form_show') == 2 && $commentsCount == 0)
			|| $app->input->getInt('comment_id') > 0;

		if ($this->params->get('enable_plugins'))
		{
			$dispatcher = $this->getDispatcher();
			$this->item->event = new \StdClass;

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
			$this->item->event->jcommentsFormBeforeDisplay = trim(
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
			$this->item->event->jcommentsFormAfterDisplay = trim(
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
			$this->item->event->jcommentsFormPrepend = trim(
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
			$this->item->event->jcommentsFormAppend = trim(
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
		$userState = $acl->getUserBlockState();

		if ($userState['state'])
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($this->params->get('messages_fields'), 'message_banned', $lang->getTag());
			$reason = '';

			if ($message != '')
			{
				$reason = !empty($user['reason']) ? '<br>' . Text::_('REPORT_REASON') . ': ' . $user['reason'] : '';
			}

			echo JcommentsComponentHelper::renderMessage(nl2br($message . $reason), 'warning');

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
			echo JcommentsComponentHelper::renderMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'warning');

			return false;
		}

		if ($table->get('userid') == $acl->userID)
		{
			echo JcommentsComponentHelper::renderMessage(Text::_('ERROR_YOU_CANNOT_REPORT_OWN'), 'warning');

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
}

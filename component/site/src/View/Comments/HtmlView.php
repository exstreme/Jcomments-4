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

namespace Joomla\Component\Jcomments\Site\View\Comments;

defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * HTML View class for the Jcomments component
 *
 * @since  4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * @var    \Joomla\CMS\Document\Document  The active document object
	 * @since  3.0
	 */
	public $document;

	/**
	 * @var    string
	 * @since  4.1
	 */
	public $paginationPrefix = 'jc_';

	/**
	 * @var    object  The item for Form object
	 * @since  4.1
	 */
	protected $item;

	/**
	 * @var    \Joomla\CMS\Form\Form  The Form object
	 * @since  4.1
	 */
	protected $form;

	/**
	 * The page to return to after the form is submitted
	 *
	 * @var    string
	 * @see    \Joomla\Component\Jcomments\Site\Model\FormModel::getReturnPage()
	 * @since  4.1
	 */
	protected $returnPage = '';

	/**
	 * @var    \Joomla\Registry\Registry  Component params
	 * @since  4.1
	 */
	protected $params = null;

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
	 * @var    boolean  Indicate if user can subscribe
	 * @since  4.1
	 */
	protected $canSubscribe = false;

	/**
	 * @var    boolean  Check if user is subscribed
	 * @since  4.1
	 */
	protected $isSubscribed = false;

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
	 * Execute and display a template script.
	 *
	 * @param   mixed  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function display($tpl = null)
	{
		$app               = Factory::getApplication();
		$user              = $app->getIdentity();
		$acl               = JcommentsFactory::getAcl();
		$state             = $this->get('State');
		$this->params      = $state->get('params');
		$this->objectID    = $state->get('object_id');
		$this->objectGroup = $state->get('object_group');

		// Document not exist in Application until dispatch happen.
		$this->document     = $app->getDocument();
		$this->canSubscribe = $acl->canSubscribe();
		$this->canViewForm  = $acl->canViewForm();
		$this->canComment   = $acl->canComment();

		if ($this->canSubscribe)
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionsModel $subscriptionsModel */
			$subscriptionsModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Subscriptions', 'Site', array('ignore_request' => true));

			$this->isSubscribed = $subscriptionsModel->isSubscribed($this->objectID, $this->objectGroup, $user->get('id'));
		}

		// Set default template. Because this view loaded through plugin event the default template is 'blog'.
		$this->setLayout('default');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$objectInfo = ObjectHelper::getObjectInfo($this->objectID, $this->objectGroup);
		$title = empty($state->get('title', null))
			? ObjectHelper::getObjectField($objectInfo, 'object_title', $this->objectID, $this->objectGroup) . ' '
			: $state->get('title') . ' ';
		$this->returnPage = base64_encode(Uri::getInstance()->toString());

		$this->document->addScriptOptions(
			'jcomments',
			array(
				'object_id'         => $this->objectID,
				'object_group'      => $this->objectGroup,
				'object_link'       => Route::_(Uri::getInstance()->toString(), true, 0, true),
				'list_url'          => Route::_('index.php?option=com_jcomments&view=comments', false),
				'pagination_prefix' => $this->paginationPrefix,
				'template'          => $this->params->get('template_view'),
				'return'            => $this->returnPage
			)
		);

		// Add feed links
		if ($this->params->get('enable_rss')
			&& $this->params->get('feed_limit', $app->get('feed_limit')) > 0
			&& !$this->params->get('comments_locked')
		)
		{
			$link    = 'index.php?option=com_jcomments&view=comments&task=rss&object_id=' . $this->objectID
				. '&object_group=' . $this->objectGroup . '&type=rss&format=feed';
			$attribs = ['type' => 'application/rss+xml', 'title' => $title . 'RSS 2.0'];
			$this->document->addHeadLink(Route::_($link . '&type=rss'), 'alternate', 'rel', $attribs);
			$attribs = ['type' => 'application/atom+xml', 'title' => $title . 'Atom 1.0'];
			$this->document->addHeadLink(Route::_($link . '&type=atom'), 'alternate', 'rel', $attribs);
		}

		Text::script('LOADING');
		Text::script('BUTTON_DELETE_CONFIRM');
		Text::script('BUTTON_BANIP');

		if ($this->canViewForm === true && $this->canComment)
		{
			PluginHelper::importPlugin('jcomments', null, true, $this->getDispatcher());

			$this->document->getWebAssetManager()
				->useScript('form.validate');

			Text::script('ERROR_YOUR_COMMENT_IS_TOO_LONG');

			$formModel = $app->bootComponent('com_jcomments')->getMVCFactory()->createModel('Form', 'Site');
			$formModel->setState('object_id', $this->objectID);
			$formModel->setState('object_group', $this->objectGroup);

			$this->item = $formModel->getItem();
			$this->form = $formModel->getForm();

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
					$this->item->captchaEnabled = true;
					break;
				}
			}

			/*$objectInfo->userid = $user->id;
			$dispatcher->dispatch(
				'onJCommentsCommentAfterAdd',
				AbstractEvent::create(
					'onJCommentsCommentAfterAdd',
					array(
						'subject' => $objectInfo
					)
				)
			);*/
		}

		parent::display($tpl);
	}
}

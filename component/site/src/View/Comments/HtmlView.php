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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

/**
 * HTML View class for the Jcomments component
 *
 * @since  4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The active document object
	 *
	 * @var    \Joomla\CMS\Document\Document
	 * @since  3.0
	 */
	public $document;

	/**
	 * @var    string
	 * @since  4.1
	 */
	public $paginationPrefix = 'jc_';

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
	 * @var    mixed  Check if user can see comment form
	 * @since  4.1
	 */
	protected $canViewForm;

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

		$this->document->addScriptOptions('jcform', array('form_show' => $this->params->get('form_show')));

		$title = empty($state->get('title', null))
			? ObjectHelper::getObjectField(null, 'object_title', $this->objectID, $this->objectGroup) . ' '
			: $state->get('title') . ' ';

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









		/*$this->document->getWebAssetManager()->useScript('form.validate');

		$formModel = $app->bootComponent('com_jcomments')->getMVCFactory()->createModel('Form', 'Site');
		$this->form = $formModel->getForm();
		$lang = $app->getLanguage();

		$commentsCount = \Joomla\Component\Jcomments\Site\Helper\ObjectHelper::getTotalCommentsForObject($this->objectID, $this->objectGroup, 1, 0);
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
		$this->terms = !empty($this->terms) ? $this->terms : Text::_('FORM_ACCEPT_TERMS_OF_USE');*/







		parent::display($tpl);
	}
}

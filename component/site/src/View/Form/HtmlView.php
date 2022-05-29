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

define('JCOMMENTS_SHOW', 1);

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
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * @var    \Joomla\CMS\Form\Form
	 * @since  4.0.0
	 */
	protected $form;

	/**
	 * @var    object
	 * @since  4.0.0
	 */
	protected $item;

	/**
	 * @var    string
	 * @since  4.0.0
	 */
	protected $return_page;

	/**
	 * @var    \Joomla\Registry\Registry
	 * @since  4.0.0
	 */
	protected $state;

	/**
	 * @var    \Joomla\Registry\Registry
	 * @since  4.0.0
	 */
	protected $params;

	/**
	 * Should we show a captcha form for the submission of the comment?
	 *
	 * @var    boolean
	 *
	 * @since  3.7.0
	 */
	protected $captchaEnabled = false;

	/**
	 * @var    \Joomla\Component\Jcomments\Site\Library\Jcomments\JCommentsAcl|null
	 * @since  4.0.0
	 */
	protected $acl;

	protected $error = '';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl   The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function display($tpl = null)
	{
		$app               = Factory::getApplication();
		$user              = $app->getIdentity();
		$this->acl         = JcommentsFactory::getACL();
		$this->state       = $this->get('State');
		$this->form        = $this->get('Form');
		$lang              = $app->getLanguage();
		$this->params      = $app->getParams('com_jcomments');
		$this->objectGroup = $app->input->getCmd('option', 'com_content');
		$this->objectID    = $app->input->getInt('id', 0);

		if ($this->params->get('comments_locked'))
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($this->params->get('messages_fields'), 'message_locked', $lang->getTag());

			if ($message != '')
			{
				$this->error = '<div class="alert alert-secondary text-center mt-2" role="alert">'
					. nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</div>';
			}

			parent::display($tpl);

			return;
		}

		if ($this->acl->isUserBlocked())
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($this->params->get('messages_fields'), 'message_banned', $lang->getTag());

			if ($message != '')
			{
				$this->error = '<div class="alert alert-warning text-center mt-2" role="alert">'
					. nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</div>';
			}

			parent::display($tpl);

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
				$this->error = '<div class="alert alert-warning text-center mt-2" role="alert">'
					. nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</div>';
			}

			parent::display($tpl);

			return;
		}

		// Disable edit form for guest. Guests still able to add new comment if it allowed.
		if ($user->get('guest') && $app->input->getInt('comment_id'))
		{
			echo '<div class="alert alert-warning text-center mt-2" role="alert">' . Text::_('ERROR_CANT_EDIT') . '</div>';

			return;
		}

		$this->return_page = $this->get('ReturnPage');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		/** @see \Joomla\Component\Jcomments\Site\Model\FormModel::getTotalCommentsForObject() $commentsCount */
		$commentsCount = $this->get('TotalCommentsForObject');

		$this->displayForm = ((int) $this->params->get('form_show') == 1)
			|| ((int) $this->params->get('form_show') == 2 && $commentsCount == 0);
		$this->policy      = JcommentsText::getMessagesBasedOnLanguage(
			$this->params->get('messages_fields'),
			'message_policy_post', $lang->getTag()
		);

		if ($this->params->get('enable_plugins'))
		{
			$this->event = new \StdClass;

			$results = $app->triggerEvent('onJCommentsFormBeforeDisplay', array($this->objectID, $this->objectGroup));
			$this->event->jcommentsFormBeforeDisplay = trim(implode("\n", $results));

			$results = $app->triggerEvent('onJCommentsFormAfterDisplay', array($this->objectID, $this->objectGroup));
			$this->event->jcommentsFormAfterDisplay = trim(implode("\n", $results));

			$results = $app->triggerEvent('onJCommentsFormPrepend', array($this->objectID, $this->objectGroup));
			$this->event->jcommentsFormPrepend = trim(implode("\n", $results));

			$results = $app->triggerEvent('onJCommentsFormAppend', array($this->objectID, $this->objectGroup));
			$this->event->jcommentsFormAppend = trim(implode("\n", $results));
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

		$this->document = $app->getDocument();

		parent::display($tpl);
	}
}

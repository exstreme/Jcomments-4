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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;

error_reporting(E_ALL);
@ini_set('error_reporting', E_ALL);

ob_start();

// Regular expression for links
const _JC_REGEXP_LINK = '#(^|\s|\>|\()((http://|https://|news://|ftp://|www.)\w+[^\s\<\>\"\'\)]+)#iu';
const _JC_REGEXP_EMAIL = '#([\w\.\-]+)@(\w+[\w\.\-]*\.\w{2,6})#iu';
const _JC_REGEXP_EMAIL2 = '#^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,6})$#iu';

require_once JPATH_ROOT . '/components/com_jcomments/jcomments.class.php';
require_once JPATH_ROOT . '/components/com_jcomments/models/jcomments.php';
ob_end_clean();

$app = Factory::getApplication();
$task = $app->input->get('task', '');

// TODO Must be placed in main component view class.
\Joomla\CMS\HTML\HTMLHelper::_('jquery.framework');

switch ($task)
{
	case 'captcha':
		$config        = ComponentHelper::getParams('com_jcomments');
		$captchaEngine = $config->get('captcha_engine', 'kcaptcha');

		if ($captchaEngine == 'kcaptcha' || (int) $config->get('enable_plugins') == 0)
		{
			require_once JPATH_ROOT . '/components/com_jcomments/jcomments.captcha.php';

			JCommentsCaptcha::image();
		}
		else
		{
			if ((int) $config->get('enable_plugins') == 1)
			{
				JCommentsEvent::trigger('onJCommentsCaptchaImage');
			}
		}

		break;
	case 'cmd':
		JComments::executeCmd();

		break;
	case 'notifications-cron':
		$limit  = $app->input->getInt('limit', 10);
		$secret = trim($app->input->get('secret', ''));

		if ($secret == $app->get('secret'))
		{
			JCommentsNotification::send($limit);
		}

		break;
	case 'refreshObjectsAjax':
		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.ajax.php';

		JCommentsAJAX::refreshObjectsAjax();

		jexit();
	case 'subscriptions.add':
	case 'subscriptions.remove':
	case 'show_all':
	case 'rss':
	case 'rss_full':
	case 'rss_user':
		$controller = BaseController::getInstance('JComments');
		$controller->execute($app->input->get('task'));
		$controller->redirect();

		break;
	default:
		$jcOption = $app->input->get('option', '');
		$jcAjax   = $app->input->get('jtxf', '');

		if ($jcOption == 'com_jcomments' && $jcAjax == '' && !$app->isClient('administrator'))
		{
			$itemid = $app->input->getInt('Itemid');
			$tmpl   = $app->input->get('tmpl');

			if ($itemid !== 0 && $tmpl !== 'component')
			{
				// TODO What's this?
				// $params = JComponentHelper::getParams('com_jcomments');
				$params = $app->getParams();

				$objectGroup = $params->get('object_group');
				$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);
				$objectID    = (int) $params->get('object_id', 0);

				if ($objectID != 0 && $objectGroup != '')
				{
					$keywords    = $params->get('menu-meta_keywords');
					$description = $params->get('menu-meta_description');
					$title       = $params->get('page_title');

					$document = $app->getDocument();

					if (empty($title))
					{
						$title = $app->get('sitename');
					}
					elseif ($app->get('sitename_pagetitles', 0) == 1)
					{
						$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
					}
					elseif ($app->get('sitename_pagetitles', 0) == 2)
					{
						$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
					}

					$document->setTitle($title);

					if ($keywords)
					{
						$document->setMetaData('keywords', $keywords);
					}

					if ($description)
					{
						$document->setDescription($description);
					}

					echo JComments::show($objectID, $objectGroup);
				}
				else
				{
					$app->redirect(Route::_(rtrim(Uri::base(), '/')));
				}
			}
			else
			{
				$app->redirect(Route::_(rtrim(Uri::base(), '/')));
			}
		}

		break;
}

if (isset($_REQUEST['jtxf']))
{
	require_once JPATH_ROOT . '/components/com_jcomments/jcomments.ajax.php';

	$jtx = new JoomlaTuneAjax;
	$jtx->setCharEncoding('utf-8');
	$jtx->registerFunction(array('JCommentsAddComment', 'JCommentsAJAX', 'addComment'));
	$jtx->registerFunction(array('JCommentsDeleteComment', 'JCommentsAJAX', 'deleteComment'));
	$jtx->registerFunction(array('JCommentsEditComment', 'JCommentsAJAX', 'editComment'));
	$jtx->registerFunction(array('JCommentsCancelComment', 'JCommentsAJAX', 'cancelComment'));
	$jtx->registerFunction(array('JCommentsSaveComment', 'JCommentsAJAX', 'saveComment'));
	$jtx->registerFunction(array('JCommentsPublishComment', 'JCommentsAJAX', 'publishComment'));
	$jtx->registerFunction(array('JCommentsQuoteComment', 'JCommentsAJAX', 'quoteComment'));
	$jtx->registerFunction(array('JCommentsShowPage', 'JCommentsAJAX', 'showPage'));
	$jtx->registerFunction(array('JCommentsShowComment', 'JCommentsAJAX', 'showComment'));
	$jtx->registerFunction(array('JCommentsJump2email', 'JCommentsAJAX', 'jump2email'));
	$jtx->registerFunction(array('JCommentsShowForm', 'JCommentsAJAX', 'showForm'));
	$jtx->registerFunction(array('JCommentsVoteComment', 'JCommentsAJAX', 'voteComment'));
	$jtx->registerFunction(array('JCommentsShowReportForm', 'JCommentsAJAX', 'showReportForm'));
	$jtx->registerFunction(array('JCommentsReportComment', 'JCommentsAJAX', 'reportComment'));
	$jtx->registerFunction(array('JCommentsBanIP', 'JCommentsAJAX', 'BanIP'));
	$jtx->processRequests();
}

/**
 * Frontend event handler
 *
 * @since  3.0
 */
class JComments
{
	/**
	 * The main function that displays comments list & form (if needed).
	 *
	 * @param   integer  $objectID     Comment ID.
	 * @param   string   $objectGroup  Component system name.
	 * @param   string   $objectTitle  Item title.
	 *
	 * @return  string
	 *
	 * @throws  Exception
	 * @since   3.0
	 */
	public static function show($objectID, $objectGroup = 'com_content', $objectTitle = '')
	{
		// Only one copy of JComments per page is allowed
		if (defined('JCOMMENTS_SHOW'))
		{
			return '';
		}

		$app         = Factory::getApplication();
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		if ($objectGroup == '' || !isset($objectID) || $objectID == '')
		{
			return '';
		}

		$objectID    = (int) $objectID;
		$objectTitle = trim($objectTitle);
		$acl         = JCommentsFactory::getACL();
		$config      = ComponentHelper::getParams('com_jcomments');
		$document    = $app->getDocument();
		$user        = $app->getIdentity();

		$tmpl = JCommentsFactory::getTemplate($objectID, $objectGroup);
		$tmpl->load('tpl_index');

		if (!defined('JCOMMENTS_CSS'))
		{
			include_once JPATH_ROOT . '/components/com_jcomments/helpers/system.php';

			if ($app->isClient('administrator'))
			{
				$tmpl->addVar('tpl_index', 'comments-css', 1);
			}
			else
			{
				$document->addStyleSheet(JCommentsSystem::getCSS());
				$language = $app->getLanguage();

				if ($language->isRTL())
				{
					$rtlCSS = JCommentsSystem::getCSS(true);

					if ($rtlCSS != '')
					{
						$document->addStyleSheet($rtlCSS);
					}
				}
			}
		}

		if (!defined('JCOMMENTS_JS'))
		{
			include_once JPATH_ROOT . '/components/com_jcomments/helpers/system.php';

			$document->addScript(JCommentsSystem::getCoreJS());

			define('JCOMMENTS_JS', 1);

			if (!defined('JOOMLATUNE_AJAX_JS'))
			{
				$document->addScript(JCommentsSystem::getAjaxJS());
				define('JOOMLATUNE_AJAX_JS', 1);
			}
		}

		$commentsCount     = self::getCommentsCount($objectID, $objectGroup);
		$commentsPerObject = (int) $config->get('max_comments_per_object');
		$showForm          = ((int) $config->get('form_show') == 1) || ((int) $config->get('form_show') == 2 && $commentsCount == 0);

		if ($commentsPerObject != 0 && $commentsCount >= $commentsPerObject)
		{
			$config->set('comments_locked', 1);
		}

		// The 'comments_locked' option value is set in PlgContentJcomments::prepareContent()
		if ((int) $config->get('comments_locked', 0) == 1)
		{
			$config->set('enable_rss', 0);
			$tmpl->addVar('tpl_index', 'comments-form-locked', 1);
			$acl->setCommentsLocked(true);
		}

		$tmpl->addVar(
			'tpl_index',
			'comments-form-captcha',
			!$user->authorise('comment.captcha', 'com_jcomments')
		);
		$tmpl->addVar('tpl_index', 'comments-form-link', $showForm ? 0 : 1);

		if ((int) $config->get('enable_rss') == 1)
		{
			if ($document->getType() == 'html')
			{
				$link    = JCommentsFactory::getLink('rss', $objectID, $objectGroup);
				$title   = htmlspecialchars($objectTitle, ENT_COMPAT);
				$attribs = array('type' => 'application/rss+xml', 'title' => $title);
				$document->addHeadLink($link, 'alternate', 'rel', $attribs);
			}
		}

		$cacheEnabled = (bool) $app->get('caching');
		$loadCachedComments = intval((int) $config->get('load_cached_comments', 0) && $commentsCount > 0);

		if ($cacheEnabled)
		{
			$tmpl->addVar('tpl_index', 'comments-anticache', 1);
		}

		if (!$cacheEnabled || $loadCachedComments === 1)
		{
			if ($config->get('template_view') == 'tree')
			{
				$tmpl->addVar(
					'tpl_index',
					'comments-list',
					$commentsCount > 0 ? self::getCommentsTree($objectID, $objectGroup) : ''
				);
			}
			else
			{
				$tmpl->addVar(
					'tpl_index',
					'comments-list',
					$commentsCount > 0 ? self::getCommentsList($objectID, $objectGroup) : ''
				);
			}
		}

		$needScrollToComment = ($cacheEnabled || ((int) $config->get('comments_per_page') > 0)) && $commentsCount > 0;
		$tmpl->addVar('tpl_index', 'comments-gotocomment', (int) $needScrollToComment);
		$tmpl->addVar('tpl_index', 'comments-form', self::getCommentsForm($objectID, $objectGroup, $showForm));
		$tmpl->addVar('tpl_index', 'comments-form-position', (int) $config->get('form_position'));

		$result = $tmpl->renderTemplate('tpl_index');
		$tmpl->freeAllTemplates();

		// Send notifications
		srand((float) microtime() * 10000000);
		$randValue = rand(0, 100);

		if ($randValue <= 30)
		{
			JCommentsNotification::send();
		}

		define('JCOMMENTS_SHOW', 1);

		return $result;
	}

	public static function getCommentsForm($objectID, $objectGroup, $showForm = true)
	{
		$objectID    = (int) $objectID;
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		$tmpl = JCommentsFactory::getTemplate($objectID, $objectGroup);
		$tmpl->load('tpl_form');

		$app    = Factory::getApplication();
		$lang   = $app->getLanguage();
		$user   = $app->getIdentity();
		$acl    = JCommentsFactory::getACL();
		$config = ComponentHelper::getParams('com_jcomments');

		if ($user->authorise('comment.comment', 'com_jcomments'))
		{
			// The 'comments_locked' option value is set in PlgContentJcomments::prepareContent()
			if ((int) $config->get('comments_locked') == 1)
			{
				$message = JCommentsText::getMessagesBasedOnLanguage($config->get('messages_fields'), 'message_locked', $lang->getTag());

				if ($message != '')
				{
					$message = stripslashes($message);

					if ($message == strip_tags($message))
					{
						$message = nl2br($message);
					}
				}
				else
				{
					$message = '<div class="alert alert-warning" role="alert">' . Text::_('ERROR_CANT_COMMENT') . '</div>';
				}

				$tmpl->addVar('tpl_form', 'comments-form-message', 1);
				$tmpl->addVar('tpl_form', 'comments-form-message-header', Text::_('FORM_HEADER'));
				$tmpl->addVar('tpl_form', 'comments-form-message-text', $message);

				return $tmpl->renderTemplate('tpl_form');
			}

			if (!$user->authorise('comment.captcha', 'com_jcomments'))
			{
				$captchaEngine = $config->get('captcha_engine', 'kcaptcha');

				if ($captchaEngine != 'kcaptcha')
				{
					JCommentsEvent::trigger('onJCommentsCaptchaJavaScript');
				}
			}

			if (!$showForm)
			{
				$tmpl->addVar('tpl_form', 'comments-form-link', 1);

				return $tmpl->renderTemplate('tpl_form');
			}
			else
			{
				if ((int) $config->get('form_show') != 1)
				{
					$tmpl->addVar('tpl_form', 'comments-form-ajax', 1);
				}
			}

			if ((int) $config->get('enable_plugins') == 1)
			{
				$htmlBeforeForm = JCommentsEvent::trigger('onJCommentsFormBeforeDisplay', array($objectID, $objectGroup));
				$htmlAfterForm  = JCommentsEvent::trigger('onJCommentsFormAfterDisplay', array($objectID, $objectGroup));

				$htmlBeforeForm = implode("\n", $htmlBeforeForm);
				$htmlAfterForm  = implode("\n", $htmlAfterForm);

				// Show HTML before or after form element
				$tmpl->addVar('tpl_form', 'comments-html-before-form', $htmlBeforeForm);
				$tmpl->addVar('tpl_form', 'comments-html-after-form', $htmlAfterForm);

				// Backward compatibility
				$tmpl->addVar('tpl_form', 'comments-form-html-before', $htmlBeforeForm);
				$tmpl->addVar('tpl_form', 'comments-form-html-after', $htmlAfterForm);

				// Prepend or append HTML code inside form element
				$htmlFormPrepend = JCommentsEvent::trigger('onJCommentsFormPrepend', array($objectID, $objectGroup));
				$htmlFormAppend  = JCommentsEvent::trigger('onJCommentsFormAppend', array($objectID, $objectGroup));

				$tmpl->addVar('tpl_form', 'comments-form-html-prepend', $htmlFormPrepend);
				$tmpl->addVar('tpl_form', 'comments-form-html-append', $htmlFormAppend);
			}

			$show_checkbox_terms_of_use = $config->get('show_checkbox_terms_of_use');

			if (($show_checkbox_terms_of_use == 1) && ($acl->showTermsOfUse()))
			{
				$tosLabelText = JCommentsText::getMessagesBasedOnLanguage(
					$config->get('messages_fields'),
					'message_terms_of_use',
					$lang->getTag()
				);
				$tosLabelText = !empty($tosLabelText) ? $tosLabelText : Text::_('FORM_ACCEPT_TERMS_OF_USE');

				$tmpl->addVar('tpl_form', 'var_show_checkbox_terms_of_use', 1);
				$tmpl->addVar('tpl_form', 'var_tos_text', $tosLabelText);
			}

			$policy = JCommentsText::getMessagesBasedOnLanguage($config->get('messages_fields'), 'message_policy_post', $lang->getTag());

			if ($policy != '' && $acl->showPolicy())
			{
				$policy = stripslashes($policy);

				if ($policy == strip_tags($policy))
				{
					$policy = nl2br($policy);
				}

				$tmpl->addVar('tpl_form', 'comments-form-policy', 1);
				$tmpl->addVar('tpl_form', 'comments-policy', $policy);
			}

			if ($user->get('id'))
			{
				$currentUser = Factory::getUser($user->get('id'));
				$user->name  = $currentUser->name;
				unset($currentUser);
			}

			$tmpl->addObject('tpl_form', 'user', $user);

			if ((int) $config->get('enable_smilies') == 1)
			{
				$tmpl->addVar('tpl_form', 'comment-form-smiles', JCommentsFactory::getSmilies()->getList());
			}

			$bbcode = JCommentsFactory::getBBCode();

			if ($bbcode->enabled())
			{
				$tmpl->addVar('tpl_form', 'comments-form-bbcode', 1);

				foreach ($bbcode->getCodes() as $code)
				{
					$tmpl->addVar('tpl_form', 'comments-form-bbcode-' . $code, $bbcode->canUse($code));
				}
			}

			if ((int) $config->get('enable_custom_bbcode'))
			{
				$tmpl->addVar('tpl_form', 'comments-form-custombbcodes', JCommentsFactory::getCustomBBCode()->getList());
			}

			$username_maxlength = (int) $config->get('username_maxlength');

			if ($username_maxlength <= 0 || $username_maxlength > 255)
			{
				$username_maxlength = 255;
			}

			$tmpl->addVar('tpl_form', 'comment-name-maxlength', $username_maxlength);

			if (((int) $config->get('show_commentlength') == 1)
				&& (!$user->authorise('comment.length_check', 'com_jcomments'))
			)
			{
				$tmpl->addVar('tpl_form', 'comments-form-showlength-counter', 1);
				$tmpl->addVar('tpl_form', 'comment-maxlength', (int) $config->get('comment_maxlength'));
			}
			else
			{
				$tmpl->addVar('tpl_form', 'comment-maxlength', 0);
			}

			if (!$user->authorise('comment.captcha', 'com_jcomments'))
			{
				$tmpl->addVar('tpl_form', 'comments-form-captcha', 1);

				$captchaEngine = $config->get('captcha_engine', 'kcaptcha');

				if (($captchaEngine == 'kcaptcha') || ($captchaEngine == 'recaptcha') || ($captchaEngine == 'recaptcha_invisible'))
				{
					$tmpl->addVar('tpl_form', 'comments-form-captcha-html', $captchaEngine);
				}
				else
				{
					$captchaHTML = JCommentsEvent::trigger('onJCommentsCaptchaDisplay');
					$tmpl->addVar('tpl_form', 'comments-form-captcha-html', implode("\n", $captchaHTML));
				}
			}

			$canSubscribe = $user->authorise('comment.subscribe', 'com_jcomments');

			if ($user->get('id') && $canSubscribe)
			{
				require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

				$subscriptionModel = new JcommentsModelSubscriptions;
				$canSubscribe = (!$subscriptionModel->isSubscribed($objectID, $objectGroup, $user->get('id')));
			}

			$tmpl->addVar('tpl_form', 'comments-form-subscribe', (int) $canSubscribe);
			$tmpl->addVar('tpl_form', 'comments-form-email-required', 0);

			switch ((int) $config->get('author_name'))
			{
				case 2:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-name-required', 1);
						$tmpl->addVar('tpl_form', 'comments-form-user-name', 1);
					}
					else
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-name', 0);
					}
					break;
				case 1:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-name', 1);
						$tmpl->addVar('tpl_form', 'comments-form-user-name-required', 0);
					}
					else
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-name', 0);
					}
					break;
				case 0:
				default:
					$tmpl->addVar('tpl_form', 'comments-form-user-name', 0);
					break;
			}


			switch ((int) $config->get('author_email'))
			{
				case 2:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-email-required', 1);
						$tmpl->addVar('tpl_form', 'comments-form-user-email', 1);
					}
					else
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-email', 0);
					}
					break;
				case 1:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-email', 1);
					}
					else
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-email', 0);
					}
					break;
				case 0:
				default:
					$tmpl->addVar('tpl_form', 'comments-form-user-email', 0);

					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-subscribe', 0);
					}
					break;
			}

			$tmpl->addVar('tpl_form', 'comments-form-homepage-required', 0);

			switch ((int) $config->get('author_homepage'))
			{
				case 5:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-homepage-required', 0);
						$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 1);
					}
					else
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 0);
					}
					break;
				case 4:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-homepage-required', 1);
						$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 1);
					}
					else
					{
						$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 0);
					}
					break;
				case 3:
					$tmpl->addVar('tpl_form', 'comments-form-homepage-required', 1);
					$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 1);
					break;
				case 2:
					if (!$user->get('id'))
					{
						$tmpl->addVar('tpl_form', 'comments-form-homepage-required', 1);
					}

					$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 1);
					break;
				case 1:
					$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 1);
					break;
				case 0:
				default:
					$tmpl->addVar('tpl_form', 'comments-form-user-homepage', 0);
					break;
			}

			$tmpl->addVar('tpl_form', 'comments-form-title-required', 0);

			switch ((int) $config->get('comment_title'))
			{
				case 3:
					$tmpl->addVar('tpl_form', 'comments-form-title-required', 1);
					$tmpl->addVar('tpl_form', 'comments-form-title', 1);
					break;
				case 1:
					$tmpl->addVar('tpl_form', 'comments-form-title', 1);
					break;
				case 0:
				default:
					$tmpl->addVar('tpl_form', 'comments-form-title', 0);
					break;
			}

			$result = $tmpl->renderTemplate('tpl_form');

			// Support old-style templates
			$result = str_replace('name="captcha-refid"', 'name="captcha_refid"', $result);

			if ($user->get('id'))
			{
				$result = str_replace(
					'</form>',
					'<div><input type="hidden" name="userid" value="' . $user->get('id') . '" /></div></form>',
					$result
				);
			}

			return $result;
		}
		else
		{
			$message = $acl->getUserBlocked()
				? JCommentsText::getMessagesBasedOnLanguage($config->get('messages_fields'), 'message_banned', $lang->getTag())
				: JCommentsText::getMessagesBasedOnLanguage($config->get('messages_fields'), 'message_policy_whocancomment', $lang->getTag());

			if ($message != '')
			{
				$header  = Text::_('FORM_HEADER');
				$message = '<div class="alert alert-warning text-center" role="alert">' . nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</div>';
			}
			else
			{
				$header  = '';
				$message = '';
			}

			$tmpl->addVar('tpl_form', 'comments-form-message', 1);
			$tmpl->addVar('tpl_form', 'comments-form-message-header', $header);
			$tmpl->addVar('tpl_form', 'comments-form-message-text', $message);

			return $tmpl->renderTemplate('tpl_form');
		}
	}

	public static function getCommentsReportForm($id, $objectID, $objectGroup)
	{
		$id          = (int) $id;
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		$user = Factory::getApplication()->getIdentity();
		$tmpl = JCommentsFactory::getTemplate($objectID, $objectGroup);

		$tmpl->load('tpl_report_form');
		$tmpl->addVar('tpl_report_form', 'comment-id', $id);
		$tmpl->addVar('tpl_report_form', 'isGuest', (int) $user->get('guest'));

		return $tmpl->renderTemplate('tpl_report_form');
	}

	public static function getCommentsList($objectID, $objectGroup = 'com_content', $page = 0)
	{
		$objectID        = (int) $objectID;
		$objectGroup     = JCommentsSecurity::clearObjectGroup($objectGroup);
		$user            = Factory::getApplication()->getIdentity();
		$acl             = JCommentsFactory::getACL();
		$config          = ComponentHelper::getParams('com_jcomments');
		$commentsPerPage = (int) $config->get('comments_per_page');
		$limitstart      = 0;
		$total           = self::getCommentsCount($objectID, $objectGroup);

		if (!$user->authorise('comment.comment', 'com_jcomments') && $total == 0)
		{
			return '';
		}

		if ($total > 0)
		{
			$options                 = array();
			$options['object_id']    = $objectID;
			$options['object_group'] = $objectGroup;
			$options['published']    = $acl->canPublish() || $acl->canPublishForObject($objectID, $objectGroup) ? null : 1;
			$options['votes']        = (int) $config->get('enable_voting');

			if ($commentsPerPage > 0)
			{
				$page = (int) $page;

				require_once JPATH_ROOT . '/components/com_jcomments/helpers/pagination.php';

				$pagination = new JCommentsPagination($objectID, $objectGroup);
				$pagination->setCurrentPage($page);

				$totalPages      = $pagination->getTotalPages();
				$thisPage        = $pagination->getCurrentPage();
				$limitstart      = $pagination->getLimitStart();
				$commentsPerPage = $pagination->getCommentsPerPage();

				$options['limit']      = $commentsPerPage;
				$options['limitStart'] = $limitstart;
			}

			$rows = JCommentsModel::getCommentsList($options);
		}
		else
		{
			$rows = array();
		}

		$tmpl = JCommentsFactory::getTemplate($objectID, $objectGroup);
		$tmpl->load('tpl_list');
		$tmpl->load('tpl_comment');

		if (count($rows))
		{
			// The 'comments_locked' option value is set in PlgContentJcomments::prepareContent()
			$isLocked = ((int) $config->get('comments_locked', 0) == 1); // TODO Convert to int

			$tmpl->addVar('tpl_list', 'comments-refresh', !$isLocked);
			$tmpl->addVar('tpl_list', 'comments-rss', intval((int) $config->get('enable_rss') && !$isLocked));
			$tmpl->addVar('tpl_list', 'comments-can-subscribe', intval($user->get('id') && $user->authorise('comment.subscribe', 'com_jcomments') && !$isLocked));
			$tmpl->addVar('tpl_list', 'comments-count', count($rows));

			if ($user->get('id') && $user->authorise('comment.subscribe', 'com_jcomments'))
			{
				require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

				$subscriptionModel = new JcommentsModelSubscriptions;
				$isSubscribed = $subscriptionModel->isSubscribed($objectID, $objectGroup, $user->get('id'));
				$tmpl->addVar('tpl_list', 'comments-user-subscribed', $isSubscribed);
			}

			if ($config->get('comments_list_order') == 'DESC')
			{
				if ($commentsPerPage > 0)
				{
					$i = $total - ($commentsPerPage * ($page > 0 ? $page - 1 : 0));
				}
				else
				{
					$i = count($rows);
				}
			}
			else
			{
				$i = $limitstart + 1;
			}

			JCommentsEvent::trigger('onJCommentsCommentsPrepare', array(&$rows));

			if ($user->authorise('comment.avatar', 'com_jcomments'))
			{
				JCommentsEvent::trigger('onPrepareAvatars', array(&$rows));
			}

			$items = array();

			foreach ($rows as $row)
			{
				// Run autocensor, replace quotes, smilies and other pre-view processing
				self::prepareComment($row);

				// Setup toolbar
				if (!$acl->canModerate($row))
				{
					$tmpl->addVar('tpl_comment', 'comments-panel-visible', 0);
				}
				else
				{
					$tmpl->addVar('tpl_comment', 'comments-panel-visible', 1);
					$tmpl->addVar('tpl_comment', 'button-edit', $acl->canEdit($row));
					$tmpl->addVar('tpl_comment', 'button-delete', $acl->canDelete($row));
					$tmpl->addVar('tpl_comment', 'button-publish', $acl->canPublish($row));
					$tmpl->addVar('tpl_comment', 'button-ip', $acl->canViewIP($row));
					$tmpl->addVar('tpl_comment', 'button-ban', $acl->canBan($row));
				}

				$tmpl->addVar('tpl_comment', 'comment-show-vote', (int) $config->get('enable_voting'));
				$tmpl->addVar('tpl_comment', 'comment-show-email', $acl->canViewEmail($row));
				$tmpl->addVar('tpl_comment', 'comment-show-homepage', $acl->canViewHomepage($row));
				$tmpl->addVar('tpl_comment', 'comment-show-title', (int) $config->get('comment_title'));
				$tmpl->addVar('tpl_comment', 'comment-display-title', (int) $config->get('display_title'));
				$tmpl->addVar('tpl_comment', 'button-vote', $acl->canVote($row));
				$tmpl->addVar('tpl_comment', 'button-quote', $acl->canQuote($row));
				$tmpl->addVar('tpl_comment', 'button-reply', $acl->canReply($row));
				$tmpl->addVar('tpl_comment', 'button-report', $acl->canReport($row));
				$tmpl->addVar('tpl_comment', 'avatar', $user->authorise('comment.avatar', 'com_jcomments') && !$row->deleted);

				$tmpl->addObject('tpl_comment', 'comment', $row);

				if (isset($row->_number))
				{
					$tmpl->addVar('tpl_comment', 'comment-number', $row->_number);
				}
				else
				{
					$tmpl->addVar('tpl_comment', 'comment-number', $i);

					if ($config->get('comments_list_order') == 'DESC')
					{
						$i--;
					}
					else
					{
						$i++;
					}
				}

				$items[$row->id] = $tmpl->renderTemplate('tpl_comment');
			}

			$tmpl->addObject('tpl_list', 'comments-items', $items);

			// Build page navigation
			if (($commentsPerPage > 0) && ($totalPages > 1))
			{
				$tmpl->addVar('tpl_list', 'comments-nav-first', 1);
				$tmpl->addVar('tpl_list', 'comments-nav-total', $totalPages);
				$tmpl->addVar('tpl_list', 'comments-nav-active', $thisPage);

				$pagination = $config->get('comments_pagination');

				// Show top pagination
				if (($pagination == 'both') || ($pagination == 'top'))
				{
					$tmpl->addVar('tpl_list', 'comments-nav-top', 1);
				}

				// Show bottom pagination
				if (($pagination == 'both') || ($pagination == 'bottom'))
				{
					$tmpl->addVar('tpl_list', 'comments-nav-bottom', 1);
				}
			}

			unset($rows);
		}

		return $tmpl->renderTemplate('tpl_list');
	}

	public static function getCommentsTree($objectID, $objectGroup = 'com_content')
	{
		$objectID    = (int) $objectID;
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);
		$user        = Factory::getApplication()->getIdentity();
		$acl         = JCommentsFactory::getACL();
		$config      = ComponentHelper::getParams('com_jcomments');
		$total       = self::getCommentsCount($objectID, $objectGroup);

		if (!$user->authorise('comment.comment', 'com_jcomments') && $total == 0)
		{
			return '';
		}

		if ($total > 0)
		{
			$options                 = array();
			$options['object_id']    = $objectID;
			$options['object_group'] = $objectGroup;
			$options['published']    = $acl->canPublish() || $acl->canPublishForObject($objectID, $objectGroup) ? null : 1;
			$options['votes']        = (int) $config->get('enable_voting');

			$rows = JCommentsModel::getCommentsList($options);
		}
		else
		{
			$rows = array();
		}

		$tmpl = JCommentsFactory::getTemplate($objectID, $objectGroup);
		$tmpl->load('tpl_tree');
		$tmpl->load('tpl_comment');

		if (count($rows))
		{
			// The 'comments_locked' option value is set in PlgContentJcomments::prepareContent()
			$isLocked = ((int) $config->get('comments_locked', 0) == 1);

			$tmpl->addVar('tpl_tree', 'comments-refresh', !$isLocked);
			$tmpl->addVar('tpl_tree', 'comments-rss', intval((int) $config->get('enable_rss') && !$isLocked));
			$tmpl->addVar('tpl_tree', 'comments-can-subscribe', intval($user->get('id') && $user->authorise('comment.subscribe', 'com_jcomments') && !$isLocked));
			$tmpl->addVar('tpl_tree', 'comments-count', count($rows));

			if ($user->get('id') && $user->authorise('comment.subscribe', 'com_jcomments'))
			{
				require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

				$subscriptionModel = new JcommentsModelSubscriptions;
				$isSubscribed = $subscriptionModel->isSubscribed($objectID, $objectGroup, $user->get('id'));
				$tmpl->addVar('tpl_tree', 'comments-user-subscribed', $isSubscribed);
			}

			$i = 1;

			JCommentsEvent::trigger('onJCommentsCommentsPrepare', array(&$rows));

			if ($user->authorise('comment.avatar', 'com_jcomments'))
			{
				JCommentsEvent::trigger('onPrepareAvatars', array(&$rows));
			}

			require_once JPATH_ROOT . '/components/com_jcomments/libraries/joomlatune/tree.php';

			$tree  = new JoomlaTuneTree($rows);
			$items = $tree->get();

			foreach ($rows as $row)
			{
				// Run autocensor, replace quotes, smilies and other pre-view processing
				self::prepareComment($row);

				// Setup toolbar
				if (!$acl->canModerate($row))
				{
					$tmpl->addVar('tpl_comment', 'comments-panel-visible', 0);
				}
				else
				{
					$tmpl->addVar('tpl_comment', 'comments-panel-visible', 1);
					$tmpl->addVar('tpl_comment', 'button-edit', $acl->canEdit($row));
					$tmpl->addVar('tpl_comment', 'button-delete', $acl->canDelete($row));
					$tmpl->addVar('tpl_comment', 'button-publish', $acl->canPublish($row));
					$tmpl->addVar('tpl_comment', 'button-ip', $acl->canViewIP($row));
					$tmpl->addVar('tpl_comment', 'button-ban', $acl->canBan($row));
				}

				$tmpl->addVar('tpl_comment', 'comment-show-vote', (int) $config->get('enable_voting'));
				$tmpl->addVar('tpl_comment', 'comment-show-email', $acl->canViewEmail($row));
				$tmpl->addVar('tpl_comment', 'comment-show-homepage', $acl->canViewHomepage($row));
				$tmpl->addVar('tpl_comment', 'comment-show-title', (int) $config->get('comment_title'));
				$tmpl->addVar('tpl_comment', 'comment-display-title', (int) $config->get('display_title'));
				$tmpl->addVar('tpl_comment', 'button-vote', $acl->canVote($row));
				$tmpl->addVar('tpl_comment', 'button-quote', $acl->canQuote($row));
				$tmpl->addVar('tpl_comment', 'button-reply', $acl->canReply($row));
				$tmpl->addVar('tpl_comment', 'button-report', $acl->canReport($row));
				$tmpl->addVar('tpl_comment', 'avatar', $user->authorise('comment.avatar', 'com_jcomments') && !$row->deleted);

				if (isset($items[$row->id]))
				{
					$tmpl->addVar('tpl_comment', 'comment-number', '');
					$tmpl->addObject('tpl_comment', 'comment', $row);
					$items[$row->id]->html = $tmpl->renderTemplate('tpl_comment');
					$i++;
				}
			}

			$tmpl->addObject('tpl_tree', 'comments-items', $items);

			unset($rows);
		}

		return $tmpl->renderTemplate('tpl_tree');
	}

	public static function getCommentItem(&$comment)
	{
		$user   = Factory::getApplication()->getIdentity();
		$acl    = JCommentsFactory::getACL();
		$config = ComponentHelper::getParams('com_jcomments');

		if ($user->authorise('comment.avatar', 'com_jcomments'))
		{
			JCommentsEvent::trigger('onPrepareAvatar', array(&$comment));
		}

		// Run autocensor, replace quotes, smilies and other pre-view processing
		self::prepareComment($comment);

		$tmpl = JCommentsFactory::getTemplate($comment->object_id, $comment->object_group);
		$tmpl->load('tpl_comment');

		// Setup toolbar
		if (!$acl->canModerate($comment))
		{
			$tmpl->addVar('tpl_comment', 'comments-panel-visible', 'visibility');
		}
		else
		{
			$tmpl->addVar('tpl_comment', 'comments-panel-visible', 1);
			$tmpl->addVar('tpl_comment', 'button-edit', $acl->canEdit($comment));
			$tmpl->addVar('tpl_comment', 'button-delete', $acl->canDelete($comment));
			$tmpl->addVar('tpl_comment', 'button-publish', $acl->canPublish($comment));
			$tmpl->addVar('tpl_comment', 'button-ip', $acl->canViewIP($comment));
			$tmpl->addVar('tpl_comment', 'button-ban', $acl->canBan($comment));
			$tmpl->addVar('tpl_comment', 'comment-show-email', $acl->canViewEmail());
			$tmpl->addVar('tpl_comment', 'comment-show-homepage', $acl->canViewHomepage());
		}

		$tmpl->addVar('tpl_comment', 'comment-show-vote', (int) $config->get('enable_voting'));
		$tmpl->addVar('tpl_comment', 'comment-show-email', $acl->canViewEmail($comment));
		$tmpl->addVar('tpl_comment', 'comment-show-homepage', $acl->canViewHomepage($comment));
		$tmpl->addVar('tpl_comment', 'comment-show-title', (int) $config->get('comment_title'));
		$tmpl->addVar('tpl_comment', 'comment-display-title', (int) $config->get('display_title'));
		$tmpl->addVar('tpl_comment', 'button-vote', $acl->canVote($comment));
		$tmpl->addVar('tpl_comment', 'button-quote', $acl->canQuote($comment));
		$tmpl->addVar('tpl_comment', 'button-reply', $acl->canReply($comment));
		$tmpl->addVar('tpl_comment', 'button-report', $acl->canReport($comment));
		$tmpl->addVar('tpl_comment', 'comment-number', '');
		$tmpl->addVar('tpl_comment', 'avatar', $user->authorise('comment.avatar', 'com_jcomments') && !$comment->deleted);

		$tmpl->addObject('tpl_comment', 'comment', $comment);

		return $tmpl->renderTemplate('tpl_comment');
	}

	public static function getCommentListItem(&$comment)
	{
		$total = self::getCommentsCount($comment->object_id, $comment->object_group, 'parent = ' . $comment->parent);

		$tmpl = JCommentsFactory::getTemplate($comment->object_id, $comment->object_group);
		$tmpl->load('tpl_list');
		$tmpl->addVar('tpl_list', 'comment-id', $comment->id);
		$tmpl->addVar('tpl_list', 'comment-item', self::getCommentItem($comment));
		$tmpl->addVar('tpl_list', 'comment-modulo', $total % 2 ? 1 : 0);

		return $tmpl->renderTemplate('tpl_list');
	}

	/**
	 * Sends notification about new/updated comment to administrators
	 *
	 * @param   JCommentsTableComment  $comment  The comment object
	 * @param   boolean                $isNew    True if the comment is new
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function sendNotification($comment, $isNew = true)
	{
		$data            = array();
		$data['comment'] = clone $comment;

		JCommentsNotification::push($data, $isNew ? 'moderate-new' : 'moderate-update');
	}

	/**
	 * Sends user's report to administrators
	 *
	 * @param   JCommentsTableComment  $comment  The comment object
	 * @param   string                 $name     The reporter's name
	 * @param   string                 $reason   The report description
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function sendReport($comment, $name, $reason = '')
	{
		$data                  = array();
		$data['comment']       = clone $comment;
		$data['report-name']   = $name;
		$data['report-reason'] = $reason;

		JCommentsNotification::push($data, 'report');
	}

	/**
	 * Sends notification about new or updated comment to subscribers
	 *
	 * @param   JCommentsTableComment  $comment  The comment object
	 * @param   boolean                $isNew    True if the comment is new
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function sendToSubscribers($comment, $isNew = true)
	{
		if ($comment->published)
		{
			$data            = array();
			$data['comment'] = clone $comment;

			JCommentsNotification::push($data, $isNew ? 'comment-new' : 'comment-update');
		}
	}

	/**
	 * @param   JCommentsTableComment  $comment  Comment object
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   3.0
	 */
	public static function prepareComment(&$comment)
	{
		if (isset($comment->_skip_prepare) && $comment->_skip_prepare == 1)
		{
			return;
		}

		JCommentsEvent::trigger('onJCommentsCommentBeforePrepare', array(&$comment));

		$config = ComponentHelper::getParams('com_jcomments');
		$acl    = JCommentsFactory::getACL();
		$user   = Factory::getApplication()->getIdentity();

		// Run autocensor
		if ($acl->enableAutocensor())
		{
			$comment->comment = JCommentsText::censor($comment->comment);

			if ($comment->title != '')
			{
				$comment->title = JCommentsText::censor($comment->title);
			}
		}

		// Replace deleted comment text with predefined message
		if ($comment->deleted == 1)
		{
			$comment->comment  = Text::_('COMMENT_TEXT_COMMENT_HAS_BEEN_DELETED');
			$comment->username = '';
			$comment->name     = '';
			$comment->email    = '';
			$comment->homepage = '';
			$comment->userid   = 0;
			$comment->isgood   = 0;
			$comment->ispoor   = 0;
		}

		// Replace BBCode tags
		$comment->comment = JCommentsFactory::getBBCode()->replace($comment->comment);

		if ((int) $config->get('enable_custom_bbcode'))
		{
			$comment->comment = JCommentsFactory::getCustomBBCode()->replace($comment->comment);
		}

		if ($user->authorise('comment.email.protect', 'com_jcomments'))
		{
			$comment->comment = self::maskEmail($comment->id, $comment->comment);
		}

		// Autolink urls
		if ($user->authorise('comment.autolink', 'com_jcomments'))
		{
			$comment->comment = preg_replace_callback(_JC_REGEXP_LINK, array('JComments', 'urlProcessor'), $comment->comment);

			if (!$user->authorise('comment.email.protect', 'com_jcomments'))
			{
				$comment->comment = preg_replace(_JC_REGEXP_EMAIL, '<a href="mailto:\\1@\\2">\\1@\\2</a>', $comment->comment);
			}
		}

		// Replace smilies' codes with images
		if ($config->get('enable_smilies') == '1')
		{
			$comment->comment = JCommentsFactory::getSmilies()->replace($comment->comment);
		}

		$comment->author = JCommentsContent::getCommentAuthorName($comment);

		// Avatar support
		if (empty($comment->avatar))
		{
			$comment->avatar = Uri::base() . 'media/com_jcomments/images/no_avatar.png';
			$comment->profileLink = '';
		}

		JCommentsEvent::trigger('onJCommentsCommentAfterPrepare', array(&$comment));
	}

	/**
	 * @param   integer  $id     Comment ID.
	 * @param   string   $text   Comment text or string where to search.
	 * @param   boolean  $email  Search and replace only in email address, in comment text otherwise.
	 *                           Used only in JCommentsAJAX::jump2email() but required here.
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public static function maskEmail($id, $text, $email = false)
	{
		$id = (int) $id;

		if ($id)
		{
			$image = str_replace('/administrator', '', Uri::root()) . 'media/com_jcomments/images/email.png';

			$matches = array();
			$count   = preg_match_all(_JC_REGEXP_EMAIL, $text, $matches);

			for ($i = 0; $i < $count; $i++)
			{
				$html = '<span onclick="jcomments.jump2email(' . $id . ', \'' . md5($matches[0][$i]) . '\', ' . (int) $email . ');" class="email">';
				$html .= $matches[1][$i] . '<img src="' . $image . '" alt="@" />' . $matches[2][$i];
				$html .= '</span>';
				$text = str_replace($matches[0][$i], $html, $text);
			}
		}

		return $text;
	}

	public static function urlProcessor($matches)
	{
		$link       = $matches[2];
		$linkSuffix = '';

		while (preg_match('#[\,\.]+#', $link[strlen($link) - 1]))
		{
			$sl          = strlen($link) - 1;
			$linkSuffix .= $link[$sl];
			$link        = StringHelper::substr($link, 0, $sl);
		}

		$linkText      = preg_replace('#(http|https|news|ftp)\:\/\/#i', '', $link);
		$config        = ComponentHelper::getParams('com_jcomments');
		$linkMaxlength = (int) $config->get('link_maxlength');

		if (($linkMaxlength > 0) && (strlen($linkText) > $linkMaxlength))
		{
			$linkParts = preg_split('#\/#i', preg_replace('#/$#i', '', $linkText));
			$cnt       = count($linkParts);

			if ($cnt >= 2)
			{
				$linkSite     = $linkParts[0];
				$linkDocument = $linkParts[$cnt - 1];
				$shortLink    = $linkSite . '/.../' . $linkDocument;

				if ($cnt == 2)
				{
					$shortLink = $linkSite . '/.../';
				}
				elseif (strlen($shortLink) > $linkMaxlength)
				{
					$linkSite       = str_replace('www.', '', $linkSite);
					$linkSiteLength = strlen($linkSite);
					$shortLink      = $linkSite . '/.../' . $linkDocument;

					if (strlen($shortLink) > $linkMaxlength)
					{
						if ($linkSiteLength < $linkMaxlength)
						{
							$shortLink = $linkSite . '/.../...';
						}
						elseif ($linkDocument < $linkMaxlength)
						{
							$shortLink = '.../' . $linkDocument;
						}
						else
						{
							$linkProtocol = preg_replace('#([^a-z])#i', '', $matches[3]);

							if ($linkProtocol == 'www')
							{
								$linkProtocol = 'http';
							}

							if ($linkProtocol != '')
							{
								$shortLink = $linkProtocol;
							}
							else
							{
								$shortLink = '/.../';
							}
						}
					}
				}

				$linkText = wordwrap($shortLink, $linkMaxlength, ' ', true);
			}
			else
			{
				$linkText = wordwrap($linkText, $linkMaxlength, ' ', true);
			}
		}

		$liveSite = trim(str_replace(Uri::root(true), '', str_replace('/administrator', '', Uri::root())), '/');

		if (strpos($link, $liveSite) === false)
		{
			return $matches[1] . "<a href=\"" . ((StringHelper::substr($link, 0, 3) == 'www') ? "http://" : "") . $link . "\" target=\"_blank\" rel=\"external nofollow\">$linkText</a>" . $linkSuffix;
		}
		else
		{
			return $matches[1] . "<a href=\"$link\" target=\"_blank\">$linkText</a>" . $linkSuffix;
		}
	}

	public static function getCommentPage($objectID, $objectGroup, $commentID)
	{
		$config      = ComponentHelper::getParams('com_jcomments');
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);
		$thisPage    = 0;

		if ((int) $config->get('comments_per_page') > 0)
		{
			require_once JPATH_ROOT . '/components/com_jcomments/helpers/pagination.php';

			$pagination = new JCommentsPagination($objectID, $objectGroup);
			$thisPage   = $pagination->getCommentPage($objectID, $objectGroup, $commentID);
		}

		return $thisPage;
	}

	/**
	 * Method to execute some admin actions.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   3.0
	 */
	public static function executeCmd()
	{
		$app       = Factory::getApplication();
		$config    = ComponentHelper::getParams('com_jcomments');
		$cmd       = strtolower($app->input->get('cmd', ''));
		$hash      = $app->input->get('hash', '');
		$id        = $app->input->getInt('id', 0);
		$message   = '';
		$link      = str_replace('/administrator', '', Uri::root()) . 'index.php';
		$checkHash = JCommentsFactory::getCmdHash($cmd, $id);

		if ($hash == $checkHash)
		{
			if ((int) $config->get('enable_quick_moderation') == 1)
			{
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jcomments/tables');

				/** @var JCommentsTableComment $comment */
				$comment = Table::getInstance('Comment', 'JCommentsTable');

				if ($comment->load($id))
				{
					$link = JCommentsObject::getLink($comment->object_id, $comment->object_group, $comment->lang);
					$link = str_replace('&amp;', '&', $link);

					switch ($cmd)
					{
						case 'publish':
							$comment->published = 1;
							$comment->store();

							// Send notification to comment subscribers
							self::sendToSubscribers($comment);

							$link .= '#comment-' . $comment->id;
							break;

						case 'unpublish':
							$comment->published = 0;
							$comment->store();

							$acl = JCommentsFactory::getACL();

							if ($acl->canPublish())
							{
								$link .= '#comment-' . $comment->id;
							}
							else
							{
								$link .= '#comments';
							}
							break;

						case 'delete':
							if ((int) $config->get('delete_mode') == 0)
							{
								$comment->delete();
								$link .= '#comments';
							}
							else
							{
								$comment->markAsDeleted();
								$link .= '#comment-' . $comment->id;
							}
							break;

						case 'ban':
							if ((int) $config->get('enable_blacklist') == 1)
							{
								// We will not ban own IP ;)
								if ($comment->ip != $_SERVER['REMOTE_ADDR'])
								{
									$options       = array();
									$options['ip'] = $comment->ip;

									// Check if this IP already banned
									if (JCommentsSecurity::checkBlacklist($options))
									{
										$blacklist     = Table::getInstance('Blacklist', 'JCommentsTable');
										$blacklist->ip = $comment->ip;
										$blacklist->store();
										$message = Text::_('SUCCESSFULLY_BANNED');
									}
									else
									{
										$message = Text::_('ERROR_IP_ALREADY_BANNED');
									}
								}
								else
								{
									$message = Text::_('ERROR_YOU_CAN_NOT_BAN_YOUR_IP');
								}
							}
							break;
					}

					JCommentsNotification::send();
				}
				else
				{
					$message = Text::_('ERROR_NOT_FOUND');
				}
			}
			else
			{
				$message = Text::_('ERROR_QUICK_MODERATION_DISABLED');
			}
		}
		else
		{
			$message = Text::_('ERROR_QUICK_MODERATION_INCORRECT_HASH');
		}

		$app->redirect($link, $message);
	}

	/**
	 * @param   integer  $objectID     Item ID (not a comment ID).
	 * @param   string   $objectGroup  Component internal name
	 * @param   string   $filter       Additional filters
	 *
	 * @return  integer
	 *
	 * @since   3.0
	 */
	public static function getCommentsCount($objectID, $objectGroup = 'com_content', $filter = '')
	{
		$acl         = JCommentsFactory::getACL();
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		$options                 = array();
		$options['object_id']    = (int) $objectID;
		$options['object_group'] = trim($objectGroup);
		$options['published']    = $acl->canPublish() || $acl->canPublishForObject($objectID, $objectGroup) ? null : 1;
		$options['filter']       = $filter;

		return JCommentsModel::getCommentsCount($options);
	}
}

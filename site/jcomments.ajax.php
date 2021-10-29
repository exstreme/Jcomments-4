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

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

ob_start();

if (!defined('JOOMLATUNE_AJAX'))
{
	require_once JPATH_ROOT . '/components/com_jcomments/libraries/joomlatune/ajax.php';
}

if ((version_compare(phpversion(), '5.1.0') >= 0))
{
	date_default_timezone_set('UTC');
}

Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jcomments/tables');

/**
 * Frontend event handler
 *
 * @since  3.0
 */
class JCommentsAJAX
{
	public static function prepareValues(&$values)
	{
		foreach ($values as $k => $v)
		{
			if ($k == 'comment')
			{
				// Strip all HTML except [code]
				$m = array();
				preg_match_all('#(\[code\=?([a-z0-9]*?)\].*\[\/code\])#isUu', trim($v), $m);

				$tmp = array();
				$key = '';

				foreach ($m[1] as $code)
				{
					$key       = '{' . md5($code . $key) . '}';
					$tmp[$key] = $code;
					$v         = preg_replace('#' . preg_quote($code, '#') . '#isUu', $key, $v);
				}

				$v = trim(strip_tags($v));
				$v = JCommentsText::nl2br($v);

				foreach ($tmp as $key => $code)
				{
					$v = preg_replace('#' . preg_quote($key, '#') . '#isUu', $code, $v);
				}

				unset($tmp, $m);
				$values[$k] = $v;
			}
			else
			{
				$values[$k] = trim(strip_tags($v));
			}
		}

		return $values;
	}

	public static function escapeMessage($message)
	{
		$message = str_replace("\n", '\n', $message);
		$message = str_replace('\n', '<br />', $message);

		return JCommentsText::jsEscape($message);
	}

	public static function showErrorMessage($message, $name = '', $target = '')
	{
		$message  = self::escapeMessage($message);
		$response = JCommentsFactory::getAjaxResponse();
		$response->addScript("jcomments.error('$message','$target','$name');");
	}

	public static function showInfoMessage($message, $target = '')
	{
		$message  = self::escapeMessage($message);
		$response = JCommentsFactory::getAjaxResponse();
		$response->addScript("jcomments.message('$message', '$target');");
	}

	public static function showForm($objectID, $objectGroup, $target)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$response    = JCommentsFactory::getAjaxResponse();
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		$form = JComments::getCommentsForm($objectID, $objectGroup);
		$response->addAssign($target, 'innerHTML', $form);

		return $response;
	}

	public static function showReportForm($id, $target)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$config = ComponentHelper::getParams('com_jcomments');

		if ((int) $config->get('report_reason_required') == 0)
		{
			Factory::getApplication()->input->post->set('commentid', (int) $id);
			$response = JCommentsFactory::getAjaxResponse();
			$response->addAssign($target, 'innerHTML', '<div id="comments-report-form"></div>');

			return self::reportComment();
		}
		else
		{
			$response = JCommentsFactory::getAjaxResponse();
			$comment  = Table::getInstance('Comment', 'JCommentsTable');

			if ($comment->load($id))
			{
				$form = JComments::getCommentsReportForm($id, $comment->object_id, $comment->object_group);
				$response->addAssign($target, 'innerHTML', $form);
			}

			return $response;
		}
	}

	public static function addComment($values = array())
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		/** @var User $user */
		$user     = Factory::getApplication()->getIdentity();
		$acl      = JCommentsFactory::getACL();
		$config   = ComponentHelper::getParams('com_jcomments');
		$response = JCommentsFactory::getAjaxResponse();

		if ($user->authorise('comment.comment', 'com_jcomments'))
		{
			$values = self::prepareValues($_POST);

			$objectGroup = isset($values['object_group']) ? JCommentsSecurity::clearObjectGroup($values['object_group']) : '';
			$objectID    = isset($values['object_id']) ? (int) $values['object_id'] : '';

			if ($objectGroup == '' || $objectID == '')
			{
				$response->addAlert(self::escapeMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED')));

				return $response;
			}

			$commentsPerObject = (int) $config->get('max_comments_per_object');

			if ($commentsPerObject > 0)
			{
				$commentsCount = JComments::getCommentsCount($objectID, $objectGroup);

				if ($commentsCount >= $commentsPerObject)
				{
					$message = $config->get('message_locked');

					if (empty($message))
					{
						$message = Text::_('ERROR_CANT_COMMENT');
					}

					$response->addAlert(self::escapeMessage($message));

					return $response;
				}
			}

			$userIP = $_SERVER['REMOTE_ADDR'];

			if (!$user->get('id'))
			{
				$noErrors = false;

				if (isset($values['userid']) && intval($values['userid']) > 0)
				{
					// TODO: we need more correct way to detect login timeout. Add session token check.
					self::showErrorMessage(Text::_('ERROR_SESSION_EXPIRED'));
				}
				elseif (($config->get('author_name', 2) == 2) && empty($values['name']))
				{
					self::showErrorMessage(Text::_('ERROR_EMPTY_NAME'), 'name');
				}
				elseif (JCommentsSecurity::checkIsRegisteredUsername($values['name']) == 1)
				{
					self::showErrorMessage(Text::_('ERROR_NAME_EXISTS'), 'name');
				}
				elseif (JCommentsSecurity::checkIsForbiddenUsername($values['name']) == 1)
				{
					self::showErrorMessage(Text::_('ERROR_FORBIDDEN_NAME'), 'name');
				}
				elseif (preg_match('/[\"\'\[\]\=\<\>\(\)\;]+/', $values['name']))
				{
					self::showErrorMessage(Text::_('ERROR_INVALID_NAME'), 'name');
				}
				elseif (($config->get('username_maxlength') != 0)
					&& (StringHelper::strlen($values['name']) > $config->get('username_maxlength')))
				{
					self::showErrorMessage(Text::_('ERROR_TOO_LONG_USERNAME'), 'name');
				}
				elseif (((int) $config->get('author_email') == 2) && empty($values['email']))
				{
					self::showErrorMessage(Text::_('ERROR_EMPTY_EMAIL'), 'email');
				}
				elseif (!empty($values['email']) && (!preg_match(_JC_REGEXP_EMAIL2, $values['email'])))
				{
					self::showErrorMessage(Text::_('ERROR_INCORRECT_EMAIL'), 'email');
				}
				elseif (((int) $config->get('author_email') != 0) && JCommentsSecurity::checkIsRegisteredEmail($values['email']) == 1)
				{
					self::showErrorMessage(Text::_('ERROR_EMAIL_EXISTS'), 'email');
				}
				elseif (((int) $config->get('author_homepage') == 2) && empty($values['homepage']))
				{
					self::showErrorMessage(Text::_('ERROR_EMPTY_HOMEPAGE'), 'homepage');
				}
				else
				{
					$noErrors = true;
				}

				if (!$noErrors)
				{
					return $response;
				}
			}

			$values['name_checkbox_terms_of_use'] = isset($values['name_checkbox_terms_of_use']) ? (int) $values['name_checkbox_terms_of_use'] : 0;

			if (($user->authorise('comment.flood', 'com_jcomments') == 1) && (JCommentsSecurity::checkFlood($userIP)))
			{
				self::showErrorMessage(Text::_('ERROR_TOO_QUICK'));
			}
			elseif (((int) $config->get('show_checkbox_terms_of_use') == 1)
				&& ($values['name_checkbox_terms_of_use'] == 0)
				&& ($user->authorise('comment.terms_of_use', 'com_jcomments') == 1))
			{
				self::showErrorMessage(Text::_('ERROR_CHECKBOX_TERMS_OF_USE_NO_SELECTED'), 'name_checkbox_terms_of_use');
			}
			elseif (empty($values['homepage']) && ($config->get('author_homepage') == 3))
			{
				self::showErrorMessage(Text::_('ERROR_EMPTY_HOMEPAGE'), 'homepage');
			}
			elseif (empty($values['title']) && ($config->get('comment_title') == 3))
			{
				self::showErrorMessage(Text::_('ERROR_EMPTY_TITLE'), 'title');
			}
			elseif (empty($values['comment']))
			{
				self::showErrorMessage(Text::_('ERROR_EMPTY_COMMENT'), 'comment');
			}
			elseif (((int) $config->get('comment_maxlength') != 0)
				&& ($user->authorise('comment.length_check', 'com_jcomments') == 1)
				&& (StringHelper::strlen($values['comment']) > $config->get('comment_maxlength')))
			{
				self::showErrorMessage(Text::_('ERROR_YOUR_COMMENT_IS_TOO_LONG'), 'comment');
			}
			elseif (((int) $config->get('comment_minlength', 0) != 0)
				&& ($user->authorise('comment.length_check', 'com_jcomments') == 1)
				&& (StringHelper::strlen($values['comment']) < $config->get('comment_minlength')))
			{
				self::showErrorMessage(Text::_('ERROR_YOUR_COMMENT_IS_TOO_SHORT'), 'comment');
			}
			else
			{
				if ($user->authorise('comment.captcha', 'com_jcomments') == 1)
				{
					$captchaEngine = $config->get('captcha_engine', 'kcaptcha');

					switch ($captchaEngine)
					{
						case 'kcaptcha':
							require_once JPATH_ROOT . '/components/com_jcomments/jcomments.captcha.php';

							if (!JCommentsCaptcha::check($values['captcha_refid']))
							{
								self::showErrorMessage(Text::_('ERROR_CAPTCHA'), 'captcha');
								JCommentsCaptcha::destroy();
								$response->addScript("jcomments.clear('captcha');");

								return $response;
							}

							break;
						case 'recaptcha':
						case 'recaptcha_invisible':
							if ($captchaEngine == 'recaptcha')
							{
								JPluginHelper::importPlugin('captcha', 'recaptcha');
							}
							else
							{
								JPluginHelper::importPlugin('captcha', 'recaptcha_invisible');
							}

							try
							{
								Factory::getApplication()->triggerEvent('onCheckAnswer');
							}
							catch (Exception $e)
							{
								self::showErrorMessage($e->getMessage());
								$response->addScript("grecaptcha.reset()");

								return $response;
							}

							break;
						case 'hcaptcha':
						case 'hcaptcha_invisible':
							if ($captchaEngine == 'hcaptcha')
							{
								JPluginHelper::importPlugin('captcha', 'hcaptcha');
							}
							else
							{
								JPluginHelper::importPlugin('captcha', 'hcaptcha_invisible');
							}

							try
							{
								Factory::getApplication()->triggerEvent('onCheckAnswer');
							}
							catch (Exception $e)
							{
								self::showErrorMessage($e->getMessage());
								$response->addScript("hcaptcha.reset()");

								return $response;
							}

							break;
						default:
							$result = JCommentsEvent::trigger('onJCommentsCaptchaVerify', array($values['captcha_refid'], &$response));

							// If all plugins returns false
							if (!in_array(true, $result, true))
							{
								self::showErrorMessage(Text::_('ERROR_CAPTCHA'));

								return $response;
							}
					}
				}

				/** @var DatabaseDriver $db */
				$db = Factory::getContainer()->get('DatabaseDriver');

				// Small fix (by default $my has empty 'name' and 'email' field)
				if ($user->get('id'))
				{
					$currentUser    = Factory::getUser($user->get('id'));
					$user->name     = $currentUser->name;
					$user->username = $currentUser->username;
					$user->email    = $currentUser->email;
					unset($currentUser);
				}

				// Set user real name to 'Guest' for all languages.
				if (empty($values['name']))
				{
					$values['name'] = 'Guest';
				}

				$comment = Table::getInstance('Comment', 'JCommentsTable');

				$comment->id       = 0;
				$comment->name     = $user->get('id') ? $user->name : preg_replace("/[\'\"\>\<\(\)\[\]]?+/i", '', $values['name']);
				$comment->username = $user->get('id') ? $user->username : $comment->name;
				$comment->email    = $user->get('id') ? $user->email : ($values['email'] ?? '');

				if (((int) $config->get('author_homepage') != 0)
					&& !empty($values['homepage']))
				{
					$comment->homepage = JCommentsText::url($values['homepage']);
				}

				$comment->comment = $values['comment'];

				// Filter forbidden bbcodes
				$bbcode           = JCommentsFactory::getBBCode();
				$comment->comment = $bbcode->filter($comment->comment);

				if ($comment->comment != '')
				{
					if ((int) $config->get('enable_custom_bbcode'))
					{
						// Filter forbidden custom bbcodes
						$commentLength    = strlen($comment->comment);
						$customBBCode     = JCommentsFactory::getCustomBBCode();
						$comment->comment = $customBBCode->filter($comment->comment);

						if (strlen($comment->comment) == 0 && $commentLength > 0)
						{
							self::showErrorMessage(Text::_('ERROR_YOU_HAVE_NO_RIGHTS_TO_USE_THIS_TAG'), 'comment');

							return $response;
						}
					}
				}

				if ($comment->comment == '')
				{
					self::showErrorMessage(Text::_('ERROR_EMPTY_COMMENT'), 'comment');

					return $response;
				}

				$commentWithoutQuotes = $bbcode->removeQuotes($comment->comment);

				if ($commentWithoutQuotes == '')
				{
					self::showErrorMessage(Text::_('ERROR_NOTHING_EXCEPT_QUOTES'), 'comment');

					return $response;
				}
				elseif (((int) $config->get('comment_minlength', 0) != 0)
					&& ($user->authorise('comment.length_check', 'com_jcomments') == 1)
					&& (StringHelper::strlen($commentWithoutQuotes) < $config->get('comment_minlength')))
				{
					self::showErrorMessage(Text::_('ERROR_YOUR_COMMENT_IS_TOO_SHORT'), 'comment');

					return $response;
				}

				$values['subscribe'] = isset($values['subscribe']) ? (int) $values['subscribe'] : 0;

				if ($values['subscribe'] == 1 && $comment->email == '')
				{
					self::showErrorMessage(Text::_('ERROR_SUBSCRIPTION_EMAIL'), 'email');

					return $response;
				}

				$comment->object_id    = (int) $objectID;
				$comment->object_group = $objectGroup;
				$comment->title        = $values['title'] ?? '';
				$comment->parent       = isset($values['parent']) ? (int) $values['parent'] : 0;
				$comment->lang         = Factory::getApplication()->getLanguage()->getTag();
				$comment->ip           = $userIP;
				$comment->userid       = $user->get('id') ? $user->get('id') : 0;
				$comment->published    = $user->authorise('comment.autopublish', 'com_jcomments');
				$comment->date         = Factory::getDate()->toSql();

				$query = $db->getQuery(true);
				$query
					->select('COUNT(*)')
					->from($db->quoteName('#__jcomments'))
					->where($db->quoteName('comment') . ' = ' . $db->Quote($comment->comment))
					->where($db->quoteName('ip') . ' = ' . $db->Quote($comment->ip))
					->where($db->quoteName('name') . ' = ' . $db->Quote($comment->name))
					->where($db->quoteName('userid') . ' = ' . $comment->userid)
					->where($db->quoteName('object_id') . ' = ' . $comment->object_id)
					->where($db->quoteName('parent') . ' = ' . $comment->parent)
					->where($db->quoteName('object_group') . ' = ' . $db->Quote($comment->object_group));

				if (JCommentsFactory::getLanguageFilter())
				{
					$query->where($db->quoteName('lang') . ' = ' . $db->Quote(Factory::getApplication()->getLanguage()->getTag()));
				}

				$db->setQuery($query);

				try
				{
					$found = $db->loadResult();
				}
				catch (RuntimeException $e)
				{
					$found = 0;
					Log::add($e->getMessage(), 'warning', 'jcomments.ajax');
				}

				// If duplicates is not found
				if ($found == 0)
				{
					$result = JCommentsEvent::trigger('onJCommentsCommentBeforeAdd', array(&$comment));

					if (in_array(false, $result, true))
					{
						return $response;
					}

					// Save comments subscription
					if ($values['subscribe'])
					{
						require_once JPATH_ROOT . '/components/com_jcomments/jcomments.subscription.php';

						$manager = JCommentsSubscriptionManager::getInstance();
						$manager->subscribe($comment->object_id, $comment->object_group, $comment->userid, $comment->email, $comment->name, $comment->lang);
					}

					$merged    = false;
					$mergeTime = (int) $config->get('merge_time', 0);

					// Merge comments from same author
					if ($user->get('id') && $mergeTime > 0)
					{
						// Load previous comment for same object and group
						$prevComment = JCommentsModel::getLastComment($comment->object_id, $comment->object_group, $comment->parent);

						if ($prevComment != null)
						{
							// If previous comment from same author and it currently not edited
							// by any user - we'll update comment, else - insert new record to database
							if (($prevComment->userid == $comment->userid)
								&& ($prevComment->parent == $comment->parent)
								&& (!$acl->isLocked($prevComment)))
							{
								$newText  = $prevComment->comment . '<br /><br />' . $comment->comment;
								$timeDiff = strtotime($comment->date) - strtotime($prevComment->date);

								if ($timeDiff < $mergeTime)
								{
									$maxlength = (int) $config->get('comment_maxlength');
									$needcheck = $user->authorise('comment.length_check', 'com_jcomments');

									// Validate new comment text length and if it longer than specified -
									// disable union current comment with previous
									if (($needcheck == 0) || (($needcheck == 1) && ($maxlength != 0)
											&& (StringHelper::strlen($newText) <= $maxlength)))
									{
										$comment->id      = $prevComment->id;
										$comment->comment = $newText;
										$merged           = true;
									}
								}
							}

							unset($prevComment);
						}
					}

					// Save new comment to database
					if (!$comment->store())
					{
						$response->addScript("jcomments.clear('comment');");

						if ($user->authorise('comment.captcha', 'com_jcomments') == 1)
						{
							if ($config->get('captcha_engine', 'kcaptcha') == 'kcaptcha')
							{
								JCommentsCaptcha::destroy();
								$response->addScript("jcomments.clear('captcha');");
							}
							elseif ($config->get('captcha_engine', 'kcaptcha') == 'recaptcha')
							{
								$response->addScript("grecaptcha.reset()");
							}
							elseif ($config->get('captcha_engine', 'kcaptcha') == 'hcaptcha')
							{
								$response->addScript("hcaptcha.reset()");
							}
						}

						return $response;
					}

					// Store/update information about commented object
					JCommentsObject::storeObjectInfo($comment->object_id, $comment->object_group, $comment->lang);

					JCommentsEvent::trigger('onJCommentsCommentAfterAdd', array(&$comment));

					// Send notification to administrators
					if ((int) $config->get('enable_notification') == 1)
					{
						if (in_array(1, $config->get('notification_type')))
						{
							JComments::sendNotification($comment, true);
						}
					}

					// If comment published we need update comments list
					if ($comment->published)
					{
						// Send notification to comment subscribers
						JComments::sendToSubscribers($comment, true);

						if ($merged)
						{
							$commentText = $comment->comment;
							$html        = JCommentsText::jsEscape(JComments::getCommentItem($comment));
							$response->addScript("jcomments.updateComment(" . $comment->id . ", '$html');");
							$comment->comment = $commentText;
						}
						else
						{
							$count = JComments::getCommentsCount($comment->object_id, $comment->object_group);

							if ($config->get('template_view') == 'tree')
							{
								if ($count > 1)
								{
									$html = JComments::getCommentListItem($comment);
									$html = JCommentsText::jsEscape($html);
									$mode = ((int) $config->get('comments_tree_order') == 1
										|| ((int) $config->get('comments_tree_order') == 2 && $comment->parent > 0)) ? 'b' : 'a';
									$response->addScript("jcomments.updateTree('$html','$comment->parent','$mode');");
								}
								else
								{
									$html = JComments::getCommentsTree($comment->object_id, $comment->object_group);
									$html = JCommentsText::jsEscape($html);
									$response->addScript("jcomments.updateTree('$html',null);");
								}
							}
							else
							{
								// If pagination disabled and comments count > 1...
								if ((int) $config->get('comments_per_page') == 0 && $count > 1)
								{
									// Update only added comment
									$html = JComments::getCommentListItem($comment);
									$html = JCommentsText::jsEscape($html);

									if ($config->get('comments_list_order') == 'DESC')
									{
										$response->addScript("jcomments.updateList('$html','p');");
									}
									else
									{
										$response->addScript("jcomments.updateList('$html','a');");
									}
								}
								else
								{
									// Update comments list
									$html = JComments::getCommentsList(
										$comment->object_id,
										$comment->object_group,
										JComments::getCommentPage($comment->object_id, $comment->object_group, $comment->id)
									);
									$html = JCommentsText::jsEscape($html);
									$response->addScript("jcomments.updateList('$html','r');");
								}

								// Scroll to first comment
								if ($config->get('comments_list_order') == 'DESC')
								{
									$response->addScript("jcomments.scrollToList();");
								}
							}
						}

						self::showInfoMessage(Text::_('THANK_YOU_FOR_YOUR_SUBMISSION'));
					}
					else
					{
						self::showInfoMessage(Text::_('THANK_YOU_YOUR_COMMENT_WILL_BE_PUBLISHED_ONCE_REVIEWED'));
					}

					// Clear comments textarea & update comment length counter if needed
					$response->addScript("jcomments.clear('comment');");

					if ($user->authorise('comment.captcha', 'com_jcomments') == 1)
					{
						if ($config->get('captcha_engine', 'kcaptcha') == 'kcaptcha')
						{
							require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.captcha.php');

							JCommentsCaptcha::destroy();
							$response->addScript("jcomments.clear('captcha');");
						}
						elseif ($config->get('captcha_engine', 'kcaptcha') == 'recaptcha')
						{
							$response->addScript("grecaptcha.reset();");
						}
						elseif ($config->get('captcha_engine', 'kcaptcha') == 'hcaptcha')
						{
							$response->addScript("hcaptcha.reset();");
						}
					}
				}
				else
				{
					self::showErrorMessage(Text::_('ERROR_DUPLICATE_COMMENT'), 'comment');
				}
			}
		}
		else
		{
			$message = Text::_('ERROR_CANT_COMMENT');

			if ($acl->getUserBlocked())
			{
				$bannedMessage = $config->get('message_banned');

				if (!empty($bannedMessage))
				{
					$message = self::escapeMessage($bannedMessage);
				}
			}

			$response->addAlert($message);
		}

		return $response;
	}

	public static function deleteComment($id)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$acl      = JCommentsFactory::getACL();
		$config   = ComponentHelper::getParams('com_jcomments');
		$response = JCommentsFactory::getAjaxResponse();

		$comment = Table::getInstance('Comment', 'JCommentsTable');

		if ($comment->load((int) $id))
		{
			if ($acl->isLocked($comment))
			{
				$response->addAlert(Text::_('ERROR_BEING_EDITTED'));
			}
			elseif ($acl->canDelete($comment))
			{
				$objectID    = $comment->object_id;
				$objectGroup = $comment->object_group;

				$result = JCommentsEvent::trigger('onJCommentsCommentBeforeDelete', array(&$comment));

				if (!in_array(false, $result, true))
				{
					if ((int) $config->get('delete_mode') == 0)
					{
						$comment->delete();
						$count = JComments::getCommentsCount($objectID, $objectGroup);

						if ($config->get('template_view') == 'tree')
						{
							if ($count > 0)
							{
								$response->addScript("jcomments.updateComment('$id','');");
							}
							else
							{
								$response->addScript("jcomments.updateTree('',null);");
							}
						}
						else
						{
							if ($count > 0)
							{
								if ((int) $config->get('comments_per_page') > 0)
								{
									require_once JPATH_ROOT . '/components/com_jcomments/helpers/pagination.php';

									$pagination = new JCommentsPagination($objectID, $objectGroup);
									$pagination->setCommentsCount($count);
									$currentPage = $pagination->getCommentPage($objectID, $objectGroup, $id);
									$currentPage = min($currentPage, $pagination->getTotalPages());

									$html = JComments::getCommentsList($objectID, $objectGroup, $currentPage);
									$html = JCommentsText::jsEscape($html);
									$response->addScript("jcomments.updateList('$html','r');");
								}
								else
								{
									$response->addScript("jcomments.updateComment('$id','');");
								}
							}
							else
							{
								$response->addScript("jcomments.updateList('','r');");
							}
						}
					}
					else
					{
						$comment->markAsDeleted();
						$html = JCommentsText::jsEscape(JComments::getCommentItem($comment));
						$response->addScript("jcomments.updateComment(" . $comment->id . ", '$html');");
					}

					JCommentsEvent::trigger('onJCommentsCommentAfterDelete', array(&$comment));
				}
			}
			else
			{
				$response->addAlert(Text::_('ERROR_CANT_DELETE'));
			}
		}

		return $response;
	}

	/**
	 * Publish or unpublish comment
	 *
	 * @param   integer  $id  Comment ID
	 *
	 * @return  JoomlaTuneAjaxResponse|null
	 *
	 * @see     JCommentsPublishComment
	 * @since   3.0
	 */
	public static function publishComment($id)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$acl      = JCommentsFactory::getACL();
		$response = JCommentsFactory::getAjaxResponse();

		$comment = Table::getInstance('Comment', 'JCommentsTable');

		if ($comment->load((int) $id))
		{
			if ($acl->isLocked($comment))
			{
				$response->addAlert(Text::_('ERROR_BEING_EDITTED'));
			}
			elseif ($acl->canPublish($comment))
			{
				$objectID           = $comment->object_id;
				$objectGroup        = $comment->object_group;
				$page               = JComments::getCommentPage($objectID, $objectGroup, $comment->id);
				$comment->published = $comment->published == 1 ? 0 : 1;

				$result = JCommentsEvent::trigger('onJCommentsCommentBeforePublish', array(&$comment));

				if (!in_array(false, $result, true))
				{
					if ($comment->store())
					{
						JCommentsEvent::trigger('onJCommentsCommentAfterPublish', array(&$comment));

						if ($comment->published)
						{
							JComments::sendToSubscribers($comment);
						}

						self::updateCommentsList($response, $objectID, $objectGroup, $page);
					}
				}
			}
			else
			{
				$response->addAlert(Text::_('ERROR_CANT_PUBLISH'));
			}
		}

		return $response;
	}

	public static function cancelComment($id)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$response = JCommentsFactory::getAjaxResponse();
		$comment  = Table::getInstance('Comment', 'JCommentsTable');

		if ($comment->load((int) $id))
		{
			$acl = JCommentsFactory::getACL();

			if (!$acl->isLocked($comment))
			{
				$comment->checkin();
			}
		}

		return $response;
	}

	public static function editComment($id, $loadForm = 0)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$user     = Factory::getUser();
		$response = JCommentsFactory::getAjaxResponse();
		$comment  = Table::getInstance('Comment', 'JCommentsTable');

		if ($comment->load((int) $id))
		{
			$acl = JCommentsFactory::getACL();

			if ($acl->isLocked($comment))
			{
				$response->addAlert(Text::_('ERROR_BEING_EDITTED'));
			}
			elseif ($acl->canEdit($comment))
			{
				$comment->checkout($user->id);

				$name     = ($comment->userid) ? '' : JCommentsText::jsEscape($comment->name);
				$email    = ($comment->userid) ? '' : JCommentsText::jsEscape($comment->email);
				$homepage = JCommentsText::jsEscape($comment->homepage);
				$text     = JCommentsText::jsEscape(JCommentsText::br2nl($comment->comment));
				$title    = JCommentsText::jsEscape(str_replace("\n", '', JCommentsText::br2nl($comment->title)));

				if ((int) $loadForm == 1)
				{
					$form = JComments::getCommentsForm($comment->object_id, $comment->object_group);
					$response->addAssign('comments-form-link', 'innerHTML', $form);
				}

				$response->addScript("jcomments.showEdit(" . $comment->id . ", '$name', '$email', '$homepage', '$title', '$text');");
			}
			else
			{
				$response->addAlert(Text::_('ERROR_CANT_EDIT'));
			}
		}

		return $response;
	}

	public static function saveComment($values = array())
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$config = ComponentHelper::getParams('com_jcomments');
		$user   = Factory::getApplication()->getIdentity();

		$response = JCommentsFactory::getAjaxResponse();
		$values   = self::prepareValues($_POST); // TODO $values from function or from method?
		$comment  = Table::getInstance('Comment', 'JCommentsTable');
		$id       = (int) $values['id'];

		if ($comment->load($id))
		{
			$acl = JCommentsFactory::getACL();

			$values['name_checkbox_terms_of_use'] = isset($values['name_checkbox_terms_of_use']) ? (int) $values['name_checkbox_terms_of_use'] : 0;

			if ($acl->canEdit($comment))
			{
				if ($values['comment'] == '')
				{
					self::showErrorMessage(Text::_('ERROR_EMPTY_COMMENT'), 'comment');
				}
				elseif (((int) $config->get('show_checkbox_terms_of_use') == 1)
					&& ($values['name_checkbox_terms_of_use'] == 0)
					&& ($user->authorise('comment.terms_of_use', 'com_jcomments') == 1))
				{
					self::showErrorMessage(Text::_('ERROR_CHECKBOX_TERMS_OF_USE_NO_SELECTED'), 'name_checkbox_terms_of_use');
				}
				elseif (((int) $config->get('comment_maxlength') != 0)
					&& ($user->authorise('comment.length_check', 'com_jcomments') == 1)
					&& (StringHelper::strlen($values['comment']) > (int) $config->get('comment_maxlength')))
				{
					self::showErrorMessage(Text::_('ERROR_YOUR_COMMENT_IS_TOO_LONG'), 'comment');
				}
				elseif (((int) $config->get('comment_minlength') != 0)
					&& ($user->authorise('comment.length_check', 'com_jcomments') == 1)
					&& (StringHelper::strlen($values['comment']) < (int) $config->get('comment_minlength')))
				{
					self::showErrorMessage(Text::_('ERROR_YOUR_COMMENT_IS_TOO_SHORT'), 'comment');
				}
				else
				{
					$bbcode = JCommentsFactory::getBBCode();

					$comment->comment   = $values['comment'];
					$comment->comment   = $bbcode->filter($comment->comment);
					$comment->published = $user->authorise('comment.autopublish', 'com_jcomments');


					if (((int) $config->get('comment_title') != 0) && isset($values['title']))
					{
						$comment->title = stripslashes((string) $values['title']);
					}

					if (((int) $config->get('author_homepage') == 1) && isset($values['homepage']))
					{
						$comment->homepage = JCommentsText::url($values['homepage']);
					}
					else
					{
						$comment->homepage = '';
					}

					$result = JCommentsEvent::trigger('onJCommentsCommentBeforeChange', array(&$comment));

					if (in_array(false, $result, true))
					{
						return $response;
					}

					$comment->store();
					$comment->checkin();

					JCommentsEvent::trigger('onJCommentsCommentAfterChange', array(&$comment));

					if ((int) $config->get('enable_notification') == 1)
					{
						if (in_array(1, $config->get('notification_type')))
						{
							JComments::sendNotification($comment, false);
						}
					}

					$html = JCommentsText::jsEscape(JComments::getCommentItem($comment));
					$response->addScript("jcomments.updateComment(" . $comment->id . ", '$html');");
				}
			}
			else
			{
				$response->addAlert(Text::_('ERROR_CANT_EDIT'));
			}
		}

		return $response;
	}

	public static function quoteComment($id, $loadForm = 0)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$user     = Factory::getApplication()->getIdentity();
		$acl      = JCommentsFactory::getACL();
		$config   = ComponentHelper::getParams('com_jcomments');
		$response = JCommentsFactory::getAjaxResponse();
		$comment  = Table::getInstance('Comment', 'JCommentsTable');

		if ($comment->load((int) $id))
		{
			$commentName = JCommentsContent::getCommentAuthorName($comment);
			$commentText = JCommentsText::br2nl($comment->comment);

			if ((int) $config->get('enable_nested_quotes') == 0)
			{
				$bbcode      = JCommentsFactory::getBBCode();
				$commentText = $bbcode->removeQuotes($commentText);
			}

			if ((int) $config->get('enable_custom_bbcode'))
			{
				$customBBCode = JCommentsFactory::getCustomBBCode();
				$commentText  = $customBBCode->filter($commentText, true);
			}

			if ($user->get('id') == 0)
			{
				$bbcode      = JCommentsFactory::getBBCode();
				$commentText = $bbcode->removeHidden($commentText);
			}

			if ($commentText != '')
			{
				if ($acl->enableAutocensor())
				{
					$commentText = JCommentsText::censor($commentText);
				}

				if ((int) $loadForm == 1)
				{
					$form = JComments::getCommentsForm($comment->object_id, $comment->object_group);
					$response->addAssign('comments-form-link', 'innerHTML', $form);
				}

				$commentName = JCommentsText::jsEscape($commentName);
				$commentText = JCommentsText::jsEscape($commentText);
				$text        = '[quote name="' . $commentName . '"]' . $commentText . '[/quote]\n';
				$response->addScript("jcomments.insertText('" . $text . "');");
			}
			else
			{
				$response->addAlert(Text::_('ERROR_NOTHING_TO_QUOTE'));
			}
		}

		return $response;
	}

	public static function updateCommentsList($response, $objectID, $objectGroup, $page)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if ($config->get('template_view') == 'tree')
		{
			$html = JComments::getCommentsTree($objectID, $objectGroup, $page);
			$html = JCommentsText::jsEscape($html);
			$response->addScript("jcomments.updateTree('$html',null);");
		}
		else
		{
			$html = JComments::getCommentsList($objectID, $objectGroup, $page);
			$html = JCommentsText::jsEscape($html);
			$response->addScript("jcomments.updateList('$html','r');");
		}
	}

	public static function showPage($objectID, $objectGroup, $page)
	{
		$response = JCommentsFactory::getAjaxResponse();

		$objectID    = (int) $objectID;
		$objectGroup = strip_tags($objectGroup);
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);
		$page        = (int) $page;

		self::updateCommentsList($response, $objectID, $objectGroup, $page);

		return $response;
	}

	public static function showComment($id)
	{
		$response = JCommentsFactory::getAjaxResponse();
		$acl      = JCommentsFactory::getACL();
		$config   = ComponentHelper::getParams('com_jcomments');
		$comment  = Table::getInstance('Comment', 'JCommentsTable');

		if ($comment->load((int) $id) && ($acl->canPublish($comment) || $comment->published))
		{
			if ($config->get('template_view') == 'tree')
			{
				$page = 0;
			}
			else
			{
				$page = JComments::getCommentPage($comment->object_id, $comment->object_group, $comment->id);
			}

			self::updateCommentsList($response, $comment->object_id, $comment->object_group, $page);
			$response->addScript("jcomments.scrollToComment('$id');");
		}
		else
		{
			$response->addAlert(Text::_('ERROR_NOT_FOUND'));
		}

		return $response;
	}

	public static function jump2email($id, $hash)
	{
		$response = JCommentsFactory::getAjaxResponse();
		$comment  = Table::getInstance('Comment', 'JCommentsTable');
		$hash     = preg_replace('#[\(\)\'\"]#is', '', strip_tags($hash));

		if ((strlen($hash) == 32) && ($comment->load((int) $id)))
		{
			$matches = array();
			preg_match_all(_JC_REGEXP_EMAIL, $comment->comment, $matches);

			foreach ($matches[0] as $email)
			{
				if (md5((string) $email) == $hash)
				{
					$response->addScript("window.location='mailto:$email';");
				}
			}
		}

		return $response;
	}

	public static function subscribeUser($objectID, $objectGroup)
	{
		$user     = Factory::getApplication()->getIdentity();
		$response = JCommentsFactory::getAjaxResponse();

		if ($user->get('id'))
		{
			require_once JPATH_ROOT . '/components/com_jcomments/jcomments.subscription.php';

			$manager = JCommentsSubscriptionManager::getInstance();
			$result  = $manager->subscribe($objectID, $objectGroup, $user->get('id'));

			if ($result)
			{
				$response->addScript("jcomments.updateSubscription(true, '" . Text::_('BUTTON_UNSUBSCRIBE') . "');");
			}
			else
			{
				$errors = $manager->getErrors();

				if (count($errors))
				{
					$response->addAlert(implode('\n', $errors));
				}
			}
		}

		return $response;
	}

	public static function unsubscribeUser($objectID, $objectGroup)
	{
		$user        = Factory::getApplication()->getIdentity();
		$response    = JCommentsFactory::getAjaxResponse();
		$objectGroup = JCommentsSecurity::clearObjectGroup($objectGroup);

		if ($user->get('id'))
		{
			require_once JPATH_ROOT . '/components/com_jcomments/jcomments.subscription.php';

			$manager = JCommentsSubscriptionManager::getInstance();
			$result  = $manager->unsubscribe($objectID, $objectGroup, $user->get('id'));

			if ($result)
			{
				$response->addScript("jcomments.updateSubscription(false, '" . Text::_('BUTTON_SUBSCRIBE') . "');");
			}
			else
			{
				$errors = $manager->getErrors();
				$response->addAlert(implode('\n', $errors));
			}
		}

		return $response;
	}

	public static function voteComment($id, $value)
	{
		/** @var DatabaseDriver $db */
		$db       = Factory::getContainer()->get('DatabaseDriver');
		$user     = Factory::getApplication()->getIdentity();
		$acl      = JCommentsFactory::getACL();
		$response = JCommentsFactory::getAjaxResponse();

		$value = (int) $value;
		$value = ($value > 0) ? 1 : -1;
		$ip    = $_SERVER['REMOTE_ADDR'];

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments_votes'))
			->where($db->quoteName('commentid') . ' = ' . (int) $id);

		if ($user->get('id'))
		{
			$query->where($db->quoteName('userid') . ' = ' . $user->get('id'));
		}
		else
		{
			$query->where($db->quoteName('userid') . ' = 0')
				->where($db->quoteName('ip') . ' = ' . $db->quote($ip));
		}

		$db->setQuery($query);
		$voted = $db->loadResult();

		if ($voted == 0)
		{
			$comment = Table::getInstance('Comment', 'JCommentsTable');

			if ($comment->load((int) $id))
			{
				if ($acl->canVote($comment))
				{
					$result = JCommentsEvent::trigger('onJCommentsCommentBeforeVote', array(&$comment, &$value));

					if (!in_array(false, $result, true))
					{
						if ($value > 0)
						{
							$comment->isgood++;
						}
						else
						{
							$comment->ispoor++;
						}

						$comment->store();

						$now   = Factory::getDate()->toSql();
						$query = $db->getQuery(true)
							->insert($db->quoteName('#__jcomments_votes'))
							->columns(
								array(
									$db->quoteName('commentid'),
									$db->quoteName('userid'),
									$db->quoteName('ip'),
									$db->quoteName('date'),
									$db->quoteName('value')
								)
							)
							->values(
								$db->quote($comment->id) . ', ' .
								$db->quote($user->get('id')) . ', ' .
								$db->quote($ip) . ', ' .
								$db->quote($now) . ', ' .
								$value
							);

						$db->setQuery($query);
						$db->execute();

						JCommentsEvent::trigger('onJCommentsCommentAfterVote', array(&$comment, $value));
					}

					$tmpl = JCommentsFactory::getTemplate();
					$tmpl->load('tpl_comment');
					$tmpl->addVar('tpl_comment', 'get_comment_vote', 1);
					$tmpl->addObject('tpl_comment', 'comment', $comment);

					$html = $tmpl->renderTemplate('tpl_comment');
					$html = JCommentsText::jsEscape($html);
					$response->addScript("jcomments.updateVote('" . $comment->id . "','$html');");
				}
				else
				{
					$response->addAlert(Text::_('ERROR_CANT_VOTE'));
				}
			}
			else
			{
				$response->addAlert(Text::_('ERROR_NOT_FOUND'));
			}
		}
		else
		{
			$response->addAlert(Text::_('ERROR_ALREADY_VOTED'));
		}

		return $response;
	}

	public static function reportComment()
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		/** @var DatabaseDriver $db */
		$db       = Factory::getContainer()->get('DatabaseDriver');
		$app      = Factory::getApplication();
		$acl      = JCommentsFactory::getACL();
		$config   = ComponentHelper::getParams('com_jcomments');
		$response = JCommentsFactory::getAjaxResponse();
		$user     = $app->getIdentity();

		$id     = $app->input->getInt('commentid');
		$reason = $app->input->getString('reason');
		$name   = $app->input->getString('name');
		$ip     = $_SERVER['REMOTE_ADDR'];

		if (empty($reason))
		{
			if ((int) $config->get('report_reason_required') == 1)
			{
				self::showErrorMessage(Text::_('ERROR_NO_REASON_FOR_REPORT'), '', 'comments-report-form');

				return $response;
			}
			else
			{
				$reason = Text::_('REPORT_REASON_UNKNOWN_REASON');
			}
		}

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments_reports'))
			->where($db->quoteName('commentid') . ' = ' . $id);

		if ($user->get('id'))
		{
			$query->where($db->quoteName('userid') . ' = ' . $user->get('id'));
		}
		else
		{
			$query->where($db->quoteName('userid') . ' = 0')
				->where($db->quoteName('ip') . ' = ' . $db->quote($ip));
		}

		$db->setQuery($query);
		$reported = $db->loadResult();

		if (!$reported)
		{
			$maxReportsPerComment      = (int) $config->get('reports_per_comment', 1);
			$maxReportsBeforeUnpublish = (int) $config->get('reports_before_unpublish', 0);
			$query->clear()
				->select('COUNT(*)')
				->from($db->quoteName('#__jcomments_reports'))
				->where($db->quoteName('commentid') . ' = ' . $id);

			$db->setQuery($query);
			$reported = $db->loadResult();

			if ($reported < $maxReportsPerComment || $maxReportsPerComment == 0)
			{
				$comment = Table::getInstance('Comment', 'JCommentsTable');

				if ($comment->load($id))
				{
					if ($acl->canReport($comment))
					{
						if ($user->get('id'))
						{
							$name = $user->get('name');
						}
						else
						{
							if (empty($name))
							{
								$name = 'Guest';
							}
						}

						$report            = Table::getInstance('Report', 'JCommentsTable');
						$report->commentid = $comment->id;
						$report->date      = Factory::getDate()->toSql();
						$report->userid    = $user->get('id');
						$report->ip        = $ip;
						$report->name      = $name;
						$report->reason    = $reason;

						$html   = '';
						$result = JCommentsEvent::trigger('onJCommentsCommentBeforeReport', array(&$comment, &$report));

						if (!in_array(false, $result, true))
						{
							if ($report->store())
							{
								JCommentsEvent::trigger('onJCommentsCommentAfterReport', array(&$comment, $report));

								if ((int) $config->get('enable_notification') == 1)
								{
									if (in_array(2, $config->get('notification_type')))
									{
										JComments::sendReport($comment, $name, $reason);
									}
								}

								// Unpublish comment if reports count is enough
								if ($maxReportsBeforeUnpublish > 0 && $reported >= $maxReportsBeforeUnpublish)
								{
									$comment->published = 0;
									$comment->store();
								}

								$html = Text::_('REPORT_SUCCESSFULLY_SENT');
								$html = str_replace("\n", '\n', $html);
								$html = str_replace('\n', '<br />', $html);
								$html = JCommentsText::jsEscape($html);
							}
						}

						$response->addScript("jcomments.closeReport('$html');");
					}
					else
					{
						self::showErrorMessage(Text::_('ERROR_YOU_HAVE_NO_RIGHTS_TO_REPORT'), '', 'comments-report-form');
					}
				}
				else
				{
					$response->addAlert(Text::_('ERROR_NOT_FOUND'));
				}
			}
			else
			{
				self::showErrorMessage(Text::_('ERROR_COMMENT_ALREADY_REPORTED'), '', 'comments-report-form');
			}
		}
		else
		{
			self::showErrorMessage(Text::_('ERROR_YOU_CAN_NOT_REPORT_THE_SAME_COMMENT_MORE_THAN_ONCE'), '', 'comments-report-form');
		}

		return $response;
	}

	public static function BanIP($id)
	{
		if (JCommentsSecurity::badRequest() == 1)
		{
			JCommentsSecurity::notAuth();
		}

		$acl      = JCommentsFactory::getACL();
		$response = JCommentsFactory::getAjaxResponse();

		if ($acl->canBan())
		{
			$config = ComponentHelper::getParams('com_jcomments');

			if ((int) $config->get('enable_blacklist') == 1)
			{
				$comment = Table::getInstance('Comment', 'JCommentsTable');

				if ($comment->load((int) $id))
				{
					// We will not ban own IP ;)
					if ($comment->ip != $_SERVER['REMOTE_ADDR'])
					{
						$options       = array();
						$options['ip'] = $comment->ip;

						// Check if this IP already banned
						if (JCommentsSecurity::checkBlacklist($options))
						{
							$result = JCommentsEvent::trigger('onJCommentsUserBeforeBan', array(&$comment, &$options));

							if (!in_array(false, $result, true))
							{
								$blacklist             = Table::getInstance('Blacklist', 'JCommentsTable');
								$blacklist->ip         = $comment->ip;
								$blacklist->created    = Factory::getDate()->toSql();
								$blacklist->created_by = Factory::getApplication()->getIdentity()->get('id');

								if ($blacklist->store())
								{
									JCommentsEvent::trigger('onJCommentsUserAfterBan', array(&$comment, $options));
									self::showInfoMessage(Text::_('SUCCESSFULLY_BANNED'), 'comment-item-' . (int) $id);
								}
							}
						}
						else
						{
							self::showErrorMessage(Text::_('ERROR_IP_ALREADY_BANNED'), '', 'comment-item-' . (int) $id);
						}
					}
					else
					{
						self::showErrorMessage(Text::_('ERROR_YOU_CAN_NOT_BAN_YOUR_IP'), '', 'comment-item-' . (int) $id);
					}
				}
			}
		}

		return $response;
	}

	public static function refreshObjectsAjax()
	{
		$app = Factory::getApplication();

		$hash = $app->input->post->get('hash', '');
		$step = $app->input->post->getInt('step');
		$lang = $app->input->post->get('lang', '');

		if ($hash === md5($app->get('secret')))
		{
			/** @var DatabaseDriver $db * */
			$db = Factory::getContainer()->get('DatabaseDriver');

			if ($step == 0)
			{
				if ($app->get('caching') != 0)
				{
					// Clean cache for all object groups
					$query = $db->getQuery(true)
						->select('DISTINCT ' . $db->quoteName('object_group'))
						->from($db->quoteName('#__jcomments_objects'));

					$db->setQuery($query);
					$rows = $db->loadColumn();

					foreach ($rows as $row)
					{
						/** @var CallbackController $cache */
						$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
							->createCacheController('callback', ['defaultgroup' => 'com_jcomments_objects_' . strtolower($row)]);

						/** @var Cache $cache */
						$cache->clean();
					}
				}

				$db->setQuery('TRUNCATE TABLE ' . $db->quoteName('#__jcomments_objects'));
				$db->execute();
			}

			// Count objects without information
			$query = $db->getQuery(true)
				->clear()
				->select('COUNT(DISTINCT ' . $db->quoteName('c.object_id') . ', ' . $db->quoteName('c.object_group') . ', ' . $db->quoteName('c.lang') . ')')
				->from($db->quoteName('#__jcomments', 'c'))
				->where('IFNULL(' . $db->quoteName('c.lang') . ', "") <> ""');

			$db->setQuery($query);
			$total = (int) $db->loadResult();

			$count = 0;

			if ($total > 0)
			{
				// Get list of first objects without information
				$query = $db->getQuery(true)
					->select('DISTINCT ' . $db->quoteName('c.object_id') . ', ' . $db->quoteName('c.object_group') . ', ' . $db->quoteName('c.lang'))
					->from($db->quoteName('#__jcomments', 'c'))
					->where('IFNULL(' . $db->quoteName('c.lang') . ', "") <> ""');

					$subquery = $db->getQuery(true)
						->select($db->quoteName('o.id'))
						->from($db->quoteName('#__jcomments_objects', 'o'))
						->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.object_id'))
						->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.object_group'))
						->where($db->quoteName('o.object_id') . ' = ' . $db->quoteName('c.lang'));

				$query->where('NOT EXISTS (' . $subquery . ')')
					->order($db->quoteName(array('c.object_group', 'c.lang')));

				$db->setQuery($query, 0, $count);
				$rows = $db->loadObjectList();

				$i             = 0;
				$multilanguage = JCommentsFactory::getLanguageFilter();
				$nextLanguage  = $lang;

				if (count($rows))
				{
					foreach ($rows as $row)
					{
						if ($nextLanguage != $row->lang && $multilanguage)
						{
							$nextLanguage = $row->lang;
							break;
						}

						// Retrieve and store object information
						JCommentsObject::storeObjectInfo($row->object_id, $row->object_group, $row->lang, false, true);
						$i++;
					}
				}

				if ($i > 0)
				{
					$query = $db->getQuery(true)
						->select('COUNT(id)')
						->from($db->quoteName('#__jcomments_objects'));

					$db->setQuery($query);
					$count = (int) $db->loadResult();
				}

				$percent = ceil(($count / $total) * 100);
				$percent = min($percent, 100);
			}
			else
			{
				$percent = 100;
			}

			$step++;

			$langCodes   = LanguageHelper::getLanguages('lang_code');
			$languageSef = isset($langCodes[$nextLanguage]) ? $langCodes[$nextLanguage]->sef : $nextLanguage;

			$data = array(
				'count'        => $count,
				'total'        => $total,
				'percent'      => $percent,
				'step'         => $step,
				'hash'         => $hash,
				'object_group' => null,
				'lang'         => $nextLanguage,
				'lang_sef'     => $languageSef
			);

			echo json_encode($data);
		}

		$app->close();
	}
}

$result = ob_get_contents();
ob_end_clean();

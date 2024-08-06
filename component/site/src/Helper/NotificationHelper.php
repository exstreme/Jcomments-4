<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2024 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use PHPMailer\PHPMailer\Exception as phpMailerException;

/**
 * JComments Notification Helper
 *
 * @since  4.1
 */
class NotificationHelper
{
	/**
	 * Pushes the email notification to the mail queue
	 *
	 * @param   mixed   $data  An array or object with notification data. Usually comment object.
	 * @param   string  $type  Type of notification
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function enqueueMessage($data, string $type): bool
	{
		if (empty($type))
		{
			return false;
		}

		if (is_array($data))
		{
			$data = ArrayHelper::toObject($data);
		}

		if (isset($data->id))
		{
			PluginHelper::importPlugin('jcomments');
			$app = Factory::getApplication();

			// Load frontend language file if this method called from backend.
			if ($app->isClient('administrator'))
			{
				$app->getLanguage()->load('com_jcomments', JPATH_SITE, $data->lang);
			}

			$dispatcher = $app->getDispatcher();
			$user       = $app->getIdentity();
			$email      = $user->get('email');
			$data       = self::prepareData($data, $type);

			$dispatcher->dispatch(
				'onMailBeforeNotificationPush',
				AbstractEvent::create(
					'onMailBeforeNotificationPush',
					array('subject' => new \stdClass, 'data' => $data, 'type' => $type)
				)
			);

			/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $model */
			$model = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Subscription', 'Site', array('ignore_request' => true));
			$subscribers = $model->getSubscribers(
				$data->object_id,
				$data->object_group,
				$data->lang,
				$type
			);

			if (count($subscribers))
			{
				$userEvents = array(
					'comment-new', 'comment-reply', 'comment-update', 'comment-published', 'comment-unpublished', 'comment-delete'
				);

				if (in_array($type, $userEvents))
				{
					// Get an email list of moderators to exclude from the mailing list by users.
					$moderEmails = $model->getSubscribers(
						$data->object_id,
						$data->object_group,
						$data->lang,
						'report'
					);

					// Exclude user emails from the list if they are present in the list of moderators.
					$subscribers = array_diff_key($subscribers, $moderEmails);
				}

				foreach ($subscribers as $subscriber)
				{
					// Blocked(not banned) users cannot receive notifications.
					if ($subscriber->block == 1)
					{
						continue;
					}

					/** @var \Joomla\Component\Jcomments\Administrator\Table\MailqueueTable $table */
					$table = $app->bootComponent('com_jcomments')->getMVCFactory()
						->createTable('Mailqueue', 'Administrator');

					$table->name     = $subscriber->name;
					$table->email    = $subscriber->email;
					$table->subject  = self::getMessageSubject($data);
					$table->body     = self::getMessageBody($data, $subscriber);
					$table->priority = self::getMessagePriority($type);
					$table->created  = Factory::getDate()->toSql();

					// Always send report notifications.
					if ($type == 'report')
					{
						// The user cannot submit a report about his own comment.
						if ($data->email <> $email)
						{
							$result = $table->store();

							if (!$result)
							{
								Log::add($table->getError() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
							}
						}
					}
					else
					{
						if (!$table->store())
						{
							Log::add($table->getError() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
						}
					}
				}

				$sendResult = self::send(self::getMessagePriority($type));

				$dispatcher->dispatch(
					'onMailAfterNotificationPush',
					AbstractEvent::create(
						'onMailAfterNotificationPush',
						array('subject' => new \stdClass, 'data' => $data, 'type' => $type, 'sendResult' => $sendResult)
					)
				);
			}
			else
			{
				Log::add('No subscribed users to send notification! ' . __METHOD__, Log::INFO, 'com_jcomments');

				return false;
			}
		}
		else
		{
			Log::add('Comment object cannot be empty on notification push! ' . __METHOD__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Sends notifications from the mail queue to recipients
	 *
	 * @param   integer  $priority  Message priority
	 * @param   integer  $limit     The number of messages to be sent
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function send(int $priority, int $limit = 10): bool
	{
		$app         = Factory::getApplication();
		$senderEmail = $app->get('mailfrom');
		$senderName  = $app->get('fromname');

		if (!empty($senderEmail) && !empty($senderName))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__jcomments_mailq'))
				->order($db->quoteName('priority') . ' DESC');

			try
			{
				$db->setQuery($query, 0, $limit);
				$items = $db->loadColumn();
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

				return false;
			}

			if (!empty($items))
			{
				if (self::lock($items, $priority) === false)
				{
					Log::add('Cannot set lock state to Mailqueue table for current session. ' . __METHOD__, Log::ERROR, 'com_jcomments');

					return false;
				}

				PluginHelper::importPlugin('jcomments');

				/** @var \Joomla\CMS\Session\Session $session */
				$session = $app->getSession();
				$dispatcher = $app->getDispatcher();

				foreach ($items as $item)
				{
					/** @var \Joomla\Component\Jcomments\Administrator\Table\MailqueueTable $table */
					$table = $app->bootComponent('com_jcomments')->getMVCFactory()->createTable('Mailqueue', 'Administrator');

					if ($table->load(array('id' => $item, 'priority' => $priority)))
					{
						if (empty($table->session_id) || $table->session_id == $session->getId())
						{
							$dispatcher->dispatch(
								'onMailBeforeSend',
								AbstractEvent::create(
									'onMailBeforeSend',
									array('subject' => new \stdClass, 'data' => $table)
								)
							);

							$result = self::sendMail($senderEmail, $senderName, $table->email, $table->subject, $table->body);

							if ($result)
							{
								$table->delete();

								$dispatcher->dispatch(
									'onMailAfterSend',
									AbstractEvent::create(
										'onMailAfterSend',
										array('subject' => new \stdClass, 'data' => $table)
									)
								);
							}
							else
							{
								$table->attempts = $table->attempts + 1;
								$table->session_id = null;
								$table->store();
							}
						}
						else
						{
							Log::add(
								'Wrong session ID from mailqueue table for mailqueue item with ID ' . $item . ' in ' . __METHOD__,
								Log::WARNING,
								'com_jcomments'
							);
						}
					}
					else
					{
						Log::add(
							'Cannot load mailqueue item with ID ' . $item . ' in ' . __METHOD__,
							Log::WARNING,
							'com_jcomments'
						);
					}
				}
			}
			else
			{
				Log::add('Nothing to send. ' . __METHOD__, Log::WARNING, 'com_jcomments');

				return false;
			}
		}
		else
		{
			Log::add('Cannot send notification email due to empty sender name and sender email. ' . __METHOD__, Log::WARNING, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Purges all notifications from the mail queue
	 *
	 * @return  boolean
	 *
	 * @since   4.1
	 */
	public static function purge(): bool
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jcomments_mailq'));

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Set lock to mail queue items by current session
	 *
	 * @param   array    $keys      Array of IDs
	 * @param   integer  $priority  Message priority
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	private static function lock(array $keys, int $priority): bool
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->update($db->quoteName('#__jcomments_mailq'))
			->set($db->quoteName('session_id') . ' = ' . $db->quote(Factory::getApplication()->getSession()->getId()))
			->where($db->quoteName('session_id') . ' IS NULL')
			->where($db->quoteName('priority') . ' = :priority')
			->whereIn($db->quoteName('id'), $keys)
			->bind(':priority', $priority, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Prepares data for notification
	 *
	 * @param   object  $data  An object of notification data - comment object
	 * @param   string  $type  Type of notification
	 *
	 * @return  object
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	private static function prepareData(object $data, string $type)
	{
		if (ObjectHelper::isEmpty($data))
		{
			$objectInfo         = ObjectHelper::getObjectInfo($data->object_id, $data->object_group, $data->lang);
			$data->object_link  = $objectInfo->object_link;
			$data->object_title = $objectInfo->object_title;
		}

		$params                  = ComponentHelper::getParams('com_jcomments');
		$data->notification_type = $type;
		$data->object_link       = JcommentsContentHelper::getAbsLink($data->object_link);
		$data->author            = JcommentsContentHelper::getCommentAuthorName($data);
		$data->title             = JcommentsText::censor($data->title);
		$data->comment           = JcommentsText::censor($data->comment);
		$data->email             = PunycodeHelper::emailToPunycode($data->email);

		// Convert bbcodes back to HTML.
		if ($params->get('editor_format') == 'bbcode')
		{
			$bbcode = JcommentsFactory::getBBCode();
			$data->comment = $bbcode->replace($data->comment);

			if ($params->get('enable_custom_bbcode'))
			{
				$data->comment = $bbcode->replaceCustom($data->comment, true);
			}
		}

		// Remove extra spaces
		$data->comment = trim(preg_replace('/(\s){4,}/iu', '\\1', $data->comment));

		return $data;
	}

	/**
	 * Returns priority of the message
	 *
	 * @param   string  $type  Type of notification
	 *
	 * @return  integer
	 *
	 * @since   4.1
	 */
	private static function getMessagePriority(string $type): int
	{
		switch ($type)
		{
			case 'comment-admin-new':
			case 'comment-admin-update':
			case 'comment-admin-published':
			case 'comment-admin-unpublished':
				$priority = 10;
				break;

			case 'report':
				$priority = 5;
				break;

			case 'comment-new':
			case 'comment-reply':
			case 'comment-update':
			case 'comment-published':
			case 'comment-unpublished':
			case 'comment-delete':
			default:
				$priority = 0;
				break;
		}

		return $priority;
	}

	/**
	 * Returns message subject
	 *
	 * @param   object  $data  An object of notification data
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	private static function getMessageSubject(object $data): string
	{
		// Limit the length of the article title so as not to get into spam. We hope.
		$objectTitle = HTMLHelper::_('string.truncate', $data->object_title, 60, true, false);

		switch ($data->notification_type)
		{
			case 'report':
				$subject = Text::sprintf('REPORT_NOTIFICATION_SUBJECT', $data->author);
				break;

			case 'comment-delete':
			case 'comment-admin-delete':
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_DELETED', $objectTitle);
				break;

			case 'comment-new':
			case 'comment-admin-new':
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_NEW', $objectTitle);
				break;

			case 'comment-published':
			case 'comment-unpublished':
			case 'comment-admin-published':
			case 'comment-admin-unpublished':
				$txt = $data->notification_type == 'comment-published' || $data->notification_type == 'comment-admin-published'
					? 'NOTIFICATION_SUBJECT_STATE_1' : 'NOTIFICATION_SUBJECT_STATE_0';
				$subject = Text::sprintf($txt, $objectTitle);
				break;

			case 'comment-reply':
			case 'comment-update':
			case 'comment-admin-update':
			default:
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_UPDATED', $objectTitle);
				break;
		}

		return strip_tags($subject);
	}

	/**
	 * Returns message body
	 *
	 * @param   object  $data        An object of notification data
	 * @param   object  $subscriber  An object with information about subscriber
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	private static function getMessageBody(object $data, object $subscriber): string
	{
		$config = ComponentHelper::getParams('com_jcomments');
		$layout = $config->get('mail_style') == 'html' ? 'email-html' : 'email-plain';

		switch ($data->notification_type)
		{
			case 'comment-admin-new':
			case 'comment-admin-update':
			case 'comment-admin-published':
			case 'comment-admin-unpublished':
				$layoutData = array('data' => $data, 'hash' => $subscriber->hash, 'isAdmin' => true, 'report' => false, 'config' => $config);
				break;

			case 'report':
				$layoutData = array('data' => $data, 'hash' => $subscriber->hash, 'isAdmin' => true, 'report' => true, 'config' => $config);
				break;

			case 'comment-new':
			case 'comment-reply':
			case 'comment-update':
			case 'comment-published':
			case 'comment-unpublished':
			case 'comment-delete':
			default:
				$layoutData = array('data' => $data, 'hash' => $subscriber->hash, 'isAdmin' => false, 'report' => false, 'config' => $config);
				break;
		}

		// Load layout file only from frontend layouts folder and support layout overrides.
		return LayoutHelper::render($layout, $layoutData, null, array('client' => 'site'));
	}

	/**
	 * Function to send an email
	 *
	 * @param   string   $from       From email address
	 * @param   string   $fromName   From name
	 * @param   mixed    $recipient  Recipient email address(es)
	 * @param   string   $subject    Email subject
	 * @param   string   $body       Message body
	 *
	 * @return  boolean  True on success
	 *
	 * @since   4.1
	 */
	private static function sendMail(string $from, string $fromName, $recipient, string $subject, string $body): bool
	{
		$mailStyle = ComponentHelper::getParams('com_jcomments')->get('mail_style');
		$isHtml = ($mailStyle == 'html');

		try
		{
			/** @var \Joomla\CMS\Mail\Mail $mailer */
			$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

			$mailer->setSender(array($from, $fromName))
				->addRecipient($recipient)
				->setSubject($subject)
				->setBody($body)
				->isHtml($isHtml);

			// 'Error in Mail API' isn't an error. See https://github.com/joomla/joomla-cms/issues/25703#issuecomment-515047963
			$result = $mailer->Send();
		}
		catch (MailDisabledException | phpMailerException $e)
		{
			try
			{
				Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::WARNING, 'com_jcomments');
			}
			catch (\RuntimeException $exception)
			{
				Factory::getApplication()->enqueueMessage(Text::_($exception->errorMessage()), 'warning');
			}

			return false;
		}

		return $result;
	}
}

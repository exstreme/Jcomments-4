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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Log\Log;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

/**
 * JComments Notification Helper
 *
 * @since  4.0
 */
class NotificationHelper
{
	/**
	 * Pushes the email notification to the mail queue
	 *
	 * @param   object  $data  An object of notification data
	 * @param   string  $type  Type of notification
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public static function push($data, string $type = 'comment-new'): bool
	{
		if (isset($data->id))
		{
			$app = Factory::getApplication();

			/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $model */
			$model = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Subscription', 'Site', array('ignore_request' => true));
			$subscribers = $model->getSubscribers(
				$data->object_id,
				$data->object_group,
				$data->lang,
				$type
			);

			if (count(get_object_vars($subscribers)))
			{
				// Load frontend language file if this method called from backend.
				if ($app->isClient('administrator'))
				{
					$lang = $app->getLanguage();
					$lang->load('com_jcomments', JPATH_SITE, $data->lang);
				}

				$email = $app->getIdentity()->get('email');
				$data  = self::prepareData($data, $type);

				/** @var \Joomla\Component\Jcomments\Administrator\Table\MailqueueTable $table */
				$table = $app->bootComponent('com_jcomments')->getMVCFactory()->createTable('Mailqueue', 'Administrator');

				foreach ($subscribers as $subscriber)
				{
					// Do not send notifications if admin or user email is the same with email from comment object
					// and if user isn't subsribed to this article comments.
					if (($data->email <> $subscriber->email) && ($email <> $subscriber->email))
					{
						if ($data->userid == 0 || $data->userid <> $subscriber->userid)
						{
							$table->name     = $subscriber->name;
							$table->email    = $subscriber->email;
							$table->subject  = self::getMessageSubject($data);
							$table->body     = self::getMessageBody($data, $subscriber);
							$table->priority = self::getMessagePriority($type);
							$table->created  = Factory::getDate()->toSql();

							if (!$table->store())
							{
								Log::add($table->getError(), Log::ERROR, 'com_jcomments');
							}
						}
					}
				}

				self::send();
			}
			else
			{
				Log::add('No subscribed users to send notification!', Log::INFO, 'com_jcomments');

				return false;
			}
		}
		else
		{
			Log::add('Comment object cannot be empty on notification push!', Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Sends notifications from the mail queue to recipients
	 *
	 * @param   integer  $limit   The number of messages to be sent
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public static function send(int $limit = 10): bool
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
				$items = $db->loadObjectList('id');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

				return false;
			}

			if (!empty($items))
			{
				if (self::lock(array_keys($items)) === false)
				{
					Log::add('Cannot set lock state to Mailqueue table for current session.', Log::ERROR, 'com_jcomments');

					return false;
				}

				/** @var \Joomla\Component\Jcomments\Administrator\Table\MailqueueTable $table */
				$table = $app->bootComponent('com_jcomments')->getMVCFactory()->createTable('Mailqueue', 'Administrator');

				foreach ($items as $item)
				{
					if ($table->load($item->id))
					{
						if (empty($table->session_id) || $table->session_id == $app->getSession()->getId())
						{
							$result = self::sendMail($senderEmail, $senderName, $table->email, $table->subject, $table->body);

							if ($result)
							{
								$table->delete();
							}
							else
							{
								$table->attempts   = $table->attempts + 1;
								$table->session_id = null;
								$table->store();
							}
						}
						else
						{
							Log::add(
								'Wrong session ID from mailqueue table for mailqueue item with ID ' . $item->id . ' in NotificationHelper::send().',
								Log::WARNING,
								'com_jcomments'
							);
						}
					}
					else
					{
						Log::add(
							'Cannot load mailqueue item with ID ' . $item->id . ' in NotificationHelper::send().',
							Log::WARNING,
							'com_jcomments'
						);
					}
				}
			}
		}
		else
		{
			Log::add('Cannot send notification email due to empty sender name and sender email.', Log::WARNING, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Purges all notifications from the mail queue
	 *
	 * @return  boolean
	 *
	 * @since   3.0
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
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Set lock to mail queue items by current session
	 *
	 * @param   array  $keys  Array of IDs
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	private static function lock(array $keys): bool
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->update($db->quoteName('#__jcomments_mailq'))
			->set($db->quoteName('session_id') . ' = ' . $db->quote(Factory::getApplication()->getSession()->getId()))
			->where($db->quoteName('session_id') . ' IS NULL')
			->whereIn($db->quoteName('id'), $keys);

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		return true;
	}

	/**
	 * Prepares data for notification
	 *
	 * @param   object  $data  An object of notification data
	 * @param   string  $type  Type of notification
	 *
	 * @return  object
	 *
	 * @since   3.0
	 */
	private static function prepareData(object $data, string $type)
	{
		/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
		$model = Factory::getApplication()->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Objects', 'Site', array('ignore_request' => true));

		$object = $model->getItem($data->object_id, $data->object_group, $data->lang);
		$config = ComponentHelper::getParams('com_jcomments');

		$data->notification_type = $type;
		$data->object_title      = $object->title;
		$data->object_link       = JcommentsFactory::getAbsLink($object->link);
		$data->author            = ContentHelper::getCommentAuthorName($data);
		$data->title             = JcommentsText::censor($data->title);
		$data->comment           = JcommentsText::censor($data->comment);
		$data->comment           = JcommentsFactory::getBBCode()->replace($data->comment);

		if ($config->get('enable_custom_bbcode'))
		{
			$data->comment = JcommentsFactory::getCustomBBCode()->replace($data->comment, true);
		}

		$data->comment = trim(preg_replace('/(\s){2,}/iu', '\\1', $data->comment));

		return $data;
	}

	/**
	 * Returns priority of the message
	 *
	 * @param   string  $type  Type of notification
	 *
	 * @return  integer
	 *
	 * @since   3.0
	 */
	private static function getMessagePriority(string $type): int
	{
		switch ($type)
		{
			case 'moderate-new':
			case 'moderate-update':
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
	 * @since   3.0
	 */
	private static function getMessageSubject(object $data): string
	{
		// Limit the length of the article title so as not to get into spam. I hope.
		$objectTitle = HTMLHelper::_('string.truncate', $data->object_title, 60, true, false);

		switch ($data->notification_type)
		{
			case 'report':
				$subject = Text::sprintf('REPORT_NOTIFICATION_SUBJECT', $data->author);
				break;

			case 'comment-delete':
			case 'moderate-delete':
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_DELETED', $objectTitle);
				break;

			case 'comment-new':
			case 'moderate-new':
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_NEW', $objectTitle);
				break;

			case 'comment-published':
			case 'comment-unpublished':
				$txt = $data->notification_type == 'comment-published' ? 'NOTIFICATION_SUBJECT_STATE_1' : 'NOTIFICATION_SUBJECT_STATE_0';
				$subject = Text::sprintf($txt, $objectTitle);
				break;

			case 'comment-reply':
			case 'comment-update':
			case 'moderate-update':
			default:
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_UPDATED', $objectTitle);
				break;
		}

		return $subject;
	}

	/**
	 * Returns message body
	 *
	 * @param   object  $data        An object of notification data
	 * @param   object  $subscriber  An object with information about subscriber
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	private static function getMessageBody(object $data, object $subscriber): string
	{
		$config    = ComponentHelper::getParams('com_jcomments');
		$mailStyle = ComponentHelper::getParams('com_mails')->get('mail_style');
		$layout    = ($mailStyle == 'html') ? 'email-html' : 'email-plain';

		switch ($data->notification_type)
		{
			case 'moderate-new':
			case 'moderate-update':
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
	 * @since   3.0
	 */
	private static function sendMail(string $from, string $fromName, $recipient, string $subject, string $body): bool
	{
		$mailStyle = ComponentHelper::getParams('com_mails')->get('mail_style');
		$isHtml = ($mailStyle == 'html');

		try
		{
			$mailer = Factory::getMailer()
				->setSender(array($from, $fromName))
				->addRecipient($recipient)
				->setSubject($subject)
				->setBody($body)
				->isHtml($isHtml);

			$result = $mailer->Send();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		return $result;
	}
}

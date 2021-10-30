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
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;

/**
 * JComments Notification Helper
 *
 * @since  3.0
 */
class JCommentsNotification
{
	/**
	 * Pushes the email notification to the mail queue
	 *
	 * @param   array   $data  An associative array of notification data
	 * @param   string  $type  Type of notification
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function push($data, $type = 'comment-new')
	{
		if (isset($data['comment']))
		{
			$subscribers = self::getSubscribers(
				$data['comment']->object_id,
				$data['comment']->object_group,
				$data['comment']->lang,
				$type
			);

			if (count($subscribers))
			{
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jcomments/tables');

				$email = Factory::getApplication()->getIdentity()->get('email');
				$data = self::prepareData($data, $type);

				foreach ($subscribers as $subscriber)
				{
					if (($data['comment']->email <> $subscriber->email) && ($email <> $subscriber->email))
					{
						if ($data['comment']->userid == 0 || $data['comment']->userid <> $subscriber->userid)
						{
							$table           = Table::getInstance('Mailqueue', 'JCommentsTable');
							$table->name     = $subscriber->name;
							$table->email    = $subscriber->email;
							$table->subject  = self::getMessageSubject($data);
							$table->body     = self::getMessageBody($data, $subscriber);
							$table->priority = self::getMessagePriority($type);
							$table->created  = Factory::getDate()->toSql();
							$table->store();
						}
					}
				}

				self::send();
			}
		}
	}

	/**
	 * Sends notifications from the mail queue to recipients
	 *
	 * @param   integer  $limit  The number of messages to be sent
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function send($limit = 10)
	{
		$app         = Factory::getApplication();
		$senderEmail = $app->get('mailfrom');
		$senderName  = $app->get('fromname');

		if (!empty($senderEmail) && !empty($senderName))
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__jcomments_mailq'))
				->order($db->quoteName('priority') . ' DESC');

			$db->setQuery($query, 0, $limit);
			$items = $db->loadObjectList('id');

			if (!empty($items))
			{
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jcomments/tables');

				self::lock(array_keys($items));

				foreach ($items as $item)
				{
					$table = Table::getInstance('Mailqueue', 'JCommentsTable');

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
					}
				}
			}
		}
	}

	/**
	 * Purges all notifications from the mail queue
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function purge()
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__jcomments_mailq'));

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Set lock to mail queue items by current session
	 *
	 * @param   array  $keys  Array of IDs
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	private static function lock($keys)
	{
		if (is_array($keys))
		{
			$app = Factory::getApplication();

			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->update($db->quoteName('#__jcomments_mailq'))
				->set($db->quoteName('session_id') . ' = ' . $db->Quote($app->getSession()->getId()))
				->where($db->quoteName('session_id') . ' IS NULL')
				->where($db->quoteName('id') . ' IN (' . implode(',', $keys) . ')');

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Prepares data for notification
	 *
	 * @param   array   $data  An associative array of notification data
	 * @param   string  $type  Type of notification
	 *
	 * @return  array
	 *
	 * @since   3.0
	 */
	private static function prepareData($data, $type)
	{
		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		$object = JCommentsObject::getObjectInfo($data['comment']->object_id, $data['comment']->object_group, $data['comment']->lang);
		$config = ComponentHelper::getParams('com_jcomments');

		$data['notification-type'] = $type;
		$data['object_title']      = $object->title;
		$data['object_link']       = JCommentsFactory::getAbsLink($object->link);

		$data['comment']->author  = JCommentsContent::getCommentAuthorName($data['comment']);
		$data['comment']->title   = JCommentsText::censor($data['comment']->title);
		$data['comment']->comment = JCommentsText::censor($data['comment']->comment);
		$data['comment']->comment = JCommentsFactory::getBBCode()->replace($data['comment']->comment);

		if ($config->get('enable_custom_bbcode'))
		{
			$data['comment']->comment = JCommentsFactory::getCustomBBCode()->replace($data['comment']->comment, true);
		}

		$data['comment']->comment = trim(preg_replace('/(\s){2,}/iu', '\\1', $data['comment']->comment));

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
	private static function getMessagePriority($type)
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
			default:
				$priority = 0;
				break;
		}

		return $priority;
	}

	/**
	 * Returns message subject
	 *
	 * @param   array  $data  An associative array of notification data
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	private static function getMessageSubject($data)
	{
		switch ($data['notification-type'])
		{
			case 'report':
				$subject = Text::sprintf('REPORT_NOTIFICATION_SUBJECT', $data['comment']->author);
				break;

			case 'comment-new':
			case 'moderate-new':
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_NEW', $data['object_title']);
				break;

			case 'comment-reply':
			case 'comment-update':
			case 'moderate-update':
			default:
				$subject = Text::sprintf('NOTIFICATION_SUBJECT_UPDATED', $data['object_title']);
				break;
		}

		return $subject;
	}

	/**
	 * Returns message body
	 *
	 * @param   array   $data        An associative array of notification data
	 * @param   object  $subscriber  An object with information about subscriber
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	private static function getMessageBody($data, $subscriber)
	{
		switch ($data['notification-type'])
		{
			case 'moderate-new':
			case 'moderate-update':
				$templateName = 'tpl_email_administrator';
				break;

			case 'report':
				$templateName = 'tpl_email_report';
				break;

			case 'comment-new':
			case 'comment-reply':
			case 'comment-update':
			default:
				$templateName = 'tpl_email';
				break;
		}

		$tmpl = JCommentsFactory::getTemplate($data['comment']->object_id, $data['comment']->object_group);

		if ($tmpl->load($templateName))
		{
			$config = ComponentHelper::getParams('com_jcomments');

			foreach ($data as $key => $value)
			{
				if (is_scalar($value))
				{
					$tmpl->addVar($templateName, $key, $value);
				}
				else
				{
					$tmpl->addObject($templateName, $key, $value);
				}
			}

			$tmpl->addVar($templateName, 'notification-unsubscribe-link', self::getUnsubscribeLink($subscriber->hash));
			$tmpl->addVar($templateName, 'comment-object_title', $data['object_title']);
			$tmpl->addVar($templateName, 'comment-object_link', $data['object_link']);

			if ($data['notification-type'] == 'report'
				|| $data['notification-type'] == 'moderate-new'
				|| $data['notification-type'] == 'moderate-update'
			)
			{
				$tmpl->addVar($templateName, 'quick-moderation', (int) $config->get('enable_quick_moderation'));
				$tmpl->addVar($templateName, 'enable-blacklist', (int) $config->get('enable_blacklist'));
			}

			// Backward compatibility only
			$tmpl->addVar($templateName, 'hash', $subscriber->hash);
			$tmpl->addVar($templateName, 'comment-isnew', ($data['notification-type'] == 'new') ? 1 : 0);

			return $tmpl->renderTemplate($templateName);
		}

		return false;
	}

	/**
	 * Returns link for canceling the user's subscription for notifications about new comments
	 *
	 * @param   string  $hash  Unique subscriber's hash value
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public static function getUnsubscribeLink($hash)
	{
		$link = 'index.php?option=com_jcomments&task=unsubscribe&hash=' . $hash . '&format=raw';
		$app  = Factory::getApplication();

		if ($app->isClient('administrator'))
		{
			$link = trim(str_replace('/administrator', '', Uri::root()), '/') . '/' . $link;
		}
		else
		{
			$liveSite = trim(str_replace(Uri::root(true), '', str_replace('/administrator', '', Uri::root())), '/');
			$link     = $liveSite . Route::_($link);
		}

		return $link;
	}

	/**
	 * Returns list of subscribers for given object and subscription type
	 *
	 * @param   int     $objectID     Object ID
	 * @param   string  $objectGroup  Object group, e.g. com_content
	 * @param   string  $lang         The language tag, e.g. en-GB
	 * @param   string  $type         The subscription type
	 *
	 * @return  array
	 *
	 * @since   3.0
	 */
	private static function getSubscribers($objectID, $objectGroup, $lang, $type)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$subscribers = array();

		switch ($type)
		{
			case 'moderate-new':
			case 'moderate-update':
			case 'report':
				$config = ComponentHelper::getParams('com_jcomments');

				if ($config->get('notification_email') != '')
				{
					$emails = explode(',', $config->get('notification_email'));

					$query = $db->getQuery(true)
						->select('*')
						->from($db->quoteName('#__users'))
						->where($db->quoteName('email') . " IN ('" . implode("', '", $emails) . "')");

					$db->setQuery($query);
					$users = $db->loadObjectList('email');

					foreach ($emails as $email)
					{
						$email = trim($email);

						$subscriber        = new stdClass;
						$subscriber->id    = isset($users[$email]) ? $users[$email]->id : 0;
						$subscriber->name  = isset($users[$email]) ? $users[$email]->name : '';
						$subscriber->email = $email;
						$subscriber->hash  = md5($email);

						$subscribers[] = $subscriber;
					}
				}
				break;

			case 'comment-new':
			case 'comment-reply':
			case 'comment-update':
			default:
				$query = $db->getQuery(true)
					->select('DISTINCTROW js.name, js.email, js.hash, js.userid')
					->from($db->quoteName('#__jcomments_subscriptions', 'js'))
					->join(
						'INNER',
						$db->quoteName('#__jcomments_objects', 'jo'),
						' js.object_id = jo.object_id AND js.object_group = jo.object_group'
					)
					->where($db->quoteName('js.object_group') . ' = ' . $db->quote($objectGroup))
					->where($db->quoteName('js.object_id') . ' = ' . (int) $objectID)
					->where($db->quoteName('js.published') . ' = 1');

				if (JCommentsFactory::getLanguageFilter())
				{
					$query->where($db->quoteName('js.lang') . ' = ' . $db->quote($lang))
						->where($db->quoteName('jo.lang') . ' = ' . $db->quote($lang));
				}

				try
				{
					$db->setQuery($query);
					$subscribers = $db->loadObjectList();
				}
				catch (RuntimeException $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
				}

				break;
		}

		return is_array($subscribers) ? $subscribers : array();
	}

	/**
	 * Function to send an email
	 *
	 * @param   string  $from       From email address
	 * @param   string  $fromName   From name
	 * @param   mixed   $recipient  Recipient email address(es)
	 * @param   string  $subject    Email subject
	 * @param   string  $body       Message body
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.0
	 */
	private static function sendMail($from, $fromName, $recipient, $subject, $body)
	{
		try
		{
			$mailer = Factory::getMailer()
				->setSender(array($from, $fromName))
				->addRecipient($recipient)
				->setSubject($subject)
				->setBody($body)
				->isHtml(true);

			$result = $mailer->Send();
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		return $result;
	}
}

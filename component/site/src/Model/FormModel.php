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

namespace Joomla\Component\Jcomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Jcomments\Site\Helper\NotificationHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\String\StringHelper;
use Joomla\Utilities\IpHelper;

/**
 * Form Model
 *
 * @since  4.1
 */
class FormModel extends \Joomla\Component\Jcomments\Administrator\Model\CommentModel
{
	/**
	 * Method to get form.
	 * This is the place where we must set field values.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if ($app->input->getWord('layout', '') == 'report')
		{
			$form = $this->loadForm('com_jcomments.report', 'report', array('control' => 'jform', 'load_data' => false));

			if (empty($form))
			{
				return false;
			}

			if ($user->get('guest'))
			{
				$form->setValue('name', '', Text::_('REPORT_GUEST'));
			}

			$form->setValue('comment_id', '', $app->input->getInt('comment_id', 0));
		}
		else
		{
			$form = $this->loadForm('com_jcomments.comment', 'comment', array('control' => 'jform', 'load_data' => $loadData));

			if (empty($form))
			{
				return false;
			}

			// Check if user can subscribe to comments update, set checkbox state and set email field to required.
			if (JcommentsFactory::getACL()->canSubscribe())
			{
				/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionsModel $subscriptionsModel */
				$subscriptionsModel = $app->bootComponent('com_jcomments')->getMVCFactory()
					->createModel('Subscriptions', 'Site', array('ignore_request' => true));

				$subscribed = $subscriptionsModel->isSubscribed(
					$app->input->getInt('object_id', 0),
					$app->input->getCmd('object_group', 'com_content'),
					$user->get('id')
				);

				if ($subscribed)
				{
					$form->setValue('subscribe', '', 1);
					$form->setFieldAttribute('subscribe', 'checked', 'checked');
				}
			}
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  object  The default data is an empty array.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if ($app->input->getInt('quote') == 1)
		{
			$this->setState('comment.id', 0);
			$data = $this->getQuotedItem();
		}
		elseif ($app->input->getInt('reply') == 1)
		{
			// Override empty comment_id
			$this->setState('comment.id', 0);
			$data = $this->getItem();
			$data->parent = $app->input->getInt('comment_id');

			if (!$user->get('guest'))
			{
				$data->userid = $user->get('id');
				$data->email = $user->get('email');
			}
		}
		else
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_jcomments.comment', $data);

		return $data;
	}

	/**
	 * Method to get form data.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  object|boolean  Data object on success, false on failure.
	 *
	 * @throws  \Exception
	 *
	 * @since   4.1
	 */
	public function getItem($pk = null)
	{
		$pk          = (!empty($pk)) ? $pk : (int) $this->getState('comment.id');
		$app         = Factory::getApplication();
		$db          = $this->getDatabase();
		$user        = $app->getIdentity();
		$acl         = JcommentsFactory::getAcl();
		$lang        = $app->getLanguage();
		$params      = ComponentHelper::getParams('com_jcomments');
		$objectGroup = $this->getState('object_group');
		$objectId    = $this->getState('object_id');
		$userId      = $user->get('id');

		$query = $db->getQuery(true)
			->select('*')
			->select($db->quoteName('id', 'comment_id'))
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $pk, ParameterType::INTEGER);

		if ($objectId !== null)
		{
			$query->where($db->quoteName('object_id') . ' = :oid')
				->bind(':oid', $objectId, ParameterType::INTEGER);
		}

		if ($objectGroup !== null)
		{
			$query->where($db->quoteName('object_group') . ' = :ogroup')
				->bind(':ogroup', $objectGroup);
		}

		try
		{
			$db->setQuery($query);
			$result = $db->loadObject();

			if ($pk > 0 && !$result)
			{
				$this->setError(Text::_('ERROR_NOT_FOUND'));

				return false;
			}

			if (!$pk)
			{
				// Create default table object to avoid errors about undefined variables.
				/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $result */
				$result = parent::getItem($pk);
				$result->comment_id = $result->id;
			}
			else
			{
				$result->title = StringHelper::trim($result->title);

				if ($params->get('editor_format') == 'bbcode')
				{
					$result->comment = JcommentsText::br2nl(htmlspecialchars_decode($result->comment));
				}
			}

			if (!$user->get('guest'))
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('terms_of_use'))
					->from($db->quoteName('#__jcomments_users'))
					->where($db->quoteName('id') . ' = :uid')
					->bind(':uid', $userId, ParameterType::INTEGER);

				$db->setQuery($query);
				$result->terms_of_use = (int) $db->loadResult();
			}
			else
			{
				$result->terms_of_use = 0;
			}

			if ($acl->showPolicy())
			{
				$result->policy = JcommentsText::getMessagesBasedOnLanguage(
					$params->get('messages_fields'),
					'message_policy_post', $lang->getTag()
				);
			}
			else
			{
				$result->policy = '';
			}

			$terms = JcommentsText::getMessagesBasedOnLanguage(
				$params->get('messages_fields'),
				'message_terms_of_use', $lang->getTag()
			);
			$result->terms        = !empty($terms) ? $terms : Text::_('FORM_ACCEPT_TERMS_OF_USE');
			$result->object_id    = $objectId;
			$result->object_group = $objectGroup;
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $result;
	}

	/**
	 * Method to get form data for quoted comment.
	 *
	 * @param   integer|null  $pk  The id of the primary key.
	 *
	 * @return  object  Data object
	 *
	 * @throws  \Exception
	 *
	 * @since   4.1
	 */
	public function getQuotedItem($pk = null)
	{
		$app           = Factory::getApplication();
		$params        = ComponentHelper::getParams('com_jcomments');
		$user          = $app->getIdentity();
		$commentId     = (!empty($pk)) ? $pk : $app->input->getInt('comment_id');
		$result        = (object) array();
		$parentComment = $this->getItem($commentId);

		if (empty($parentComment))
		{
			return $result;
		}

		/** @var \Joomla\Component\Jcomments\Administrator\Table\CommentTable $parentComment */
		$result->object_id = $parentComment->object_id;
		$result->object_group = $parentComment->object_group;

		if (!$user->get('guest'))
		{
			$result->userid = $user->get('id');
			$result->email = $user->get('email');
		}

		$result->comment = $parentComment->comment;

		if ($params->get('editor_format') == 'bbcode')
		{
			$bbcode = JcommentsFactory::getBbcode();

			if (!$params->get('enable_nested_quotes'))
			{
				$result->comment = $bbcode->removeQuotes($result->comment);
			}

			if ($params->get('enable_custom_bbcode'))
			{
				$result->comment = $bbcode->filterCustom($result->comment, true);
			}

			if ($user->get('id') == 0)
			{
				$result->comment = $bbcode->removeHidden($result->comment);
			}
		}
		else
		{
			// TODO Not implemented for html
		}

		if ($result->comment != '')
		{
			if (JcommentsFactory::getAcl()->enableAutocensor())
			{
				$result->comment = JcommentsText::censor($result->comment);
			}

			$authorName = \Joomla\Component\Jcomments\Site\Helper\ContentHelper::getCommentAuthorName($parentComment);

			if ($params->get('editor_format') == 'bbcode')
			{
				$result->comment = '[quote name="' . $authorName . ';' . $commentId . '"]' . $result->comment . '[/quote]' . "\n";
			}
			else
			{
				$result->comment = '<blockquote class="blockquote" data-quoted="' . $commentId . '">
					<span class="cite d-block">' . Text::_('COMMENT_TEXT_QUOTE') . '<span class="author fst-italic fw-semibold">' . $authorName . '</span></span>' . $result->comment . '
				</blockquote><br>';
			}

			// Quoted original comment
			$result->quoted = $parentComment->comment;
		}

		return $result;
	}

	/**
	 * Get the return URL.
	 *
	 * @return  string  The return URL.
	 *
	 * @since   4.1
	 */
	public function getReturnPage(): string
	{
		return base64_encode($this->getState('return_page', ''));
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data filtered and validated.
	 *
	 * @return  array|boolean  Array with comment state on success, false otherwise.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function save($data)
	{
		$app    = Factory::getApplication();
		$db     = $this->getDatabase();
		$acl    = JcommentsFactory::getAcl();
		$user   = $app->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');
		$lang   = $app->getLanguage()->getTag();
		$id     = $app->input->post->getInt('comment_id');

		// Check comment length after filtering
		if ($data['comment'] == '')
		{
			$this->setError(Text::_('ERROR_EMPTY_COMMENT'));

			return false;
		}

		// Set user real name to 'Guest' for all languages.
		$data['name']      = empty($data['name']) ? 'Guest' : $data['name'];
		$data['parent']    = $data['parent'] ?? 0;
		$data['lang']      = $app->getLanguage()->getTag();
		$data['ip']        = IpHelper::getIp();
		$data['userid']    = $user->get('id') ? $user->get('id') : 0;
		$data['date']      = Factory::getDate()->toSql();
		$data['published'] = (int) $user->authorise('comment.autopublish', $this->option);

		if ($params->get('editor_format') == 'bbcode')
		{
			$user                 = Factory::getApplication()->getIdentity();
			$bbcode               = JcommentsFactory::getBbcode();
			$commentWithoutQuotes = $bbcode->removeQuotes($data['comment']);

			if ($commentWithoutQuotes == '')
			{
				$this->setError(Text::_('ERROR_NOTHING_EXCEPT_QUOTES'));

				return false;
			}
			elseif (($params->get('comment_minlength') != 0)
				&& (!$user->authorise('comment.length_check', 'com_jcomments'))
				&& (StringHelper::strlen($commentWithoutQuotes) < $params->get('comment_minlength')))
			{
				$this->setError(Text::_('ERROR_YOUR_COMMENT_IS_TOO_SHORT'));

				return false;
			}
		}

		// Check for duplicate comment
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('comment') . ' = ' . $db->quote($data['comment']))
			->where($db->quoteName('ip') . ' = :ip')
			->where($db->quoteName('name') . ' = ' . $db->quote($data['name']))
			->where($db->quoteName('userid') . ' = :uid')
			->where($db->quoteName('object_id') . ' = :oid')
			->where($db->quoteName('parent') . ' = :parent')
			->where($db->quoteName('object_group') . ' = ' . $db->quote($data['object_group']))
			->bind(':ip', $data['ip'])
			->bind(':uid', $data['userid'], ParameterType::INTEGER)
			->bind(':oid', $data['object_id'], ParameterType::INTEGER)
			->bind(':parent', $data['parent'], ParameterType::INTEGER);

		if (Multilanguage::isEnabled())
		{
			$_lang = $db->quote($lang);
			$query->where($db->quoteName('lang') . ' = :lang')
				->bind(':lang', $_lang);
		}

		$db->setQuery($query);

		try
		{
			$found = $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$found = 0;
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
		}

		if ($found != 0)
		{
			$this->setError(Text::_('ERROR_DUPLICATE_COMMENT'));

			return false;
		}

		PluginHelper::importPlugin('jcomments');

		$dispatcher = $this->getDispatcher();
		$eventResults = $dispatcher->dispatch(
			'onJcommentsCommentBeforeAdd',
			AbstractEvent::create(
				'onJcommentsCommentBeforeAdd',
				array(
					'eventClass' => 'Joomla\Component\Jcomments\Site\Event\FormEvent',
					'subject' => $this, 'data' => $data
				)
			)
		)->getArgument('result', array());

		if (array_key_exists(0, $eventResults))
		{
			// If plugin method return 'false' this indicate about error.
			if (in_array(false, $eventResults[0], true))
			{
				$this->setError(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));

				return false;
			}
		}

		if (!empty($id))
		{
			$merged = false;
			$mergeTime = (int) $params->get('merge_time', 0);

			// Merge comments from same author
			if ($user->get('id') && $mergeTime > 0)
			{
				// Load previous comment for same object and group
				$commentModel = $this->getMVCFactory()->createModel('Comment', 'Site', array('ignore_request' => true));
				$prevComment = $commentModel->getLastComment($data['object_id'], $data['object_group'], $data['parent']);

				if ($prevComment != null)
				{
					// If previous comment from same author and it currently not edited
					// by any user - we'll update comment, else - insert new record to database
					/*if (($prevComment->userid == $comment->userid)
						&& ($prevComment->parent == $comment->parent)
						&& (!$acl->isLocked($prevComment)))
					{
						$newText  = $prevComment->comment . '<br /><br />' . $comment->comment;
						$timeDiff = strtotime($comment->date) - strtotime($prevComment->date);

						if ($timeDiff < $mergeTime)
						{
							$maxlength = (int) $config->get('comment_maxlength');
							$needcheck = !$user->authorise('comment.length_check', 'com_jcomments');

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

					unset($prevComment);*/
				}
			}
		}

		//$result = parent::save($data);
		$result = true;

		if ($result)
		{
			if ($acl->canSubscribe())
			{
				$factory = $app->bootComponent('com_jcomments')->getMVCFactory();

				/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionsModel $subscriptionsModel */
				$subscriptionsModel = $factory->createModel('Subscriptions', 'Site', array('ignore_request' => true));

				/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $subscriptionModel */
				$subscriptionModel = $factory->createModel('Subscription', 'Site', array('ignore_request' => true));

				$subscribed = $subscriptionsModel->isSubscribed(
					$data['object_id'],
					$data['object_group'],
					$user->get('id'),
					$data['email']
				);

				if (!$subscribed && $data['subscribe'] == 1)
				{
					// Subscribe
					$subsribeResult = $subscriptionModel->subscribe(
						$data['object_id'],
						$data['object_group'],
						$user->get('id'),
						$data['name'],
						$data['email']
					);

					if ($subsribeResult)
					{
						$app->enqueueMessage(Text::_('SUCCESSFULLY_SUBSCRIBED'), 'success');
					}
				}
				elseif ($subscribed && $data['subscribe'] == 0)
				{
					// Unsubscribe
					$subsribeResult = $subscriptionModel->unsubscribe(
						$data['object_id'],
						$data['object_group'],
						$user->get('id')
					);

					if ($subsribeResult)
					{
						$app->enqueueMessage(Text::_('SUCCESSFULLY_UNSUBSCRIBED'), 'success');
					}
				}
			}
		}
		else
		{
			$this->setError(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));

			return false;
		}

		// Store/update information about commented object
		$objectModel = $this->getMVCFactory()->createModel('Objects', 'Site', ['ignore_request' => true]);
		$objectModel->save($data['object_id'], ObjectHelper::getObjectInfo($data['object_id'], $data['object_group'], $lang));

		$dispatcher->dispatch(
			'onJCommentsCommentAfterAdd',
			AbstractEvent::create(
				'onJCommentsCommentAfterAdd',
				array(
					'eventClass' => 'Joomla\Component\Jcomments\Site\Event\FormEvent',
					'subject' => $this, 'data' => $data
				)
			)
		);

		echo '<pre>';
		print_r($data);

		//return array('state' => 0);
	}

	/**
	 * Method to save the report form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function saveReport($data): bool
	{
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$db     = $this->getDatabase();
		$acl    = JcommentsFactory::getAcl();
		$uid    = $user->get('id');
		$config = ComponentHelper::getParams('com_jcomments');
		$ip     = IpHelper::getIp();

		if ($app->input->getWord('layout', '') == 'report')
		{
			// Check if comment not reported by user or IP
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__jcomments_reports'))
				->where($db->quoteName('commentid') . ' = :id')
				->bind(':id', $data['comment_id'], ParameterType::INTEGER);

			if ($uid)
			{
				$query->where($db->quoteName('userid') . ' = :uid')
					->bind(':uid', $uid, ParameterType::INTEGER);
			}
			else
			{
				$query->where($db->quoteName('userid') . ' = 0')
					->where($db->quoteName('ip') . ' = :ip')
					->bind(':ip', $ip);
			}

			$db->setQuery($query);
			$reported = $db->loadResult();

			// Allready reported
			if ($reported)
			{
				$this->setError(Text::_('ERROR_YOU_CAN_NOT_REPORT_THE_SAME_COMMENT_MORE_THAN_ONCE'));

				return false;
			}

			$maxReportsPerComment      = $config->get('reports_per_comment', 1);
			$maxReportsBeforeUnpublish = $config->get('reports_before_unpublish', 0);

			// Clean query cache and check if already reported comment by ID
			$query->clear()
				->select('COUNT(*)')
				->from($db->quoteName('#__jcomments_reports'))
				->where($db->quoteName('commentid') . ' = :id')
				->bind(':id', $data['comment_id'], ParameterType::INTEGER);

			$db->setQuery($query);
			$reported = $db->loadResult();

			if ($reported < $maxReportsPerComment || $maxReportsPerComment == 0)
			{
				$this->setState('object_id');
				$this->setState('object_group');

				$item = $this->getItem($data['comment_id']);

				if (!$item)
				{
					$this->setError(Text::_('JLIB_APPLICATION_ERROR_RECORD'));

					return false;
				}

				// Check only access rights
				if (!$acl->canReport())
				{
					$this->setError(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));

					return false;
				}

				// Check if comment is published.
				if ($item->published == 0)
				{
					$this->setError(Text::_('ERROR_NOT_FOUND'));

					return false;
				}

				if ($uid)
				{
					$name = $user->get('name');
				}
				else
				{
					$name = $app->input->getString('name');

					if (empty($name))
					{
						$name = Text::_('REPORT_GUEST');
					}
				}

				PluginHelper::importPlugin('jcomments');

				/** @var \Joomla\Component\Jcomments\Administrator\Table\ReportTable $report */
				$report            = $this->getTable('Report');
				$report->commentid = $item->id;
				$report->date      = Factory::getDate()->toSql();
				$report->userid    = $uid;
				$report->ip        = $db->escape($ip);
				$report->name      = $db->escape($name);
				$report->reason    = $config->get('report_reason_required') ? $db->escape($data['reason']) : '';

				$dispatcher = $this->getDispatcher();
				$eventResult = $dispatcher->dispatch(
					'onJCommentsCommentBeforeReport',
					AbstractEvent::create(
						'onJCommentsCommentBeforeReport',
						array('subject' => new \stdClass, 'comment' => $item, 'report' => $report)
					)
				);

				if (!$eventResult->getArgument('abort', false))
				{
					if ($report->store())
					{
						$dispatcher->dispatch(
							'onJCommentsCommentAfterReport',
							AbstractEvent::create(
								'onJCommentsCommentAfterReport',
								array('subject' => new \stdClass, 'comment' => $item, 'report' => $report)
							)
						);

						if ($config->get('enable_notification') && in_array(2, $config->get('notification_type')))
						{
							$notify                = clone $item;
							$notify->report_name   = $name;
							$notify->report_reason = $data['reason'];

							if ($user->get('guest'))
							{
								$notify->email = $data['email'];
							}

							NotificationHelper::enqueueMessage($notify, 'report');
						}

						// Unpublish comment if reports count is enough
						if ($maxReportsBeforeUnpublish > 0 && $reported >= $maxReportsBeforeUnpublish)
						{
							try
							{
								$query = $db->getQuery(true)
									->update($db->quoteName('#__jcomments'))
									->set($db->quoteName('published') . ' = 0')
									->where($db->quoteName('id') . ' = :id')
									->bind(':id', $data['comment_id'], ParameterType::INTEGER);

								$db->setQuery($query);

								$db->execute();
							}
							catch (\RuntimeException $e)
							{
								Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

								return false;
							}
						}
					}
				}
			}
			else
			{
				$this->setError(Text::_('ERROR_COMMENT_ALREADY_REPORTED'));

				return false;
			}
		}

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->input->getInt('comment_id');
		$this->setState('comment.id', $pk);

		$return = $app->input->get('return', '', 'base64');
		$this->setState('return_page', base64_decode($return));

		$this->setState('object_group', $app->input->getCmd('object_group', $app->input->getCmd('option', 'com_content')));

		if ($app->input->getInt('object_id', 0) > 0)
		{
			$this->setState('object_id', $app->input->getInt('object_id', 0));
		}

		// Load the parameters.
		$params = ComponentHelper::getParams('com_jcomments');
		$this->setState('params', $params);

		$this->setState('layout', $app->input->getString('layout'));
	}

	/**
	 * Method to allow preprocess the data.
	 *
	 * @param   string  $context  The context identifier.
	 * @param   mixed   $data     The data to be processed. It gets altered directly.
	 * @param   string  $group    The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected function preprocessData($context, &$data, $group = 'content')
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if ($app->input->getInt('reply') == 1 || $app->input->getInt('quote') == 1 && $app->input->getInt('comment_id', 0) > 0)
		{
			$data->parent = $app->input->getInt('comment_id');
		}
		else
		{
			$data->name   = $data->userid ? $data->username : $data->name;
			$data->email  = !empty($data->email) ? $data->email : $user->get('email');
			$data->userid = !empty($data->userid) ? $data->userid : $user->get('id');
		}

		parent::preprocessData($context, $data, $group);
	}

	/**
	 * Allows preprocessing of the Form object.
	 *
	 * @param   Form    $form   The form object
	 * @param   object  $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$app = Factory::getApplication();

		if ($app->input->getWord('layout') == 'report')
		{
			$this->preprocessReportForm($form, $data, $group);
		}
		else
		{
			$this->preprocessCommentForm($form, $data, $group);
		}
	}

	/**
	 * Preprocessing of the Form object for comment form.
	 *
	 * @param   Form    $form   The form object
	 * @param   object  $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	private function preprocessCommentForm(Form $form, $data, $group = 'content')
	{
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');
		$acl    = JcommentsFactory::getACL();

		if ($user->authorise('comment.captcha', $this->option))
		{
			$form->removeField('comment_captcha');
		}

		$usernameMaxlength = $params->get('username_maxlength');

		$form->setFieldAttribute(
			'name',
			'maxlength',
			($usernameMaxlength <= 0 || $usernameMaxlength > 255) ? 255 : $usernameMaxlength
		);

		if ($params->get('author_name') != 0)
		{
			if ($user->get('guest') && $params->get('author_name') == 2)
			{
				$form->setFieldAttribute('name', 'required', 'true');
			}
			else
			{
				if (!empty($data->comment_id))
				{
					// Disable field for registered user while editing comment.
					$form->setFieldAttribute('name', 'disabled', 'true');
				}
			}
		}
		else
		{
			$form->removeField('name');
		}

		// Ugly checks if user can subscibe, we will always require email field, except for registered where predefined
		// value is set and field set to readonly.
		if ($user->get('guest') && $params->get('author_email') != 0)
		{
			if ($user->get('guest') && $params->get('author_email') == 2)
			{
				$form->setFieldAttribute('email', 'required', 'true');
			}

			// Do not change original email from comment while editing by guest
			if (isset($data->comment_id))
			{
				$form->setFieldAttribute('email', 'readonly', 'true');
			}
		}
		else
		{
			// Check if registered user can subscribe
			if ($acl->canSubscribe())
			{
				if (!$user->get('isRoot'))
				{
					$form->setFieldAttribute('email', 'required', 'true');
					$form->setFieldAttribute('email', 'readonly', 'true');
				}
			}
			else
			{
				if ($user->authorise('comment.subscribe', $this->option))
				{
					$form->setFieldAttribute('email', 'required', 'true');
				}
				else
				{
					if ($params->get('author_email') == 0)
					{
						$form->removeField('email');
					}
				}
			}
		}

		// Required for all
		if ($params->get('author_homepage') == 3
			|| ($params->get('author_homepage') == 4 && $user->get('guest'))
			|| ($params->get('author_homepage') == 2 && $user->get('guest'))
		)
		{
			if (!$user->get('isRoot'))
			{
				$form->setFieldAttribute('homepage', 'required', 'true');
			}
		}
		// Optional for guests, disabled for registered.
		elseif ($params->get('author_homepage') == 5)
		{
			if (!$user->get('guest'))
			{
				$form->removeField('homepage');
			}
		}
		// Required for guests, disabled for registered.
		elseif ($params->get('author_homepage') == 4 && !$user->get('guest') || $params->get('author_homepage') == 0)
		{
			$form->removeField('homepage');
		}

		if ($params->get('comment_title') == 3)
		{
			if (!$user->get('isRoot'))
			{
				$form->setFieldAttribute('title', 'required', 'true');
			}
		}
		elseif ($params->get('comment_title') == 0)
		{
			$form->removeField('title');
		}

		// Do not use JcommentsFactory::getACL()->canSubscribe() here!
		if (!$user->authorise('comment.subscribe', $this->option))
		{
			$form->removeField('subscribe');
		}
		else
		{
			$form->setFieldAttribute('email', 'required', 'true');
		}

		// It is False to show input
		if (!$user->authorise('comment.terms_of_use', $this->option))
		{
			$articleId = JcommentsText::getMessagesBasedOnLanguage(
				$params->get('messages_fields'),
				'message_terms_of_use_article',
				$app->getLanguage()->getTag()
			);

			if ($articleId > 0)
			{
				try
				{
					if (Associations::isEnabled())
					{
						$termsAssociated = Associations::getAssociations('com_content', '#__content', 'com_content.item', $articleId);
						$currentLang = $app->getLanguage()->getTag();

						if (isset($termsAssociated[$currentLang]))
						{
							$articleId = $termsAssociated[$currentLang]->id;
						}
					}

					$articleModel = (new \Joomla\Component\Content\Site\Model\ArticleModel)->getItem($articleId);

					if ($articleModel !== false)
					{
						$slug = $articleModel->alias ? ($articleId . ':' . $articleModel->alias) : $articleId;
						$articleLink = RouteHelper::getArticleRoute(
							$slug,
							$articleModel->catid,
							$articleModel->language
						);

						if (!empty($articleLink))
						{
							$articleLink = Route::_($articleLink . '&tmpl=component');
							$required    = $form->getFieldAttribute('terms_of_use', 'required') ? 'required' : '';
							$label       = $form->getFieldAttribute('terms_of_use', 'label');

							$form->setFieldAttribute(
								'terms_of_use',
								'label',
								'<a href="' . $articleLink . '" data-bs-toggle="modal" data-bs-target="#tosModal"'
								. ' class="' . $required . '">' . Text::_($label) . '</a>'
							);
							$form->setFieldAttribute('terms_of_use', 'data-url', $articleLink);
							$form->setFieldAttribute('terms_of_use', 'data-label', Text::_($label));
						}
					}
				}
				catch (\Exception $e)
				{
				}
			}
		}
		else
		{
			$form->removeField('terms_of_use');
		}

		// Disable some fields for registered users while editing existing records. Super user can change values of these fields.
		if (!empty($data->comment_id) && !$user->get('isRoot'))
		{
			$form->setFieldAttribute('name', 'disabled', 'true');
			$form->setFieldAttribute('email', 'disabled', 'true');
		}

		$form->setFieldAttribute(
			'comment',
			'maxlength',
			$user->authorise('comment.length_check', $this->option) ? 0 : $params->get('comment_maxlength')
		);

		if (!$acl->canPin)
		{
			$form->removeField('pinned');
		}
		else
		{
			// Check state only on edit comment and if not allready pinned
			if (!empty($data->comment_id))
			{
				// Check for allready pinned comments. Only 'max_pinned' pinned comments allowed per object.
				$totalPinned = $this->getTotalPinned($data->object_id, $data->object_group);
				$totalCommentsByObject = ObjectHelper::getTotalCommentsForObject($data->object_id, $data->object_group, 1, 0);

				// Allow pinning for a user with rights, even if pinning is prohibited.
				$maxPinned = $params->get('max_pinned') == 0 || $user->get('isRoot') ? 1 : $params->get('max_pinned');

				if ($totalPinned >= $maxPinned || $totalPinned >= ($totalCommentsByObject - 1))
				{
					if (!$user->get('isRoot'))
					{
						$form->removeField('pinned');
					}
				}
				else
				{
					if ($data->pinned == 1)
					{
						$form->setValue('pinned', '', 1);
						$form->setFieldAttribute('pinned', 'checked', 'checked');
					}
				}
			}
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Preprocessing of the Form object for report form.
	 *
	 * @param   Form    $form   The form object
	 * @param   object  $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	private function preprocessReportForm(Form $form, $data, $group = 'content')
	{
		$user = Factory::getApplication()->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');

		if ($user->authorise('comment.captcha', $this->option))
		{
			$form->removeField('report_captcha');
		}

		if ($params->get('report_reason_required') == 0)
		{
			$form->removeField('reason');
		}

		if (!$user->get('guest'))
		{
			$form->removeField('email');
			$form->removeField('name');
		}
		else
		{
			$form->setFieldAttribute('name', 'required', true);
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return  boolean|Table  A Table object
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function getTable($name = 'Comment', $prefix = 'Administrator', $options = array())
	{
		return parent::getTable($name, $prefix, $options);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form    $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     FormRule
	 * @see     InputFilter
	 * @since   1.6
	 */
	public function validate($form, $data, $group = null)
	{
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');

		if ($app->input->getWord('layout') == 'report')
		{
			return parent::validate($form, $data, $group);
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $commentModel */
		$commentModel = $this->getMVCFactory()->createModel('Comment', 'Site');

		// Validate 'name' field only for new comment. If variable is not set, check it later in parent method for empty value.
		if (empty($app->input->getInt('comment_id')) && $params->get('author_name') != 0 && isset($data['name']))
		{
			if ($commentModel->isRegisteredUsername($data['name']))
			{
				$this->setError(Text::_('ERROR_NAME_EXISTS'));

				return false;
			}
			elseif ($commentModel->checkIsForbiddenUsername($data['name']))
			{
				$this->setError(Text::_('ERROR_FORBIDDEN_NAME'));

				return false;
			}
			elseif (preg_match('/[\"\'\[\]\=\<\>\(\)\;]+/', $data['name']))
			{
				$this->setError(Text::_('ERROR_INVALID_NAME'));

				return false;
			}
			elseif (($params->get('username_maxlength') != 0)
				&& (StringHelper::strlen($data['name']) > $params->get('username_maxlength')))
			{
				$this->setError(Text::_('ERROR_TOO_LONG_USERNAME'));

				return false;
			}
		}

		if (empty($app->input->getInt('comment_id')) && $params->get('author_email') != 0 && isset($data['email']))
		{
			if (($params->get('author_email') != 0) && $commentModel->isRegisteredEmail($data['email']) == 1)
			{
				$this->setError(Text::_('ERROR_EMAIL_EXISTS'));

				return false;
			}
		}

		if (!$user->authorise('comment.terms_of_use', $this->option))
		{
			if ($form->getFieldAttribute('terms_of_use', 'required') && (int) $data['terms_of_use'] == 0)
			{
				$this->setError(Text::sprintf('ERROR_TERMS_OF_USE', $form->getFieldAttribute('terms_of_use', 'label')));

				return false;
			}
		}

		if (($params->get('comment_maxlength') != 0)
			&& (!$user->authorise('comment.length_check', $this->option))
			&& (StringHelper::strlen($data['comment']) > $params->get('comment_maxlength')))
		{
			$this->setError(Text::_('ERROR_YOUR_COMMENT_IS_TOO_LONG'));

			return false;
		}

		if (($params->get('comment_minlength') != 0)
			&& (!$user->authorise('comment.length_check', $this->option))
			&& (StringHelper::strlen($data['comment']) < $params->get('comment_minlength')))
		{
			$this->setError(Text::_('ERROR_YOUR_COMMENT_IS_TOO_SHORT'));

			return false;
		}

		if ((isset($data['subscribe']) && $data['subscribe'] == 1) && $data['email'] == '')
		{
			$this->setError(Text::_('ERROR_SUBSCRIPTION_EMAIL'));

			return false;
		}

		return parent::validate($form, $data, $group);
	}
}

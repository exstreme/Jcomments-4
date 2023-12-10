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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper;
use Joomla\Component\Jcomments\Site\Helper\NotificationHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\ParameterType;
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

		if ($app->input->getString('layout', '') == 'report')
		{
			$form = $this->loadForm('com_jcomments.report', 'report', array('control' => 'jform', 'load_data' => false));

			if ($user->get('guest'))
			{
				$form->setValue('name', '', Text::_('REPORT_GUEST'));
			}

			$form->setValue('comment_id', '', $app->input->getInt('comment_id', 0));

			if (empty($form))
			{
				return false;
			}
		}
		else
		{
			$form = parent::getForm($data, $loadData);

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
		if (Factory::getApplication()->input->getInt('quote') > 0)
		{
			return $this->getQuotedItem();
		}

		return $this->getItem();
	}

	/**
	 * Method to get form data.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  false|object  Data object on success, false on failure.
	 *
	 * @throws  \Exception
	 *
	 * @since   4.1
	 */
	public function getItem($pk = null)
	{
		$commentId   = (int) (!empty($pk)) ? $pk : $this->getState('comment.id');
		$app         = Factory::getApplication();
		$db          = $this->getDatabase();
		$user        = $app->getIdentity();
		$objectGroup = $this->getState('object_group');
		$objectId    = $this->getState('object_id');
		$userId      = $user->get('id');

		$query = $db->getQuery(true)
			->select('c.*')
			->select($db->quoteName('c.id', 'comment_id'))
			->from($db->quoteName('#__jcomments', 'c'))
			->where($db->quoteName('c.published') . ' = 1')
			->where($db->quoteName('c.id') . ' = :cid')
			->bind(':cid', $commentId, ParameterType::INTEGER);

		if ($objectId !== null)
		{
			$query->where($db->quoteName('c.object_id') . ' = :oid')
				->bind(':oid', $objectId, ParameterType::INTEGER);
		}

		if ($objectGroup !== null)
		{
			$query->where($db->quoteName('c.object_group') . ' = :ogroup')
				->bind(':ogroup', $objectGroup);
		}

		try
		{
			$db->setQuery($query);
			$result = $db->loadObject();

			if ($commentId > 0 && !$result)
			{
				$this->setError(Text::_('ERROR_NOT_FOUND'));

				return false;
			}

			if (!$commentId)
			{
				$result = (object) array();
			}
			else
			{
				$result->title = StringHelper::trim($result->title);
				$result->comment = JcommentsText::br2nl($result->comment);
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
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		return $result;
	}

	/**
	 * Method to get form data for quoted comment.
	 *
	 * @return  object  Data object
	 *
	 * @throws  \Exception
	 *
	 * @since   4.1
	 */
	public function getQuotedItem()
	{
		$app           = Factory::getApplication();
		$params        = ComponentHelper::getParams('com_jcomments');
		$user          = $app->getIdentity();
		$commentId     = $app->input->getInt('parent');
		$result        = (object) array();
		$parentComment = $this->getItem($commentId);

		if (empty($parentComment))
		{
			return $result;
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

			$authorName = ContentHelper::getCommentAuthorName($parentComment);

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
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function save($data)
	{
		return parent::save($data);
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

		if ($app->input->getString('layout', '') == 'report')
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
					$this->setError(Text::_('ERROR_YOU_HAVE_NO_RIGHTS_TO_REPORT'));

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
				$report->reason    = $db->escape($data['reason']);

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

							NotificationHelper::push($notify, 'report');
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
		$this->setState('object_id', $app->input->getInt('object_id', 0));

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
		$data->name = $data->userid ? $data->username : $data->name;

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
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$params = ComponentHelper::getParams('com_jcomments');
		$acl    = JcommentsFactory::getACL();

		if ($user->authorise('comment.captcha', 'com_jcomments'))
		{
			$form->removeField('comment_captcha');
			$form->removeField('report_captcha');
		}

		// Skip some fields preprocess for report form and run only default preprocess.
		if ($app->input->getString('layout', '') == 'report')
		{
			if ($params->get('report_reason_required') == 0)
			{
				$form->removeField('reason');
			}

			if (!$user->get('guest'))
			{
				$form->removeField('email');
			}

			$form->setFieldAttribute('name', 'required', (bool) $user->get('guest'));

			parent::preprocessForm($form, $data, $group);

			return;
		}

		if ($app->input->getInt('quote') == 1)
		{
			$form->setValue('parent', '', $app->input->getInt('parent'));
		}

		$usernameMaxlength = $params->get('username_maxlength');

		$form->setFieldAttribute(
			'name',
			'maxlength',
			($usernameMaxlength <= 0 || $usernameMaxlength > 255) ? 255 : $usernameMaxlength
		);

		if ($user->get('guest') && $params->get('author_name') != 0)
		{
			if ($user->get('guest') && $params->get('author_name') == 2)
			{
				$form->setFieldAttribute('name', 'required', true);
			}

			if (!empty($data) && property_exists($data, 'id'))
			{
				$form->setFieldAttribute('name', 'disabled', true);
			}
		}
		else
		{
			$form->removeField('name');
		}

		// Ugly checks if user can subscibe we will always require email field, except for registered where predefined
		// value is set and field set to readonly.
		if ($user->get('guest') && $params->get('author_email') != 0)
		{
			if ($user->get('guest') && $params->get('author_email') == 2)
			{
				$form->setFieldAttribute('email', 'required', true);
			}

			// Do not change original email from comment while editing by guest
			if (!empty($data) && property_exists($data, 'id'))
			{
				$form->setFieldAttribute('email', 'readonly', true);
			}
		}
		else
		{
			// Check if registered user can subscribe
			if ($acl->canSubscribe())
			{
				$form->setValue('email', '', $user->get('email'));
				$form->setFieldAttribute('email', 'required', true);
				$form->setFieldAttribute('email', 'readonly', true);
			}
			else
			{
				if ($user->authorise('comment.subscribe', 'com_jcomments'))
				{
					$form->setFieldAttribute('email', 'required', true);
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
			$form->setFieldAttribute('homepage', 'required', true);
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
			$form->setFieldAttribute('title', 'required', true);
		}
		elseif ($params->get('comment_title') == 0)
		{
			$form->removeField('title');
		}

		// Do not use JcommentsFactory::getACL()->canSubscribe() here!
		if (!$user->authorise('comment.subscribe', 'com_jcomments'))
		{
			$form->removeField('subscribe');
		}
		else
		{
			$form->setFieldAttribute('email', 'required', true);
		}

		if ($acl->showTermsOfUse())
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
							$required = $form->getFieldAttribute('terms_of_use', 'required') ? 'required' : '';
							$label = $form->getFieldAttribute('terms_of_use', 'label');

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
		if (!empty($data->id) && !$user->get('isRoot'))
		{
			$form->setFieldAttribute('name', 'disabled', true);
			$form->setFieldAttribute('email', 'disabled', true);
		}

		$form->setFieldAttribute(
			'comment',
			'maxlength',
			$user->authorise('comment.length_check', 'com_jcomments') ? 0 : $params->get('comment_maxlength')
		);

		$form->setValue('userid', '', $user->get('id'));

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
}

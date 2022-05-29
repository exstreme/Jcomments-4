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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Form Model
 *
 * @since  4.0.0
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
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$app  = Factory::getApplication();
		$form = parent::getForm($data, $loadData);

		if (JcommentsFactory::getACL()->canSubscribe())
		{
			$user = $app->getIdentity();
			$subscriptionModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Subscription', 'Site', array('ignore_request' => true));

			/** @see \Joomla\Component\Jcomments\Site\Model\SubscriptionModel::isSubscribed() */
			$subscribed = $subscriptionModel->isSubscribed(
				$app->input->getInt('id', 0),
				$app->input->getCmd('option', 'com_content'),
				$user->get('id')
			);

			if ($subscribed)
			{
				$form->setValue('subscribe', '', 1);
				$form->setFieldAttribute('subscribe', 'checked', 'checked');
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
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		return $this->getItem();
	}

	/**
	 * Method to get total rows.
	 *
	 * @param   integer  $objectID     Item ID.
	 * @param   string   $objectGroup  Option.
	 *
	 * @return  integer
	 *
	 * @since   4.0.0
	 */
	public function getTotalCommentsForObject($objectID = null, $objectGroup = 'com_content'): int
	{
		$db    = $this->getDbo();
		$input = Factory::getApplication()->input;
		$total = 0;
		$objectID = ($objectID === null) ? $input->getInt('object_id', 0) : $input->getInt('id', 0);

		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('object_id') . ' = :oid')
			->where($db->quoteName('object_group') . ' = :ogroup')
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('deleted') . ' = 0')
			->bind(':oid', $objectID, ParameterType::INTEGER)
			->bind(':ogroup', $objectGroup);

		try
		{
			$db->setQuery($query);
			$total = $db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');
		}

		return $total;
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
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{
		$itemID = (int) (!empty($pk)) ? $pk : $this->getState('comment.id');
		$app    = Factory::getApplication();
		$db     = $this->getDbo();
		$user   = $app->getIdentity();

		if ($itemID === null)
		{
			//return (object) array();
		}

		$query = $db->getQuery(true)
			->select('c.*')
			->from($db->quoteName('#__jcomments', 'c'))
			->where($db->quoteName('c.id') . ' = :cid')
			->bind(':cid', $itemID, ParameterType::INTEGER);

		// Deny edit any comment
		if (!$user->authorise('comment.edit', 'com_jcomments')
			&& !$user->authorise('comment.edit.own', 'com_jcomments')
			&& !$user->authorise('comment.edit.own.articles', 'com_jcomments'))
		{
			return false;
		}
		else
		{
			// TODO Сделано не полностью
			if ((!$user->authorise('comment.edit.own', 'com_jcomments')
				&& !$user->authorise('comment.edit.own.articles', 'com_jcomments'))
				|| ($user->authorise('comment.edit.own', 'com_jcomments')
				&& !$user->authorise('comment.edit.own.articles', 'com_jcomments')))
			{
				$query->where($db->qn('userid') . ' = ' . $user->get('id'));
				$query->where($db->qn('published') . ' = 1');
			}
			elseif (!$user->authorise('comment.edit.own', 'com_jcomments')
				&& $user->authorise('comment.edit.own.articles', 'com_jcomments'))
			{
				$query->where($db->qn('object_id') . ' = ' . $app->input->getInt('id'));
				$query->where($db->qn('published') . ' = 1');
			}
			else
			{
				//return false;
			}
		}
echo $query;
		try
		{
			$db->setQuery($query);
			$result = $db->loadObject();
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		return $result;
	}

	/**
	 * Get the return URL.
	 *
	 * @return  string  The return URL.
	 *
	 * @since   4.0.0
	 */
	public function getReturnPage()
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
	 * @since   4.0.0
	 *
	 * @throws  Exception
	 */
	public function save($data)
	{
		// Associations are not edited in frontend ATM so we have to inherit them
		if (Associations::isEnabled() && !empty($data['id'])
			&& $associations = Associations::getAssociations('com_contact', '#__contact_details', 'com_contact.item', $data['id']))
		{
			foreach ($associations as $tag => $associated)
			{
				$associations[$tag] = (int) $associated->id;
			}

			$data['associations'] = $associations;
		}

		return parent::save($data);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  \Exception
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->input->getInt('comment_id');
		$this->setState('comment.id', $pk);

		$return = $app->input->get('return', null, 'base64');

		if (empty($return) || !Uri::isInternal(base64_decode($return)))
		{
			$return = Uri::getInstance();
		}

		$this->setState('return_page', $return);

		// Load the parameters.
		$params = $app->getParams('com_jcomments');
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
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   object  $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   3.7.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$app    = Factory::getApplication();
		$params = $this->getState()->get('params');
		$user   = $app->getIdentity();
		$usernameMaxlength = $params->get('username_maxlength');

		$form->setFieldAttribute('name', 'maxlength', ($usernameMaxlength <= 0 || $usernameMaxlength > 255) ? 255 : $usernameMaxlength);

		if ($user->get('guest') && $params->get('author_name') != 0)
		{
			if ($user->get('guest') && $params->get('author_name') == 2)
			{
				$form->setFieldAttribute('name', 'required', true);
			}

			if (!empty($data) && $data->id)
			{
				$form->setFieldAttribute('name', 'disabled', true);
			}
		}
		else
		{
			$form->removeField('name');
		}

		if ($user->get('guest') && $params->get('author_email') != 0)
		{
			if ($user->get('guest') && $params->get('author_email') == 2)
			{
				$form->setFieldAttribute('email', 'required', true);
			}

			if (!empty($data) && $data->id)
			{
				$form->setFieldAttribute('email', 'readonly', true);
			}
		}
		else
		{
			$form->removeField('email');
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

		if (!JcommentsFactory::getACL()->canSubscribe())
		{
			$form->removeField('subscribe');
		}

		if (JcommentsFactory::getACL()->showTermsOfUse())
		{
			$tosLabelText = JcommentsText::getMessagesBasedOnLanguage(
				$params->get('messages_fields'),
				'message_terms_of_use',
				$app->getLanguage()->getTag()
			);

			if (!empty($tosLabelText))
			{
				$form->setFieldAttribute('terms_of_use', 'label', $tosLabelText);
			}
		}
		else
		{
			$form->removeField('terms_of_use');
		}

		if ($user->authorise('comment.captcha', 'com_jcomments'))
		{
			$form->removeField('captcha');
		}

		// Disable some fields for registered users while editing existing records. Super user can change values of these fields.
		if (!empty($data->id) && !$user->get('isRoot'))
		{
			$form->setFieldAttribute('name', 'disabled', true);
			$form->setFieldAttribute('email', 'disabled', true);
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
	 * @since   4.0.0

	 * @throws  \Exception
	 */
	public function getTable($name = 'Comment', $prefix = 'Administrator', $options = array())
	{
		return parent::getTable($name, $prefix, $options);
	}
}

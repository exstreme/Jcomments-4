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

namespace Joomla\Component\Jcomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

class BlacklistModel extends AdminModel
{
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_jcomments.blacklist', 'blacklist', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		// Don't allow to change the created_by user if not allowed to access com_users.
		if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_users'))
		{
			$form->setFieldAttribute('created_by', 'filter', 'unset');
		}

		$form->setValue('created_by', '', Factory::getApplication()->getIdentity()->get('id'));

		return $form;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jcomments.edit.blacklist.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
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
		// Filter and validate the form data.
		$data   = $form->filter($data);
		$return = $form->validate($data, $group);

		// Check for an error.
		if ($return instanceof \Exception)
		{
			Factory::getApplication()->enqueueMessage($return->getMessage(), 'error');

			return false;
		}

		if (empty($data['ip']) && empty($data['userid']))
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf(
					'A_BLACKLIST_REQUIRED_IP_UID',
					Text::_($form->getFieldAttribute('ip', 'label')),
					Text::_($form->getFieldAttribute('userid', 'label'))
				),
				'error'
			);

			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				if ($message instanceof \Exception)
				{
					$message = $message->getMessage();
				}

				Factory::getApplication()->enqueueMessage($message, 'error');
			}

			return false;
		}

		return $data;
	}
}

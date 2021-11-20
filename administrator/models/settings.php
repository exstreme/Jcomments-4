<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Utilities\ArrayHelper;

class JCommentsModelSettings extends FormModel
{
	/**
	 * Method to get a form object.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A Form object on success, false on failure
	 *
	 * @since  3.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		Form::addFieldPath(JPATH_ROOT . '/components/com_jcomments/models/fields/');

		// Load config.xml from root component folder.
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_jcomments/');
		$form = $this->loadForm('com_jcomments.config', 'config', array('control' => 'jform', 'load_data' => $loadData), false, '/config');

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 *
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		$params = ComponentHelper::getComponent('com_jcomments')->getParams();

		// Check for data in the session.
		$temp = Factory::getApplication()->getUserState('com_jcomments.edit.settings.data');

		// Merge in the session data.
		if (!empty($temp))
		{
			// $temp can sometimes be an object, and we need it to be an array
			if (is_object($temp))
			{
				$temp = ArrayHelper::fromObject($temp);
			}

			$params = array_merge($temp, $params);
		}

		return $params;
	}

	/**
	 * Method to save the configuration data.
	 *
	 * @param   array  $data  Config data.
	 *
	 * @return  boolean   True on success, false on failure.
	 *
	 * @since  3.0
	 */
	public function save($data)
	{
		$app   = Factory::getApplication();
		$db    = $this->getDbo();

		// Adjust some values
		if (isset($data['forbidden_names']))
		{
			$data['forbidden_names'] = preg_replace("#[\n|\r]+#", ',', $data['forbidden_names']);
			$data['forbidden_names'] = preg_replace("#,+#", ',', $data['forbidden_names']);
		}

		if (isset($data['badwords']))
		{
			$data['badwords'] = preg_replace('#[\s|\,]+#i', "\n", $data['badwords']);
			$data['badwords'] = preg_replace('#[\n|\r]+#i', "\n", $data['badwords']);

			$data['badwords'] = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $data['badwords']));
			$data['badwords'] = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $data['badwords']));
		}

		if (!isset($data['comment_minlength']))
		{
			$data['comment_minlength'] = 0;
		}

		if (!isset($data['comment_maxlength']))
		{
			$data['comment_maxlength'] = 0;
		}

		if ($data['comment_minlength'] > $data['comment_maxlength'])
		{
			$data['comment_minlength'] = 0;
		}

		$params = json_encode($data);

		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('params') . " = '" . $db->escape($params) . "'")
			->where(array($db->quoteName('type') . " = 'component'", $db->quoteName('element') . " = 'com_jcomments'"));

		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			$app->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		// Clean the component cache.
		$this->cleanCache('com_jcomments');

		return true;
	}

	/**
	 * Restore settings from file into DB
	 *
	 * @param   object  $data  Configuration
	 *
	 * @return boolean
	 *
	 * @since  3.0
	 */
	public function restoreConfig($data)
	{
		$db     = $this->getDbo();
		$params = json_encode($data->params);
		$access = json_encode($data->access);
		$query  = $db->getQuery(true);

		try
		{
			$query->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . " = '" . $db->escape($params) . "'")
				->where(array($db->quoteName('type') . " = 'component'", $db->quoteName('element') . " = 'com_jcomments'"));

			$db->setQuery($query);
			$db->execute();

			// Store access rules to assets table
			$query = $db->getQuery(true);

			$query->clear()
				->update($db->quoteName('#__assets'))
				->set($db->quoteName('rules') . ' = ' . $db->quote($access))
				->where($db->quoteName('name') . " = 'com_jcomments'")
				->where($db->quoteName('level') . ' = 1')
				->where($db->quoteName('parent_id') . ' = 1');

			$db->setQuery($query);
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		return true;
	}
}

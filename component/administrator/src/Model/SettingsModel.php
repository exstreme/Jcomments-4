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
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;

class SettingsModel extends FormModel
{
	/**
	 * Restore settings from file into DB
	 *
	 * @param   object  $data  Configuration
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function restoreConfig($data): bool
	{
		$db     = $this->getDatabase();
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
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		return true;
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  false|Form  A Form object on success, false on failure
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_jcomments/');
		$form = $this->loadForm('com_jcomments.config', 'config', array('control' => 'form', 'load_data' => $loadData), false, '/config');

		if (empty($form))
		{
			return false;
		}

		return $form;
	}
}

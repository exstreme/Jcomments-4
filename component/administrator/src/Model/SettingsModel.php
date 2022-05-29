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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SettingsModel extends BaseDatabaseModel
{
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
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		return true;
	}
}

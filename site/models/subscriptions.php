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

use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\DatabaseModelInterface;

/**
 * Subscriptions class
 *
 * @since  4.0
 */
abstract class JcommentsModelSubscriptions extends BaseDatabaseModel implements DatabaseModelInterface
{
	/**
	 * Delete all subscriptions or filtered by user ID.
	 *
	 * @param   integer  $objectID     The object identifier
	 * @param   string   $objectGroup  The object group (component name)
	 * @param   integer  $userID       The registered user identifier
	 * @param   string   $lang         Content language
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   4.0
	 */
	public function deleteSubscriptions($objectID, $objectGroup, $userID = null, $lang = null)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->delete()
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('object_id') . ' = ' . (int) $objectID)
			->where($db->quoteName('object_group') . ' = ' . $db->quote($objectGroup));

		if (!empty($userID))
		{
			$query->where($db->quoteName('userid') . ' = ' . (int) $userID);
		}

		if (!empty($lang))
		{
			$query->where($db->quoteName('lang') . ' = ' . $db->quote($lang));
		}

		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_jcomments');

			return false;
		}

		$this->cleanCache('com_jcomments_subscriptions_' . strtolower($objectGroup));

		return true;
	}
}

<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * User plugin for updating user info in comments
 *
 * @since 1.5
 */
class plgUserJComments extends CMSPlugin
{
	function onUserAfterSave($user, $isNew, $success, $msg)
	{
		if ($success && !$isNew) {
			$id = (int)$user['id'];

			if ($id > 0 && trim($user['username']) != '' && trim($user['email']) != '') {
				$db = Factory::getContainer()->get('DatabaseDriver');

				// update name, username and email in comments
				$query = $db->getQuery(true);
				$query->update($db->quoteName('#__jcomments'));
				$query->set($db->quoteName('name') . ' = ' . $db->Quote($user['name']));
				$query->set($db->quoteName('username') . ' = ' . $db->Quote($user['username']));
				$query->set($db->quoteName('email') . ' = ' . $db->Quote($user['email']));
				$query->where($db->quoteName('userid') . ' = ' . $id);
				$db->setQuery($query);
				$db->execute();

				// update email in subscriptions
				$query = $db->getQuery(true);
				$query->update($db->quoteName('#__jcomments_subscriptions'));
				$query->set($db->quoteName('email') . ' = ' . $db->Quote($user['email']));
				$query->where($db->quoteName('userid') . ' = ' . $id);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	function onUserAfterDelete($user, $success, $msg)
	{
		if ($success) {
			$id = (int)$user['id'];

			if ($id > 0) {
				$db = Factory::getContainer()->get('DatabaseDriver');

				$query = $db->getQuery(true);
				$query->update($db->quoteName('#__jcomments'));
				$query->set($db->quoteName('userid') . ' = 0');
				$query->where($db->quoteName('userid') . ' = ' . $id);
				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__jcomments_reports'));
				$query->where($db->quoteName('userid') . ' = ' . $id);
				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__jcomments_subscriptions'));
				$query->where($db->quoteName('userid') . ' = ' . $id);
				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__jcomments_votes'));
				$query->where($db->quoteName('userid') . ' = ' . $id);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}
}
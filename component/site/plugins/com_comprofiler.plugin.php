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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_comprofiler extends JcommentsPlugin
{
	/**
	 * Get object information for com_content
	 *
	 * @param   integer  $id        Article ID
	 * @param   mixed    $language  Language tag
	 *
	 * @return  object
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function getObjectInfo($id, $language = null)
	{
		/** @var Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select($db->quoteName(array('u.id', 'u.name', 'u.username', 'cb.firstname', 'cb.lastname', 'cb.middlename')))
			->from($db->quoteName('#__users', 'u'))
			->innerJoin(
				$db->quoteName('#__comprofiler', 'cb'),
				$db->quoteName('cb.user_id') . ' = ' . $db->quoteName('u.id')
			)
			->where($db->quoteName('u.id') . ' = :uid')
			->bind(':uid', $id, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query);
			$user = $db->loadObject();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');
		}

		$info = new JCommentsObjectInfo;

		if (!empty($user))
		{
			$itemId = self::getItemid('com_comprofiler', 'index.php?option=com_comprofiler');
			$itemId = $itemId > 0 ? '&Itemid=' . $itemId : '';

			$title = trim(preg_replace('#\s+#', ' ', $user->lastname . ' ' . $user->firstname . ' ' . $user->middlename));

			if ($title == '')
			{
				$title = $user->name;
			}

			$info->title = $title;
			$info->userid = $user->id;
			$info->link = Route::_('index.php?option=com_comprofiler&task=userProfile&user=' . $user->id . $itemId);
		}

		return $info;
	}
}

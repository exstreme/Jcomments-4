<?php
/**
 * JComments plugin for CommunityBuilder profiles support
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_comprofiler extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('u.id', 'u.name', 'u.username', 'cb.firstname', 'cb.lastname', 'cb.middlename')))
			->from($db->quoteName('#__users', 'u'))
			->join('INNER', $db->quoteName('#__comprofiler', 'cb'), 'cb.user_id = u.id')
			->where($db->quoteName('u.id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$user = $db->loadObject();

		$info = new JcommentsObjectInfo;

		if (!empty($user))
		{
			$itemid = self::getItemid('com_comprofiler', 'index.php?option=com_comprofiler');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$title = trim(preg_replace('#\s+#', ' ', $user->lastname . ' ' . $user->firstname . ' ' . $user->middlename));

			if ($title == '')
			{
				$title = $user->name;
			}

			$info->title  = $title;
			$info->userid = $user->id;
			$info->link   = Route::_('index.php?option=com_comprofiler&task=userProfile&user=' . $user->id . $itemid);
		}

		return $info;
	}
}

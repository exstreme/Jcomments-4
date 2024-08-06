<?php
/**
 * JComments plugin for RSEvents!PRO (https://www.rsjoomla.com/joomla-extensions/joomla-events.html) objects support
 *
 * @version       4.0
 * @package       JComments
 * @author        Webcanyon (www.webcanyon.be) - based on work of Oregon
 * @copyright (C) 2014 by Webcanyon
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

class jc_com_rseventspro extends JCommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'name', 'owner')))
			->from($db->quoteName('#__rseventspro_events'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		$info = new JCommentsObjectInfo;

		if (!empty($row))
		{
			$itemid = self::getItemid('com_rseventspro');
			$itemid = $itemid > 0 ? '&Itemid=' . $itemid : '';

			$info->title  = $row->name;
			$info->userid = $row->owner;
			$info->link   = Route::_('index.php?option=com_rseventspro&view=rseventspro&layout=show&id=' . $row->id . $itemid);
		}

		return $info;
	}
}

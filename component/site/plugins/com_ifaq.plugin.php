<?php
/**
 * JComments plugin for iFaq (https://idealextensions.com/joomla-extensions/component-ifaq-frequently-asked-questions.html)
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
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_ifaq extends JcommentsPlugin
{
	public function getObjectTitle($id): string
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'title')))
			->from($db->quoteName('#__content'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}

	public function getObjectLink($id): string
	{
		require_once JPATH_ROOT . '/components/com_ifaq/helpers/route.php';

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.catid', 'a.access')))
			->select('CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug')
			->select('CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug')
			->from($db->quoteName('#__content', 'a'))
			->join('LEFT', $db->quoteName('#__categories', 'cc'), 'cc.id = a.catid')
			->where($db->quoteName('a.id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);
		$row = $db->loadObject();

		$userGroups = Factory::getApplication()->getIdentity()->getAuthorisedGroups();

		if (in_array($row->access, $userGroups))
		{
			$link = Route::_(IfaqHelperRoute::getArticleRoute($row->slug, $row->catslug));
		}
		else
		{
			$link = Route::_('index.php?option=com_user&task=register');
		}

		return $link;
	}

	public function getObjectOwner($id): int
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select($db->quoteName('created_by'))
			->from($db->quoteName('#__content'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$db->setQuery($query);

		return $db->loadResult();
	}
}

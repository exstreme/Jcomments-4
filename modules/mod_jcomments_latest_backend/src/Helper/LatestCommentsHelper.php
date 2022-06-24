<?php
/**
 * JComments Latest - Shows latest comments in Joomla's backend
 *
 * @version           4.0.0
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Module\LatestComments\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

// @TODO Must be removed later when component frontend will use namespaces.
include_once JPATH_ROOT . '/components/com_jcomments/classes/bbcode.php';
include_once JPATH_ROOT . '/components/com_jcomments/classes/factory.php';
include_once JPATH_ROOT . '/components/com_jcomments/classes/text.php';
include_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

/**
 * Helper for mod_jcomments_latest_backend
 *
 * @since  1.5
 */
class LatestCommentsHelper
{
	/**
	 * Retrieve list of articles
	 *
	 * @param   Registry  $params  Module parameters
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public static function getList(Registry &$params)
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db   = Factory::getContainer()->get('DatabaseDriver');
		$user = Factory::getApplication()->getIdentity();

		$query = $db->getQuery(true)
			->select('jc.*')
			->from($db->qn('#__jcomments', 'jc'));

		// Join over the users
		$query->select($db->qn('u.name', 'editor'))
			->leftJoin($db->qn('#__users', 'u'), 'u.id = jc.checked_out');

		$query->order($db->qn('jc.date') . ' DESC');

		try
		{
			$db->setQuery($query, 0, $params->get('count'));
			$list = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_jcomments_latest_backend');

			return array();
		}

		if (count($list))
		{
			$bbcode           = \JCommentsFactory::getBBCode();
			$limitCommentText = (int) $params->get('limit_comment_text', 0);

			foreach ($list as &$item)
			{
				$item->link = '';

				if ($user->authorise('core.edit', 'com_jcomments'))
				{
					$item->link = 'index.php?option=com_jcomments&task=comment.edit&id=' . $item->id;
				}

				$item->author = \JCommentsContent::getCommentAuthorName($item);

				// @TODO Must be changed later when component frontend will use namespaces.
				$text = \JCommentsText::censor($item->comment);
				$text = $bbcode->filter($text, true);
				$text = \JCommentsText::cleanText($text);

				if ($limitCommentText && StringHelper::strlen($text) > $limitCommentText)
				{
					$text = HTMLHelper::_('string.truncate', $text, $limitCommentText - 1);
				}

				$item->comment = $text;
			}
		}

		return $list;
	}
}

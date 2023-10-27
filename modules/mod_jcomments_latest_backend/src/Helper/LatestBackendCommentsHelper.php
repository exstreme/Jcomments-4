<?php
/**
 * JComments Latest - Shows latest comments in Joomla's dashboard
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Module\LatestBackendComments\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Helper for mod_jcomments_latest_backend
 *
 * @since  1.5
 */
class LatestBackendCommentsHelper implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * Retrieve list of articles
	 *
	 * @param   Registry                  $params  Module parameters
	 * @param   AdministratorApplication  $app     Application
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function getComments(Registry $params, AdministratorApplication $app): array
	{
		$db   = $this->getDatabase();
		$user = $app->getIdentity();

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
			$limitCommentText = (int) $params->get('limit_comment_text', 0);

			foreach ($list as $item)
			{
				$item->link = '';

				if ($user->authorise('core.edit', 'com_jcomments'))
				{
					$item->link = 'index.php?option=com_jcomments&task=comment.edit&id=' . $item->id;
				}

				$item->author = JcommentsContentHelper::getCommentAuthorName($item);
				$text = JcommentsText::censor($item->comment);
				$text = JcommentsText::filterText($text, true);
				$text = JcommentsText::cleanText($text);

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

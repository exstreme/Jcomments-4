<?php
/**
 * JComments Latest Comments - Shows latest comments
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Module\LatestComments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Helper for mod_jcomments_latest
 *
 * @since  1.5
 */
class LatestCommentsHelper implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * Retrieve list of comments
	 *
	 * @param   Registry         $params  Module parameters
	 * @param   SiteApplication  $app     Application
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function getComments(Registry $params, SiteApplication $app)
	{
		$db      = $this->getDatabase();
		$user    = $app->getIdentity();
		$source  = $params->get('source', 'com_content');
		$date    = Factory::getDate();
		$nowDate = $date->toSql();
		$access  = array_unique(Access::getAuthorisedViewLevels($user->get('id')));

		if (!is_array($source))
		{
			$source = explode(',', $source);
			$filter = InputFilter::getInstance();
			$source = array_map(
				function ($source) use ($filter)
				{
					return $filter->clean($source, 'cmd');
				},
				$source
			);
		}

		$query = $db->getQuery(true)
			->select(
				$db->qn(
					array(
						'c.id', 'c.userid', 'c.comment', 'c.title', 'c.name', 'c.username', 'c.email', 'c.date',
						'c.object_id', 'c.object_group'
					)
				)
			)
			->select("'' avatar")
			->select(
				array(
					$db->qn('obj.title', 'object_title'),
					$db->qn('obj.link', 'object_link'),
					$db->qn('obj.access', 'object_access'),
					$db->qn('obj.userid', 'object_owner')
				)
			)
			->from($db->qn('#__jcomments', 'c'))
			->innerJoin(
				$db->qn('#__jcomments_objects', 'obj'),
				$db->qn('c.object_id') . ' = ' . $db->qn('obj.object_id')
					. ' AND ' . $db->qn('c.object_group') . ' = ' . $db->qn('obj.object_group')
					. ' AND ' . $db->qn('c.lang') . ' = ' . $db->qn('obj.lang')
			)
			->where(
				array(
					$db->qn('c.published') . ' = 1',
					$db->qn('c.deleted') . ' = 0',
					$db->qn('obj.link') . " <> ''"
				)
			)
			->whereIn($db->qn('obj.access'), $access);

		if (JcommentsFactory::getLanguageFilter())
		{
			$langTag = $app->getLanguage()->getTag();
			$query->where($db->qn('c.lang') . ' = :lang')
				->bind(':lang', $langTag);
		}

		if (count($source) == 1 && $source[0] == 'com_content')
		{
			$query->innerJoin(
				$db->qn('#__content', 'content'),
				$db->qn('content.id') . ' = ' . $db->qn('obj.object_id')
			)
				->leftJoin(
					$db->qn('#__categories', 'cat'),
					$db->qn('cat.id') . ' = ' . $db->qn('content.catid')
				)
				->where(
					array(
						$db->qn('c.object_group') . ' = :source',
						'(' . $db->qn('content.publish_up') . ' IS NULL OR ' . $db->qn('content.publish_up') . ' <= :publishUp)',
						'(' . $db->qn('content.publish_down') . ' IS NULL OR ' . $db->qn('content.publish_down') . ' >= :publishDown)'
					)
				)
				->bind(':source', $source[0])
				->bind(':publishUp', $nowDate)
				->bind(':publishDown', $nowDate);

			$categories = $params->get('catid');

			if (!is_array($categories))
			{
				$categories = explode(',', $categories);
			}

			$categories = ArrayHelper::toInteger($categories);
			$categories = array_filter($categories);

			if (!empty($categories))
			{
				$query->whereIn($db->qn('content.catid'), $categories);
			}
		}
		elseif (count($source))
		{
			$query->whereIn($db->qn('c.object_group'), $source, ParameterType::STRING);
		}

		switch ($params->get('ordering', ''))
		{
			case 'vote':
				$query->order($db->qn('c.isgood') . ' - ' . $db->qn('c.ispoor') . ' DESC');
				break;

			case 'date':
			default:
				$query->order($db->qn('c.date') . ' DESC');
				break;
		}

		try
		{
			$db->setQuery($query, 0, $params->get('count'));
			$list = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'mod_jcomments_latest');

			return array();
		}

		if (count($list))
		{
			$showDate         = $params->get('show_comment_date', 0);
			$dateType         = $params->get('date_type', '');
			$dateFormat       = $params->get('date_format', 'd.m.Y H:i');
			$showAuthor       = $params->get('show_comment_author', 0);
			$showObjectTitle  = $params->get('show_object_title', 0);
			$showCommentTitle = $params->get('show_comment_title', 0);
			$showSmiles       = $params->get('show_smiles', 0);
			$showAvatar       = $params->get('show_avatar', 0);
			$limitCommentText = (int) $params->get('limit_comment_text', 0);
			$bbcode           = JcommentsFactory::getBBCode();
			$smiles           = JcommentsFactory::getSmilies();

			if ($showAvatar)
			{
				if (PluginHelper::importPlugin('jcomments', 'avatar') === true)
				{
					$app->triggerEvent('onPrepareAvatars', array(&$list));
				}
			}

			foreach ($list as $item)
			{
				if ($showDate)
				{
					if ($dateType == 'relative')
					{
						$item->displayDate = self::getRelativeDate($item->date);
					}
					else
					{
						$item->displayDate = HTMLHelper::_('date', $item->date, $dateFormat);
					}
				}
				else
				{
					$item->displayDate = '';
				}

				$itemid = JcommentsContentHelper::getItemid($app->input->getWord('view'));
				$item->object_link         = Route::_($item->object_link . '&Itemid=' . $itemid);
				$item->displayAuthorName   = $showAuthor ? JcommentsContentHelper::getCommentAuthorName($item) : '';
				$item->displayObjectTitle  = $showObjectTitle ? $item->object_title : '';
				$item->displayCommentTitle = $showCommentTitle ? $item->title : '';
				$item->displayCommentLink  = $item->object_link . '#comment-' . $item->id;

				$text = JcommentsText::censor($item->comment);
				$text = preg_replace('#\[quote[^\]]*?\](((?R)|.)*?)\[\/quote\]#ismu', '', $text);
				$text = $bbcode->filter($text, true);

				if ($user->authorise('comment.autolink', 'com_jcomments'))
				{
					$text = preg_replace_callback(
						'#(^|\s|\>|\()((http://|https://|news://|ftp://|www.)\w+[^\s\<\>\"\'\)]+)#iu',
						'Joomla\Component\Jcomments\Site\Helper\ContentHelper::urlProcessor',
						$text
					);
				}

				$text = JcommentsText::cleanText($text);

				if ($limitCommentText && StringHelper::strlen($text) > $limitCommentText)
				{
					$text = HTMLHelper::_('string.truncate', $text, $limitCommentText - 1);
				}

				if ($showSmiles == 1)
				{
					$text = $smiles->replace($text);
				}
				elseif ($showSmiles == 2)
				{
					$text = $smiles->strip($text);
				}

				$item->displayCommentText = $text;

				// Set default avatar if JComments avatars plugin is not  enabled.
				if ($showAvatar && empty($item->avatar))
				{
					$item->avatar = Uri::base() . 'media/com_jcomments/images/no_avatar.png';
				}

				$item->readmoreText = Text::_('MOD_JCOMMENTS_LATEST_READMORE');
			}
		}

		return $list;
	}

	public static function groupBy($list, $fieldName, $groupingDirection = 'ksort')
	{
		$grouped = array();

		if (!is_array($list))
		{
			if ($list == '')
			{
				return $grouped;
			}

			$list = array($list);
		}

		foreach ($list as $key => $item)
		{
			if (!isset($grouped[$item->$fieldName]))
			{
				$grouped[$item->$fieldName] = array();
			}

			$grouped[$item->$fieldName][] = $item;
			unset($list[$key]);
		}

		$groupingDirection($grouped);

		return $grouped;
	}

	public static function getRelativeDate($value, $countParts = 1)
	{
		$offset = Factory::getApplication()->get('offset');

		$now  = new Date('now', $offset);
		$date = new Date($value);

		$diff   = $now->toUnix() - $date->toUnix();
		$result = $value;

		$timeParts = array(
			31536000 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_YEARS',
			2592000  => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_MONTHS',
			604800   => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_WEEKS',
			86400    => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_DAYS',
			3600     => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_HOURS',
			60       => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_MINUTES',
			1        => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_SECONDS'
		);

		if ($diff < 5)
		{
			$result = Text::_('MOD_JCOMMENTS_LATEST_RELATIVE_DATE_NOW');
		}
		else
		{
			$dayDiff = floor($diff / 86400);
			$nowDay  = date('d', $now->toUnix());
			$dateDay = date('d', $date->toUnix());

			if ($dayDiff == 1 || ($dayDiff == 0 && $nowDay != $dateDay))
			{
				$result = Text::_('MOD_JCOMMENTS_LATEST_RELATIVE_DATE_YESTERDAY');
			}
			else
			{
				$count = 0;
				$resultParts = array();

				foreach ($timeParts as $key => $value)
				{
					if ($diff >= $key)
					{
						$time = floor($diff / $key);
						$resultParts[] = Text::plural($value, $time);
						$diff = $diff % $key;
						$count++;

						if ($count > $countParts - 1 || $diff == 0)
						{
							break;
						}
					}
				}

				if (count($resultParts))
				{
					$result = Text::sprintf('MOD_JCOMMENTS_LATEST_RELATIVE_DATE_AGO', implode(', ', $resultParts));
				}
			}
		}

		return $result;
	}
}

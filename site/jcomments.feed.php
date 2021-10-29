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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Document\FeedDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Export comments to feed.
 *
 * @since  4.0
 */
class JCommentsFeed
{
	/**
	 * Render feed
	 *
	 * @param   string  $itemsType  Comments type
	 *
	 * @return  string|void
	 *
	 * @throws  RuntimeException|Exception
	 * @since   4.0
	 */
	public static function display($itemsType)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if (!$config->get('enable_rss'))
		{
			return '';
		}

		$app = Factory::getApplication();

		/** @var FeedDocument $document */
		$document    = $app->getDocument();
		$objectID    = $app->input->getInt('object_id', 0);
		$objectGroup = $app->input->getString('object_group', 'com_content');
		$limit       = $app->input->getInt('limit', (int) $config->get('feed_limit', 100));
		$lm          = $limit != (int) $config->get('feed_limit') ? ('&limit=' . $limit) : '';

		// If no group or id specified - return 404
		if ($objectID === 0 || $objectGroup == '')
		{
			header('HTTP/1.0 404 Not Found');

			throw new RuntimeException(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
		}

		if (JCommentsFactory::getLanguageFilter())
		{
			$language = $app->getLanguage()->getTag();
			$lp       = '&lang=' . $language;
		}
		else
		{
			$language = null;
			$lp       = '';
		}

		$liveSite = trim(
			str_replace(
				Uri::root(true),
				'',
				str_replace('/administrator', '', Uri::root())
			),
			'/'
		);
		$wordMaxlength = (int) $config->get('word_maxlength');
		$options       = array(
			'filter'       => 'c.deleted = 0',
			'published'    => 1,
			'lang'         => $language,
			'objectinfo'   => true,
			'orderBy'      => 'c.date DESC',
			'limit'        => $limit,
			'limitStart'   => 0,
		);

		$document->setGenerator('JComments');

		if ($itemsType == 'object')
		{
			$document->title          = JCommentsObject::getTitle($objectID, $objectGroup, $language);
			$document->link           = Route::_(JCommentsObject::getLink($objectID, $objectGroup, $language));
			$document->syndicationURL = $liveSite . Route::_(
				'index.php?option=com_jcomments&task=rss&object_id=' . $objectID . '&object_group=' . $objectGroup . $lm . $lp . '&format=feed'
			);
			$document->setDescription(Text::sprintf('OBJECT_FEED_DESCRIPTION', $document->title));

			$options['object_id']    = $objectID;
			$options['object_group'] = $objectGroup;
		}
		elseif ($itemsType == 'all')
		{
			$document->title          = Text::sprintf('SITE_FEED_TITLE', $app->get('sitename'));
			$document->link           = str_replace('/administrator', '', Uri::root());
			$og                       = $objectGroup ? ('&object_group=' . $objectGroup) : '';
			$document->syndicationURL = $liveSite . Route::_(
				'index.php?option=com_jcomments&task=rss_full' . $og . $lm . $lp . '&format=feed'
			);
			$document->setDescription(Text::sprintf('SITE_FEED_DESCRIPTION', $app->get('sitename')));

			$options['object_group'] = explode(',', $objectGroup);
			$options['votes']        = false;
			$options['access']       = JCommentsFactory::getACL()->getUserAccess();
		}
		elseif ($itemsType == 'user')
		{
			$uid = $app->input->getInt('userid', 0);

			if (empty($uid))
			{
				header('HTTP/1.0 404 Not Found');

				throw new RuntimeException(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
			}
			else
			{
				// Do not use Factory::getApplication()->getIdentity()->load($uid)->get('name') as it always return an error.
				$user         = Factory::getUser($uid);
				$user->userid = $user->get('id');
				$username     = JCommentsContent::getCommentAuthorName($user);
			}

			$document->title          = Text::sprintf('USER_FEED_TITLE', $username);
			$document->link           = str_replace('/administrator', '', Uri::root());
			$document->syndicationURL = $liveSite
				. Route::_('index.php?option=com_jcomments&task=rss_user&userid=' . $uid . $lm . $lp . '&format=feed');
			$document->setDescription(Text::sprintf('USER_FEED_DESCRIPTION', $username));

			$options['userid']     = $uid;
			$options['objectinfo'] = true;
			$options['votes']      = false;
			$options['access']     = JCommentsFactory::getACL()->getUserAccess();
		}

		$rows = JCommentsModel::getCommentsList($options);

		foreach ($rows as $row)
		{
			$author  = JCommentsContent::getCommentAuthorName($row);
			$comment = JCommentsText::cleanText($row->comment);

			if ($comment != '')
			{
				$comment = JCommentsText::censor($comment);

				if ($wordMaxlength > 0)
				{
					$comment = JCommentsText::fixLongWords($comment, $wordMaxlength, ' ');
				}

				if ($itemsType == 'object')
				{
					// Strip html from feed item title
					$title       = htmlspecialchars($row->title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$title       = JCommentsText::censor($title);
					$title       = ($wordMaxlength > 0 && $title != '') ? JCommentsText::fixLongWords($title, $wordMaxlength, ' ') : $title;
					$objectTitle = ($title != '') ? $title : Text::sprintf('OBJECT_FEED_ITEM_TITLE', $author);

					$objectLink  = JCommentsObject::getLink($objectID, $objectGroup, $language);
					$objectLink  = JCommentsFactory::getAbsLink($objectLink);
					$description = $comment;
				}
				else
				{
					// Strip html from feed item title
					$title       = htmlspecialchars($row->object_title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$title       = JCommentsText::censor($title);
					$objectTitle = ($wordMaxlength > 0 && $title != '') ? JCommentsText::fixLongWords($title, $wordMaxlength, ' ') : $title;

					$objectLink  = JCommentsFactory::getAbsLink($row->object_link);
					$description = Text::sprintf(
						($itemsType == 'all') ? 'SITE_FEED_ITEM_DESCRIPTION' : 'USER_FEED_ITEM_DESCRIPTION',
						$author,
						$comment
					);
				}

				$item = new FeedItem;
				$item->title       = $objectTitle;
				$item->link        = $objectLink . '#comment-' . $row->id;
				$item->description = $description;
				$item->source      = $objectLink;
				$item->date        = $row->date;
				$item->author      = $author;

				$document->addItem($item);
			}
		}

		echo $document->render();
	}
}

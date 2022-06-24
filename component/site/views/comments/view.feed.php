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

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\AbstractView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Comments feed View class
 *
 * @since  4.0
 */
class JCommentsViewComments extends AbstractView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  string|void
	 *
	 * @since  3.0
	 */
	public function display($tpl = null)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		if (!$config->get('enable_rss'))
		{
			return '';
		}

		if (count($this->get('Errors')))
		{
			return '';
		}

		$app         = Factory::getApplication();
		$user        = $app->getIdentity();
		$language    = JCommentsFactory::getLanguageFilter() ? $app->getLanguage()->getTag() : null;
		$itemsType   = $app->input->getWord('task', 'rss');
		$objectID    = $app->input->getInt('object_id', 0);
		$objectGroup = $app->input->getString('object_group', 'com_content');
		$limit       = $app->input->getInt('limit', (int) $config->get('feed_limit', 100));
		$lm          = $limit != (int) $config->get('feed_limit') ? ('&limit=' . $limit) : '';

		// If no group or id specified - return 404
		if ($objectID === 0 || empty($objectGroup))
		{
			header('HTTP/1.0 404 Not Found');

			throw new RuntimeException(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
		}

		$liveSite = trim(
			str_replace(
				Uri::root(true),
				'',
				str_replace('/administrator', '', Uri::root())
			),
			'/'
		);
		$options       = array(
			'filter'       => 'c.deleted = 0',
			'published'    => 1,
			'lang'         => $language,
			'objectinfo'   => true,
			'orderBy'      => 'c.date DESC',
			'limit'        => $limit,
			'limitStart'   => 0,
		);

		$this->document->setGenerator('JComments');

		if ($itemsType == 'rss')
		{
			$this->document->title          = JCommentsObject::getTitle($objectID, $objectGroup, $language);
			$this->document->link           = Route::_(
				htmlspecialchars(JCommentsObject::getLink($objectID, $objectGroup, $language), ENT_COMPAT),
				false
			);
			$this->document->syndicationURL = $liveSite . Route::_(
				'index.php?option=com_jcomments&task=rss&object_id=' . $objectID . '&object_group=' . $objectGroup . $lm . '&format=feed'
			);
			$this->document->setDescription(Text::sprintf('OBJECT_FEED_DESCRIPTION', $this->document->title));

			$options['object_id']    = $objectID;
			$options['object_group'] = $objectGroup;
		}
		elseif ($itemsType == 'rss_full')
		{
			$this->document->title = Text::sprintf('SITE_FEED_TITLE', $app->get('sitename'));
			$this->document->link  = str_replace('/administrator', '', Uri::root());
			$og                    = $objectGroup ? ('&object_group=' . $objectGroup) : '';
			$this->document->syndicationURL = $liveSite . Route::_(
				'index.php?option=com_jcomments&task=rss_full' . $og . $lm . '&format=feed'
			);
			$this->document->setDescription(Text::sprintf('SITE_FEED_DESCRIPTION', $app->get('sitename')));

			$options['object_group'] = explode(',', $objectGroup);
			$options['votes']        = false;
			$options['access']       = Access::getAuthorisedViewLevels($user->get('id'));
		}
		elseif ($itemsType == 'rss_user')
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

			$this->document->title          = Text::sprintf('USER_FEED_TITLE', $username);
			$this->document->link           = str_replace('/administrator', '', Uri::root());
			$this->document->syndicationURL = $liveSite
				. Route::_('index.php?option=com_jcomments&task=rss_user&userid=' . $uid . $lm . '&format=feed');
			$this->document->setDescription(Text::sprintf('USER_FEED_DESCRIPTION', $username));

			$options['userid']     = $uid;
			$options['objectinfo'] = true;
			$options['votes']      = false;
			$options['access']     = Access::getAuthorisedViewLevels($user->get('id'));
		}

		$rows = JCommentsModel::getCommentsList($options);

		foreach ($rows as $row)
		{
			$author  = JCommentsContent::getCommentAuthorName($row);
			$comment = JCommentsText::cleanText($row->comment);

			if ($comment != '')
			{
				$comment = JCommentsText::censor($comment);

				if ($itemsType == 'rss')
				{
					// Strip html from feed item title
					$title       = htmlspecialchars($row->title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$title       = JCommentsText::censor($title);
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
					$objectTitle = JCommentsText::censor($title);

					$objectLink  = JCommentsFactory::getAbsLink($row->object_link);
					$description = Text::sprintf(
						($itemsType == 'rss_full') ? 'SITE_FEED_ITEM_DESCRIPTION' : 'USER_FEED_ITEM_DESCRIPTION',
						$author,
						$comment
					);
				}

				$item = new FeedItem;
				$item->title       = $objectTitle;
				$item->link        = htmlspecialchars($objectLink, ENT_COMPAT) . '#comment-' . $row->id;
				$item->description = $description;
				$item->source      = htmlspecialchars($objectLink, ENT_COMPAT);
				$item->date        = $row->date;
				$item->author      = $author;

				$this->document->addItem($item);
			}
		}
	}
}

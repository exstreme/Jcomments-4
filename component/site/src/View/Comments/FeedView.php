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

namespace Joomla\Component\Jcomments\Site\View\Comments;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\AbstractView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

/**
 * Comments feed View class
 *
 * @see    \Joomla\CMS\Document\FeedDocument
 * @see    \Joomla\CMS\Document\Renderer\Feed\RssRenderer
 * @see    \Joomla\CMS\Document\Renderer\Feed\AtomRenderer
 *
 * @since  4.0
 * @noinspection PhpUnused
 */
class FeedView extends AbstractView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function display($tpl = null)
	{
		$app    = Factory::getApplication();
		$config = ComponentHelper::getParams('com_jcomments');

		if (!$config->get('enable_rss'))
		{
			$app->close();
		}

		if (count($this->get('Errors')))
		{
			$app->close();
		}

		$feedLimit = $config->get('feed_limit', $app->get('feed_limit'));
		$app->input->set('limit', $feedLimit);

		$acl         = JcommentsFactory::getAcl();
		$state       = $this->get('State');
		$itemsType   = $app->input->getWord('task', 'rss');
		$objectId    = $state->get('object_id');
		$objectGroup = $state->get('object_group');

		// Set up model state before calling getItems()
		$state->set('list.options.filter', 'c.deleted = 0');
		$state->set('list.options.published', 1);
		$state->set('list.options.lang', $app->getLanguage()->getTag());
		$state->set('list.ordering', 'c.date');
		$state->set('list.direction', 'DESC');
		$state->set('list.limit', $feedLimit);
		$state->set('list.start', 0);

		$this->document->setGenerator('JComments');

		if ($itemsType == 'rss')
		{
			if ($objectId === 0 || empty($objectGroup))
			{
				header('HTTP/1.0 404 Not Found');

				$app->close();
			}

			$objectInfo                     = ObjectHelper::getObjectInfo($objectId, $objectGroup, $app->getLanguage()->getTag());
			$this->document->title          = $objectInfo->title;
			$this->document->link           = htmlspecialchars($objectInfo->link, ENT_COMPAT, 'UTF-8');
			$this->document->syndicationURL = Route::_(
				'index.php?option=com_jcomments&task=rss&object_id=' . $objectId . '&object_group=' . $objectGroup . '&type=rss&format=feed',
				true, 0, true
			);
			$this->document->setDescription(Text::sprintf('OBJECT_FEED_DESCRIPTION', $this->document->title));

			$state->set('object_id', $objectId);
			$state->set('object_group', $objectGroup);
			$state->set('list.options.object_info', true);
		}
		elseif ($itemsType == 'rss_full')
		{
			if (empty($objectGroup))
			{
				header('HTTP/1.0 404 Not Found');

				$app->close();
			}

			$this->document->title = Text::sprintf('SITE_FEED_TITLE', $app->get('sitename'));
			$this->document->link  = Uri::base();
			$this->document->syndicationURL = Route::_(
				'index.php?option=com_jcomments&task=rss_full&object_group=' . $objectGroup . '&type=rss&format=feed', true, 0, true
			);
			$this->document->setDescription(Text::sprintf('SITE_FEED_DESCRIPTION', $app->get('sitename')));

			$state->set('object_id', null);
			$state->set('object_group', explode(',', $objectGroup));
			$state->set('list.options.access', $acl->getAuthorisedViewLevels($app->getIdentity()->get('id')));
			$state->set('list.options.votes', 0);
			$state->set('list.options.object_info', true);
		}
		elseif ($itemsType == 'rss_user')
		{
			$uid = $app->input->getInt('userid', 0);

			if (empty($uid))
			{
				header('HTTP/1.0 404 Not Found');

				$app->close();
			}

			/** @var \Joomla\CMS\User\UserFactory $userFactory */
			$userFactory = Factory::getContainer()->get('user.factory');
			$user = $userFactory->loadUserById($uid);

			// User not found
			if (empty($user))
			{
				header('HTTP/1.0 404 Not Found');

				$app->close();
			}

			$username = JcommentsContentHelper::getCommentAuthorName(
				(object) array(
					'userid'   => $user->get('id'),
					'name'     => $user->get('name'),
					'username' => $user->get('username')
				)
			);

			$this->document->title          = Text::sprintf('USER_FEED_TITLE', $username);
			$this->document->link           = Uri::base();
			$this->document->syndicationURL = Route::_(
				'index.php?option=com_jcomments&task=rss_user&userid=' . $uid . '&type=rss&format=feed',
				true, 0, true
			);
			$this->document->setDescription(Text::sprintf('USER_FEED_DESCRIPTION', $username));

			if (!empty($objectGroup))
			{
				$groups = explode(',', $objectGroup);

				// The object identifier is available only if the object group is one.
				$state->set('object_id', (count($groups) == 1) ? $objectId : null);
				$state->set('object_group', $groups);
			}
			else
			{
				$state->set('object_id', null);
				$state->set('object_group', null);
			}

			$state->set('list.options.userid', $uid);
			$state->set('list.options.access', $acl->getAuthorisedViewLevels($uid));
			$state->set('list.options.votes', 0);
			$state->set('list.options.object_info', true);
		}
		else
		{
			header('HTTP/1.0 404 Not Found');

			$app->close();
		}

		$rows = $this->get('Items');

		foreach ($rows as $row)
		{
			$author  = JcommentsContentHelper::getCommentAuthorName($row);
			$comment = JcommentsText::cleanText($row->comment);

			if ($comment != '')
			{
				$comment = JcommentsText::censor($comment);

				if ($itemsType == 'rss')
				{
					// Strip html from feed item title
					$title       = htmlspecialchars($row->title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$title       = JcommentsText::censor($title);
					$objectTitle = ($title != '') ? $title : Text::sprintf('OBJECT_FEED_ITEM_TITLE', $author);
					$objectLink  = JcommentsFactory::getAbsLink($row->object_link);
					$description = $comment;
				}
				else
				{
					// Strip html from feed item title
					$title       = htmlspecialchars($row->object_title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$objectTitle = JcommentsText::censor($title);
					$objectLink  = JcommentsFactory::getAbsLink($row->object_link);
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
				$item->date        = $row->date;
				$item->author      = $author;

				$this->document->addItem($item);
			}
		}
	}
}

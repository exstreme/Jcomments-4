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
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Filesystem\File;

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
	 * The active document object
	 *
	 * @var    \Joomla\CMS\Document\FeedDocument
	 * @since  4.1
	 */
	public $document;

	/**
	 * @var    \Joomla\Registry\Registry
	 * @since  4.1
	 */
	protected $params;

	/**
	 * The model state
	 *
	 * @var    \Joomla\Registry\Registry
	 * @since  4.1
	 */
	protected $state = null;

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
		$app          = Factory::getApplication();
		$this->params = ComponentHelper::getParams('com_jcomments');
		$feedLimit    = $this->params->get('feed_limit', $app->get('feed_limit'));

		if (!$this->params->get('enable_rss') || $feedLimit == 0)
		{
			header('HTTP/1.0 403 Forbidden');

			$app->close();
		}

		$app->input->set('limit', $feedLimit);

		$this->state = $this->get('State');
		$itemsType = $app->input->getWord('task', 'rss');

		// Set up model state before calling getItems()
		$this->state->set('list.options.filter', 'c.deleted = 0');
		$this->state->set('list.options.published', 1);
		$this->state->set('list.options.lang', $app->getLanguage()->getTag());
		$this->state->set('list.ordering', 'c.date');
		$this->state->set('list.direction', 'DESC');
		$this->state->set('list.limit', $feedLimit);
		$this->state->set('list.start', 0);
		$this->state->set('list.options.object_info', true);

		$this->document->setGenerator('JComments');

		switch ($itemsType)
		{
			case 'rss':
				$this->getItemRss();
				break;
			case 'rss_full':
				$this->getComponentRss();
				break;
			case 'rss_user':
				$this->getUserRss();
				break;
			default:
				header('HTTP/1.0 404 Not Found');

				$app->close();
		}

		$style = File::makeSafe($this->params->get('custom_css'));
		$this->document->addStyleSheet(Uri::base() . 'media/vendor/bootstrap/css/bootstrap.min.css');
		$this->document->addStyleSheet(Uri::base() . 'media/com_jcomments/css/' . $style . '.css');
	}

	/**
	 * Get rss for one item in component
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	private function getItemRss()
	{
		$app       = Factory::getApplication();
		$feedLimit = $this->params->get('feed_limit', $app->get('feed_limit'));

		if ($this->params->get('comments_locked'))
		{
			header('HTTP/1.0 403 Forbidden');

			$app->close();
		}

		$app->input->set('limit', $feedLimit);

		$user        = $app->getIdentity();
		$objectId    = $this->state->get('object_id');
		$objectGroup = $this->state->get('object_group');

		if ($objectId === 0 || empty($objectGroup))
		{
			header('HTTP/1.0 404 Not Found');

			$app->close();
		}

		$objectInfo = ObjectHelper::getObjectInfo($objectId, $objectGroup);

		if (!in_array($objectInfo->object_access, $user->getAuthorisedViewLevels()))
		{
			header('HTTP/1.0 403 Forbidden');

			$app->close();
		}

		$this->document->title          = $objectInfo->object_title;
		$this->document->link           = htmlspecialchars($objectInfo->object_link, ENT_COMPAT, 'UTF-8');
		$this->document->syndicationURL = Route::_(
			'index.php?option=com_jcomments&task=rss&object_id=' . $objectId . '&object_group=' . $objectGroup . '&type=rss&format=feed',
			true, 0, true
		);
		$this->document->setDescription(Text::sprintf('OBJECT_FEED_DESCRIPTION', $this->document->title));

		$rows = $this->get('Items');

		if (count($this->get('Errors')))
		{
			header('HTTP/1.0 500 Server error');

			$app->close();
		}

		foreach ($rows as $row)
		{
			JcommentsContentHelper::prepareComment($row);
			$author  = JcommentsContentHelper::getCommentAuthorName($row);
			$comment = preg_replace('#<script[^>]*>.*?</script>#ismu', '', $row->comment);

			if ($comment != '')
			{
				$comment = JcommentsText::censor($comment);

				// Strip html from feed item title
				$title       = htmlspecialchars($row->title, ENT_QUOTES);
				$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
				$title       = JcommentsText::censor($title);
				$objectTitle = ($title != '') ? $title : Text::sprintf('OBJECT_FEED_ITEM_TITLE', $author);
				$objectLink  = !empty($row->object_link) ? JcommentsContentHelper::getAbsLink($row->object_link) : Uri::base();
				$description = $comment;

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

	/**
	 * Get rss for component
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	private function getComponentRss()
	{
		$app       = Factory::getApplication();
		$feedLimit = $this->params->get('feed_limit', $app->get('feed_limit'));

		$app->input->set('limit', $feedLimit);

		$user = $app->getIdentity();
		$objectGroup = $this->state->get('object_group');

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

		$this->state->set('object_id', null);
		$this->state->set('object_group', $objectGroup);
		$this->state->set('list.options.votes', 0);

		$rows = $this->get('Items');
		$viewLevels = $user->getAuthorisedViewLevels();

		if (count($this->get('Errors')))
		{
			header('HTTP/1.0 500 Server error');

			$app->close();
		}

		foreach ($rows as $row)
		{
			if (ObjectHelper::isEmpty($row))
			{
				$objectInfo         = ObjectHelper::getObjectInfo($row->object_id, $row->object_group, $row->lang);
				$row->object_title  = $objectInfo->object_title;
				$row->object_link   = $objectInfo->object_link;
				$row->object_access = $objectInfo->object_access;
			}

			if (!in_array($row->object_access, $viewLevels))
			{
				continue;
			}

			JcommentsContentHelper::prepareComment($row);
			$author  = JcommentsContentHelper::getCommentAuthorName($row);
			$comment = preg_replace('#<script[^>]*>.*?</script>#ismu', '', $row->comment);

			if ($comment != '')
			{
				$comment = JcommentsText::censor($comment);

				// Strip html from item title
				if (!empty($row->object_title))
				{
					$title       = htmlspecialchars($row->object_title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$objectTitle = JcommentsText::censor($title);
				}
				else
				{
					$objectTitle = $row->title;
				}

				$objectLink  = JcommentsContentHelper::getAbsLink($row->object_link);
				$description = Text::sprintf(
					'SITE_FEED_ITEM_DESCRIPTION',
					$author,
					$comment
				);

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

	/**
	 * Get rss for user comments
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	private function getUserRss()
	{
		$app       = Factory::getApplication();
		$feedLimit = $this->params->get('feed_limit', $app->get('feed_limit'));

		$app->input->set('limit', $feedLimit);

		$objectGroup = $this->state->get('object_group');
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
		if (empty($user->get('id')))
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
		$this->document->link           = Route::_(
			'index.php?option=com_jcomments&task=rss_user&userid=' . $uid . '&type=rss&format=feed',
			true, 0, true
		);
		$this->document->syndicationURL = $this->document->link;
		$this->document->setDescription(Text::sprintf('USER_FEED_DESCRIPTION', $username));

		if (!empty($app->input->getCmd('object_group')))
		{
			$this->state->set('object_group', $objectGroup);
		}
		else
		{
			$this->state->set('object_group', null);
		}

		$this->state->set('object_id', null);
		$this->state->set('list.options.userid', $uid);
		$this->state->set('list.options.votes', 0);

		$rows = $this->get('Items');
		$viewLevels = $user->getAuthorisedViewLevels();

		if (count($this->get('Errors')))
		{
			header('HTTP/1.0 500 Server error');

			$app->close();
		}

		foreach ($rows as $row)
		{
			if (ObjectHelper::isEmpty($row))
			{
				$objectInfo         = ObjectHelper::getObjectInfo($row->object_id, $row->object_group, $row->lang);
				$row->object_title  = $objectInfo->object_title;
				$row->object_link   = $objectInfo->object_link;
				$row->object_access = $objectInfo->object_access;
			}

			if (!in_array($row->object_access, $viewLevels))
			{
				continue;
			}

			JcommentsContentHelper::prepareComment($row);
			$author  = JcommentsContentHelper::getCommentAuthorName($row);
			$comment = preg_replace('#<script[^>]*>.*?</script>#ismu', '', $row->comment);

			if ($comment != '')
			{
				$comment = JcommentsText::censor($comment);

				// Strip html from item title
				if (!empty($row->object_title))
				{
					$title       = htmlspecialchars($row->object_title, ENT_QUOTES);
					$title       = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
					$objectTitle = JcommentsText::censor($title);
				}
				else
				{
					$objectTitle = $row->title;
				}

				$objectLink  = JcommentsContentHelper::getAbsLink($row->object_link);
				$description = Text::sprintf(
					'USER_FEED_ITEM_DESCRIPTION',
					$author,
					$comment
				);

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

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

use Joomla\CMS\Access\Access;
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
 * @since  4.0
 */
class FeedView extends AbstractView
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
		$app    = Factory::getApplication();
		$config = $app->getParams('com_jcomments');

		if (!$config->get('enable_rss'))
		{
			return '';
		}

		if (count($this->get('Errors')))
		{
			return '';
		}

		$user        = $app->getIdentity();
		$language    = \Joomla\CMS\Language\Multilanguage::isEnabled() ? $app->getLanguage()->getTag() : null;
		$itemsType   = $app->input->getWord('task', 'rss');
		$objectID    = $app->input->getInt('object_id', 0);
		$objectGroup = $app->input->getString('object_group', 'com_content');
		$limit       = $app->input->getInt('limit', (int) $config->get('feed_limit', 100));
		$lm          = $limit != (int) $config->get('feed_limit') ? ('&limit=' . $limit) : '';

		// If no group or id specified - return 404
		if ($itemsType !== 'rss_user' && ($objectID === 0 || empty($objectGroup)))
		{
			header('HTTP/1.0 404 Not Found');

			return '';
		}

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
			$this->document->title = ObjectHelper::getObjectField('title', $objectID, $objectGroup, $language);
			$this->document->link = Route::_(
				ObjectHelper::getObjectField('link', $objectID, $objectGroup, $language), true, 0, true
			);
			$this->document->syndicationURL = Route::_(
				'index.php?option=com_jcomments&task=rss&object_id=' . $objectID . '&object_group=' . $objectGroup . $lm . '&type=rss&format=feed',
				true, 0, true
			);
			$this->document->setDescription(Text::sprintf('OBJECT_FEED_DESCRIPTION', $this->document->title));

			$options['object_id']    = $objectID;
			$options['object_group'] = $objectGroup;
		}
		elseif ($itemsType == 'rss_full')
		{
			$this->document->title = Text::sprintf('SITE_FEED_TITLE', $app->get('sitename'));
			$this->document->link  = Uri::base();
			$og                    = $objectGroup ? ('&object_group=' . $objectGroup) : '';
			$this->document->syndicationURL = Route::_(
				'index.php?option=com_jcomments&task=rss_full' . $og . $lm . '&type=rss&format=feed', true, 0, true
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

				return '';
			}
			else
			{
				/** @var \Joomla\CMS\User\UserFactory $userFactory */
				$userFactory  = Factory::getContainer()->get('user.factory');
				$user         = $userFactory->loadUserById($uid);
				$user->userid = $user->get('id');
				$username     = JcommentsContentHelper::getCommentAuthorName($user);
			}

			$this->document->title          = Text::sprintf('USER_FEED_TITLE', $username);
			$this->document->link           = Uri::base();
			$this->document->syndicationURL = Route::_(
				'index.php?option=com_jcomments&task=rss_user&userid=' . $uid . $lm . '&type=rss&format=feed',
				true, 0, true
			);
			$this->document->setDescription(Text::sprintf('USER_FEED_DESCRIPTION', $username));

			$options['userid']     = $uid;
			$options['objectinfo'] = true;
			$options['votes']      = false;
			$options['access']     = Access::getAuthorisedViewLevels($user->get('id'));
		}
		else
		{
			header('HTTP/1.0 404 Not Found');

			return '';
		}

		// TODO Not yet implemented
		//$rows = JCommentsModel::getCommentsList($options);
		$rows = $this->get('Items');
		print_r($rows);
exit;
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

					$objectLink  = ObjectHelper::getObjectField('link', $objectID, $objectGroup, $language);
					$objectLink  = JcommentsFactory::getAbsLink($objectLink);
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
				$item->source      = htmlspecialchars($objectLink, ENT_COMPAT);
				$item->date        = $row->date;
				$item->author      = $author;

				$this->document->addItem($item);
			}
		}
	}
}

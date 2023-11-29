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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * JComments Content Plugin Helper.
 *
 * @alias  JcommentsContentHelper
 *
 * @since  4.0
 */
class ContentHelper
{
	/**
	 *
	 * @param   object  $row           The content item object
	 * @param   array   $patterns      Array with patterns strings to search for
	 * @param   array   $replacements  Array with strings to replace
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	private static function processTags($row, array $patterns = array(), array $replacements = array())
	{
		if (count($patterns) > 0)
		{
			ob_start();

			$keys = array('introtext', 'fulltext', 'text');

			foreach ($keys as $key)
			{
				if (isset($row->$key))
				{
					$row->$key = preg_replace($patterns, $replacements, $row->$key);
				}
			}

			ob_end_clean();
		}
	}

	/**
	 * Searches given tag in content object
	 *
	 * @param   object  $row      The content item object
	 * @param   string  $pattern  RegExp
	 *
	 * @return  boolean True if any tag found, False otherwise
	 *
	 * @since   1.5
	 */
	private static function findTag($row, string $pattern): bool
	{
		$keys = array('introtext', 'fulltext', 'text');

		foreach ($keys as $key)
		{
			if (isset($row->$key) && preg_match($pattern, $row->$key))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Replaces or removes commenting systems tags like {moscomment}, {jomcomment} etc
	 *
	 * @param   object   $row         The content item object
	 * @param   boolean  $removeTags  Remove all 3rd party tags or replace it to JComments tags?
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function processForeignTags($row, bool $removeTags = false)
	{
		if (!$removeTags)
		{
			$patterns     = array('#\{(jomcomment|easycomments|KomentoEnable)\}#is', '#\{(\!jomcomment|KomentoDisable)\}#is', '#\{KomentoLock\}#is');
			$replacements = array('{jcomments on}', '{jcomments off}', '{jcomments lock}');
		}
		else
		{
			$patterns     = array('#\{(jomcomment|easycomments|KomentoEnable|KomentoDisable|KomentoLock)\}#is');
			$replacements = array('');
		}

		self::processTags($row, $patterns, $replacements);
	}

	/**
	 * Return true if one of text fields contains {jcomments on} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments on} found, False otherwise
	 *
	 * @since   1.5
	 */
	public static function isEnabled($row): bool
	{
		return self::findTag($row, '/{jcomments\s+on}/is');
	}

	/**
	 * Return true if one of text fields contains {jcomments off} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments off} found, False otherwise
	 *
	 * @since   1.5
	 */
	public static function isDisabled($row): bool
	{
		return self::findTag($row, '/{jcomments\s+off}/is');
	}

	/**
	 * Return true if one of text fields contains {jcomments lock} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments lock} found, False otherwise
	 *
	 * @since   1.5
	 */
	public static function isLocked($row): bool
	{
		return self::findTag($row, '/{jcomments\s+lock}/is');
	}

	/**
	 * Clears all JComments tags from content item
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function clear($row)
	{
		$patterns     = array('/{jcomments\s+(off|on|lock)}/i');
		$replacements = array('');

		self::processTags($row, $patterns, $replacements);
	}

	/**
	 * Checks if comments are enabled for specified category
	 *
	 * @param   integer  $id  Category ID
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 */
	public static function checkCategory(int $id): bool
	{
		$config     = ComponentHelper::getParams('com_jcomments');
		$categories = (array) $config->get('enable_categories');

		return (in_array('*', $categories) || in_array($id, $categories));
	}

	/**
	 * Get author name
	 *
	 * @param   mixed  $comment  Comment object
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	public static function getCommentAuthorName($comment): string
	{
		$name = '';

		if ($comment != null)
		{
			$config = ComponentHelper::getParams('com_jcomments');

			if ($comment->userid && $config->get('display_author') == 'username' && $comment->username != '')
			{
				$name = $comment->username;
			}
			else
			{
				$name = $comment->name ?: Text::_('REPORT_GUEST');
			}
		}

		return $name;
	}

	public static function urlProcessor($matches)
	{
		$link = $matches[2];
		$linkSuffix = '';

		while (preg_match('#[\,\.]+#', $link[strlen($link) - 1]))
		{
			$sl          = strlen($link) - 1;
			$linkSuffix .= $link[$sl];
			$link        = StringHelper::substr($link, 0, $sl);
		}

		$linkText      = preg_replace('#(http|https|news|ftp)\:\/\/#i', '', $link);
		$config        = ComponentHelper::getParams('com_jcomments');
		$linkMaxlength = (int) $config->get('link_maxlength');

		if (($linkMaxlength > 0) && (strlen($linkText) > $linkMaxlength))
		{
			$linkParts = preg_split('#\/#i', preg_replace('#/$#i', '', $linkText));
			$cnt       = count($linkParts);

			if ($cnt >= 2)
			{
				$linkSite     = $linkParts[0];
				$linkDocument = $linkParts[$cnt - 1];
				$shortLink    = $linkSite . '/.../' . $linkDocument;

				if ($cnt == 2)
				{
					$shortLink = $linkSite . '/.../';
				}
				elseif (strlen($shortLink) > $linkMaxlength)
				{
					$linkSite       = str_replace('www.', '', $linkSite);
					$linkSiteLength = strlen($linkSite);
					$shortLink      = $linkSite . '/.../' . $linkDocument;

					if (strlen($shortLink) > $linkMaxlength)
					{
						if ($linkSiteLength < $linkMaxlength)
						{
							$shortLink = $linkSite . '/.../...';
						}
						elseif ($linkDocument < $linkMaxlength)
						{
							$shortLink = '.../' . $linkDocument;
						}
						else
						{
							$linkProtocol = preg_replace('#([^a-z])#i', '', $matches[3]);

							if ($linkProtocol == 'www')
							{
								$linkProtocol = 'http';
							}

							if ($linkProtocol != '')
							{
								$shortLink = $linkProtocol;
							}
							else
							{
								$shortLink = '/.../';
							}
						}
					}
				}

				$linkText = wordwrap($shortLink, $linkMaxlength, ' ', true);
			}
			else
			{
				$linkText = wordwrap($linkText, $linkMaxlength, ' ', true);
			}
		}

		$liveSite = trim(str_replace(Uri::root(true), '', str_replace('/administrator', '', Uri::root())), '/');

		if (strpos($link, $liveSite) === false)
		{
			return $matches[1] . "<a href=\"" . ((StringHelper::substr($link, 0, 3) == 'www') ? "http://" : "") . $link . "
				\" target=\"_blank\" rel=\"external nofollow\">$linkText</a>" . $linkSuffix;
		}
		else
		{
			return $matches[1] . "<a href=\"$link\" target=\"_blank\">$linkText</a>" . $linkSuffix;
		}
	}

	/**
	 * Prepare comment, set some initial data
	 *
	 * @param   object   $comment  Comment object
	 * @param   boolean  $text     Prepare only comment field
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function prepareComment($comment, bool $text = false)
	{
		if (isset($comment->_skip_prepare) && $comment->_skip_prepare == 1)
		{
			return;
		}

		$app        = Factory::getApplication();
		$params     = ComponentHelper::getParams('com_jcomments');
		$acl        = JcommentsFactory::getACL();
		$user       = $app->getIdentity();
		$dispatcher = $app->getDispatcher();

		$dispatcher->dispatch(
			'onJCommentsCommentBeforePrepare',
			AbstractEvent::create(
				'onJCommentsCommentBeforePrepare',
				array('subject' => new \stdClass, array($comment))
			)
		);

		// Run autocensor
		if ($acl->enableAutocensor())
		{
			$comment->comment = JcommentsText::censor($comment->comment);

			if ($comment->title != '')
			{
				$comment->title = JcommentsText::censor($comment->title);
			}
		}

		if (!empty($comment->email) && MailHelper::isEmailAddress($comment->email))
		{
			$comment->email = HTMLHelper::_(
				'email.cloak',
				$comment->email,
				true,
				'<span class="fa icon-envelope pe-1" aria-hidden="true"></span>' . $comment->email,
				true,
				'class="link-secondary" title="' . Text::_('NOTIFICATION_COMMENT_EMAIL') . '" itemprop="email"'
			);
		}

		// Replace deleted comment text with predefined message
		if ($comment->deleted == 1)
		{
			$comment->title    = '';
			$comment->comment  = Text::_('COMMENT_TEXT_COMMENT_HAS_BEEN_DELETED');
			$comment->username = '';
			$comment->name     = '';
			$comment->email    = '';
			$comment->homepage = '';
			$comment->userid   = 0;
			$comment->isgood   = 0;
			$comment->ispoor   = 0;
		}

		// NOTE! Do not filter comment text in other formats as it will be allready filtered when comment save.
		if ($params->get('editor_format') == 'bbcode')
		{
			$bbcode = JcommentsFactory::getBBCode();

			// Replace BBCode tags
			$comment->comment = $bbcode->replace($comment->comment);

			if ((int) $params->get('enable_custom_bbcode'))
			{
				$comment->comment = $bbcode->replaceCustom($comment->comment);
			}
		}

		if ($user->authorise('comment.email.protect', 'com_jcomments'))
		{
			/** @note Joomla email cloak plugin did not support IDN in emails. */
			$comment->comment = preg_replace_callback(
				'~([\w\.\-]+)@(\w+[\w\.\-]*\.\w{2,6})~iu',
				function ($matches) use ($text)
				{
					if (MailHelper::isEmailAddress($matches[0]))
					{
						if ($text)
						{
							$email = '<a href="mailto:' . $matches[0] . '" class="email">' . $matches[0] . '</a>';
						}
						else
						{
							/** @see \Joomla\CMS\HTML\Helpers\Email::cloak */
							$email = HTMLHelper::_(
								'email.cloak',
								$matches[0],
								true,
								'',
								true,
								'class="email"'
							);
						}
					}
					else
					{
						$email = $matches[0];
					}

					return $email;
				},
				$comment->comment
			);
		}

		// Autolink urls, emails always displayed as link.
		if ($user->authorise('comment.autolink', 'com_jcomments'))
		{
			// TODO Broken links in bbcodes
			/*$comment->comment = preg_replace_callback(
				'#(^|\s|\>|\()((http://|https://|news://|ftp://|www.)\w+[^\s\<\>\"\'\)]+)#iu',
				'self::urlProcessor',
				$comment->comment
			);*/

			/*$comment->comment = preg_replace_callback(
				'#(^|\s|\>|\()((http://|https://|news://|ftp://|www.)\w+[^\s\<\>\"\'\)]+)#iu',
				function ($matches)
				{
					return '';
				},
				$comment->comment
			);*/
		}

		// Replace smilies' codes with images
		if ($params->get('enable_smilies') == '1')
		{
			$comment->comment = JcommentsFactory::getSmilies()->replace($comment->comment);
		}

		if ($text === false)
		{
			$comment->author     = self::getCommentAuthorName($comment);
			$comment->permaLink  = self::getPermalink($comment);
			$comment->parentLink = self::getParentLink($comment);
		}

		// Avatar support. Set default values if plugin is not enabled.
		if (empty($comment->avatar))
		{
			$comment->avatar = Uri::base() . 'media/com_jcomments/images/no_avatar.png';
			$comment->profileLink = '';
		}

		$comment->adminPanel  = self::initialCommentData('adminPanel');
		$comment->userPanel   = self::initialCommentData('userPanel');
		$comment->commentData = self::initialCommentData('commentData');

		if ($text === false)
		{
			if ($acl->canModerate($comment))
			{
				$comment->adminPanel->set('show', true);
				$comment->adminPanel->set('button.edit', $acl->canEdit($comment));
				$comment->adminPanel->set('button.delete', $acl->canDelete($comment));
				$comment->adminPanel->set('button.publish', $acl->canPublish($comment));
				$comment->adminPanel->set('button.ip', $acl->canViewIP($comment));
				$comment->adminPanel->set('button.ban', $acl->canBan($comment));
			}

			$comment->userPanel->set('button.vote', $acl->canVote($comment));
			$comment->userPanel->set('button.quote', $acl->canQuote($comment));
			$comment->userPanel->set('button.reply', $acl->canReply($comment));
			$comment->userPanel->set('button.report', $acl->canReport($comment));

			$comment->commentData->set('showVote', $params->get('enable_voting'));
			$comment->commentData->set('showEmail', $acl->canViewEmail($comment));
			$comment->commentData->set('showHomepage', $acl->canViewHomepage($comment));
			$comment->commentData->set('showTitle', $params->get('display_title'));
			$comment->commentData->set('showAvatar', $user->authorise('comment.avatar', 'com_jcomments') && !$comment->deleted);

			$comment->object_link = $comment->object_link . '&Itemid=' . self::getItemid($app->input->getWord('view'));
			$comment->labels      = isset($comment->labels) ? json_decode($comment->labels) : null;
		}
		else
		{
			$comment->object_link = '';
			$comment->labels = null;
		}

		$dispatcher->dispatch(
			'onJCommentsCommentAfterPrepare',
			AbstractEvent::create(
				'onJCommentsCommentAfterPrepare',
				array('subject' => new \stdClass, array($comment))
			)
		);
	}

	/**
	 *
	 * Set some default values for comment object
	 *
	 * @param   string  $element  Element to get
	 *
	 * @return  Registry
	 *
	 * @since   4.1
	 */
	public static function initialCommentData(string $element): Registry
	{
		$data = array(
			'adminPanel' => new Registry(
				array(
					'show'   => false,
					'button' => array(
						'edit'    => false,
						'delete'  => false,
						'publish' => false,
						'ip'      => false,
						'ban'     => false
					)
				)
			),
			'userPanel' => new Registry(
				array(
					'button' => array(
						'vote'   => false,
						'quote'  => false,
						'reply'  => false,
						'report' => false
					)
				)
			),
			'commentData' => new Registry(
				array(
					'showVote'     => false,
					'showEmail'    => false,
					'showHomepage' => false,
					'showTitle'    => false,
					'showAvatar'   => false,
					'number'   => null
				)
			)
		);

		return $data[$element];
	}

	/**
	 * Build permanent link for comment.
	 *
	 * @param   object  $comment  Comment object
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function getPermalink($comment): string
	{
		$input = Factory::getApplication()->input;

		// Single comment have a custom links.
		if ($input->getCmd('controller') . '.' . $input->getCmd('task') == 'comment.show')
		{
			$permaLink = Route::_(
				'index.php?option=com_jcomments&task=comment.show&object_id=' . $comment->object_id
				. '&object_group=' . $comment->object_group . '&id=' . $comment->id . '&lang=' . $comment->language,
				true, 0, true
			);
		}
		else
		{
			$permaLink = Route::_(
				'index.php?option=com_jcomments&task=comments.goto&object_id=' . $comment->object_id
				. '&object_group=' . $comment->object_group . '&id=' . $comment->id . '&lang=' . $comment->language,
				true, 0, true
			) . '#comment-item-' . $comment->id;
		}

		return $permaLink;
	}

	/**
	 * Build parent link for child comment.
	 *
	 * @param   object  $comment  Comment object
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function getParentLink($comment): string
	{
		$input = Factory::getApplication()->input;

		// Single comment have custom links.
		if ($input->getCmd('controller') . '.' . $input->getCmd('task') == 'comment.show')
		{
			$parentLink = Route::_(
				'index.php?option=com_jcomments&task=comment.show&object_id=' . $comment->object_id
				. '&object_group=' . $comment->object_group . '&id=' . $comment->parent,
				true, 0, true
			);
		}
		else
		{
			$parentLink = Route::_(
				'index.php?option=com_jcomments&task=comments.goto&object_id=' . $comment->object_id
				. '&object_group=' . $comment->object_group . '&id=' . $comment->parent . '&lang=' . $comment->language,
				true, 0, true
			) . '#comment-item-' . $comment->parent;
		}

		return $parentLink;
	}

	/**
	 * Get proper itemid for menu &view=?&Itemid=? links based on view type.
	 *
	 * @param   string|null  $view     View name.
	 * @param   array        $options  Extra options.
	 *
	 * @return  integer
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function getItemid(?string $view, array $options = array()): int
	{
		$app          = Factory::getApplication();
		$lang         = $app->getLanguage();
		$itemid       = $app->input->get('Itemid', 0, 'int');
		$properItemid = $itemid;
		$menus        = $app->getMenu();
		$_options     = array(
			'link'     => is_null($view) ? 'index.php?option=com_jcomments' : 'index.php?option=com_jcomments&view=' . $view,
			'language' => $lang->getTag()
		);

		if (!empty($options))
		{
			$_options = array_merge($_options, $options);
		}

		$menu = self::searchMenu($menus, $_options);

		// Get menu ID for current link and language.
		if (!empty($menu))
		{
			$properItemid = $menu->id;
		}
		// Try to get menu for all languages.
		else
		{
			$_options['language'] = '*';
			$menu = self::searchMenu($menus, $_options);

			if (!empty($menu))
			{
				$properItemid = $menu->id;
			}
		}

		return (int) $properItemid;
	}

	/**
	 * Search for menu by parameters.
	 *
	 * @param   \Joomla\CMS\Menu\AbstractMenu  $menus    MenuItems.
	 * @param   array                          $options  Options.
	 *
	 * @return  MenuItem|MenuItem[]
	 *
	 * @since   4.1
	 */
	private static function searchMenu($menus, $options)
	{
		$menu = array();

		// Check for keyword 'params' to search by menu params.
		if (array_key_exists('params', $options))
		{
			// Save params to temporary variable and remove from search.
			$_menuParams = $options['params'];
			unset($options['params']);

			$_menus = $menus->getItems(array_keys($options), array_values($options));

			foreach ($_menus as $_menu)
			{
				$_params = $_menu->getParams()->toArray();
				$diff = array_diff_assoc($_menuParams, $_params);

				// We found menu by parameter(s)
				if (empty($diff))
				{
					$menu = $_menu;
					break;
				}
			}
		}
		else
		{
			$menu = $menus->getItems(array_keys($options), array_values($options), true);
		}

		return $menu;
	}
}

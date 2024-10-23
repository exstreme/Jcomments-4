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

namespace Joomla\Component\Jcomments\Site\Library\Jcomments;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Utilities\IpHelper;

/**
 * JComments ACL
 *
 * @since  3.0
 */
class JcommentsAcl
{
	/**
	 * @var    boolean
	 * @since  4.1
	 */
	public $canPin = false;

	/**
	 * @var    false
	 * @since  4.1
	 */
	public $canViewAvatar = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canVote = false;

	/**
	 * @var    integer
	 * @since  3.0
	 */
	public $userID = 0;

	/**
	 * Asset name
	 *
	 * @var    object
	 * @since  4.1
	 */
	private $asset = 'com_jcomments';

	/**
	 * User object
	 *
	 * @var    \Joomla\CMS\User\User
	 * @since  3.0
	 */
	protected $user;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canDelete = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canDeleteOwn = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canDeleteForMyObject = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canEdit = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canEditOwn = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canEditForMyObject = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canPublish = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canPublishForMyObject = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canViewIP = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canViewEmail = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canViewHomepage = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canQuote = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canReply = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canReport = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canBan = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $canComment = false;

	/**
	 * @var    integer
	 * @since  3.0
	 */
	protected $deleteMode = 0;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	protected $userBlocked = false;

	/**
	 * @var    string
	 * @since  4.1
	 */
	protected $userBlockedReason = '';

	/**
	 * @var    false
	 * @since  4.1
	 */
	protected $commentsLocked = false;

	/**
	 * @throws \Exception
	 * @since  3.0
	 */
	public function __construct()
	{
		$app    = Factory::getApplication();
		$config = ComponentHelper::getParams('com_jcomments');
		$user   = $app->getIdentity();

		$this->canComment            = $user->authorise('comment.comment', $this->asset);
		$this->canDelete             = $user->authorise('comment.delete', $this->asset);
		$this->canDeleteOwn          = $user->authorise('comment.delete.own', $this->asset);
		$this->canDeleteForMyObject  = $user->authorise('comment.delete.own.articles', $this->asset);
		$this->canEdit               = $user->authorise('comment.edit', $this->asset);
		$this->canEditOwn            = $user->authorise('comment.edit.own', $this->asset);
		$this->canEditForMyObject    = $user->authorise('comment.edit.own.articles', $this->asset);
		$this->canPublish            = $user->authorise('comment.publish', $this->asset);
		$this->canPublishForMyObject = $user->authorise('comment.publish.own', $this->asset);
		$this->canViewIP             = $user->authorise('comment.view.ip', $this->asset);
		$this->canViewEmail          = $user->authorise('comment.view.email', $this->asset);
		$this->canViewHomepage       = $user->authorise('comment.view.site', $this->asset);
		$this->canViewAvatar         = $user->authorise('comment.avatar', $this->asset);
		$this->canVote               = $user->authorise('comment.vote', $this->asset);
		$this->canReport             = $user->authorise('comment.report', $this->asset)
											&& $config->get('enable_reports');
		$this->canBan                = $user->authorise('comment.ban', $this->asset);
		$this->canPin                = ($user->authorise('core.edit.state', $this->asset)
											&& $config->get('max_pinned')) || $user->authorise('core.edit.state', $this->asset);
		$this->canQuote              = $user->authorise('comment.comment', 'com_jcomments')
											&& $user->authorise('comment.bbcode.quote', $this->asset);
		$this->canReply              = $user->authorise('comment.comment', $this->asset)
											&& $user->authorise('comment.reply', $this->asset);
		$this->userID                = $user->get('id');
		$this->deleteMode            = (int) $config->get('delete_mode');

		if ($config->get('enable_blacklist', 0) == 1)
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\BlacklistModel $blacklistModel */
			$blacklistModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Blacklist', 'Site', array('ignore_request' => true));
			$isBlacklisted = $blacklistModel->isBlacklisted(IpHelper::getIp(), $user);

			// Check if logged in user is not banned.
			if ($isBlacklisted['block'])
			{
				$this->userBlocked       = true;
				$this->userBlockedReason = $isBlacklisted['reason'];
				$this->canQuote          = false;
				$this->canReply          = false;
				$this->canVote           = false;
				$this->canBan            = false;
			}
		}

		$this->user = &$user;
	}

	/**
	 * Check if need to use autocensor on current user group.
	 *
	 * @return boolean   True if user must be autocensored.
	 *
	 * @since  4.0
	 */
	public function enableAutocensor(): bool
	{
		$config       = ComponentHelper::getParams('com_jcomments');
		$userGroups   = $this->user->getAuthorisedGroups();
		$censorGroups = $config->get('enable_autocensor');

		foreach ($userGroups as $userGroup)
		{
			if (in_array($userGroup, $censorGroups))
			{
				// Current usergroup must be censored.
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if need to see policy message. This parameter is located on the "Comment form" tab -> "Show policies".
	 *
	 * @return  boolean
	 *
	 * @since   4.0
	 */
	public function showPolicy(): bool
	{
		$config       = ComponentHelper::getParams('com_jcomments');
		$userGroups   = $this->user->getAuthorisedGroups();
		$censorGroups = $config->get('show_policy');

		foreach ($userGroups as $userGroup)
		{
			if (in_array($userGroup, $censorGroups))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user allowed to see custom bbcode button.
	 *
	 * @param   string  $buttonACL  Comma separated string with usergroup IDs.
	 *
	 * @return  boolean   True if can see.
	 *
	 * @since   4.0
	 */
	public function enableCustomBBCode(string $buttonACL): bool
	{
		$userGroups = $this->user->getAuthorisedGroups();

		foreach ($userGroups as $userGroup)
		{
			if (in_array($userGroup, explode(',', $buttonACL)))
			{
				// Can see the button.
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if comment is bein edited by someone else.
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean  True if edit, false otherwise
	 *
	 * @since   3.0
	 */
	public function isCheckout($comment): bool
	{
		if (!is_null($comment))
		{
			return $comment->checked_out && $comment->checked_out != $this->userID;
		}

		return false;
	}

	/**
	 * Test if user is owner of the object
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function isObjectOwner($comment): bool
	{
		if (is_null($comment))
		{
			return false;
		}
		else
		{
			$objectOwner = $this->userID
				? (property_exists($comment, 'object_owner')
					? $comment->object_owner
					: ObjectHelper::getObjectField($comment, 'object_owner', $comment->object_id, $comment->object_group))
				: 0;

			return $this->userID && $this->userID == $objectOwner;
		}
	}

	/**
	 * Check if user can delete comment
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canDelete($comment): bool
	{
		return ($this->canDelete || ($this->canDeleteForMyObject && $this->isObjectOwner($comment))
				|| ($this->canDeleteOwn && ($comment->userid == $this->userID)))
			&& (!$this->isCheckout($comment)) && (!$comment->deleted || $this->deleteMode == 0);
	}

	/**
	 * Check if user can edit comment
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canEdit($comment): bool
	{
		return ($this->canEdit || ($this->canEditForMyObject && $this->isObjectOwner($comment))
				|| ($this->canEditOwn && ($comment->userid == $this->userID)))
			&& (!$this->isCheckout($comment)) && (!$comment->deleted);
	}

	/**
	 * Check if user can publish comment
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canPublish($comment = null): bool
	{
		if (is_null($comment))
		{
			return ($this->canPublish || ($this->canPublishForMyObject && $this->isObjectOwner($comment)));
		}
		else
		{
			return ($this->canPublish || ($this->canPublishForMyObject && $this->isObjectOwner($comment)))
				&& (!$this->isCheckout($comment)) && (!$comment->deleted);
		}
	}

	/**
	 * Check if user can publish for certain object.
	 *
	 * @param   int|null     $objectID     Object ID
	 * @param   string|null  $objectGroup  Object group
	 * @param   mixed        $object       Object information
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canPublishForObject(?int $objectID, ?string $objectGroup, $object = null): bool
	{
		return $this->userID
			&& $this->canPublishForMyObject
			&& $this->userID == ObjectHelper::getObjectField($object, 'object_owner', $objectID, $objectGroup);
	}

	/**
	 * Check if user can view IP
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canViewIP($comment = null): bool
	{
		if (is_null($comment))
		{
			return $this->canViewIP;
		}
		else
		{
			return $this->canViewIP && (!empty($comment->ip)) && (!$comment->deleted);
		}
	}

	/**
	 * Check if user can view comment form
	 *
	 * @param   boolean  $sendHeader  Send HTTP header
	 * @param   boolean  $onlyText    Return only message text
	 *
	 * @return  string|true
	 *
	 * @since   4.1
	 */
	public function canViewForm(bool $sendHeader = false, bool $onlyText = false)
	{
		$app    = Factory::getApplication();
		$lang   = $app->getLanguage();
		$params = ComponentHelper::getParams('com_jcomments');

		if ($params->get('comments_locked'))
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($params->get('messages_fields'), 'message_locked', $lang->getTag());

			if ($sendHeader)
			{
				$app->setHeader('status', 403, true);
			}

			if ($message != '')
			{
				return JcommentsComponentHelper::renderMessage(nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')), 'warning', $onlyText);
			}

			return JcommentsComponentHelper::renderMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error', $onlyText);
		}

		$userState = $this->getUserBlockState();

		if ($userState['state'])
		{
			$message = JcommentsText::getMessagesBasedOnLanguage($params->get('messages_fields'), 'message_banned', $lang->getTag());
			$reason = !empty($userState['reason']) ? '<br>' . Text::_('REPORT_REASON') . ': ' . $userState['reason'] : '';

			if ($sendHeader)
			{
				$app->setHeader('status', 403, true);
			}

			return JcommentsComponentHelper::renderMessage(nl2br($message . $reason), 'warning', $onlyText);
		}

		return true;
	}

	/**
	 * Check if user can view email
	 *
	 * @param   mixed  $email  User email from comment
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canViewEmail($email = null): bool
	{
		if (is_null($email))
		{
			return $this->canViewEmail;
		}
		else
		{
			return $this->canViewEmail && ($email != '');
		}
	}

	/**
	 *
	 * Check if user can view homepage.
	 *
	 * @param   mixed  $url  URL.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canViewHomepage($url = null): bool
	{
		if (is_null($url))
		{
			return $this->canViewHomepage;
		}
		else
		{
			return $this->canViewHomepage && ($url != '');
		}
	}

	/**
	 *
	 * Check if user can quote.
	 *
	 * @param   mixed  $comment  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canQuote($comment = null): bool
	{
		if (is_null($comment))
		{
			return $this->canQuote && !$this->commentsLocked;
		}
		else
		{
			return $this->canQuote && !$this->commentsLocked && (!$comment->deleted);
		}
	}

	/**
	 *
	 * Check if user can reply.
	 *
	 * @param   mixed  $comment  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canReply($comment = null): bool
	{
		if (is_null($comment))
		{
			return $this->canReply && !$this->commentsLocked;
		}
		else
		{
			return $this->canReply && !$this->commentsLocked && (!$comment->deleted);
		}
	}

	/**
	 * Check if user can subscribe to comments.
	 *
	 * @return  boolean
	 *
	 * @since   4.0
	 */
	public function canSubscribe(): bool
	{
		return ($this->user->get('id') && $this->user->authorise('comment.subscribe', $this->asset));
	}

	/**
	 *
	 * Check if user can vote.
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canVote($comment): bool
	{
		if ($this->userID)
		{
			return ($this->canVote && $comment->userid != $this->userID && !isset($comment->voted) && (!$comment->deleted));
		}
		else
		{
			return ($this->canVote && $comment->ip != IpHelper::getIp() && !isset($comment->voted) && (!$comment->deleted));
		}
	}

	/**
	 *
	 * Check if user can report bad comment.
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canReport($comment = null): bool
	{
		if (is_null($comment))
		{
			return $this->canReport;
		}
		else
		{
			return $this->canReport && (!$comment->deleted);
		}
	}

	/**
	 *
	 * Check if user can do some moderator actions.
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canModerate($comment): bool
	{
		return ($this->canEdit($comment) || $this->canDelete($comment) || $this->canPublish($comment)
			|| $this->canViewIP($comment) || $this->canBan($comment)) && (!$comment->deleted || $this->deleteMode == 0);
	}

	/**
	 *
	 * Check if user can ban.
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canBan($comment = null): bool
	{
		if (is_null($comment))
		{
			return $this->canBan;
		}
		else
		{
			return $this->canBan && (!$comment->deleted);
		}
	}

	/**
	 *
	 * Check if user can add comment.
	 *
	 * @param   \Joomla\CMS\User\User|null  $user  User object
	 *
	 * @return  boolean
	 *
	 * @since   4.1
	 */
	public function canComment($user = null): bool
	{
		return is_null($user) ? $this->canComment : $user->authorise('comment.comment', $this->asset);
	}

	/**
	 *
	 * Check if user can pin.
	 *
	 * @param   mixed  $comment  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canPin($comment = null): bool
	{
		if (is_null($comment))
		{
			return $this->canPin;
		}
		else
		{
			return $this->canPin && (!$this->isCheckout($comment));
		}
	}

	/**
	 *
	 * Getter for user block state and reason.
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public function getUserBlockState(): array
	{
		return array('state' => $this->userBlocked, 'reason' => $this->userBlockedReason);
	}

	/**
	 * Method to return a list of view levels for which the user is authorised.
	 *
	 * @param   integer  $uid  User Id
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public function getAuthorisedViewLevels(int $uid): array
	{
		// B/C access levels
		$viewLevels = array_merge(
			array(0),
			Access::getAuthorisedViewLevels($uid)
		);

		return array_unique($viewLevels);
	}

	public function setCommentsLocked($value)
	{
		$this->commentsLocked = $value;
		$this->canComment     = $this->canComment && !$this->commentsLocked;
		$this->canQuote       = $this->canQuote && !$this->commentsLocked;
		$this->canReply       = $this->canReply && !$this->commentsLocked;
	}
}

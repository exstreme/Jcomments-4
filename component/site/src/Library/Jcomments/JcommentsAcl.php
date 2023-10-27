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
	 * User object
	 *
	 * @var    object
	 * @since  3.0
	 */
	protected $user;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canDelete = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canDeleteOwn = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canDeleteForMyObject = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canEdit = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canEditOwn = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canEditForMyObject = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canPublish = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canPublishForMyObject = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canViewIP = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canViewEmail = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canViewHomepage = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canQuote = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canReply = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canVote = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canReport = false;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canBan = false;

	/**
	 * @var    integer
	 * @since  3.0
	 */
	public $userID = 0;

	/**
	 * @var    integer
	 * @since  3.0
	 */
	protected $deleteMode = 0;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $userBlocked = false;

	/**
	 * @var    string
	 * @since  4.1
	 */
	public $userBlockedReason = '';

	/**
	 * @throws \Exception
	 * @since  3.0
	 */
	public function __construct()
	{
		$app    = Factory::getApplication();
		$config = ComponentHelper::getParams('com_jcomments');
		$user   = $app->getIdentity();

		$this->canDelete             = $user->authorise('comment.delete', 'com_jcomments');
		$this->canDeleteOwn          = $user->authorise('comment.delete.own', 'com_jcomments');
		$this->canDeleteForMyObject  = $user->authorise('comment.delete.own.articles', 'com_jcomments');
		$this->canEdit               = $user->authorise('comment.edit', 'com_jcomments');
		$this->canEditOwn            = $user->authorise('comment.edit.own', 'com_jcomments');
		$this->canEditForMyObject    = $user->authorise('comment.edit.own.articles', 'com_jcomments');
		$this->canPublish            = $user->authorise('comment.publish', 'com_jcomments');
		$this->canPublishForMyObject = $user->authorise('comment.publish.own', 'com_jcomments');
		$this->canViewIP             = $user->authorise('comment.view.ip', 'com_jcomments');
		$this->canViewEmail          = $user->authorise('comment.view.email', 'com_jcomments');
		$this->canViewHomepage       = $user->authorise('comment.view.site', 'com_jcomments');
		$this->canVote               = $user->authorise('comment.vote', 'com_jcomments');
		$this->canReport             = $user->authorise('comment.report', 'com_jcomments')
											&& $config->get('enable_reports');
		$this->canBan                = $user->authorise('comment.ban', 'com_jcomments');
		$this->canQuote              = $user->authorise('comment.comment', 'com_jcomments')
											&& $user->authorise('comment.bbcode.quote', 'com_jcomments');
		$this->canReply              = $user->authorise('comment.comment', 'com_jcomments')
											&& $user->authorise('comment.reply', 'com_jcomments');
		$this->userID                = $user->get('id');
		$this->deleteMode            = (int) $config->get('delete_mode');
		$this->commentsLocked        = false;

		if ($config->get('enable_blacklist', 0) == 1)
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\BlacklistModel $blacklistModel */
			$blacklistModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Blacklist', 'Site', array('ignore_request' => true));
			$isBlacklisted = $blacklistModel->isBlacklisted(IpHelper::getIp(), $user);

			// Check of logged in user is not banned.
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
	 * Check if need to show 'Term of use' on current user group.
	 *
	 * @return  boolean  True if must to show. Always false for Super User
	 *
	 * @since   4.0
	 */
	public function showTermsOfUse(): bool
	{
		return !$this->user->authorise('comment.terms_of_use', 'com_jcomments');
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
	 * Check if user is blocked.
	 *
	 * @param   mixed  $ip   IP address.
	 * @param   mixed  $uid  User object or user ID.
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function isUserBlocked($ip = null, $uid = null): array
	{
		$app    = Factory::getApplication();
		$config = ComponentHelper::getParams('com_jcomments');

		if (empty($ip) && empty($uid))
		{
			return array('block' => $this->userBlocked, 'reason' => $this->userBlockedReason);
		}

		if ($config->get('enable_blacklist', 0) == 1)
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\BlacklistModel $blacklistModel */
			$blacklistModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Blacklist', 'Site', array('ignore_request' => true));

			return $blacklistModel->isBlacklisted($ip, $uid);
		}

		return array('block' => false, 'reason' => $this->userBlockedReason);
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
	public function isLocked($comment): bool
	{
		if (isset($comment) && ($comment != null))
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
					: ObjectHelper::getObjectField($comment, 'userid', $comment->object_id, $comment->object_group))
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
			&& (!$this->isLocked($comment)) && (!$comment->deleted || $this->deleteMode == 0);
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
			&& (!$this->isLocked($comment)) && (!$comment->deleted);
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
				&& (!$this->isLocked($comment)) && (!$comment->deleted);
		}
	}

	/**
	 * Check if user can publish for certain object.
	 *
	 * @param   integer  $objectID     Object ID
	 * @param   string   $objectGroup  Object group
	 * @param   mixed    $object       Object information
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canPublishForObject(int $objectID, string $objectGroup, $object = null): bool
	{
		return $this->userID
			&& $this->canPublishForMyObject
			&& $this->userID == ObjectHelper::getObjectField($object, 'userid', $objectID, $objectGroup);
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
			return $this->canViewIP && ($comment->ip != '') && (!$comment->deleted);
		}
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
		return ($this->user->get('id') && $this->user->authorise('comment.subscribe', 'com_jcomments'));
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

		// TODO Line bellow for that?
		// $this->canComment = $this->canComment && !$this->commentsLocked;

		$this->canQuote = $this->canQuote && !$this->commentsLocked;
		$this->canReply = $this->canReply && !$this->commentsLocked;
	}
}

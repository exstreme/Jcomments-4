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
use Joomla\CMS\Factory;

/**
 * JComments ACL
 *
 * @since  3.0
 */
class JCommentsACL
{
	/**
	 * User object
	 *
	 * @var    object
	 * @since  3.0
	 */
	protected $user;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canDelete = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canDeleteOwn = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canDeleteForMyObject = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canEdit = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canEditOwn = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canEditForMyObject = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canPublish = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canPublishForMyObject = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canViewIP = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canViewEmail = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canViewHomepage = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canQuote = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canReply = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canVote = 0;

	/**
	 * @var    boolean|integer
	 * @since  3.0
	 */
	public $canReport = 0;

	/**
	 * @var    boolean
	 * @since  3.0
	 */
	public $canBan = false;

	/**
	 * @var    integer
	 * @since  3.0
	 */
	protected $userID = 0;

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
	 * @throws Exception
	 * @since  3.0
	 */
	public function __construct()
	{
		$config = ComponentHelper::getParams('com_jcomments');
		$user   = Factory::getApplication()->getIdentity();

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
											&& (int) $config->get('enable_reports')
											&& ($config->get('enable_notification') != 0
												|| $config->get('notification_type', 2) == true);
		$this->canBan                = $user->authorise('comment.ban', 'com_jcomments');
		$this->canQuote              = $user->authorise('comment.comment', 'com_jcomments')
											&& $user->authorise('comment.bbcode.quote', 'com_jcomments');
		$this->canReply              = $user->authorise('comment.comment', 'com_jcomments')
											&& $user->authorise('comment.reply', 'com_jcomments')
											&& $config->get('template_view') == 'tree';
		$this->userID                = $user->get('id');
		$this->userBlocked           = false;
		$this->deleteMode            = (int) $config->get('delete_mode');
		$this->commentsLocked        = false;

		if ((int) $config->get('enable_blacklist', 0) == 1)
		{
			$options           = array();
			$options['ip']     = $_SERVER['REMOTE_ADDR'];
			$options['userid'] = $user->get('id');

			if (!JCommentsSecurity::checkBlacklist($options))
			{
				$this->userBlocked = true;
				$this->canQuote    = 0;
				$this->canReply    = 0;
				$this->canVote     = 0;
				$this->canBan      = false;
			}
			else
			{
				$this->canBan = $user->authorise('comment.ban', 'com_jcomments');
			}
		}

		$this->user = $user;
	}

	/**
	 * Check if need to use autocensor on current user group.
	 *
	 * @return boolean   True if user must be autocensored.
	 *
	 * @since  4.0
	 */
	public function enableAutocensor()
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
	 * Check if need to use autocensor on current user group.
	 *
	 * @return  boolean   True if user must be autocensored.
	 *
	 * @since   4.0
	 */
	public function showTermsOfUse()
	{
		return !$this->user->authorise('comment.terms_of_use', 'com_jcomments');
	}

	/**
	 * Check if need to see plicy message.
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
	public function enableCustomBBCode($buttonACL)
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
	 * Check if user is blocked
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function getUserBlocked()
	{
		return $this->userBlocked;
	}

	public function isLocked($object)
	{
		if (isset($object) && ($object != null))
		{
			return ($object->checked_out && $object->checked_out != $this->userID) ? 1 : 0;
		}

		return 0;
	}

	public function isDeleted($object)
	{
		if (isset($object) && ($object != null))
		{
			return $object->deleted ? 1 : 0;
		}

		return 0;
	}

	public function isObjectOwner($object)
	{
		if (is_null($object))
		{
			return false;
		}
		else
		{
			$objectOwner = $this->userID ? JCommentsObject::getOwner($object->object_id, $object->object_group) : 0;

			return $this->userID ? ($this->userID == $objectOwner) : false;
		}
	}

	public function canDelete($object)
	{
		return (($this->canDelete || ($this->canDeleteForMyObject && $this->isObjectOwner($object))
				|| ($this->canDeleteOwn && ($object->userid == $this->userID)))
			&& (!$this->isLocked($object)) && (!$this->isDeleted($object) || $this->deleteMode == 0)) ? 1 : 0;
	}

	public function canEdit($object)
	{
		return (($this->canEdit || ($this->canEditForMyObject && $this->isObjectOwner($object))
				|| ($this->canEditOwn && ($object->userid == $this->userID)))
			&& (!$this->isLocked($object)) && (!$this->isDeleted($object))) ? 1 : 0;
	}

	public function canPublish($object = null)
	{
		return (($this->canPublish || ($this->canPublishForMyObject && $this->isObjectOwner($object)))
			&& (!$this->isLocked($object)) && (!$this->isDeleted($object))) ? 1 : 0;
	}

	public function canPublishForObject($objectID, $objectGroup)
	{
		return ($this->userID
			&& $this->canPublishForMyObject
			&& $this->userID == JCommentsObject::getOwner($objectID, $objectGroup)) ? 1 : 0;
	}

	public function canViewIP($object = null)
	{
		if (is_null($object))
		{
			return ($this->canViewIP) ? 1 : 0;
		}
		else
		{
			return ($this->canViewIP && ($object->ip != '') && (!$this->isDeleted($object))) ? 1 : 0;
		}
	}

	public function canViewEmail($object = null)
	{
		if (is_null($object))
		{
			return ($this->canViewEmail) ? 1 : 0;
		}
		else
		{
			return ($this->canViewEmail && ($object->email != '')) ? 1 : 0;
		}
	}

	public function canViewHomepage($object = null)
	{
		if (is_null($object))
		{
			return ($this->canViewHomepage) ? 1 : 0;
		}
		else
		{
			return ($this->canViewHomepage && ($object->homepage != '')) ? 1 : 0;
		}
	}

	public function canQuote($object = null)
	{
		if (is_null($object))
		{
			return $this->canQuote && !$this->commentsLocked;
		}
		else
		{
			return ($this->canQuote && !$this->commentsLocked && (!isset($object->_disable_quote)) && (!$this->isDeleted($object))) ? 1 : 0;
		}
	}

	public function canReply($object = null)
	{
		if (is_null($object))
		{
			return $this->canReply && !$this->commentsLocked;
		}
		else
		{
			return ($this->canReply && !$this->commentsLocked && (!isset($object->_disable_reply)) && (!$this->isDeleted($object))) ? 1 : 0;
		}
	}

	public function canVote($object)
	{
		if ($this->userID)
		{
			return ($this->canVote && $object->userid != $this->userID && !isset($object->voted) && (!$this->isDeleted($object)));
		}
		else
		{
			return ($this->canVote && $object->ip != $_SERVER['REMOTE_ADDR'] && !isset($object->voted) && (!$this->isDeleted($object)));
		}
	}

	public function canReport($object = null)
	{
		if (is_null($object))
		{
			return $this->canReport;
		}
		else
		{
			return ($this->canReport && (!isset($object->_disable_report)) && (!$this->isDeleted($object))) ? 1 : 0;
		}
	}

	public function canModerate($object)
	{
		return ($this->canEdit($object) || $this->canDelete($object) || $this->canPublish($object)
			|| $this->canViewIP($object) || $this->canBan($object)) && (!$this->isDeleted($object) || $this->deleteMode == 0);
	}

	/**
	 *
	 * Check if user can ban comment.
	 *
	 * @param   mixed  $item  Item ID or null.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canBan($item = null)
	{
		if (is_null($item))
		{
			return $this->canBan;
		}
		else
		{
			return $this->canBan && (!$this->isDeleted($item));
		}
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
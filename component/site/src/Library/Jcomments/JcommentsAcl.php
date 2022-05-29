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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;

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
											&& $config->get('enable_reports')
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

		if ($config->get('enable_blacklist', 0) == 1)
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\CommentModel $commentModel */
			$commentModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Comment', 'Site', array('ignore_request' => true));

			if ($commentModel->isBlacklisted(self::getIP(), $user))
			{
				$this->userBlocked = true;
				$this->canQuote    = false;
				$this->canReply    = false;
				$this->canVote     = false;
				$this->canBan      = false;
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
	public function isUserBlocked(): bool
	{
		return $this->userBlocked;
	}

	/**
	 * Check if comment is bein edited by someone else.
	 *
	 * @param   object  $object  Comment item.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function isLocked($object): bool
	{
		if (isset($object) && ($object != null))
		{
			return $object->checked_out && $object->checked_out != $this->userID;
		}

		return false;
	}

	/**
	 * Test if user is owner of the object
	 *
	 * @param   object  $object  Comment object
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function isObjectOwner($object): bool
	{
		if (is_null($object))
		{
			return false;
		}
		else
		{
			$objectOwner = $this->userID ? ObjectHelper::getObjectField('userid', $object->object_id, $object->object_group) : 0;

			return $this->userID && $this->userID == $objectOwner;
		}
	}

	/**
	 * Check if user can delete comment
	 *
	 * @param   object  $object  Comment object
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canDelete($object): bool
	{
		return ($this->canDelete || ($this->canDeleteForMyObject && $this->isObjectOwner($object))
				|| ($this->canDeleteOwn && ($object->userid == $this->userID)))
			&& (!$this->isLocked($object)) && (!$object->deleted || $this->deleteMode == 0);
	}

	/**
	 * Check if user can edit comment
	 *
	 * @param   object  $object  Comment object
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canEdit($object): bool
	{
		return ($this->canEdit || ($this->canEditForMyObject && $this->isObjectOwner($object))
				|| ($this->canEditOwn && ($object->userid == $this->userID)))
			&& (!$this->isLocked($object)) && (!$object->deleted);
	}

	/**
	 * Check if user can publish comment
	 *
	 * @param   object  $object  Comment object
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canPublish($object = null): bool
	{
		return ($this->canPublish || ($this->canPublishForMyObject && $this->isObjectOwner($object)))
			&& (!$this->isLocked($object)) && (!$object->deleted);
	}

	/**
	 * Check if user can publish for certain object.
	 *
	 * @param   integer  $objectID     Object ID
	 * @param   string   $objectGroup  Object group
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canPublishForObject(int $objectID, string $objectGroup): bool
	{
		return $this->userID
			&& $this->canPublishForMyObject
			&& $this->userID == ObjectHelper::getObjectField('userid', $objectID, $objectGroup);
	}

	/**
	 * Check if user can view IP
	 *
	 * @param   object|null  $object  Comment object
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canViewIP($object = null): bool
	{
		if (is_null($object))
		{
			return $this->canViewIP;
		}
		else
		{
			return $this->canViewIP && ($object->ip != '') && (!$object->deleted);
		}
	}

	/**
	 * Check if user can view email
	 *
	 * @param   string|null  $email  User email from comment
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
	 * @param   string|null  $url  URL.
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
	 * @param   object|null  $object  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canQuote($object = null): bool
	{
		if (is_null($object))
		{
			return $this->canQuote && !$this->commentsLocked;
		}
		else
		{
			return $this->canQuote && !$this->commentsLocked && (!$object->deleted);
		}
	}

	/**
	 *
	 * Check if user can reply.
	 *
	 * @param   object|null  $object  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canReply($object = null): bool
	{
		if (is_null($object))
		{
			return $this->canReply && !$this->commentsLocked;
		}
		else
		{
			return $this->canReply && !$this->commentsLocked && (!$object->deleted);
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
	 * @param   object  $object  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canVote($object): bool
	{
		if ($this->userID)
		{
			return ($this->canVote && $object->userid != $this->userID && !isset($object->voted) && (!$object->deleted));
		}
		else
		{
			return ($this->canVote && $object->ip != self::getIP() && !isset($object->voted) && (!$object->deleted));
		}
	}

	/**
	 *
	 * Check if user can report bad comment.
	 *
	 * @param   object|null  $object  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canReport($object = null): bool
	{
		if (is_null($object))
		{
			return $this->canReport;
		}
		else
		{
			return $this->canReport && (!$object->deleted);
		}
	}

	/**
	 *
	 * Check if user can do some moderator actions.
	 *
	 * @param   object  $object  Comment object.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canModerate($object)
	{
		return ($this->canEdit($object) || $this->canDelete($object) || $this->canPublish($object)
			|| $this->canViewIP($object) || $this->canBan($object)) && (!$object->deleted || $this->deleteMode == 0);
	}

	/**
	 *
	 * Check if user can ban.
	 *
	 * @param   object|null  $item  Comment item or null.
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canBan($item = null): bool
	{
		if (is_null($item))
		{
			return $this->canBan;
		}
		else
		{
			return $this->canBan && (!$item->deleted);
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

	/**
	 * Returns the most accurate IP address available for the current user, in
	 * IPv4 format. This could be the proxy client's IP address.
	 *
	 * @return string IP address in presentation format.
	 * @see https://matomo.org/faq/how-to-install/faq_98/
	 */
	public function getIP()
	{
		\JLoader::registerNamespace('Matomo\Network', JPATH_ROOT . '/components/com_jcomments/src/Library/Matomo/network/src');
		\JLoader::registerNamespace('Piwik', JPATH_ROOT . '/components/com_jcomments/src/Library/Matomo');

		$options = array(
			'proxy_client_headers' => array(
				'HTTP_CF_CONNECTING_IP',
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR'
			),
			'proxy_ips' => array(),
			'proxy_ip_read_last_in_list' => false
		);

		return \Piwik\IP::getIpFromHeader($options);
	}
}

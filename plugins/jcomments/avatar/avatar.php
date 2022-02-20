<?php
/**
 * JComments avatar plugin - Enable avatar support for JComments
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Class to add support for avatar(s) in JComments
 *
 * @since  1.5
 */
class PlgJcommentsAvatar extends CMSPlugin
{
	/**
	 * List with commenting users IDs except guests.
	 *
	 * @var   array
	 * @since 4.0
	 */
	private $users = array();

	/**
	 * Prepare avatar for single comment
	 *
	 * @param   array  $comment  Array with comment object
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onPrepareAvatar(array &$comment)
	{
		$comments    = array();
		$comments[0] = &$comment;
		$this->onPrepareAvatars($comments);
	}

	/**
	 * Prepare avatars for comments list
	 *
	 * @param   array  $comments  Comments list
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onPrepareAvatars(array &$comments)
	{
		if ($this->params->get('avatar_type') == 'default')
		{
			foreach ($comments as $comment)
			{
				$comment->profileLink       = '';
				$comment->avatar            = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
				$comment->profileLinkTarget = $this->params->get('avatar_link_target');
			}
		}
		else
		{
			$method = 'get' . ucfirst($this->params->get('avatar_type')) . 'Image';

			if (method_exists($this, $method))
			{
				foreach ($comments as $comment)
				{
					if ($comment->userid != 0)
					{
						$this->users[] = (int) $comment->userid;
					}
				}

				$this->users = array_unique($this->users);

				$this->$method($comments);
			}
		}
	}

	/**
	 * Get image URL from Community Builder
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected function getComprofilerImage(array $comments)
	{
		if (count($this->users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('user_id', 'avatar')))
				->from($db->qn('#__comprofiler'))
				->where($db->qn('user_id') . ' IN (' . implode(',', $this->users) . ')')
				->where($db->qn('avatarapproved') . ' = 1');

			try
			{
				$db->setQuery($query);
				$avatars = $db->loadObjectList('user_id');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}
		}

		$itemId = self::getItemid('index.php?option=com_comprofiler&task=profile');

		if (empty($itemId))
		{
			$itemId = self::getItemid('index.php?option=com_comprofiler&task=userslist');

			if (empty($itemId))
			{
				$itemId = self::getItemid('index.php?option=com_comprofiler');
			}
		}

		foreach ($comments as $comment)
		{
			$uid = (int) $comment->userid;

			$comment->profileLink       = $uid ? Route::_('index.php?option=com_comprofiler&task=userProfile&user=' . $uid . $itemId) : '';
			$comment->profileLinkTarget = $this->params->get('avatar_link_target');

			if (isset($avatars[$uid]) && !empty($avatars[$uid]->avatar))
			{
				$thumbnail       = strpos($avatars[$uid]->avatar, 'gallery') === 0 ? '' : 'tn';
				$comment->avatar = Uri::base() . 'images/comprofiler/' . $thumbnail . $avatars[$uid]->avatar;
			}
			else
			{
				$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
			}
		}
	}

	/**
	 * Get image URL from com_contact
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected function getContactsImage(array $comments)
	{
		if (count($this->users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db      = Factory::getContainer()->get('DatabaseDriver');
			$nowDate = Factory::getDate()->toSql();

			$query = $db->getQuery(true)
				->select(
					array(
						$db->qn('cd.user_id', 'userid'), $db->qn('cd.image', 'avatar'),
						'CASE WHEN CHAR_LENGTH(cd.alias) THEN CONCAT_WS(":", cd.id, cd.alias) ELSE cd.id END as slug',
						'CASE WHEN CHAR_LENGTH(cat.alias) THEN CONCAT_WS(":", cat.id, cat.alias) ELSE cat.id END as catslug'
					)
				)
				->from($db->qn('#__contact_details', 'cd'))
				->innerJoin($db->qn('#__categories', 'cat'), 'cd.catid = cat.id')
				->where($db->qn('cd.user_id') . ' IN (' . implode(',', $this->users) . ')');

			// Select only published and not expired
			$query->where('cd.published = 1')
				->where('(' . $db->quoteName('cd.publish_up') . ' IS NULL OR ' . $db->quoteName('cd.publish_up') . ' <= :publish_up)')
				->where('(' . $db->quoteName('cd.publish_down') . ' IS NULL OR ' . $db->quoteName('cd.publish_down') . ' >= :publish_down)')
				->bind(':publish_up', $nowDate)
				->bind(':publish_down', $nowDate);

			try
			{
				$db->setQuery($query);
				$avatars = $db->loadObjectList('userid');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}
		}

		foreach ($comments as $comment)
		{
			$uid = (int) $comment->userid;

			if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '')
			{
				$comment->profileLink = $uid
					? Route::_('index.php?option=com_contact&view=contact&id=' . $avatars[$uid]->slug . '&catid=' . $avatars[$uid]->catslug)
					: '';
				$comment->avatar = Uri::base() . '/' . $avatars[$uid]->avatar;
			}
			else
			{
				$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
			}

			$comment->profileLinkTarget = $this->params->get('avatar_link_target');
		}
	}

	/**
	 * Get default image to be used as the default avatar if some user has no avatar or avatar source is not set.
	 *
	 * @param   string  $type  Avatar default type
	 *
	 * @return  string
	 *
	 * @since   4.0
	 */
	protected function getDefaultImage(string $type = 'default'): string
	{
		switch ($type)
		{
			case 'custom':
				return Uri::base() . ltrim($this->params->get('avatar_custom_default_avatar'), '/');
			default:
				return Uri::base() . 'media/com_jcomments/images/no_avatar.png';
		}
	}

	/**
	 * Get gravatar URL
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected function getGravatarImage(array $comments)
	{
		$options = $this->params->get('gravatar_options', '');

		if (!empty($options))
		{
			$options = parse_ini_string($options);

			if (!isset($options['d']))
			{
				$options['d'] = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
			}

			$query = http_build_query($options);
		}

		foreach ($comments as $comment)
		{
			$emailHash                  = md5(strtolower($comment->email));
			$comment->profileLink       = '';
			$comment->avatar            = 'https://www.gravatar.com/avatar/' . $emailHash . (!empty($options) ? '?' . $query : '');
			$comment->profileLinkTarget = $this->params->get('avatar_link_target');
		}
	}

	/**
	 * Get an itemid for given link.
	 *
	 * @param   string  $link  Link
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected static function getItemid(string $link): string
	{
		$menu = Factory::getApplication()->getMenu();
		$item = $menu->getItems('link', $link, true);

		$id = null;

		if (is_array($item))
		{
			if (count($item) > 0)
			{
				$id = $item[0]->id;
			}
		}
		elseif (is_object($item))
		{
			$id = $item->id;
		}

		return ($id !== null) ? '&Itemid=' . $id : '';
	}

	/**
	 * Get image URL from com_kunena
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected function getKunenaImage(array $comments)
	{
		if (count($this->users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('userid', 'avatar')))
				->from($db->qn('#__kunena_users'))
				->where($db->qn('userid') . ' IN (' . implode(',', $this->users) . ')');

			try
			{
				$db->setQuery($query);
				$avatars = $db->loadObjectList('userid');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}
		}

		$itemId = self::getItemid('index.php?option=com_kunena&view=home');

		if (empty($itemId))
		{
			$itemId = self::getItemid('index.php?option=com_kunena&view=user');

			if (empty($itemId))
			{
				$itemId = self::getItemid('index.php?option=com_kunena&view=category&layout=list');
			}
		}

		// Folders below is hardcoded in Kunena
		$avatarFolder = JPATH_SITE . '/media/kunena/avatars/';
		$avatarLink   = Uri::base() . 'media/kunena/avatars/';

		foreach ($comments as $comment)
		{
			$uid = (int) $comment->userid;

			$comment->profileLink = $uid
				? \Kunena\Forum\Libraries\Route\KunenaRoute::_('index.php?option=com_kunena&view=user&userid=' . $uid . '&Itemid=' . $itemId)
				: '';

			$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));

			if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '')
			{
				if (is_file($avatarFolder . $avatars[$uid]->avatar))
				{
					$comment->avatar = $avatarLink . $avatars[$uid]->avatar;
				}
			}

			$comment->profileLinkTarget = $this->params->get('avatar_link_target');
		}
	}

	/**
	 * Get image URL from com_phocagallery
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected function getPhocagalleryImage(array $comments)
	{
		if (count($this->users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('userid', 'avatar')))
				->from($db->qn('#__phocagallery_user'))
				->where($db->qn('userid') . ' IN (' . implode(',', $this->users) . ')');

			try
			{
				$db->setQuery($query);
				$avatars = $db->loadObjectList('userid');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}
		}

		$avatarFolder = JPATH_ROOT . '/images/phocagallery/avatars/thumbs/phoca_thumb_s_';
		$avatarLink   = 'images/phocagallery/avatars/thumbs/phoca_thumb_s_';

		foreach ($comments as $comment)
		{
			$uid = (int) $comment->userid;

			$comment->profileLink = '';
			$comment->avatar      = $this->getDefaultImage($this->params->get('avatar_default_avatar'));

			if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '')
			{
				if (is_file($avatarFolder . $avatars[$uid]->avatar))
				{
					$comment->avatar = $avatarLink . $avatars[$uid]->avatar;
				}
			}

			$comment->profileLinkTarget = $this->params->get('avatar_link_target');
		}
	}

	/**
	 * Get image URL from com_easysocial
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	protected function getEasysocialImage(array $comments)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/easysocial.php';

		$jConfig = ES::jConfig();
		$easysocialurlPlugin = PluginHelper::isEnabled('system', 'easysocialurl');

		foreach ($comments as $comment)
		{
			$uid                        = (int) $comment->userid;
			$esUser                     = ES::user($uid);
			$comment->profileLink       = '';
			$comment->avatar            = $esUser->getAvatar(SOCIAL_AVATAR_MEDIUM);
			$comment->profileLinkTarget = $this->params->get('avatar_link_target');

			if ($esUser->isSiteAdmin() && ($esUser->isBlock() || $esUser->hasCommunityAccess()) || $esUser->id)
			{
				if ($easysocialurlPlugin)
				{
					if (!ES::isSh404Installed() && $jConfig->getValue('sef'))
					{
						$rootUri = rtrim(Uri::root(), '/');
						$alias   = \Joomla\CMS\Filter\OutputFilter::stringURLSafe($esUser->getAlias());
						$alias   = ESR::normalizePermalink($alias);
						$url     = $rootUri . '/' . $alias;

						// Retrieve current site language code
						$langCode = ES::getCurrentLanguageCode();

						// Append language code from the simple url
						if (!empty($langCode))
						{
							$url = $rootUri . '/' . $langCode . '/' . $alias;
						}

						if ($jConfig->getValue('sef_suffix') && !(substr($url, -9) == 'index.php' || substr($url, -1) == '/'))
						{
							// $uri = JURI::getInstance(ES::getURI(true));
							// $format = $uri->getVar('format', 'html');
							$format = 'html';
							$url .= '.' . $format;

						}

						$comment->profileLink = $url;
					}
				}
				else
				{
					$options              = array('id' => $esUser->getAlias());
					$options['sef']       = true;
					$options['adminSef']  = false;
					$comment->profileLink = \FRoute::profile($options, false);
				}
			}
		}
	}
}

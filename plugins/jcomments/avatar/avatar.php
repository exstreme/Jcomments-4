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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * Class to add support for avatar(s) in JComments
 *
 * @since  1.5
 */
class PlgJcommentsAvatar extends CMSPlugin
{
	/**
	 * Prepare avatar for single comment
	 *
	 * @param   mixed  $comment  Array with comment object
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onPrepareAvatar(&$comment)
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
				$this->$method($comments);
			}
		}
	}

	/**
	 * Get image URL from user field
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.2
	 */
	protected function getFieldsImage(array $comments)
	{
		$user     = Factory::getApplication()->getIdentity();
		$users    = $this->getUsers($comments);
		$fieldsId = $this->params->get('fields_id');

		if (count($users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('fv.item_id', 'fv.value')))
				->select($db->qn(array('f.type', 'f.fieldparams')))
				->from($db->qn('#__fields_values', 'fv'))
				->leftJoin($db->qn('#__fields', 'f'), $db->qn('f.id') . ' = ' . $db->qn('fv.field_id'))
				->where($db->qn('fv.field_id') . ' = :field_id')
				->where($db->qn('f.state') . ' = 1')
				->whereIn($db->qn('f.access'), $user->getAuthorisedViewLevels())
				->bind(':field_id', $fieldsId, ParameterType::INTEGER);

			try
			{
				$db->setQuery($query);
				$avatars = $db->loadObjectList('item_id');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}
		}

		$itemId = self::getItemid('index.php?option=com_users&view=profile');

		foreach ($comments as $comment)
		{
			$uid = (int) $comment->userid;
			$comment->profileLink = '';

			if ($this->params->get('avatar_link') == 1)
			{
				$comment->profileLink = $uid ? Route::_('index.php?option=com_users&view=profile' . $itemId) : '';
			}

			$comment->profileLinkTarget = $this->params->get('avatar_link_target');

			if (isset($avatars[$uid]) && !empty($avatars[$uid]->value))
			{
				if ($avatars[$uid]->type == 'media')
				{
					$fieldValue = json_decode($avatars[$uid]->value);

					$comment->avatar = Uri::base() . $fieldValue->imagefile;
				}
				elseif ($avatars[$uid]->type == 'imagelist')
				{
					$fieldParams = json_decode($avatars[$uid]->fieldparams);

					if (property_exists($fieldParams, 'directory') || !empty($fieldParams->directory))
					{
						if ($fieldParams->directory == '/')
						{
							$comment->avatar = Uri::base() . 'images/' . $avatars[$uid]->value;
						}
						else
						{
							$comment->avatar = Uri::base() . 'images/' . StringHelper::str_ireplace('\\', '/', $fieldParams->directory) . '/' . $avatars[$uid]->value;
						}
					}
					else
					{
						$comment->avatar = Uri::base() . 'images/' . $avatars[$uid]->value;
					}
				}
				elseif ($avatars[$uid]->type == 'url')
				{
					$comment->avatar = $avatars[$uid]->value;
				}
				else
				{
					$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
				}
			}
			else
			{
				$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
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
	 * @since   4.2
	 */
	protected function getComprofilerImage(array $comments)
	{
		$users = $this->getUsers($comments);

		if (count($users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('user_id', 'avatar')))
				->from($db->qn('#__comprofiler'))
				->where($db->qn('user_id') . ' IN (' . implode(',', $users) . ')')
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
			$comment->profileLink = '';

			if ($this->params->get('avatar_link') == 1)
			{
				$comment->profileLink = $uid ? Route::_('index.php?option=com_comprofiler&task=userProfile&user=' . $uid . $itemId) : '';
			}

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
	 * @since   4.2
	 */
	protected function getContactsImage(array $comments)
	{
		$users = $this->getUsers($comments);

		if (count($users))
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
				->where($db->qn('cd.user_id') . ' IN (' . implode(',', $users) . ')');

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
			$comment->profileLink = '';

			if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '')
			{
				if ($this->params->get('avatar_link') == 1)
				{
					$comment->profileLink = $uid
						? Route::_('index.php?option=com_contact&view=contact&id=' . $avatars[$uid]->slug . '&catid=' . $avatars[$uid]->catslug)
						: '';
				}

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
	 * @since   4.2
	 */
	protected function getDefaultImage(string $type = 'default'): string
	{
		switch ($type)
		{
			case 'custom':
				if (empty(ltrim($this->params->get('avatar_custom_default_avatar'))))
				{
					return '';
				}

				return Uri::base() . ltrim($this->params->get('avatar_custom_default_avatar'), '/');
			default:
				return Uri::base() . 'media/com_jcomments/images/no_avatar.png';
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
	 * @since   4.2
	 * @noinspection PhpUndefinedClassInspection
	 */
	protected function getEasysocialImage(array $comments)
	{
		if (!is_file(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/easysocial.php'))
		{
			return;
		}

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

						if ($this->params->get('avatar_link') == 1)
						{
							$comment->profileLink = $url;
						}
					}
				}
				else
				{
					$options              = array('id' => $esUser->getAlias());
					$options['sef']       = true;
					$options['adminSef']  = false;

					if ($this->params->get('avatar_link') == 1)
					{
						$comment->profileLink = \FRoute::profile($options, false);
					}
				}
			}
		}
	}

	/**
	 * Get gravatar URL
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @since   4.2
	 */
	protected function getGravatarImage(array $comments)
	{
		$options = $this->params->get('gravatar_options', '');

		if (!empty($options))
		{
			$options = parse_ini_string($options);

			if (!isset($options['d']) && !isset($options['default']))
			{
				$defaultImg = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
				$options['d'] = empty($defaultImg) ? 'mp' : $defaultImg;
			}

			if (isset($options['f']) || isset($options['forcedefault']))
			{
				if ($options['f'] == 'y' || $options['forcedefault'] == 'y')
				{
					$options['f'] = 'y';
				}
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
	 * @since   4.2
	 */
	protected static function getItemid(string $link): string
	{
		/** @var \Joomla\CMS\Menu\AbstractMenu $menu */
		$menu = Factory::getApplication()->getMenu();
		$item = $menu->getItems('link', $link, true);
		$id   = null;

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
	 * Get JomSocial URL and avatar
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @since   4.2
	 */
	protected function getJomsocialImage(array $comments)
	{
		$users = $this->getUsers($comments);

		if (count($users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select(array($db->qn('userid'), $db->qn('thumb', 'avatar')))
				->from($db->qn('#__community_users'))
				->where($db->qn('userid') . ' IN (' . implode(',', $users) . ')');

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

		$avatarA = Path::clean(JPATH_SITE . '/');
		$avatarL = Uri::base() . '/';

		foreach ($comments as &$comment)
		{
			$uid = (int) $comment->userid;
			$comment->profileLink = '';

			if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '' && $avatars[$uid]->avatar != 'components/com_community/assets/default_thumb.jpg')
			{
				if (is_file($avatarA . $avatars[$uid]->avatar))
				{
					$comment->avatar = $avatarL . $avatars[$uid]->avatar;
				}
			}
			else
			{
				$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
			}

			if ($this->params->get('avatar_link') == 1)
			{
				$comment->profileLink = $uid ? Route::_('index.php?option=com_community&view=profile&userid=' . $uid) : '';
			}
		}
	}

	/**
	 * Get image URL from com_kunena
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.2
	 * @noinspection PhpUndefinedClassInspection
	 */
	protected function getKunenaImage(array $comments)
	{
		$users = $this->getUsers($comments);

		if (count($users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('userid', 'avatar')))
				->from($db->qn('#__kunena_users'))
				->where($db->qn('userid') . ' IN (' . implode(',', $users) . ')');

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
			$comment->profileLink = '';

			if ($this->params->get('avatar_link') == 1)
			{
				$comment->profileLink = $uid
					? \Kunena\Forum\Libraries\Route\KunenaRoute::_('index.php?option=com_kunena&view=user&userid=' . $uid . '&Itemid=' . $itemId)
					: '';
			}

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
	 * @since   4.2
	 */
	protected function getPhocagalleryImage(array $comments)
	{
		$users = $this->getUsers($comments);

		if (count($users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('userid', 'avatar')))
				->from($db->qn('#__phocagallery_user'))
				->where($db->qn('userid') . ' IN (' . implode(',', $users) . ')');

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
	 * Get image URL from com_osmembership
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.2
	 */
	protected function getMembershipproImage(array $comments)
	{
		$users = $this->getUsers($comments);

		if (count($users))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true)
				->select($db->qn(array('user_id', 'avatar')))
				->from($db->qn('#__osmembership_subscribers'))
				->where($db->qn('user_id') . ' IN (' . implode(',', $users) . ')');

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

		$avatarFolder = JPATH_ROOT . '/media/com_osmembership/avatars/';
		$avatarLink   = '/media/com_osmembership/avatars/';

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
	 * Get image URL from phpBB3
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.2
	 */
	protected function getPhpbb3Image(array $comments)
	{
		$forumConfig = Path::clean($this->params->get('forums_config'));
		$data        = array('config' => array(), 'user_data' => array());
		$cacheId     = 'plg_jcomments_avatar_phpbb3';

		/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', array('defaultgroup' => $cacheId));

		// Init default values. do not rename variables!
		$dbms         = '';
		$dbhost       = '';
		$dbport       = '';
		$dbuser       = '';
		$dbpasswd     = '';
		$dbname       = '';
		$table_prefix = '';

		if (!is_file($forumConfig) || !is_readable($forumConfig))
		{
			Log::add('phpBB3 configuration file not found at "' . $forumConfig . '"', Log::ERROR, 'plg_jcomments_avatars');

			return;
		}

		include $forumConfig;

		/** @var \Joomla\CMS\Cache\Cache $cache */
		// Force cache only if caching is disabled in global configuration.
		if (Factory::getApplication()->get('caching') == 0)
		{
			$cache->setCaching((bool) $this->params->get('force_caching'));
		}

		// Load phpBB3 config data and users data from cache
		if ($cache->contains($cacheId, 'plg_jcomments_avatar'))
		{
			$data = $cache->get($cacheId, 'plg_jcomments_avatar');
		}
		else
		{
			// Connect to phpBB3 database using settings from phpBB3 configuration file
			$dbFactory  = new \Joomla\Database\DatabaseFactory;
			$driverName = str_replace('\\', '', substr($dbms, strrpos($dbms, '\\')));
			$db         = $dbFactory->getDriver(
				$driverName,
				array(
					'host'     => $dbhost, 'port' => $dbport, 'user' => $dbuser, 'password' => $dbpasswd,
					'database' => $dbname, 'select' => true, 'prefix' => $table_prefix
				)
			);

			// Get phpBB3 board config
			$configQuery = $db->getQuery(true)
				->select($db->qn(array('config_name', 'config_value')))
				->from($db->qn('#__config'));

			try
			{
				$db->setQuery($configQuery);
				$data['config'] = $db->loadObjectList('config_name');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}

			$query = $db->getQuery(true)
				->select($db->qn(array('user_id', 'username_clean', 'user_email', 'user_avatar', 'user_avatar_type')))
				->from($db->qn('#__users'));

			if ($this->params->get('forums_link_type') == 'email')
			{
				$users = $this->getUsers($comments, true);

				// No one has left a comment yet.
				if (empty($users) || !is_array($users))
				{
					return;
				}

				$query->whereIn($db->qn('user_email'), $users, ParameterType::STRING);
			}
			elseif ($this->params->get('forums_link_type') == 'login')
			{
				$users = $this->getUsers($comments, false, true);

				// No one has left a comment yet.
				if (empty($users) || !is_array($users))
				{
					return;
				}

				$query->whereIn($db->qn('username_clean'), $users, ParameterType::STRING);
			}

			try
			{
				$db->setQuery($query);
				$data['user_data'] = $db->loadObjectList();
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}

			$cache->store($data, $cacheId, 'plg_jcomments_avatar');
		}

		foreach ($comments as $comment)
		{
			$comment->profileLink = '';
			$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
			$phpBB3Profile = array();

			foreach ($data['user_data'] as $phpBB3)
			{
				// Ugly checking but works
				if ($this->params->get('forums_link_type') == 'email' && $phpBB3->user_email == $comment->email)
				{
					$phpBB3Profile = $phpBB3;
					break;
				}
				elseif ($this->params->get('forums_link_type') == 'login' && $phpBB3->username_clean == $comment->username)
				{
					$phpBB3Profile = $phpBB3;
					break;
				}
			}

			if (!empty($phpBB3Profile->user_id))
			{
				if ($this->params->get('avatar_link') == 1)
				{
					$comment->profileLink = $this->params->get('forums_site_url') . '/memberlist.php?mode=viewprofile&u=' . $phpBB3Profile->user_id;
				}

				if ($phpBB3Profile->user_avatar_type == 'avatar.driver.remote')
				{
					$comment->avatar = $phpBB3Profile->user_avatar;
				}
				elseif ($phpBB3Profile->user_avatar_type == 'avatar.driver.upload' || $phpBB3Profile->user_avatar_type == 'avatar.driver.local')
				{
					// .htaccess file in avatars folder will deny access, so we must to use data:image
					$avatarExt = \Joomla\Filesystem\File::getExt($phpBB3Profile->user_avatar);

					if ($phpBB3Profile->user_avatar_type == 'avatar.driver.upload')
					{
						$avatarPath = $data['config']['avatar_path']->config_value;
						$avatarFile = $data['config']['avatar_salt']->config_value . '_' . $phpBB3Profile->user_id . '.' . $avatarExt;
					}
					else
					{
						$avatarPath = $data['config']['avatar_gallery_path']->config_value;
						$avatarFile = $phpBB3Profile->user_avatar;
					}

					$avatar = Path::clean(
						$this->params->get('forums_site_path') . '/' . $avatarPath . '/'
						. $avatarFile
					);
					$imageData       = @getimagesize($avatar);
					$avatar          = file_get_contents($avatar);
					$comment->avatar = 'data:' . image_type_to_mime_type($imageData[2]) . ';base64,' . base64_encode($avatar);
				}
			}

			$comment->profileLinkTarget = $this->params->get('avatar_link_target');
		}
	}

	/**
	 * Get image URL from Simple Machines Forum (SMF)
	 *
	 * @param   array  $comments  Array with comment objects
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.2
	 */
	protected function getSmfImage(array $comments)
	{
		$forumConfig = Path::clean($this->params->get('forums_config'));
		$data        = array('config' => array(), 'user_data' => array());
		$cacheId     = 'plg_jcomments_avatar_smf';

		/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', array('defaultgroup' => $cacheId));

		// Init default values. do not rename variables!
		$db_type   = '';
		$db_server = '';
		$db_port   = '';
		$db_user   = '';
		$db_passwd = '';
		$db_name   = '';
		$db_prefix = '';

		if (!is_file($forumConfig) || !is_readable($forumConfig))
		{
			Log::add('SMF configuration file not found at "' . $forumConfig . '"', Log::ERROR, 'plg_jcomments_avatars');

			return;
		}

		include $forumConfig;

		/** @var \Joomla\CMS\Cache\Cache $cache */
		// Force cache only if caching is disabled in global configuration.
		if (Factory::getApplication()->get('caching') == 0)
		{
			$cache->setCaching((bool) $this->params->get('force_caching'));
		}

		// Load SMF config data and users data from cache
		if ($cache->contains($cacheId, 'plg_jcomments_avatar'))
		{
			$data = $cache->get($cacheId, 'plg_jcomments_avatar');
		}
		else
		{
			// Connect to SMF database using settings from SMF configuration file
			$dbFactory  = new \Joomla\Database\DatabaseFactory;

			if (strpos($db_type, 'postgresql') !== false)
			{
				$driverName = 'pgsql';
			}
			else
			{
				$driverName = 'mysqli';
			}

			$db = $dbFactory->getDriver(
				$driverName,
				array(
					'host'     => $db_server, 'port' => $db_port, 'user' => $db_user, 'password' => $db_passwd,
					'database' => $db_name, 'select' => true, 'prefix' => $db_prefix
				)
			);

			// Get SMF board config
			$configQuery = $db->getQuery(true)
				->select($db->qn(array('variable', 'value')))
				->from($db->qn('#__settings'));

			try
			{
				$db->setQuery($configQuery);
				$data['config'] = $db->loadObjectList('variable');
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}

			$query = $db->getQuery(true)
				->select($db->qn(array('m.id_member', 'm.member_name', 'm.email_address', 'm.avatar', 'a.filename')))
				->from($db->qn('#__members', 'm'))
				->leftJoin($db->qn('#__attachments', 'a'), 'a.id_member = m.id_member AND a.approved = 1 AND a.attachment_type = 1');

			if ($this->params->get('forums_link_type') == 'email')
			{
				$users = $this->getUsers($comments, true);

				// No one has left a comment yet.
				if (empty($users) || !is_array($users))
				{
					return;
				}

				$query->whereIn($db->qn('email_address'), $users, ParameterType::STRING);
			}
			elseif ($this->params->get('forums_link_type') == 'login')
			{
				$users = $this->getUsers($comments, false, true);

				// No one has left a comment yet.
				if (empty($users) || !is_array($users))
				{
					return;
				}

				$query->whereIn($db->qn('member_name'), $users, ParameterType::STRING);
			}

			try
			{
				$db->setQuery($query);
				$data['user_data'] = $db->loadObjectList();
			}
			catch (\RuntimeException $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'plg_jcomments_avatars');

				return;
			}

			$cache->store($data, $cacheId, 'plg_jcomments_avatar');
		}

		foreach ($comments as $comment)
		{
			$comment->profileLink = '';
			$comment->avatar = $this->getDefaultImage($this->params->get('avatar_default_avatar'));
			$smfProfile = array();

			foreach ($data['user_data'] as $smf)
			{
				// Ugly checking but works
				if ($this->params->get('forums_link_type') == 'email' && $smf->email_address == $comment->email)
				{
					$smfProfile = $smf;
					break;
				}
				elseif ($this->params->get('forums_link_type') == 'login' && $smf->member_name == $comment->username)
				{
					$smfProfile = $smf;
					break;
				}
			}

			if (!empty($smfProfile->id_member))
			{
				if ($this->params->get('avatar_link') == 1)
				{
					$comment->profileLink = $this->params->get('forums_site_url') . '/index.php?action=profile;u=' . $smfProfile->id_member;
				}

				// Avatar from gallery or URL
				if (!empty($smfProfile->avatar))
				{
					// User specified an URL
					if (preg_match('#(https?://)#', $smfProfile->avatar, $matches))
					{
						$comment->avatar = $smfProfile->avatar;
					}
					else
					{
						$comment->avatar = $data['config']['avatar_url']->value . '/' . $smfProfile->avatar;
					}
				}
				// Avatar uploaded by user
				elseif (!empty($smfProfile->filename))
				{
					$customAvatarPath = Path::clean($data['config']['custom_avatar_dir']->value . '/' . $smfProfile->filename);

					if (is_file($customAvatarPath) && is_readable($customAvatarPath))
					{
						$comment->avatar = $data['config']['custom_avatar_url']->value . '/' . $smfProfile->filename;
					}
				}
			}

			$comment->profileLinkTarget = $this->params->get('avatar_link_target');
		}
	}

	/**
	 * Get a list of commenting users
	 *
	 * @param   array    $comments  Array with comment objects
	 * @param   boolean  $emails    Return list of emails
	 * @param   boolean  $login     Return list of user names
	 *
	 * @return  array
	 *
	 * @since   4.2
	 */
	protected function getUsers(array $comments, bool $emails = false, bool $login = false): array
	{
		$users = array();

		foreach ($comments as $comment)
		{
			if ($emails)
			{
				$users[] = $comment->email;
			}
			elseif ($login)
			{
				$users[] = $comment->username;
			}
			else
			{
				if ($comment->userid != 0)
				{
					$users[] = (int) $comment->userid;
				}
			}
		}

		return array_unique($users);
	}
}

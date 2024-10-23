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

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

/**
 * Main Controller
 *
 * @since  1.5
 */
class DisplayController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$cachable  = true;
		$objectId  = $this->input->getInt('object_id');
		$commentId = $this->input->getInt('comment_id');
		$option    = $this->app->input->get('option');
		$layout    = $this->app->input->getWord('layout');

		// Set the default view name and format from the Request.
		$viewName = $this->input->get('view', 'comments');
		$this->input->set('view', $viewName);

		if ($this->input->getMethod() === 'POST' || $viewName === 'form')
		{
			$cachable = false;
		}

		$safeurlparams = array('id' => 'INT', 'cid' => 'ARRAY', 'limit' => 'UINT', 'limitstart' => 'UINT',
							   'return' => 'BASE64', 'filter-search' => 'STRING', 'lang' => 'CMD');

		// Set locked state for views called not from Jcomments content plugin event.
		if (isset($objectId) || ($option == 'com_content' && $option == 'com_multicategories'))
		{
			/** @var \Joomla\Component\Content\Site\Model\ArticleModel $articleModel */
			$articleModel     = $this->app->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Site');
			$article          = $articleModel->getItem($this->input->getInt('object_id'));
			$config           = ComponentHelper::getParams('com_jcomments');
			$categoryEnabled  = JcommentsContentHelper::checkCategory($article->catid);
			$commentsEnabled  = JcommentsContentHelper::isEnabled($article) || $categoryEnabled;
			$commentsDisabled = JcommentsContentHelper::isDisabled($article) || !$commentsEnabled;
			$commentsLocked   = JcommentsContentHelper::isLocked($article);
			$archivesState    = 2;

			if (isset($article->state) && $article->state == $archivesState && $config->get('enable_for_archived') == 0)
			{
				$commentsLocked = true;
				JcommentsFactory::getAcl()->setCommentsLocked($commentsLocked);
			}

			$config->set('comments_on', $commentsEnabled);
			$config->set('comments_off', $commentsDisabled);
			$config->set('comments_locked', $commentsLocked);
		}

		// Global access check.
		if ($viewName === 'form')
		{
			$acl = JcommentsFactory::getACL();
			$canViewForm = $acl->canViewForm(true, true);

			if ($canViewForm !== true)
			{
				throw new \Exception($canViewForm, 403);
			}
		}

		// Check for edit form.
		if ($viewName === 'form' && $layout !== 'report')
		{
			if (($this->input->getInt('quote') != 1 && $this->input->getInt('reply') != 1) && !$this->checkEditId('com_jcomments.edit.comment', $commentId))
			{
				// Somehow the person just went to the form - we don't allow that.
				throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $commentId), 403);
			}
		}

		parent::display($cachable, $safeurlparams);

		return $this;
	}

	/**
	 * Method to display a privacy info.
	 *
	 * Used in email templates and can be used as link 'index.php?option=com_jcomments&task=privacy' in privacy message
	 * in component settings.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function privacy()
	{
		$params = ComponentHelper::getParams('com_jcomments');
		$lang   = $this->app->getLanguage();

		if (PluginHelper::isEnabled('system', 'privacyconsent'))
		{
			// Redirect to an article
			if ($params->get('privacy_type') === 'article' && !empty($params->get('privacy_article'))
				&& $this->app->isClient('site')
			)
			{
				$articleId  = (int) $params->get('privacy_article');

				if ($articleId > 0 && Associations::isEnabled())
				{
					$privacyAssociated = Associations::getAssociations('com_content', '#__content', 'com_content.item', $articleId);
					$currentLang = $lang->getTag();

					if (isset($privacyAssociated[$currentLang]))
					{
						$articleId = $privacyAssociated[$currentLang]->id;
					}
				}

				$item = (new \Joomla\Component\Content\Site\Model\ArticleModel)->getItem($articleId);
				$slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
				$link = RouteHelper::getArticleRoute($slug, $item->catid, $item->language);

				$this->setRedirect(Route::_($link, false));
			}
			// Redirect to menu item
			elseif ($params->get('privacy_type') === 'menu_item'
				&& !empty($params->get('privacy_menu_item'))
				&& $this->app->isClient('site')
			)
			{
				$itemId = (int) $params->get('privacy_menu_item');

				if ($itemId > 0 && Associations::isEnabled())
				{
					$privacyAssociated = Associations::getAssociations('com_menus', '#__menu', 'com_menus.item', $itemId, 'id', '', '');
					$currentLang = $lang->getTag();

					if (isset($privacyAssociated[$currentLang]))
					{
						$itemId = $privacyAssociated[$currentLang]->id;
					}
				}

				$link = 'index.php?Itemid=' . $itemId;

				if (Multilanguage::isEnabled())
				{
					$menus = $this->app->getMenu('site');
					$menu  = $menus->getItem($itemId);
					$link  .= '&lang=' . $menu->language;
				}

				$this->setRedirect(Route::_($link, false));
			}
			else
			{
				$lang->load('plg_content_confirmconsent', JPATH_ADMINISTRATOR);

				echo empty($params->get('privacy_note'))
					? Text::_('PLG_CONTENT_CONFIRMCONSENT_FIELD_NOTE_DEFAULT')
					: $params->get('privacy_note');
			}
		}
	}

	/**
	 * Method to display terms of use info.
	 *
	 * Used in email templates and can be used as link 'index.php?option=com_jcomments&task=terms' in terms of use
	 * message in component settings.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function terms()
	{
		$params    = ComponentHelper::getParams('com_jcomments');
		$lang      = $this->app->getLanguage();
		$articleId = JcommentsText::getMessagesBasedOnLanguage(
			$params->get('messages_fields'),
			'message_terms_of_use_article',
			$this->app->getLanguage()->getTag()
		);
		$msg       = JcommentsText::getMessagesBasedOnLanguage(
			$params->get('messages_fields'),
			'message_terms_of_use',
			$this->app->getLanguage()->getTag()
		);

		if (!empty($articleId))
		{
			if ($articleId > 0 && Associations::isEnabled())
			{
				$termsAssociated = Associations::getAssociations('com_content', '#__content', 'com_content.item', $articleId);
				$currentLang = $lang->getTag();

				if (isset($termsAssociated[$currentLang]))
				{
					$articleId = $termsAssociated[$currentLang]->id;
				}
			}

			$item = (new \Joomla\Component\Content\Site\Model\ArticleModel)->getItem($articleId);
			$slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
			$link = RouteHelper::getArticleRoute($slug, $item->catid, $item->language);

			$this->setRedirect(Route::_($link, false));
		}
		else
		{
			echo $msg != '' ? $msg : Text::_('FORM_TOS');
		}
	}
}

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
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$cachable = true;

		// Set the default view name and format from the Request.
		$vName = $this->input->get('view', 'comments');
		$this->input->set('view', $vName);

		if ($this->app->getIdentity()->get('id') || ($this->input->getMethod() === 'POST'))
		{
			$cachable = false;
		}

		$safeurlparams = array('id' => 'INT', 'cid' => 'ARRAY', 'limit' => 'UINT', 'limitstart' => 'UINT',
							   'return' => 'BASE64', 'filter-search' => 'STRING', 'lang' => 'CMD');

		parent::display($cachable, $safeurlparams);

		return $this;
	}

	/**
	 * Method to display a privacy info.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function privacy()
	{
		$params = $this->app->getParams('com_jcomments');
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
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function terms()
	{
		echo JcommentsText::getMessagesBasedOnLanguage(
			ComponentHelper::getParams('com_jcomments')->get('messages_fields'),
			'message_terms_of_use',
			$this->app->getLanguage()->getTag()
		);
	}
}

<?php
/**
 * JComments content plugin - Plugin for attaching comments list and form to content item.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Class for attaching comments list and form to content item.
 *
 * @since  1.0
 */
class PlgContentJcomments extends CMSPlugin
{
	/**
	 * Prepare 'readmore' data before display layout
	 *
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	private function prepareContent(&$article, &$params): void
	{
		require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

		// Check whether plugin has been unpublished
		if (!PluginHelper::isEnabled('content', 'jcomments'))
		{
			JCommentsContent::clear($article);

			return;
		}

		$app    = Factory::getApplication();
		$option = $app->input->get('option');
		$view   = $app->input->get('view');

		if (!isset($article->id) || ($option != 'com_content' && $option != 'com_multicategories'))
		{
			return;
		}

		if (!isset($params) || $params == null)
		{
			$params = new Registry('');
		}
		elseif (isset($params->_raw) && strpos($params->_raw, 'moduleclass_sfx') !== false)
		{
			return;
		}

		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.class.php';

		JCommentsContent::processForeignTags($article);

		$config           = ComponentHelper::getParams('com_jcomments');
		$categoryEnabled  = JCommentsContent::checkCategory($article->catid);
		$commentsEnabled  = JCommentsContent::isEnabled($article) || $categoryEnabled;
		$commentsDisabled = JCommentsContent::isDisabled($article) || !$commentsEnabled;
		$commentsLocked   = JCommentsContent::isLocked($article);
		$archivesState    = 2;

		if (isset($article->state) && $article->state == $archivesState && $this->params->get('enable_for_archived') == 0)
		{
			$commentsLocked = true;
		}

		$config->set('comments_on', (int) $commentsEnabled);
		$config->set('comments_off', (int) $commentsDisabled);
		$config->set('comments_locked', (int) $commentsLocked);

		if ($view != 'article')
		{
			$layoutData = array();
			$layoutData['params'] = &$this->params;

			$user        = $app->getIdentity();
			$authorised  = Access::getAuthorisedViewLevels($user->get('id'));
			$checkAccess = in_array($article->access, $authorised);
			$slug        = $article->slug ?? $article->id;
			$language    = $article->language ?? '*';

			if ($checkAccess)
			{
				$readmoreLink = Route::_(ContentHelperRoute::getArticleRoute($slug, $article->catid, $language));
			}
			else
			{
				$returnURL = Route::_(ContentHelperRoute::getArticleRoute($slug, $article->catid, $language));

				$menu   = $app->getMenu();
				$active = $menu->getActive();
				$itemId = $active->id;
				$link1  = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
				$link   = new Uri($link1);
				$link->setVar('return', base64_encode($returnURL));
				$readmoreLink = $link;
			}

			$commentsDisabled = false;

			if ((int) $config->get('comments_off') == 1)
			{
				$commentsDisabled = true;
			}
			elseif ((int) $config->get('comments_on') == 1)
			{
				$commentsDisabled = false;
			}

			$layoutData['commentsLinkHidden'] = $commentsDisabled;
			$count                            = 0;
			$layoutData['commentsCount']      = $count;

			// Do not query comments count if comments disabled and link hidden
			if (!$commentsDisabled)
			{
				require_once JPATH_ROOT . '/components/com_jcomments/models/jcomments.php';

				if ($this->params->get('link_read_comments') != 0)
				{
					$acl   = JCommentsFactory::getACL();
					$count = JCommentsModel::getCommentsCount(
						array(
							'object_id'    => (int) $article->id,
							'object_group' => 'com_content',
							'published'    => $acl->canPublish() || $acl->canPublishForObject($article->id, 'com_content')
								? null : 1
						)
					);

					$layoutData['commentsCount'] = $count;
				}
			}

			JCommentsContent::clear($article);
			$layoutData['item'] = &$article;
			$layoutData['link'] = $readmoreLink;

			// Hide comments link if comments enabled but link disabled in plugin params
			if ((($this->params->get('link_read_comments') == 0)
				|| ($count == 0 && $this->params->get('link_add_comment') == 0))
				&& !$commentsDisabled
			)
			{
				$layoutData['commentsLinkHidden'] = true;
			}

			// Links position
			if ($this->params->get('links_position') == 'after')
			{
				$article->text .= $this->getRenderer('links')->render($layoutData);
			}
			else
			{
				$article->text = $this->getRenderer('links')->render($layoutData) . $article->text;
			}
		}
		else
		{
			if ($this->params->get('show_comments_event') == 'prepareContent')
			{
				$isEnabled = ((int) $config->get('comments_on', 0) == 1) && ((int) $config->get('comments_off', 0) == 0);

				if ($isEnabled)
				{
					require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';

					$comments = JComments::show($article->id, 'com_content', $article->title);

					if (strpos($article->text, '{jcomments}') !== false)
					{
						$article->text = str_replace('{jcomments}', $comments, $article->text);
					}
					else
					{
						$article->text .= $comments;
					}
				}
			}

			JCommentsContent::clear($article);
		}

	}

	/**
	 * Show 'readmore' layout before the article introtext
	 *
	 * @param   string  $context  The context of the content being passed to the plugin
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function onContentBeforeDisplay(string $context, &$article, &$params)
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			$app  = Factory::getApplication();
			$view = $app->input->get('view');

			// Do not display comments in modules
			if ($params->get('moduleclass_sfx', null) !== null)
			{
				return;
			}

			$originalText  = $article->text ?? '';
			$article->text = '';
			$this->prepareContent($article, $params);

			if (isset($article->text))
			{
				if (($view == 'article') && strpos($originalText, '{jcomments}') !== false)
				{
					$originalText = str_replace('{jcomments}', $article->text, $originalText);
				}
				else
				{
					$article->introtext = str_replace('{jcomments}', '', $article->introtext);

					if ($this->params->get('links_position', 'after') == 'after')
					{
						$article->introtext = $article->introtext . $article->text;
					}
					else
					{
						$article->introtext = $article->text . $article->introtext;
					}
				}
			}

			$article->text = $originalText;
			JCommentsContent::clear($article);
		}
	}

	/**
	 * Show comments when onContentAfterDisplay trigger
	 *
	 * @param   string  $context  The context of the content being passed to the plugin
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  string
	 *
	 * @since   1.6
	 */
	public function onContentAfterDisplay(string $context, &$article, &$params): string
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			// Do not display comments in modules
			if ($params->get('moduleclass_sfx', null) !== null)
			{
				return '';
			}

			require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

			$app  = Factory::getApplication();
			$view = $app->input->get('view');

			// Check whether plugin has been unpublished
			if (!PluginHelper::isEnabled('content', 'jcomments')
				|| ($view != 'article')
				|| $params->get('intro_only')
				|| $params->get('popup')
				|| $app->input->getBool('fullview')
				|| $app->input->get('print')
			)
			{
				JCommentsContent::clear($article);

				return '';
			}

			require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';

			$config    = ComponentHelper::getParams('com_jcomments');
			$isEnabled = ((int) $config->get('comments_on', 0) == 1) && ((int) $config->get('comments_off', 0) == 0);

			if ($isEnabled && $view == 'article')
			{
				JCommentsContent::clear($article);

				return JComments::show($article->id, 'com_content', $article->title);
			}
		}

		return '';
	}

	/**
	 * After delete content logging method
	 * This method adds a record to #__action_logs contains (message, date, context, user)
	 * Method is called right after the content is deleted
	 *
	 * @param   string  $context  The context of the content passed to the plugin
	 * @param   object  $article  A JTableContent object
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onContentAfterDelete(string $context, $article): void
	{
		if ($context == 'com_content.article')
		{
			require_once JPATH_ROOT . '/components/com_jcomments/models/jcomments.php';
			require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

			JCommentsModel::deleteComments($article->id);

			$model = new JcommentsModelSubscriptions;
			$model->unsubscribe($article->id, 'com_content');
		}
	}

	/**
	 * After save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
	 * @param   object   $article  A JTableContent object
	 * @param   boolean  $isNew    If the content is just about to be created
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onContentAfterSave(string $context, $article, bool $isNew): void
	{
		if (($context == 'com_content.article' || $context == 'com_content.form') && !$isNew)
		{
			require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

			if (JCommentsContent::checkCategory($article->catid))
			{
				require_once JPATH_ROOT . '/components/com_jcomments/helpers/object.php';

				JCommentsObject::storeObjectInfo($article->id);
			}
		}
	}

	/**
	 * Get the layout paths
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	protected function getLayoutPaths(): array
	{
		$template = Factory::getApplication()->getTemplate();

		return [
			JPATH_ROOT . '/templates/' . $template . '/html/layouts/plugins/' . $this->_type . '/' . $this->_name,
			__DIR__ . '/layouts',
		];
	}

	/**
	 * Get the plugin renderer
	 *
	 * @param   string  $layoutId  Layout identifier
	 *
	 * @return  \Joomla\CMS\Layout\LayoutInterface
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	protected function getRenderer(string $layoutId = 'default')
	{
		$renderer = new FileLayout($layoutId);

		$renderer->setIncludePaths($this->getLayoutPaths());

		return $renderer;
	}

	/**
	 * Render a layout of this plugin
	 *
	 * @param   string  $layoutId  Layout identifier
	 * @param   array   $data      Data for the layout
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	public function render(string $layoutId, array $data = []): string
	{
		return $this->getRenderer($layoutId)->render($data);
	}

	/**
	 * Debug a layout of this plugin
	 *
	 * @param   string  $layoutId  Layout identifier
	 * @param   array   $data      Optional data for the layout
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	public function debug(string $layoutId, array $data = []): string
	{
		return $this->getRenderer($layoutId)->debug($data);
	}
}

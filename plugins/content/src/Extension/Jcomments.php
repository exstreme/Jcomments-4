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

namespace Joomla\Plugin\Content\Jcomments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Class for attaching comments list and form to content item.
 *
 * @since  1.5
 * @noinspection PhpUnused
 */
final class Jcomments extends CMSPlugin
{
	/**
	 * @var    \Joomla\CMS\Application\SiteApplication
	 *
	 * @since  3.9.0
	 */
	protected $app;

	/**
	 * Load the plugin language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  3.9.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface  $dispatcher  The object to observe
	 * @param   array                $config      An optional associative array of configuration settings.
	 *                                            Recognized key values include 'name', 'group', 'params', 'language'
	 *                                            (this list is not meant to be comprehensive).
	 *
	 * @since   1.5
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config = array())
	{
		parent::__construct($dispatcher, $config);

		$this->app = $this->getApplication() ?: $this->app;

		// Load component language file
		$this->app->getLanguage()->load('com_jcomments');
	}

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
	private function prepareContent($article, &$params): void
	{
		// Check whether plugin has been unpublished
		if (!PluginHelper::isEnabled('content', 'jcomments'))
		{
			JcommentsContentHelper::clear($article);

			return;
		}

		$option = $this->app->input->get('option');
		$viewName = $this->app->input->get('view');

		if (!isset($article->id) || ($option != 'com_content' && $option != 'com_multicategories'))
		{
			return;
		}

		if (!isset($params) || $params == null)
		{
			$params = new Registry('');
		}
		elseif (isset($params->_raw) && StringHelper::strpos($params->_raw, 'moduleclass_sfx') !== false)
		{
			return;
		}

		JcommentsContentHelper::processForeignTags($article);

		$config           = ComponentHelper::getParams('com_jcomments');
		$categoryEnabled  = JcommentsContentHelper::checkCategory($article->catid);
		$commentsEnabled  = JcommentsContentHelper::isEnabled($article) || $categoryEnabled;
		$commentsDisabled = JcommentsContentHelper::isDisabled($article) || !$commentsEnabled;
		$commentsLocked   = JcommentsContentHelper::isLocked($article);
		$archivesState    = 2;

		if (isset($article->state) && $article->state == $archivesState && $this->params->get('enable_for_archived') == 0)
		{
			$commentsLocked = true;
			JcommentsFactory::getAcl()->setCommentsLocked(true);
		}

		$config->set('comments_on', $commentsEnabled);
		$config->set('comments_off', $commentsDisabled);
		$config->set('comments_locked', $commentsLocked);

		// Render 'Readmore' layout
		if ($viewName != 'article')
		{
			$layoutData = array();
			$layoutData['params'] = &$this->params;

			$slug     = $article->slug ?? $article->id;
			$language = $article->language ?? '*';

			if ($params->get('access-view'))
			{
				$readmoreLink = Route::_(RouteHelper::getArticleRoute($slug, $article->catid, $language));
			}
			else
			{
				$returnURL = Route::_(RouteHelper::getArticleRoute($slug, $article->catid, $language));

				$menu   = $this->app->getMenu();
				$active = $menu->getActive();
				$itemId = $active->id;
				$link1  = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
				$link   = new Uri($link1);
				$link->setVar('return', base64_encode($returnURL));
				$readmoreLink = $link;
			}

			if ($config->get('comments_off'))
			{
				$commentsDisabled = true;
			}
			elseif ($config->get('comments_on'))
			{
				$commentsDisabled = false;
			}

			$layoutData['commentsLinkHidden'] = $commentsDisabled;
			$count                            = 0;
			$layoutData['commentsCount']      = $count;

			// Do not query comments count if comments disabled and link hidden
			if (!$commentsDisabled)
			{
				if ($this->params->get('link_read_comments') != 0)
				{
					$acl = JcommentsFactory::getACL();
					$count = ObjectHelper::getTotalCommentsForObject(
						$article->id,
						'com_content',
						$acl->canPublish() || $acl->canPublishForObject($article->id, 'com_content') ? null : 1,
						0,
						$language
					);
					$layoutData['commentsCount'] = $count;
				}
			}

			JcommentsContentHelper::clear($article);
			$layoutData['item'] = $article;
			$layoutData['link'] = $readmoreLink;

			// Hide comments link if comments enabled but link disabled in plugin params
			if ((($this->params->get('link_read_comments') == 0)
				|| ($count == 0 && $this->params->get('link_add_comment') == 0))
				&& !$commentsDisabled
			)
			{
				$layoutData['commentsLinkHidden'] = true;
			}

			$layoutData['showReadmore'] = false;

			if ($params->get('show_readmore') && $article->readmore)
			{
				// Disable joomla 'readmore' and show 'readmore' layout from this plugin
				$params->set('show_readmore', false);
				$layoutData['showReadmore'] = true;
			}
			else
			{
				if ($this->params->get('yootheme_hack') && $article->readmore)
				{
					$params->set('show_readmore', true);
					$layoutData['showReadmore'] = true;
				}
			}

			// Links position
			if ($this->params->get('links_position') == 'after')
			{
				$article->introtext = $article->text . $this->getRenderer('links')->render($layoutData);
			}
			else
			{
				$article->introtext = $this->getRenderer('links')->render($layoutData) . $article->text;
			}
		}
		// Display comments
		else
		{
			if ($this->params->get('show_comments_event') == 'onContentBeforeDisplay')
			{
				$isEnabled = ($config->get('comments_on', false)) && ($config->get('comments_off', false) == false);

				if ($isEnabled)
				{
					$basePath = JPATH_ROOT . '/components/com_jcomments';
					$view = JcommentsComponentHelper::getView(
						'Comments',
						'Site',
						'Html',
						// View config
						array('base_path' => $basePath, 'template_path' => $basePath . '/tmpl/comments/'),
						true,
						// Model config
						array('name' => 'Comments', 'prefix' => 'Site', 'base_path' => $basePath)
					);

					ob_start();

					$view->display();
					$output = ob_get_contents();

					ob_end_clean();

					$pageBreaks = preg_split('#<hr(.*)class="system-pagebreak"(.*)\/?>#iU', $article->fulltext);
					$pages      = count(array_filter($pageBreaks));
					$limitstart = $this->app->input->getInt('limitstart');
					$showAll    = $this->app->input->getBool('showall');

					// Find {jcomments} tag in whole article(w/o page breaks) and if not found - display on latest page.
					if (StringHelper::strpos($article->introtext, '{jcomments}') === false
						&& StringHelper::strpos($article->fulltext, '{jcomments}') === false)
					{
						// Tag not found in article. If article have a pagebreaks, display comments on latest page.
						if ($pages > 0)
						{
							if ($limitstart == $pages || $showAll)
							{
								$article->text = str_replace('{jcomments}', '', $article->text) . $output;
							}
						}
						// Else display comments.
						else
						{
							$article->text = str_replace('{jcomments}', '', $article->text) . $output;
						}
					}
					// Find {jcomments} tag in whole article(w/o page breaks) and if found - display on the page where found.
					else
					{
						if (StringHelper::strpos($article->text, '{jcomments}') !== false)
						{
							$article->text .= $output;
						}
					}
				}
			}
		}
	}

	/**
	 * Show 'Readmore' layout before or after article introtext in featured view, display comments otherwise.
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
	public function onContentBeforeDisplay(string $context, $article, &$params)
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			// Do not display comments in modules
			if ($params->get('moduleclass_sfx', null) !== null)
			{
				return;
			}

			$this->prepareContent($article, $params);

			if (isset($article->text))
			{
				$article->text = str_replace('{jcomments}', '', $article->text);
				$article->introtext = str_replace('{jcomments}', '', $article->introtext);

				if (($this->app->input->get('view') === 'article') && StringHelper::strpos($article->text, '{jcomments}') !== false)
				{
					$article->text = str_replace('{jcomments}', $article->text, $article->text);
				}
			}

			JcommentsContentHelper::clear($article);
		}
	}

	/**
	 * Show 'Readmore' layout before or after article introtext in featured view, display comments otherwise.
	 *
	 * @param   string  $context  The context of the content being passed to the plugin
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function onContentAfterDisplay(string $context, $article, $params): string
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			// Do not display comments in modules
			if ($params->get('moduleclass_sfx', null) !== null)
			{
				return '';
			}

			$view = $this->app->input->get('view');

			// Check whether plugin has been unpublished
			if (!PluginHelper::isEnabled('content', 'jcomments')
				|| ($view != 'article')
				|| $params->get('intro_only')
				|| $params->get('popup')
				|| $this->app->input->getBool('fullview')
				|| $this->app->input->get('print')
			)
			{
				JcommentsContentHelper::clear($article);

				return '';
			}

			$config    = ComponentHelper::getParams('com_jcomments');
			$isEnabled = ($config->get('comments_on', false)) && ($config->get('comments_off', false) == false);

			if ($isEnabled && $view === 'article' && $this->params->get('show_comments_event') == 'onContentAfterDisplay')
			{
				JcommentsContentHelper::clear($article);

				$basePath = JPATH_ROOT . '/components/com_jcomments';
				$view = JcommentsComponentHelper::getView(
					'Comments',
					'Site',
					'Html',
					// View config
					array('base_path' => $basePath, 'template_path' => $basePath . '/tmpl/comments/'),
					true,
					// Model config
					array('name' => 'Comments', 'prefix' => 'Site', 'base_path' => $basePath)
				);

				ob_start();

				$view->display();
				$output = ob_get_contents();

				ob_end_clean();

				return $output;
			}
		}

		return '';
	}

	/**
	 * After delete content method
	 * Method is called right after the content is deleted
	 *
	 * @param   string  $context  The context of the content passed to the plugin
	 * @param   object  $article  An ArticleTable object
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onContentAfterDelete(string $context, $article): void
	{
		if ($context == 'com_content.article')
		{
			$factory = $this->app->bootComponent('com_jcomments')->getMVCFactory();

			/** @var \Joomla\Component\Jcomments\Site\Model\CommentsModel $comments */
			$comments = $factory->createModel('Comments', 'Site', array('ignore_request' => true));
			$comments->delete($article->id);

			/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $subscription */
			$subscription = $factory->createModel('Subscription', 'Site', array('ignore_request' => true));
			$subscription->unsubscribe($article->id, 'com_content');
		}
	}

	/**
	 * After save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
	 * @param   object   $item     A TableContent or CategoryTable object
	 * @param   boolean  $isNew    If the content is just about to be created
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onContentAfterSave(string $context, $item, bool $isNew): void
	{
		// Check for com_categories.category required to update object_link if category alias has changed.
		if (($context == 'com_content.article' || $context == 'com_categories.category' || $context == 'com_content.form') && !$isNew)
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
			$model = $this->app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Objects', 'Site', array('ignore_request' => true));

			if ($context == 'com_content.article' || $context == 'com_content.form')
			{
				if (JcommentsContentHelper::checkCategory($item->catid))
				{
					$model->save($item->id, \Joomla\Component\Jcomments\Site\Helper\ObjectHelper::getObjectInfoFromPlugin($item->id));
				}
			}
			elseif ($context == 'com_categories.category')
			{
				if (JcommentsContentHelper::checkCategory($item->id))
				{
					$model->updateLink($item->id, 'com_categories');
				}
			}
		}
	}

	/**
	 * Load privacy consent plugin language in com_config if plugin is disabled.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   4.1
	 */
	public function onContentPrepareForm(Form $form, $data): bool
	{
		if ($this->app->isClient('administrator'))
		{
			$input = $this->app->input;

			if ($input->getWord('option', '') == 'com_config' && $input->getWord('component', '') == 'com_jcomments')
			{
				if (!PluginHelper::isEnabled('content', 'confirmconsent'))
				{
					$this->app->getLanguage()->load('plg_content_confirmconsent', JPATH_ADMINISTRATOR);
				}
			}
		}

		return true;
	}

	/**
	 * Get the layout paths
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	protected function getLayoutPaths(): array
	{
		$template = $this->app->getTemplate();

		return [
			JPATH_ROOT . '/templates/' . $template . '/html/layouts/plugins/' . $this->_type . '/' . $this->_name,
			dirname(__DIR__, 2) . '/layouts',
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
	 * @since   4.1
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
	 * @since   4.1
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
	 * @since   4.1
	 */
	public function debug(string $layoutId, array $data = []): string
	{
		return $this->getRenderer($layoutId)->debug($data);
	}
}
